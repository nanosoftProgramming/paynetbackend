<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    // اسم الأمر الذي ستقوم بكتابته في التيرمنال
    protected $signature = 'make:admin';

    // وصف الأمر
    protected $description = 'Create a new admin user account safely';

    public function handle()
    {
        $username = $this->ask('Enter admin username', 'admin');
        $organization = $this->ask('Enter organization name', 'Nanosoft');
        $email = $this->ask('Enter admin email', 'admin@nanosoft.technology');
        $password = $this->secret('Enter admin password');

        // التحقق مما إذا كان البريد موجوداً مسبقاً لمنع التكرار
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $this->error('A user with this email already exists!');
            return;
        }

        // إنشاء حساب الأدمن
        User::create([
            'username' => $username,
            'organization_name' => $organization,
            'email' => $email,
            'password' => Hash::make($password ?: 'password123'),
        ]);

        $this->info('Admin account created successfully!');
    }
}