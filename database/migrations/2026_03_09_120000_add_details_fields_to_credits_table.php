<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->string('decision')->nullable()->after('age_max');
            $table->string('receive_method')->nullable()->after('decision');
            $table->string('payment_type')->nullable()->after('receive_method');
            $table->text('penalty')->nullable()->after('payment_type');
            $table->boolean('no_collateral')->default(false)->after('penalty');
            $table->boolean('no_guarantors')->default(false)->after('no_collateral');
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn([
                'decision',
                'receive_method',
                'payment_type',
                'penalty',
                'no_collateral',
                'no_guarantors',
            ]);
        });
    }
};
