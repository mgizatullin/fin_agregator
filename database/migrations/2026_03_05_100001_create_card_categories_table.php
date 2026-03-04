<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_categories')) {
            Schema::create('card_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('card_card_category')) {
            Schema::create('card_card_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('card_id')->constrained()->cascadeOnDelete();
                $table->foreignId('card_category_id')->constrained()->cascadeOnDelete();
                $table->unique(['card_id', 'card_category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('card_card_category');
        Schema::dropIfExists('card_categories');
    }
};
