<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deposit_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service')->default('deposit');
            $table->string('title');
            $table->text('body');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_reviews');
    }
};

