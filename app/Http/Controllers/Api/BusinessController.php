<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Business;
use Illuminate\Http\Request;

/**
 * @group Businesses
 * APIs for managing business/website records
 */
class BusinessController extends BaseController
{
    /**
     * List all businesses for authenticated user
     *
     * @authenticated
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam status string Filter by status (active/inactive). Example: active
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 1,
     *       "website_url": "https://www.example.com",
     *       "business_name": "Example Business",
     *       "industry": "Technology",
     *       "country": "USA",
     *       "city": "New York",
     *       "status": "active",
     *       "last_audited_at": "2025-01-20T10:30:00Z",
     *       "created_at": "2025-01-15T10:30:00Z"
     *     }
     *   ],
     *   "pagination": {}
     * }
     */
    public function index(Request $request)
    {
        $query = Business::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $businesses = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->paginated($businesses);
    }

    /**
     * Create a new business
     *
     * @authenticated
     * @bodyParam website_url string required The website URL. Example: https://www.example.com
     * @bodyParam business_name string required The business name. Example: Example Business
     * @bodyParam industry string optional The industry. Example: Technology
     * @bodyParam country string optional The country. Example: USA
     * @bodyParam city string optional The city. Example: New York
     * @bodyParam description string optional Business description. Example: A great business
     * @bodyParam keywords string[] optional Keywords array. Example: ["seo", "digital"]
     * @bodyParam logo_url string optional Logo URL. Example: https://example.com/logo.png
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Business created successfully",
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "website_url": "https://www.example.com",
     *     "business_name": "Example Business",
     *     "industry": "Technology",
     *     "status": "active",
     *     "created_at": "2025-01-20T10:30:00Z"
     *   }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'website_url' => 'required|url|unique:businesses,website_url,NULL,id,user_id,' . $request->user()->id,
            'business_name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'keywords' => 'nullable|array',
            'logo_url' => 'nullable|url',
        ]);

        $business = Business::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        // Log activity
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'business_created',
            'description' => 'Created business: ' . $business->business_name,
            'resource_type' => 'business',
            'resource_id' => $business->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success($business, 'Business created successfully', 201);
    }

    /**
     * Get a specific business
     *
     * @authenticated
     * @urlParam id int required The business ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "website_url": "https://www.example.com",
     *     "business_name": "Example Business"
     *   }
     * }
     */
    public function show(Request $request, Business $business)
    {
        $this->authorize('view', $business);

        return $this->success($business);
    }

    /**
     * Update a business
     *
     * @authenticated
     * @urlParam id int required The business ID. Example: 1
     * @bodyParam business_name string The business name. Example: Updated Business
     * @bodyParam industry string The industry. Example: Healthcare
     * @bodyParam description string Business description. Example: Updated description
     * @bodyParam status string The status (active/inactive). Example: active
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Business updated successfully",
     *   "data": {}
     * }
     */
    public function update(Request $request, Business $business)
    {
        $this->authorize('update', $business);

        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'keywords' => 'nullable|array',
            'logo_url' => 'nullable|url',
            'status' => 'nullable|in:active,inactive',
        ]);

        $business->update($validated);

        // Log activity
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'business_updated',
            'description' => 'Updated business: ' . $business->business_name,
            'resource_type' => 'business',
            'resource_id' => $business->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success($business, 'Business updated successfully');
    }

    /**
     * Delete a business
     *
     * @authenticated
     * @urlParam id int required The business ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Business deleted successfully"
     * }
     */
    public function destroy(Request $request, Business $business)
    {
        $this->authorize('delete', $business);

        // Log activity before deletion
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'business_deleted',
            'description' => 'Deleted business: ' . $business->business_name,
            'resource_type' => 'business',
            'resource_id' => $business->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $business->delete();

        return $this->success(null, 'Business deleted successfully');
    }
}
