<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('defualt_unit_amount', 50)->nullable(); // أو decimal إذا كان رقماً
            $table->decimal('amount_dollar', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['defualt_unit_amount', 'amount_dollar']);
        });
    }
};