<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ];
    }

    /**
     * Login user and generate token.
     */
    public function login(array $credentials): ?array
    {
        if (!$token = Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return null;
        }

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ];
    }

    /**
     * Logout user.
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * Refresh token.
     */
    public function refresh(): array
    {
        $token = Auth::refresh();

        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ];
    }

    /**
     * Get authenticated user.
     */
    public function me(): User
    {
        return Auth::user();
    }
}
