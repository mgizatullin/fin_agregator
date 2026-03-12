<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rows = DB::table('deposit_reviews')->get();

        foreach ($rows as $row) {
            DB::table('reviews')->insert([
                'reviewable_type' => \App\Models\Deposit::class,
                'reviewable_id' => $row->deposit_id,
                'bank_id' => $row->bank_id,
                'service' => $row->service === 'deposit' ? 'Вклад' : $row->service,
                'title' => $row->title ?? '',
                'body' => $row->body,
                'rating' => $row->rating,
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('reviews')
            ->where('reviewable_type', \App\Models\Deposit::class)
            ->delete();
    }
};
