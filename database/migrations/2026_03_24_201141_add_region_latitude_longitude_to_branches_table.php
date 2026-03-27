<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('region')->nullable()->after('city_id');
            $table->decimal('latitude', 10, 7)->nullable()->after('working_hours');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            $table->index('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropColumn(['region', 'latitude', 'longitude']);
        });
    }
};
