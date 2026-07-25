<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('total_price_dollar', 10, 2)->nullable();
            $table->decimal('defualt_unit_total_price', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['total_price_dollar', 'defualt_unit_total_price']);
        });
    }
};