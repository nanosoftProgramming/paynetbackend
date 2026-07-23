<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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
    }
