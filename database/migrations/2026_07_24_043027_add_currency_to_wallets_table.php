<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // إضافة العمود وجعله في أول الجدول تماماً
            $table->unsignedBigInteger('currency_id')->nullable()->first();
            
            // أو إذا كنت تريد ربطه كـ Foreign Key مباشرة في البداية:
            // $table->foreignId('currency_id')->nullable()->first()->constrained('currencies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('currency_id');
        });
    }
};