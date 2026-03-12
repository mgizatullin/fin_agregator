<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_receive_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('credit_credit_receive_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_receive_method_id')->constrained('credit_receive_methods')->cascadeOnDelete();
            $table->unique(['credit_id', 'credit_receive_method_id'], 'credit_receive_method_unique');
        });

        $methodIdsByName = [];
        $now = now();

        foreach (DB::table('credits')->select(['id', 'receive_method'])->get() as $credit) {
            $receiveMethod = trim((string) ($credit->receive_method ?? ''));
            if ($receiveMethod === '') {
                continue;
            }

            $parts = preg_split('/\s*,\s*/u', $receiveMethod, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($parts as $part) {
                $name = trim(preg_replace('/\s+/u', ' ', $part) ?? $part);
                if ($name === '') {
                    continue;
                }

                $name = mb_strtolower($name);
                $name = mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);

                if (! isset($methodIdsByName[$name])) {
                    $slugBase = Str::slug($name);
                    $slug = $slugBase !== '' ? $slugBase : 'credit-receive-method';
                    $counter = 1;

                    while (DB::table('credit_receive_methods')->where('slug', $slug)->exists()) {
                        $slug = $slugBase . '-' . $counter;
                        $counter++;
                    }

                    $methodIdsByName[$name] = DB::table('credit_receive_methods')->insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('credit_credit_receive_method')->updateOrInsert([
                    'credit_id' => $credit->id,
                    'credit_receive_method_id' => $methodIdsByName[$name],
                ], []);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_credit_receive_method');
        Schema::dropIfExists('credit_receive_methods');
    }
};
