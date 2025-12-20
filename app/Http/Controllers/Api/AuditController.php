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
     * @OA\Get(
     *     path="/audits",
     *     operationId="listAudits",
     *     tags={"Audits"},
     *     summary="List all audits",
     *     description="Get paginated list of audits",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="business_id", in="query", description="Filter by business ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Audits retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", items=@OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
     * @OA\Get(
     *     path="/audits/{id}",
     *     operationId="getAudit",
     *     tags={"Audits"},
     *     summary="Get audit details",
     *     description="Retrieve a specific audit report with all findings",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Audit ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Audit retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Audit not found")
     * )
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
     * @OA\Post(
     *     path="/audits/trigger",
     *     operationId="triggerAudit",
     *     tags={"Audits"},
     *     summary="Trigger a new audit",
     *     description="Start a comprehensive audit for a business",
     *     security={{"bearerToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_id"},
     *             @OA\Property(property="business_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Audit triggered",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Audit started successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=429, description="Monthly audit quota exceeded")
     * )
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
     * @OA\Get(
     *     path="/audits/compare",
     *     operationId="compareAudits",
     *     tags={"Audits"},
     *     summary="Compare two audits",
     *     description="Compare metrics between two audit reports",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="audit_1_id", in="query", required=true, description="First audit ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="audit_2_id", in="query", required=true, description="Second audit ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Comparison retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/audits/{id}",
     *     operationId="deleteAudit",
     *     tags={"Audits"},
     *     summary="Delete audit",
     *     description="Remove an audit record",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Audit ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Audit deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Audit deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Audit not found")
     * )
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
