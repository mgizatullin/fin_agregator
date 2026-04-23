<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('name');
            $table->text('body');
            $table->boolean('is_published')->default(false)->index();
            $table->string('ip')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['article_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_comments');
    }
};

