<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        // جلب كل المستخدمين الموجودين في جدول الـ users
        $users = User::all();

        // الحالات المتاحة للـ status
        $statuses = ['pending', 'accepted', 'rejected'];

        foreach ($users as $user) {
            // إنشاء محفظة لكل مستخدم مع البيانات المالية العشوائية إذا لم تكن موجودة مسبقاً
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone_number' => '01' . rand(0, 1) . rand(10000000, 99999999),
                    'price'        => rand(100, 1000), // سعر عشوائي
                    'amount'       => rand(1, 5),      // كمية عشوائية
                    'total_price'  => rand(100, 5000), // إجمالي السعر
                    'status'       => $statuses[array_rand($statuses)], // اختيار حالة عشوائية
                ]
            );
        }
    }
}