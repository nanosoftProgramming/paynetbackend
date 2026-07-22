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
}