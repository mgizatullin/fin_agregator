<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbr_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date')->unique()->comment('Дата курса ЦБ');
            $table->decimal('usd', 10, 4);
            $table->decimal('eur', 10, 4);
            $table->decimal('cny', 10, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbr_rates');
    }
};
