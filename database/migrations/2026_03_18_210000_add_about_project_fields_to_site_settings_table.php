<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'about_project_description_1')) {
                $table->text('about_project_description_1')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_description_2')) {
                $table->text('about_project_description_2')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'about_project_facts')) {
                $table->json('about_project_facts')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'about_project_team_title')) {
                $table->string('about_project_team_title')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_team_description')) {
                $table->text('about_project_team_description')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_team_items')) {
                $table->json('about_project_team_items')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'about_project_approach_title')) {
                $table->string('about_project_approach_title')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_approach_description')) {
                $table->text('about_project_approach_description')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_approach_items')) {
                $table->json('about_project_approach_items')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'about_project_reviews_title')) {
                $table->string('about_project_reviews_title')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_reviews_description')) {
                $table->text('about_project_reviews_description')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'about_project_reviews_items')) {
                $table->json('about_project_reviews_items')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $columns = [
                'about_project_description_1',
                'about_project_description_2',
                'about_project_facts',
                'about_project_team_title',
                'about_project_team_description',
                'about_project_team_items',
                'about_project_approach_title',
                'about_project_approach_description',
                'about_project_approach_items',
                'about_project_reviews_title',
                'about_project_reviews_description',
                'about_project_reviews_items',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

