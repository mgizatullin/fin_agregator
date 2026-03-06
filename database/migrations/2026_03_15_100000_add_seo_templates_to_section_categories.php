<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'credit_categories',
        'deposit_categories',
        'loan_categories',
        'bank_categories',
        'card_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->text('h1_template')->nullable()->after('description');
                $table->text('seo_title_template')->nullable()->after('h1_template');
                $table->text('seo_description_template')->nullable()->after('seo_title_template');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['h1_template', 'seo_title_template', 'seo_description_template']);
            });
        }
    }
};
