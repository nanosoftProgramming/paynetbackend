<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    // 1. جلب كافة البنوك الخاصة بالعميل الحالي
    public function getClientBanks(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $banks = Bank::where('user_id', $userId)->get(); // مفترض وجود user_id في جدول البنوك أو ربط عبر علاقة

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
    public function adminGetAllBanks()
    {
        try {
            $banks = Bank::with('user')->get(); // جلب البنوك مع بيانات المستخدم إن وجدت علاقة

            return returnMessage(true, 'All banks fetched for admin successfully', $banks, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }
}