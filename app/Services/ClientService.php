<?php

namespace App\Services;

use App\Models\User;
use Modules\User\App\Models\Clients;

class ClientService
{
    /**
     * جلب جميع العملاء (المستخدمين الذين لديهم صلاحية user)
     */
    function findAll($data = [], $relations = [])
    {
              $Clients = Clients::query()
            ->where('role', 'user')
            ->with($relations);

        return getCaseCollection($Clients, $data);

        // يمكنك وضع أي شروط، فلترة، أو تقسيم صفحات (Pagination) هنا
        // return User::where('role', 'user')->get();
    }
public function toggleActivate($user, array $data = [])
    {
        // عكس القيمة الحالية مباشرة لتجنب مشاكل استقبال البيانات الخاطئة
        $newStatus = $user->is_active == 1 ? 0 : 1;

        $user->update([
            'is_active' => $newStatus
        ]);

        return $user->fresh();
    }
public function active(array $data, array $relations = [])
{
    // استدعاء المودل وجلب البيانات (عدل حسب اسم المودل لديك مثل Client أو User)
    return \App\Models\User::with($relations)->where('role', 'user')->get();
}
    /**
     * إيجاد عميل معين بواسطة الـ ID
     */
    public function getClientById($id)
    {
        return User::where('role', 'user')->findOrFail($id);
    }

}