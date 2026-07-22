<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id(); // id الخاص بالمحفظة
            
            // ربط المحفظة بجدول المستخدمين، مع منع تكرار المحفظة لنفس المستخدم (Unique)
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            
            $table->string('phone_number'); // رقم الهاتف الخاص بالمحفظة
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};