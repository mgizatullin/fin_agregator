<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banks') && ! Schema::hasColumn('banks', 'logo')) {
            Schema::table('banks', function (Blueprint $table): void {
                $table->string('logo')->nullable();
            });
        }

        if (Schema::hasTable('loans') && ! Schema::hasColumn('loans', 'logo')) {
            Schema::table('loans', function (Blueprint $table): void {
                $table->string('logo')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: we don't drop columns to avoid data loss.
    }
};

