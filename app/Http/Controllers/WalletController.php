<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
class WalletController extends Controller
{
    public function index(Request $request)
    {
        try {
            // التحقق مما إذا كان المستخدم الحالي هو أدمن
            if ($request->user()->role !== 'admin') {
                return returnMessage(false, 'Unauthorized. Admin access only.', null, 'forbidden');
            }

            // جلب جميع المحافظ مع بيانات المستخدم المرتبط بها
            $wallets = Wallet::with('user')->get();

            // استخدام دالة returnMessage الخاصة بمشروعك (أو استبدالها بـ response()->json العادية)
            return returnMessage(true, 'Wallets retrieved successfully', $wallets, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
    // جلب محفظة المستخدم المُسجّل حالياً
    public function myWallet(Request $request)
    {
        try {
            // جلب المحفظة المرتبطة بالمستخدم الحالي مع بياناته
            $wallet = Wallet::where('user_id', $request->user()->id)->with('user')->first();

            if (!$wallet) {
                return returnMessage(false, 'Wallet not found for this user', null, 'not_found');
            }

            return returnMessage(true, 'Wallet retrieved successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
    // إنشاء محفظة للمستخدم الحالي إذا لم تكن موجودة
// إنشاء محفظة للمستخدم الحالي إذا لم تكن موجودة
    public function createMyWallet(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // التحقق مما إذا كانت المحفظة موجودة مسبقاً
            $wallet = Wallet::where('user_id', $userId)->first();

            if ($wallet) {
                return returnMessage(false, 'User already has a wallet', $wallet, 'bad_request');
            }

            // التحقق من أن رقم الهاتف مرسل إذا كان إجبارياً
            $request->validate([
                'phone_number' => 'required|string|max:20',
            ]);

            // إنشاء المحفظة مع إضافة رقم الهاتف
            $wallet = Wallet::create([
                'user_id' => $userId,
                'phone_number' => $request->phone_number,
                'total_price' => $request->total_price,
                'balance' => 0.00,
                'status' => 1,
            ]);

            return returnMessage(true, 'Wallet created successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
}