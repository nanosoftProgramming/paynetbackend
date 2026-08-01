<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Notification;

class AdminTransactionController extends Controller
{
public function store(Request $request)
    {
        // 1. التحقق من البيانات المرسلة من الأدمن
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'price'   => ['required', 'numeric', 'min:0.01'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'defualt_unit' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
                'price_dollar' => ['nullable', 'numeric', 'min:0'],
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
            'bank_id'   => $validated['bank_id'] ?? null,
            'defualt_unit' => $validated['defualt_unit'] ?? null,
                'price_dollar' => $validated['price_dollar'] ?? null,
            'status'    => 'pending', // تلقائياً معلقة
            'phone'     => $user->phone ?? $wallet->phone_number ?? '0000000000', // إذا لم يوجد هاتف، يضع رقم افتراضي بدلاً من الانهيار
            'phone_number'     => $validated['phone_number']?? '0000000000', // إذا لم يوجد هاتف، يضع رقم افتراضي بدلاً من الانهيار
            'type'      => $validated['type'] ?? 'deposit', // نوع افتراضي
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'new_transaction_added',
            'title' => 'New Transaction Added',
            'message' => 'A new transaction has been added to your account by the admin.',
            'data' => [
                'transaction_id' => $transaction->id,
                'transaction' => $transaction->toArray(),
                'bank' => $transaction->bank ? $transaction->bank->toArray() : null
            ]
        ]);
        return response()->json([
            'message' => 'Transaction created successfully as pending.',
            'data'    => $transaction
        ], 201);
    }
public function index(Request $request)
{
    // البدء بالاستعلام مع جلب العلاقات وترتيبها من الأحدث للأقدم
    $query = Transaction::with(['user', 'wallet', 'bank'])->latest();

    // 1. الفلترة حسب الحالة (status)
    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    // 2. الفلترة حسب النوع (type)
    if ($request->filled('type') && $request->input('type') !== 'all') {
        $query->where('type', $request->input('type'));
    }

    // 3. الفلترة حسب السنة (Year) - تم إصلاحها وتأكيدها هنا
    if ($request->filled('year')) {
        $year = $request->input('year');
        if (is_numeric($year)) {
            $query->whereYear('created_at', $year);
        }
    }

    // 4. الفلترة حسب الشهر (Month)
    if ($request->filled('month')) {
        $query->whereMonth('created_at', $request->input('month'));
    }

    // 5. الفلترة حسب اليوم (Day)
    if ($request->filled('day')) {
        $dateInput = $request->input('day');
        $formattedDate = date('Y-m-d', strtotime($dateInput));
        $query->whereDate('created_at', $formattedDate);
    }

    // 6. نظام البحث الشامل (Search)
    if ($request->filled('search')) {
        $search = $request->input('search');

        $query->where(function ($q) use ($search) {
            $q->where('phone', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%")
              ->orWhere('type', 'like', "%{$search}%")
              ->orWhere('price', 'like', "%{$search}%")
              ->orWhereDate('created_at', $search)
              ->orWhereHas('user', function ($userQuery) use ($search) {
                  $userQuery->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
              })
              ->orWhereHas('bank', function ($bankQuery) use ($search) {
                  $bankQuery->where('number', 'like', "%{$search}%");
              });
        });
    }

    // 7. التقسيم (Pagination) - تم ضبط القيمة الافتراضية إلى 10 بدلاً من 1 لتجربة مستخدم أفضل
    $perPage = $request->input('per_page', 10);
    $transactions = $query->paginate($perPage);

    return response()->json([
        'status' => true,
        'message' => 'Transactions retrieved successfully.',
        'data' => $transactions
    ], 200);
}
//     public function index(Request $request)
//     {
//         // جلب المعاملات مع بيانات المستخدم والمحفظة المرتبطة بها، وترتيبها من الأحدث للأقدم
// $transactions = Transaction::with(['user', 'wallet', 'bank'])
//                 ->latest()
//                 ->paginate(15);
//         return response()->json([
//             'status' => true,
//             'message' => 'Transactions retrieved successfully.',
//             'data' => $transactions
//         ], 200);
//     }
}