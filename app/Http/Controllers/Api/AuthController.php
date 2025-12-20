<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\NotificationPreference;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 * APIs for user authentication and account management
 */
class AuthController extends BaseController
{
    /**
     * Register a new user
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required Password with minimum 8 characters. Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     * @bodyParam phone string optional The user's phone number. Example: +1234567890
     * @bodyParam company string optional The company name. Example: Acme Corp
     * @bodyParam industry string optional The industry. Example: Technology
     * @bodyParam location string optional The user's location. Example: New York, USA
     *
     * @response 201 {
     *   "success": true,
     *   "message": "User registered successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
     *   }
     * }
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'industry' => $validated['industry'] ?? null,
            'location' => $validated['location'] ?? null,
            'status' => 'active',
        ]);

        // Assign Free plan to new user
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $freePlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonths(1),
            'renewal_at' => now()->addMonths(1),
            'stripe_customer_id' => 'free_' . $user->id,
        ]);

        // Create notification preferences
        NotificationPreference::create([
            'user_id' => $user->id,
            'email_notifications_enabled' => true,
            'audit_completion_alerts' => true,
            'weekly_summary' => true,
            'monthly_reports' => true,
            'recommendation_alerts' => true,
        ]);

        // Log activity
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user_signup',
            'description' => 'New user registered',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 'User registered successfully', 201);
    }

    /**
     * Login user
     *
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
     *   }
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return $this->error('Account is ' . $user->status, 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log activity
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user_login',
            'description' => 'User logged in',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 'Login successful');
    }

    /**
     * Logout user
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Logout successful"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        // Log activity
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user_logout',
            'description' => 'User logged out',
            'resource_type' => 'user',
            'resource_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success(null, 'Logout successful');
    }

    /**
     * Get authenticated user profile
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "phone": "+1234567890",
     *     "company": "Acme Corp",
     *     "industry": "Technology",
     *     "location": "New York, USA",
     *     "status": "active",
     *     "created_at": "2025-01-20T10:30:00Z"
     *   }
     * }
     */
    public function me(Request $request)
    {
        return $this->success($request->user());
    }
}
