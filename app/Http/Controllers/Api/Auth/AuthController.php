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
use Illuminate\Http\Request;

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
            'is_active' => 1, // تفعيل الحساب افتراضياً عند التسجيل
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
// تعديل الملف الشخصي
public function updateProfile(Request $request)
{
    $user = $request->user();

    $validated = $request->validate([
        'username' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $user->id,
'organization_name' => 'sometimes|string|max:255',
        ]);

    $user->update($validated);

    return response()->json([
        'status' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح',
        'data' => $user
    ]);
}

// تغيير كلمة المرور
public function changePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required|current_password',
        'new_password' => 'required|string|min:8|confirmed', // يتطلب حقل new_password_confirmation
    ]);

    $user = $request->user();
    
    $user->update([
        'password' => Hash::make($request->new_password)
    ]);

    return response()->json([
        'status' => true,
        'message' => 'تم تغيير كلمة المرور بنجاح'
    ]);
}

public function updateOrCreateUserIp(Request $request, $id)
{
    // التحقق من أن المستخدم الحالي هو أدمن
    if ($request->user()->role !== 'admin') {
        return $this->error('Unauthorized. Admin access only.', null, 403);
    }

    // $request->validate([
    //     'ip' => 'required|ip', // التحقق من صحة صيغة الـ IP (IPv4 أو IPv6)
    // ]);

    $user = User::find($id);

    if (!$user) {
        return $this->error('User not found.', null, 404);
    }

    $user->update([
        'ip' => $request->ip
    ]);

    return $this->success('User IP updated successfully.', [
        'user' => new UserResource($user)
    ]);
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