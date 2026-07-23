<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
class AdminTransactionController extends Controller
{
public function store(Request $request)
    {
        // 1. التحقق من البيانات المرسلة من الأدمن
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'price'   => ['required', 'numeric', 'min:0.01'],
            'type'    => ['nullable', 'string'], // اختياري لكي لا يتسبب بخطأ إذا نسيت إرساله
        ]);

        // 2. جلب المستخدم
        $user = User::findOrFail($validated['user_id']);
        
        // 3. جلب محفظة هذا المستخدم تلقائياً
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json([
                'message' => 'هذا المستخدم لا يمتلك محفظة مسجلة في النظام.'
            ], 422);
        }

        // 4. إنشاء المعاملة مع وضع قيم افتراضية آمنة تمنع انهيار قاعدة البيانات
        $transaction = Transaction::create([
            'user_id'   => $user->id,
            'wallet_id' => $wallet->id,
            'price'     => $validated['price'],
            'status'    => 'pending', // تلقائياً معلقة
            'phone'     => $user->phone ?? $wallet->phone_number ?? '0000000000', // إذا لم يوجد هاتف، يضع رقم افتراضي بدلاً من الانهيار
            'type'      => $validated['type'] ?? 'deposit', // نوع افتراضي
        ]);

        return response()->json([
            'message' => 'Transaction created successfully as pending.',
            'data'    => $transaction
        ], 201);
    }
    public function index(Request $request)
    {
        // جلب المعاملات مع بيانات المستخدم والمحفظة المرتبطة بها، وترتيبها من الأحدث للأقدم
        $transactions = Transaction::with(['user', 'wallet'])
            ->latest()
            ->paginate(15); // استخدام التصفح (Pagination) لتقسيم النتائج إلى صفحات

        return response()->json([
            'status' => true,
            'message' => 'Transactions retrieved successfully.',
            'data' => $transactions
        ], 200);
    }
}