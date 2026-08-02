<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Modules\User\App\Mails\ForgetPasswordOtpEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? 'user',
            'is_active' => 1,
            'organization_name' => $validated['organization_name'],
            'password' => Hash::make($validated['password']),
                'fcm_token' => $validated['fcm_token'] ?? null,

        ]);

        $token = $user->createToken($request->userAgent() ?? 'api')->plainTextToken;

        if (empty($user->ip)) {
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'user_no_ip',
                    'title' => 'New User Registration Without IP',
                    'message' => "The user {$user->username} has registered without a recorded IP address.",
                    'data' => [
                        'user' => (new UserResource($user))->resolve($request)
                    ]
                ]);
            }
        }

        return $this->success('Account created successfully.', [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * الخطوة الأولى: إرسال كود الـ OTP عبر البريد الإلكتروني
     */
public function deleteUserByAdmin(Request $request, $id)
{
    // التحقق من أن المستخدم أدمن
    if ($request->user()->role !== 'admin') {
        return $this->error('Unauthorized. Admin access only.', null, 403);
    }

    $user = User::find($id);

    if (!$user) {
        return $this->error('User not found.', null, 404);
    }

    // منع الأدمن من حذف نفسه بالخطأ
    if ($request->user()->id === $user->id) {
        return $this->error('You cannot delete your own admin account.', null, 400);
    }

    // حذف التوكنز الخاصة بالمستخدم (Sanctum)
    $user->tokens()->delete();

    // حذف المستخدم (سيتم حذف المحافظ والمعاملات والإشعارات تلقائياً عبر قاعدة البيانات)
    $user->delete();

    return $this->success('User and all associated data deleted successfully.');
}


    // public function sendResetLink(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:users,email',
    //     ], [
    //         'email.exists' => 'هذا البريد الإلكتروني غير مسجل لدينا.',
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     // إرسال الإيميل
    //     Mail::to($user->email)->send(new ForgetPasswordOtpEmail($user));

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'OTP Sent Successfully'
    //     ], 200);
    // }

public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Email not found.'
        ], 404);
    }

    // إنشاء Token
    $token = Str::random(64);

    // حذف أي Token قديم لنفس الإيميل
    DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->delete();

    // حفظ Token
    DB::table('password_reset_tokens')->insert([
        'email' => $request->email,
        'token' => bcrypt($token), // نخزن الـ Token مشفر
        'created_at' => Carbon::now()
    ]);

    // رابط إعادة تعيين كلمة المرور
    $resetLink = "http://localhost:5173/reset-password?token={$token}&email={$request->email}";

    // إرسال الإيميل
    Mail::raw("Click the following link to reset your password:\n\n$resetLink", function ($message) use ($request) {
        $message->to($request->email)
                ->subject('Reset Password');
    });

    return response()->json([
        'message' => 'Reset password link has been sent to your email.'
    ]);
}
    /**
     * الخطوة الثانية: استقبال كلمة المرور الجديدة وتحديثها
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json([
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.'
            ], 200)
            : response()->json([
                'status' => false,
                'message' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.'
            ], 400);
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
            'new_password' => 'required|string|min:8|confirmed',
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
        if ($request->user()->role !== 'admin') {
            return $this->error('Unauthorized. Admin access only.', null, 403);
        }

        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found.', null, 404);
        }

        $user->update([
            'ip' => $request->ip
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'ip_added',
            'title' => 'IP Address Updated',
            'message' => 'An IP address has been successfully assigned to your account by the administrator.',
            'data' => [
                'ip' => $user->ip
            ]
        ]);

        return $this->success('User IP updated successfully.', [
            'user' => new UserResource($user)
        ]);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials.', null, 401);
        }

        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();


    if ($request->filled('fcm_token')) {
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);
    }


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
    public function me(Request $request)
    {
        return $this->success('User fetched.', new UserResource($request->user()));
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logged out successfully.');
    }

    /**
     * POST /api/v1/auth/logout-all
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->success('Logged out from all devices.');
    }
}