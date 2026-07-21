<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/register
     * Body: username, email, organization_name, password, password_confirmation
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'organization_name' => $validated['organization_name'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken($request->userAgent() ?? 'api')->plainTextToken;

        return $this->success('Account created successfully.', [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     * Body: email, password, device_name (optional)
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials.', null, 401);
        }

        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();

        $deviceName = $request->input('device_name', $request->userAgent() ?? 'api');
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success('Logged in successfully.', [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(\Illuminate\Http\Request $request)
    {
        return $this->success('User fetched.', new UserResource($request->user()));
    }

    /**
     * POST /api/v1/auth/logout
     * Revokes only the token used for the current request.
     */
    public function logout(\Illuminate\Http\Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logged out successfully.');
    }

    /**
     * POST /api/v1/auth/logout-all
     * Revokes every token issued to the user (all devices).
     */
    public function logoutAll(\Illuminate\Http\Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->success('Logged out from all devices.');
    }
}