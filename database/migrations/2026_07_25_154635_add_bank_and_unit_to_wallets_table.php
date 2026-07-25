<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_id')->nullable()->after('user_id');
            $table->string('defualt_unit')->nullable()->after('bank_id');
            $table->decimal('price_dollar', 10, 2)->nullable()->after('defualt_unit');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['bank_id', 'defualt_unit', 'price_dollar']);
        });
    }
};