<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    // 1. جلب كافة البنوك الخاصة بالعميل الحالي
  // public function getClientBanks(Request $request)
  //   {
  //       try {
  //           $userId = $request->user()->id; // جلب الـ ID من الـ Token مباشرة
  //           $banks = Bank::where('user_id', $userId)->get();

  //           return returnMessage(true, 'Client banks fetched successfully', $banks, 'success');
  //       } catch (\Throwable $th) {
  //           return returnMessage(false, $th->getMessage(), null, 'server_error');
  //       }
  //   }

  public function getClientBanks(Request $request)
    {
        try {
            $userId = $request->user()->id; // جلب الـ ID من الـ Token مباشرة
            
            // البدء بالاستعلام مع تخصيص البنوك الخاصة بالمستخدم الحالي فقط
            $query = Bank::where('user_id', $userId);

            // 1. نظام البحث العام (Search)
            if ($request->filled('search')) {
                $search = $request->input('search');
                
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                                                        ->orWhereDate('created_at', $search);


                    
                      // يمكنك إضافة أي حقول أخرى ترغب في البحث ضمنها مثل اسم البنك أو الفرع
                      // ->orWhere('name', 'like', "%{$search}%"); 
                });
            }

            // 2. الفلترة حسب نوع البنك (Type Filter)
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));

            }

            // 3. الفلترة حسب تاريخ الإنشاء (created_at)
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->input('date'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            // ترتيب النتائج من الأحدث للأقدم
            $query->latest();

            // 4. التقسيم (Pagination) - الافتراضي 10 عناصر لكل صفحة
            $perPage = $request->input('per_page', 10);
            $banks = $query->paginate($perPage);

            return returnMessage(true, 'Client banks fetched successfully', $banks, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // 2. إضافة بنك جديد للعميل
public function store(Request $request)
    {
        try {
            $request->validate([
                'number' => 'required|string|max:255',
                'type'   => 'required|string|max:255',
            ]);

            $bank = Bank::create([
                'user_id' => $request->user()->id, // أخذ الـ user_id من الـ Token تلقائياً
                'number'  => $request->number,
                'type'    => $request->type,

            ]);

            return returnMessage(true, 'Bank added successfully', $bank, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // 3. تعديل بنك معين
    public function update(Request $request, $id)
    {
        try {
            $bank = Bank::find($id);

            if (!$bank) {
                return returnMessage(false, 'Bank not found', null, 'not_found');
            }

            $request->validate([
                'number' => 'required|string|max:255',
                'type'   => 'required|string|max:255',
            ]);

            $bank->update([
                'number' => $request->number,
                'type'   => $request->type,
            ]);

            return returnMessage(true, 'Bank updated successfully', $bank, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // 4. حذف بنك
    public function destroy($id)
    {
        try {
            $bank = Bank::find($id);

            if (!$bank) {
                return returnMessage(false, 'Bank not found', null, 'not_found');
            }

            $bank->delete();

            return returnMessage(true, 'Bank deleted successfully', null, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // 5. جلب كافة البنوك للأدمن (جميع البنوك للنظام)
    // public function adminGetAllBanks()
    // {
    //     try {
    //         $banks = Bank::with('user')->get(); // جلب البنوك مع بيانات المستخدم إن وجدت علاقة

    //         return returnMessage(true, 'All banks fetched for admin successfully', $banks, 'success');
    //     } catch (\Throwable $th) {
    //         return returnMessage(false, $th->getMessage(), null, 'server_error');
    //     }
    // }
    // 5. جلب كافة البنوك للأدمن مع البحث والفلترة حسب التاريخ
public function adminGetAllBanks(Request $request)
    {
        try {
            // التحقق مما إذا كان المستخدم الحالي هو أدمن
            if ($request->user()->role !== 'admin') {
                return returnMessage(false, 'Unauthorized. Admin access only.', null, 'forbidden');
            }

            // البدء بالاستعلام مع ربط جدول المستخدم
            $query = Bank::with('user');

            // 1. نظام البحث العام (Search) - تم إزالة type منه لكي يصبح مستقلاً
            if ($request->filled('search')) {
                $search = $request->input('search');
                
                $query->where(function ($q) use ($search) {
                    // البحث في رقم البنك فقط أو بيانات المستخدم
                    $q->where('number', 'like', "%{$search}%")
                                        ->orWhereDate('created_at', $search)

                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('username', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('organization_name', 'like', "%{$search}%");
;

                                    
                      });
                });
            }

            // 2. الفلترة حسب نوع البنك (Type Filter) - تم إضافتها هنا كفلتر مستقل
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            // 3. الفلترة حسب تاريخ الإنشاء (created_at)
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->input('date'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            // 4. التقسيم (Pagination)
            $perPage = $request->input('per_page', 10);
            $banks = $query->paginate($perPage);

            return returnMessage(true, 'All banks fetched for admin successfully', $banks, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }



    // 6. جلب كافة البنوك الخاصة بمستخدم معين للأدمن (بدون pagination)
    public function adminGetBanksByUser(Request $request, $userId)
    {
        try {
            // التحقق مما إذا كان المستخدم الحالي هو أدمن
            if ($request->user()->role !== 'admin') {
                return returnMessage(false, 'Unauthorized. Admin access only.', null, 'forbidden');
            }

            // البدء بالاستعلام مع جلب بيانات المستخدم الأساسية أيضاً
            $query = Bank::where('user_id', $userId)->with('user');

            // 1. نظام البحث العام (Search) ضمن بنوك هذا المستخدم
            if ($request->filled('search')) {
                $search = $request->input('search');
                
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                      ->orWhereDate('created_at', $search);
                });
            }

            // 2. الفلترة حسب نوع البنك (Type Filter)
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            // 3. الفلترة حسب تواريخ الإنشاء
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->input('date'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            // ترتيب النتائج من الأحدث للأقدم وجلب البيانات كاملة
            $banks = $query->latest()->get();

            return returnMessage(true, 'User banks fetched successfully for admin', $banks, 'success');

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
}