<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'specialist_id')) {
                $table->foreignId('specialist_id')
                    ->nullable()
                    ->after('author')
                    ->constrained('specialists')
                    ->nullOnDelete();
            }
        });

        // Best-effort: if author matches specialist name, link it.
        if (Schema::hasColumn('articles', 'specialist_id') && Schema::hasColumn('articles', 'author')) {
            $specialists = DB::table('specialists')->select(['id', 'name'])->get();
            foreach ($specialists as $s) {
                if (! is_string($s->name) || trim($s->name) === '') {
                    continue;
                }
                DB::table('articles')
                    ->whereNull('specialist_id')
                    ->whereNotNull('author')
                    ->where('author', trim($s->name))
                    ->update(['specialist_id' => $s->id]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        if (! Schema::hasColumn('articles', 'specialist_id')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('specialist_id');
        });
    }
};
