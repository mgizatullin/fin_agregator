<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('banks', 'logo_square')) {
            Schema::table('banks', function (Blueprint $table): void {
                $table->string('logo_square')->nullable()->after('logo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banks', 'logo_square')) {
            Schema::table('banks', function (Blueprint $table): void {
                $table->dropColumn('logo_square');
            });
        }
    }
};
