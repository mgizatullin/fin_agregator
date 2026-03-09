<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'credit_categories',
        'loan_categories',
        'deposit_categories',
        'bank_categories',
        'card_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'sort_order')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedInteger('sort_order')->default(0)->after('seo_description_template');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'sort_order')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('sort_order');
            });
        }
    }
};
