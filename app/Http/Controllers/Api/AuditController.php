<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\Business;
use Illuminate\Http\Request;

/**
 * @group Audits
 * APIs for managing and retrieving audit results
 */
class AuditController extends BaseController
{
    /**
     * List all audits for authenticated user
     *
     * @authenticated
     * @queryParam business_id int Filter by business ID. Example: 1
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 1,
     *       "business_id": 1,
     *       "overall_score": 85,
     *       "execution_time_ms": 3500,
     *       "model_used": "gpt-4",
     *       "created_at": "2025-01-20T10:30:00Z",
     *       "business": {
     *         "id": 1,
     *         "business_name": "Example Business"
     *       }
     *     }
     *   ],
     *   "pagination": {}
     * }
     */
    public function index(Request $request)
    {
        $query = Audit::where('user_id', $request->user()->id)
            ->with('business')
            ->orderBy('created_at', 'desc');

        if ($request->has('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return $this->paginated($audits);
    }

    /**
     * Get audit details with all findings and recommendations
     *
     * @authenticated
     * @urlParam id int required The audit ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "business_id": 1,
     *     "overall_score": 85,
     *     "execution_time_ms": 3500,
     *     "model_used": "gpt-4",
     *     "created_at": "2025-01-20T10:30:00Z",
     *     "website_audit": {
     *       "id": 1,
     *       "technical_seo_score": 80,
     *       "content_quality_score": 90
     *     },
     *     "website_audit_findings": [],
     *     "social_media_profiles": [],
     *     "google_business_profile": {},
     *     "ai_recommendations": []
     *   }
     * }
     */
    public function show(Request $request, Audit $audit)
    {
        $this->authorize('view', $audit);

        return $this->success($audit->load([
            'business',
            'websiteAudit.findings',
            'socialMediaProfiles',
            'googleBusinessProfile',
            'aiRecommendations',
        ]));
    }

    /**
     * Trigger a new audit for a business
     *
     * @authenticated
     * @bodyParam business_id int required The business ID to audit. Example: 1
     *
     * @response 202 {
     *   "success": true,
     *   "message": "Audit started successfully",
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "business_id": 1,
     *     "status": "processing",
     *     "created_at": "2025-01-20T10:30:00Z"
     *   }
     * }
     */
    public function trigger(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
        ]);

        // Verify user owns this business
        $business = Business::where('id', $validated['business_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Check subscription quota
        $subscription = $request->user()->subscription;
        if ($subscription->plan->audits_per_month > 0) {
            $monthAudits = Audit::where('user_id', $request->user()->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            if ($monthAudits >= $subscription->plan->audits_per_month) {
                return $this->error('Monthly audit quota exceeded', 429);
            }
        }

        // Create audit record
        $audit = Audit::create([
            'user_id' => $request->user()->id,
            'business_id' => $business->id,
            'overall_score' => 0,
            'execution_time_ms' => 0,
            'model_used' => 'gpt-4-turbo',
            'metadata' => [
                'status' => 'processing',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // Log activity
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'audit_started',
            'description' => 'Started audit for ' . $business->business_name,
            'resource_type' => 'audit',
            'resource_id' => $audit->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // TODO: Dispatch audit job to process audit asynchronously
        // dispatch(new ProcessAuditJob($audit));

        return $this->success($audit, 'Audit started successfully', 202);
    }

    /**
     * Get audit comparison between two audits
     *
     * @authenticated
     * @queryParam audit_1_id int required First audit ID. Example: 1
     * @queryParam audit_2_id int required Second audit ID. Example: 2
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "audit_1": {},
     *     "audit_2": {},
     *     "score_improvement": 10,
     *     "key_improvements": [],
     *     "areas_declined": []
     *   }
     * }
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'audit_1_id' => 'required|exists:audits,id',
            'audit_2_id' => 'required|exists:audits,id',
        ]);

        $audit1 = Audit::where('id', $validated['audit_1_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $audit2 = Audit::where('id', $validated['audit_2_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Get or create comparison record
        $comparison = $audit1->comparisons()
            ->where('audit_id_2', $audit2->id)
            ->orWhere(function ($q) use ($audit1, $audit2) {
                $q->where('audit_id_1', $audit2->id)
                  ->where('audit_id_2', $audit1->id);
            })
            ->first();

        return $this->success([
            'audit_1' => $audit1->load('websiteAudit'),
            'audit_2' => $audit2->load('websiteAudit'),
            'comparison' => $comparison,
        ]);
    }

    /**
     * Delete an audit
     *
     * @authenticated
     * @urlParam id int required The audit ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Audit deleted successfully"
     * }
     */
    public function destroy(Request $request, Audit $audit)
    {
        $this->authorize('delete', $audit);

        // Log activity
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'audit_deleted',
            'description' => 'Deleted audit',
            'resource_type' => 'audit',
            'resource_id' => $audit->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $audit->delete();

        return $this->success(null, 'Audit deleted successfully');
    }
}
