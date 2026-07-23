<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // الربط مع جدول المستخدمين وجدول المحافظ
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            
            // المبلغ (decimal ممتاز للمال بدقة عالية)
            $table->decimal('price', 10, 2);
            
            // حالة المعاملة (مع تحديد القيمة الافتراضية pending)
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            
            // رقم الهاتف المرتبط بالمعاملة
            $table->string('phone');
            
            // نوع المعاملة (مثل: deposit, withdraw, transfer... إلخ)
            $table->string('type'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};