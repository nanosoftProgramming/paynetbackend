<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB; // <-- أضف هذا السطر الضروري جداً
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseService;

class UserTransactionController extends Controller
{
// public function myTransactions(Request $request)
//     {
//         // 1. جلب ID المستخدم الحالي المسجل الدخول عبر التوكن
//         $userId = $request->user()->id; // أو استخدام auth()->id()

//         // 2. جلب المعاملات الخاصة بهذا المستخدم فقط مع بيانات المحفظة
//         $transactions = Transaction::with(['wallet', 'bank'])
//             ->where('user_id', $userId)
//             ->latest()
//             ->get();

//         return response()->json([
//             'status' => true,
//             'message' => 'Your transactions retrieved successfully.',
//             'data' => $transactions
//         ], 200);
//     }

public function myTransactions(Request $request)
    {
        try {
            // 1. جلب ID المستخدم الحالي المسجل الدخول عبر التوكن
            $userId = $request->user()->id;

            // 2. البدء بالاستعلام وحصر المعاملات للمستخدم الحالي فقط مع جلب العلاقات
            $query = Transaction::with(['wallet', 'bank'])
                ->where('user_id', $userId)
                ->latest();

            // 3. الفلترة حسب الحالة (status)
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // 4. نظام البحث الشامل (Search)
            if ($request->filled('search')) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    // البحث في جدول المعاملات (Transactions) الخاصة به
                    $q->where('phone', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhere('price', 'like', "%{$search}%")
                      ->orWhere('price_dollar', 'like', "%{$search}%")
                      ->orWhereDate('created_at', $search)
                      
                      // البحث في جدول البنوك المرتبط (Bank)
                      ->orWhereHas('bank', function ($bankQuery) use ($search) {
                          $bankQuery->where('number', 'like', "%{$search}%");
                      })
                      
                      // البحث في جدول المحافظ المرتبط (Wallet) إن وجد
                      ->orWhereHas('wallet', function ($walletQuery) use ($search) {
                          $walletQuery->where('phone_number', 'like', "%{$search}%");
                      });
                });
            }

            // 5. الفلترة حسب التاريخ (Date Filters)
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->input('date'));
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

                if ($request->filled('type') && $request->input('type') !== 'all') {
        $query->where('type', $request->input('type'));
    }
            // 6. التقسيم (Pagination) - الافتراضي 15 عنصر لكل صفحة
            $perPage = $request->input('per_page', 15);
            $transactions = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Your transactions retrieved successfully.',
                'data' => $transactions
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
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
        $walletPrice = (float) $wallet->price;

            $walletPriceDollar = (float) $wallet->price_dollar;

           

            $transactionPrice = (float) $transaction->price;

            $transactionPriceDollar = (float) $transaction->price_dollar;
        // إذا اختار المستخدم ACCEPTED
        if ($validated['status'] === 'accepted') {
            
            // فحص نوع المعاملة (Type)
            // if ($transaction->type == 1) {
            //     // Type == 1: خصم سعر المعاملة من رصيد المحفظة (Minus)
            //     // if ($wallet->price < $transaction->price) {
            //     //     return response()->json([
            //     //         'status' => false,
            //     //         'message' => 'رصيد المحفظة غير كافٍ لإتمام عملية الخصم.'
            //     //     ], 422);
            //     // }
            //     $wallet->price += $transaction->price;
            //     $wallet->price_dollar += $transaction->price_dollar;

            // } elseif ($transaction->type == 2) {
            //     // Type == 2: إضافة سعر المعاملة إلى رصيد المحفظة (Add)
            //     $wallet->price -= $transaction->price;
            //     $wallet->price_dollar -= $transaction->price_dollar;
            // }
if ($transaction->type == 1) {
                // Type == 1: عملية سحب أو خصم (نطرح من رصيد المحفظة)
                $wallet->price = $walletPrice - $transactionPrice;
                $wallet->price_dollar = $walletPriceDollar - $transactionPriceDollar;

            } elseif ($transaction->type == 2) {
                // Type == 2: عملية إيداع أو إضافة (نجمع على رصيد المحفظة)
                $wallet->price = $walletPrice + $transactionPrice;
                $wallet->price_dollar = $walletPriceDollar + $transactionPriceDollar;
            }
            // حفظ التعديل على المحفظة وتغيير حالة المعاملة إلى accepted
            $wallet->save();
            $transaction->status = 'accepted';

        } else {
            // إذا اختار المستخدم REJECTED (فقط تغيير الحالة دون تعديل الرصيد)
            $transaction->status = 'rejected';
        }

        $transaction->save();
$admins = User::where('role', 'admin')->get();
        
        $statusText = $transaction->status=== 'accepted' ? 'accepted' : ($transaction->status === 'rejected' ? 'rejected' : 'updated');
foreach ($admins as $admin) {

    Notification::create([
        'user_id' => $admin->id,
        'type' => 'transaction_status_update',
        'title' => 'Transaction Status Updated',
        'message' => "The user {$transaction->user->username} has {$statusText} transaction #{$transaction->id}.",
        'data' => [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'user' => $transaction->user->toArray(),
            'transaction' => $transaction->toArray(),
        ]
    ]);

    if (!empty($admin->fcm_token)) {
        try {
            app(FirebaseService::class)->send(
                $admin->fcm_token,
                "Transaction state",
                "The user {$transaction->user->username} has {$statusText} transaction #{$transaction->id}."
            );
        } catch (\Throwable $e) {
            \Log::error('FCM Error', [
                'admin_id' => $admin->id,
                'token' => $admin->fcm_token,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
//         foreach ($admins as $admin) {
//             Notification::create([
//                 'user_id' => $admin->id,
//                 'type' => 'transaction_status_update',
//                 'title' => 'Transaction Status Updated',
//                 'message' => "The user {$transaction->user->username} has {$statusText} transaction #{$transaction->id}.",
//                 'data' => [
//                     'transaction_id' => $transaction->id,
//                     'status' => $transaction->status,
//                     'user' => $transaction->user->toArray(),
//                     'transaction' => $transaction->toArray()
//                 ]
//             ]);
//         }
//   $result = app(FirebaseService::class)->send(
//     $admin->fcm_token,
//     "حالة التحويل",
//     "تم {$transaction->status} التحويل"
// );
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

