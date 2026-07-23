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
                return returnMessage(true, 'Wallet not found for this user', [], 'success');
            }

            return returnMessage(true, 'Wallet retrieved successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
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


// قبول أو رفض محفظة المستخدم بواسطة الأدمن مع إضافة المبلغ والسعر عند القبول
    public function changeWalletStatus(Request $request, $id)
    {
        try {
            // التحقق من أن المستخدم أدمن
            if ($request->user()->role !== 'admin') {
                return returnMessage(false, 'Unauthorized. Admin access only.', null, 'forbidden');
            }

            $wallet = Wallet::find($id);

            if (!$wallet) {
                return returnMessage(false, 'Wallet not found', null, 'not_found');
            }

            // التحقق من البيانات المرسلة
            $request->validate([
                'status' => 'required|in:accepted,rejected,active,inactive,1,0', 
                'amount' => 'required_if:status,accepted,1|numeric|min:0',
                'price'  => 'required_if:status,accepted,1|numeric|min:0',
            ]);

            // تحديث الحالة
            $wallet->status = $request->status;

            // إذا وافق الأدمن (تأكد من القيمة التي تعبر عن الموافقة مثل accepted أو 1)
            if ($request->status == 'accepted' || $request->status == '1') {
                $wallet->amount = $request->amount;
                $wallet->price = $request->price; // أو العمود الخاص بالسعر في الجدول لدك
                
                // يمكنك أيضاً زيادة رصيد المحفظة مباشرة إذا رغبت:
                // $wallet->balance += $request->amount;
            }

            $wallet->save();

            return returnMessage(true, 'Wallet updated and processed successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
}