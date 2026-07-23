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
}