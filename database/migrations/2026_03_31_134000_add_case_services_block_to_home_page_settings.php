<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table): void {
            $table->string('case_services_title')->nullable()->after('values_grid');
            $table->text('case_services_description')->nullable()->after('case_services_title');
            $table->json('case_services_items')->nullable()->after('case_services_description');
        });
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'case_services_title',
                'case_services_description',
                'case_services_items',
            ]);
        });
    }
};
