<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbr_rates', function (Blueprint $table) {
            $table->json('rates_json')->nullable()->after('cny')->comment('Все курсы ЦБ за дату: код валюты => курс');
        });
    }

    public function down(): void
    {
        Schema::table('cbr_rates', function (Blueprint $table) {
            $table->dropColumn('rates_json');
        });
    }
};
