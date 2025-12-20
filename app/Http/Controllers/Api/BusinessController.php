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
     * @OA\Get(
     *     path="/businesses",
     *     operationId="listBusinesses",
     *     tags={"Businesses"},
     *     summary="List all businesses",
     *     description="Get paginated list of user's businesses",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (active/inactive)", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Businesses retrieved",
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
     * @OA\Post(
     *     path="/businesses",
     *     operationId="createBusiness",
     *     tags={"Businesses"},
     *     summary="Create a new business",
     *     description="Add a new business to the user's portfolio",
     *     security={{"bearerToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"website_url","business_name"},
     *             @OA\Property(property="website_url", type="string", format="url", example="https://www.example.com"),
     *             @OA\Property(property="business_name", type="string", example="Example Business"),
     *             @OA\Property(property="industry", type="string", example="Technology"),
     *             @OA\Property(property="country", type="string", example="USA"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="description", type="string", example="Business description"),
     *             @OA\Property(property="keywords", type="array", items={"type":"string"}),
     *             @OA\Property(property="logo_url", type="string", format="url", example="https://example.com/logo.png")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Business created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
     * @OA\Get(
     *     path="/businesses/{id}",
     *     operationId="getBusiness",
     *     tags={"Businesses"},
     *     summary="Get business details",
     *     description="Retrieve detailed information about a specific business",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Business ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Business retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Business not found")
     * )
     */
    public function show(Request $request, Business $business)
    {
        $this->authorize('view', $business);

        return $this->success($business);
    }

    /**
     * Update a business
     *
     * @OA\Put(
     *     path="/businesses/{id}",
     *     operationId="updateBusiness",
     *     tags={"Businesses"},
     *     summary="Update business",
     *     description="Update business information",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="business_name", type="string", example="Updated Business"),
     *             @OA\Property(property="industry", type="string", example="Healthcare"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Business not found")
     * )
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
     * @OA\Delete(
     *     path="/businesses/{id}",
     *     operationId="deleteBusiness",
     *     tags={"Businesses"},
     *     summary="Delete business",
     *     description="Remove a business from the system",
     *     security={{"bearerToken":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Business deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Business not found")
     * )
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
