<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB; // <-- أضف هذا السطر الضروري جداً
use Illuminate\Http\Request;
class UserTransactionController extends Controller
{
public function myTransactions(Request $request)
    {
        // 1. جلب ID المستخدم الحالي المسجل الدخول عبر التوكن
        $userId = $request->user()->id; // أو استخدام auth()->id()

        // 2. جلب المعاملات الخاصة بهذا المستخدم فقط مع بيانات المحفظة
        $transactions = Transaction::with(['wallet'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Your transactions retrieved successfully.',
            'data' => $transactions
        ], 200);
    }

    public function updateTransactionStatus(Request $request, $id)
{
    // 1. التحقق من الحالة المرسلة (accepted أو rejected)
    $validated = $request->validate([
        'status' => ['required', 'in:accepted,rejected'],
    ]);

    // 2. جلب المعاملة والتأكد أنها تخص المستخدم الحالي المسجل الدخول
    $transaction = Transaction::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->first();

    if (!$transaction) {
        return response()->json([
            'status' => false,
            'message' => 'المعاملة غير موجودة أو لا تمتلك صلاحية عليها.'
        ], 404);
    }

    // التأكد أن المعاملة لم يتم الرد عليها مسبقاً
    if ($transaction->status === 'accepted' || $transaction->status === 'rejected') {
        return response()->json([
            'status' => false,
            'message' => 'هذه المعاملة تم الرد عليها مسبقاً ولا يمكن تعديلها.'
        ], 422);
    }

    // استخدام DB Transaction لضمان سلامة العمليات المالية
    DB::beginTransaction();
    try {
        $wallet = Wallet::where('user_id', $transaction->user_id)->first();

        if (!$wallet) {
            return response()->json([
                'status' => false,
                'message' => 'محفظة المستخدم غير موجودة.'
            ], 404);
        }

        // إذا اختار المستخدم ACCEPTED
        if ($validated['status'] === 'accepted') {
            
            // فحص نوع المعاملة (Type)
            if ($transaction->type == 1) {
                // Type == 1: خصم سعر المعاملة من رصيد المحفظة (Minus)
                if ($wallet->price < $transaction->price) {
                    return response()->json([
                        'status' => false,
                        'message' => 'رصيد المحفظة غير كافٍ لإتمام عملية الخصم.'
                    ], 422);
                }
                $wallet->price += $transaction->price;

            } elseif ($transaction->type == 2) {
                // Type == 2: إضافة سعر المعاملة إلى رصيد المحفظة (Add)
                $wallet->price -= $transaction->price;
            }

            // حفظ التعديل على المحفظة وتغيير حالة المعاملة إلى accepted
            $wallet->save();
            $transaction->status = 'accepted';

        } else {
            // إذا اختار المستخدم REJECTED (فقط تغيير الحالة دون تعديل الرصيد)
            $transaction->status = 'rejected';
        }

        $transaction->save();

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Transaction status updated successfully.',
            'transaction' => $transaction,
            'updated_wallet_balance' => $wallet->price
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Server Error: ' . $e->getMessage()
        ], 500);
    }
}
    }

