<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully',
            'data' => $result,
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return response()->json([
                'message' => 'Invalid credentials or inactive account',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => $result,
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Refresh token.
     */
    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();

        return response()->json([
            'message' => 'Token refreshed successfully',
            'data' => $result,
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return response()->json([
            'data' => $user,
        ]);
    }
}
