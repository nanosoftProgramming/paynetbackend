<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // ربط الإشعار بالمستخدم الذي سيستقبله
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // عنوان أو نوع الإشعار (مثلا: bank_update, new_client, alert)
            $table->string('type')->nullable();
            
            // نص الرسالة أو العنوان
            $table->string('title');
            $table->text('message');
            
            // لتحديد ما إذا كان الإشعار مقروءاً أم لا
            $table->timestamp('read_at')->nullable();
            
            // بيانات إضافية اختيارية (JSON) إذا أردت تمرير ID عنصر معين
            $table->json('data')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};