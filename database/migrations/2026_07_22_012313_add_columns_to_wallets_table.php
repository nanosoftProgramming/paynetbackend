<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('total_price', 10, 2)->default(0); // إجمالي السعر
            $table->decimal('amount', 10, 2)->default(0);      // المبلغ
            $table->decimal('price', 10, 2)->default(0);       // السعر
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending'); // الحالة
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['total_price', 'amount', 'price', 'status']);
        });
    }
};