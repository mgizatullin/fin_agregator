<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('credit_categories')) {
            Schema::create('credit_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('credit_credit_category')) {
            Schema::create('credit_credit_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credit_id')->constrained('credits')->cascadeOnDelete();
                $table->foreignId('credit_category_id')->constrained('credit_categories')->cascadeOnDelete();
                $table->unique(['credit_id', 'credit_category_id']);
            });
        }

        if (! Schema::hasTable('deposit_categories')) {
            Schema::create('deposit_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('deposit_deposit_category')) {
            Schema::create('deposit_deposit_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deposit_id')->constrained('deposits')->cascadeOnDelete();
                $table->foreignId('deposit_category_id')->constrained('deposit_categories')->cascadeOnDelete();
                $table->unique(['deposit_id', 'deposit_category_id']);
            });
        }

        if (! Schema::hasTable('loan_categories')) {
            Schema::create('loan_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('loan_loan_category')) {
            Schema::create('loan_loan_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
                $table->foreignId('loan_category_id')->constrained('loan_categories')->cascadeOnDelete();
                $table->unique(['loan_id', 'loan_category_id']);
            });
        }

        if (! Schema::hasTable('bank_categories')) {
            Schema::create('bank_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_bank_category')) {
            Schema::create('bank_bank_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();
                $table->foreignId('bank_category_id')->constrained('bank_categories')->cascadeOnDelete();
                $table->unique(['bank_id', 'bank_category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_credit_category');
        Schema::dropIfExists('deposit_deposit_category');
        Schema::dropIfExists('loan_loan_category');
        Schema::dropIfExists('bank_bank_category');
        Schema::dropIfExists('credit_categories');
        Schema::dropIfExists('deposit_categories');
        Schema::dropIfExists('loan_categories');
        Schema::dropIfExists('bank_categories');
    }
};
