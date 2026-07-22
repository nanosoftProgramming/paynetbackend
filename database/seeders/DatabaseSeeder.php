<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. استخدام firstOrCreate بالبحث عن البريد أو اسم المستخدم لمنع التكرار
        User::firstOrCreate(
            ['email' => 'test@example.com'], // شرط البحث
            [
                'username' => 'testuser',
                'organization_name' => 'Nanosoft',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => 1,
            ]
        );

        User::firstOrCreate(
            ['email' => 'newadmin@nanosoft.technology'], // شرط البحث
            [
                'username' => 'new_admin',
                'organization_name' => 'NanoSoft',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => 1,
            ]
        );

        // 2. تشغيل seeder المحافظ لتوليدها للعملاء
        $this->call([
            WalletSeeder::class,
        ]);
    }
}