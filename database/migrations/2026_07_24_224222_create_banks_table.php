<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id(); // عمود الـ id (Primary Key تلقائي)
            $table->string('number'); // عمود رقم الحساب أو رقم البنك
            $table->string('type'); // عمود النوع (نص)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};