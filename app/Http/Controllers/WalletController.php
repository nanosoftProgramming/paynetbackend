<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\FirebaseService;
class WalletController extends Controller
{
public function index(Request $request)
{
    try {
        // التحقق مما إذا كان المستخدم الحالي هو أدمن
        if ($request->user()->role !== 'admin') {
            return returnMessage(false, 'Unauthorized. Admin access only.', null, 'forbidden');
        }

        // 1. تعريف المتغير الأساسي
        $query = Wallet::with('user');

        // 2. البحث فقط إذا كان مُدخلاً وليس فارغاً
        if ($request->filled('search')) {
            $search = $request->input('search');
            
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('total_price', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('price', 'like', "%{$search}%")
                                        ->orWhereDate('created_at', $search)

                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('username', 'like', "%{$search}%")
                                ->orWhere('organization_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 3. الفلترة حسب الحالة فقط إذا تم اختيار حالة حقيقية وليست فارغة أو 'all'
  if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            
            // التأكد من أن الحالة المرسلة صحيحة ومقبولة
            $allowedStatuses = ['pending', 'accepted', 'rejected', 'active', 'inactive'];
            
            if (in_array($status, $allowedStatuses)) {
                $query->where('status', $status);
            }
        }
if ($request->filled('year')) {
                $query->whereYear('created_at', $request->input('year'));
            }

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->input('month'));
            }
            if ($request->filled('day')) {
    // نفترض أن الصيغة المرسلة هي 'm/d/Y' أو صيغة قياسية يمكن تحويلها
    $dateInput = $request->input('day');
    
    // إذا كنت ترسله بصيغة m/d/Y (مثل 7/30/2026) يمكنك تحويله إلى Y-m-d هكذا:
    $formattedDate = date('Y-m-d', strtotime($dateInput));

    $query->whereDate('created_at', $formattedDate);
}
        // 4. تنفيذ الجلب مع التقسيم (Pagination)
        $perPage = $request->input('per_page', 10);
        $wallets = $query->paginate($perPage);

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

            // // التحقق مما إذا كانت المحفظة موجودة مسبقاً
            // $wallet = Wallet::where('user_id', $userId)->first();

            // if ($wallet) {
            //     return returnMessage(false, 'User already has a wallet', $wallet, 'bad_request');
            // }

            // التحقق من صحة البيانات واستقبال العملة كنص
            $request->validate([
                'phone_number' => 'required|string|max:20',
                'currency'     => 'required|string|max:50', // التحقق من أن العملة نصية وموجودة
                'total_price'  => 'nullable|numeric',
                'total_price_dollar'  => 'nullable|numeric',
                                'defualt_unit_total_price' => ['nullable', 'string', 'max:20'],

                // 'defualt_unit'             => ['nullable', 'string', 'max:255'],
                // 'price_dollar'             => ['nullable', 'numeric', 'min:0'],
                // 'total_price_dollar'       => ['nullable', 'numeric', 'min:0'],
                // 'amount_dollar'            => ['nullable', 'numeric', 'min:0'],
                // 'defualt_unit_amount'      => ['nullable', 'numeric', 'min:0'],
            ]);

            // إنشاء المحفظة مع حفظ العملة كنص في البداية
            $wallet = Wallet::updateOrCreate(
['user_id' => $userId], // شرط البحث (إذا وجد هذا المستخدم)
            [
                              'currency'     => $request->currency, // <--- حفظ اسم العملة كنص (مثال: USD أو EGP)
                // 'user_id'      => $userId,
                'phone_number' => $request->phone_number,
                'total_price'  => $request->total_price ?? 0.00,
                'balance'      => 0.00,
                'status'       => 1,
                // 'defualt_unit'             => $validated['defualt_unit'] ?? null,
                // 'price_dollar'             => $validated['price_dollar'] ?? null,
                'total_price_dollar'       => $request->total_price_dollar ?? null,
                'defualt_unit_total_price' => $request->defualt_unit_total_price ?? null,
                // 'amount_dollar'            => $validated['amount_dollar'] ?? null,
                // 'defualt_unit_amount'      => $validated['defualt_unit_amount'] ?? null,
            ]);
            $admins = User::where('role', 'admin')->get();
        
foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'wallet_pending',
                'title' => 'New Wallet Request',
                'message' => "The client {$request->user()->username} has added a new wallet pending approval.",
                'data' => [
'user' => (new UserResource($request->user()))->resolve($request),
                    
                    // إرجاع تفاصيل المحفظة كامولة (مع تحويلها لـ Array)
                    'wallet' => $wallet->toArray(),                ]
            ]);
        }


            return returnMessage(true, 'Wallet created successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }


// قبول أو رفض محفظة المستخدم بواسطة الأدمن مع إضافة المبلغ والسعر عند القبول
public function updateWallet(Request $request, $id)
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

            // التحقق من صحة البيانات الواردة
            $request->validate([
                'amount'              => 'required_if:status,accepted,1|nullable|numeric|min:0',
                'amount_dollar'       => 'required_if:status,accepted,1|nullable|numeric|min:0',
                'defualt_unit_amount' => 'required_if:status,accepted,1|nullable|string|max:50',
                'price'               => 'nullable|numeric|min:0',
                'price_dollar'        => 'nullable|numeric|min:0',
                'defualt_unit' => 'required_if:status,accepted,1|nullable|string|max:50',

            ]);


            // تحديث الحقول بالقيم المرسلة مباشرة بدون حسابات
            if ($request->has('amount')) {
                $wallet->amount = $request->amount;
            }
            if ($request->has('amount_dollar')) {
                $wallet->amount_dollar = $request->amount_dollar;
            }
            if ($request->has('defualt_unit_amount')) {
                $wallet->defualt_unit_amount = $request->defualt_unit_amount;
        
            if ($request->has('price')) {
                $wallet->price = $request->price;
            }
            if ($request->has('price_dollar')) {
                $wallet->price_dollar = $request->price_dollar;
            }

                }
            if ($request->has('defualt_unit')) {
                $wallet->defualt_unit = $request->defualt_unit;
            }
            $wallet->save();
Notification::create([
            'user_id' => $wallet->user_id,
            'type' =>  $request->status,
            'title' => 'Wallet Approved',
'message' => 'Your wallet request has been ' . $request->status . ' by the admin.',       

            'data' => [
                'wallet_id' => $wallet->id,
                'wallet' => $wallet->toArray()
            ]
        ]);
            return returnMessage(true, 'Wallet updated successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
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

            // التحقق من صحة البيانات الواردة
            $request->validate([
                'status'              => 'required|in:accepted,rejected,active,inactive,1,0', 
                'amount'              => 'required_if:status,accepted,1|nullable|numeric|min:0',
                'amount_dollar'       => 'required_if:status,accepted,1|nullable|numeric|min:0',
                'defualt_unit_amount' => 'required_if:status,accepted,1|nullable|string|max:50',
                'defualt_amount'      => 'nullable|string|max:50',
                'price'               => 'nullable|numeric|min:0',
                'price_dollar'        => 'nullable|numeric|min:0',
            ]);

            // تحديث الحالة
            $wallet->status = $request->status;

            // تحديث الحقول بالقيم المرسلة مباشرة بدون حسابات
            if ($request->has('amount')) {
                $wallet->amount = $request->amount;
            }
            if ($request->has('amount_dollar')) {
                $wallet->amount_dollar = $request->amount_dollar;
            }
            if ($request->has('defualt_unit_amount')) {
                $wallet->defualt_unit_amount = $request->defualt_unit_amount;
            }
            if ($request->has('defualt_amount')) {
                $wallet->defualt_amount = $request->defualt_amount;
            }
            if ($request->has('price')) {
                $wallet->price = $request->price;
            }
            if ($request->has('price_dollar')) {
                $wallet->price_dollar = $request->price_dollar;
            }

            $wallet->save();
Notification::create([
            'user_id' => $wallet->user_id,
            'type' =>  'status state',
            'title' => 'Wallet Approved',
'message' => 'Your wallet request has been ' . $request->status . ' by the admin.',       
     'data' => [
                'wallet_id' => $wallet->id,
                'wallet' => $wallet->toArray()
            ]
        ]);
        
$user = \App\Models\User::find($wallet->user_id);
app(FirebaseService::class)->send(
    $user->fcm_token,
    "عميل جديد",
    "تم تسجيل عميل جديد في النظام"
);


            return returnMessage(true, 'Wallet updated successfully', $wallet, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
}