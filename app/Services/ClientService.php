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
$perPage = $data['per_page'] ?? '';
        
        return User::query()
            ->where('role', 'user')
            ->with($relations)
            ->paginate($perPage);
        // return getCaseCollection($Clients, $data);

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
// public function active(array $data, array $relations = [])
// {
//   $query = \App\Models\User::with($relations)->where('role', 'user');
// $perPage = $data['per_page'] ?? 10;

//     return $query->paginate($perPage);
//     }

public function active(array $data, array $relations = [])
{
    $query = \App\Models\User::with($relations)->where('role', 'user');

    // تفعيل البحث بناءً على المعطيات المرسلة
    if (!empty($data['search'])) {
        $search = $data['search'];
        $query->where(function ($q) use ($search) {
            $q->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%")
              ->orWhere('organization_name', 'like', "%{$search}%")
              ->orWhere('ip', 'like', "%{$search}%")
                                    ->orWhereDate('created_at', $search)
;
              
        });
    }

    if (isset($data['is_active']) && $data['is_active'] !== '') {
        if ($data['is_active'] !== 'all') {
            $query->where('is_active', $data['is_active']);
        }
    }

    $perPage = $data['per_page'] ??"";

    return $query->paginate($perPage);
}
    /**
     * إيجاد عميل معين بواسطة الـ ID
     */
    public function getClientById($id)
    {
        return User::where('role', 'user')->findOrFail($id);
    }

}