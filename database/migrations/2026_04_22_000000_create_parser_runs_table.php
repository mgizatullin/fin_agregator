<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parser_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('parser_key')->index();
            $table->string('mode')->nullable();
            $table->json('params')->nullable();
            $table->json('stats')->nullable();
            $table->longText('result_json')->nullable();
            $table->longText('log_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parser_runs');
    }
};

