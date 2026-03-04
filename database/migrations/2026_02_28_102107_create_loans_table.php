<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('company_name');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->unsignedInteger('term_days')->nullable();
            $table->unsignedInteger('term_no_interest')->nullable();
            $table->decimal('psk', 8, 2)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->string('category')->nullable();
            $table->string('website')->nullable();
            $table->decimal('rating', 4, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
