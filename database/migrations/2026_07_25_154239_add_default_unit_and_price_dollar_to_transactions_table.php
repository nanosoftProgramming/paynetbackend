<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('defualt_unit')->nullable()->after('type');
            $table->decimal('price_dollar', 10, 2)->nullable()->after('defualt_unit');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['defualt_unit', 'price_dollar']);
        });
    }
};