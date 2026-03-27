<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $parseLegacyItems = static function (?string $text): array {
            if (! is_string($text) || trim($text) === '') {
                return [];
            }

            $items = [];

            foreach (preg_split('/\r\n|\r|\n/u', $text) ?: [] as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $parts = preg_split('/\s*[:\-]\s*/u', $line, 2);
                if (! is_array($parts) || count($parts) < 2) {
                    continue;
                }

                $parameter = trim((string) $parts[0]);
                $value = trim((string) $parts[1]);

                if ($parameter === '' || $value === '') {
                    continue;
                }

                $items[] = [
                    'parameter' => $parameter,
                    'value' => $value,
                ];
            }

            return $items;
        };

        Schema::table('cards', function (Blueprint $table) {
            if (! Schema::hasColumn('cards', 'atm_withdrawal_text')) {
                $table->string('atm_withdrawal_text')->nullable()->after('atm_withdrawal');
            }

            if (! Schema::hasColumn('cards', 'conditions_items')) {
                $table->json('conditions_items')->nullable()->after('conditions_text');
            }

            if (! Schema::hasColumn('cards', 'rates_items')) {
                $table->json('rates_items')->nullable()->after('rates_text');
            }

            if (! Schema::hasColumn('cards', 'cashback_details_items')) {
                $table->json('cashback_details_items')->nullable()->after('cashback_details_text');
            }
        });

        DB::table('cards')
            ->select(['id', 'conditions_text', 'rates_text', 'cashback_details_text'])
            ->orderBy('id')
            ->chunkById(100, function ($cards) use ($parseLegacyItems): void {
                foreach ($cards as $card) {
                    $conditionsItems = $parseLegacyItems($card->conditions_text);
                    $ratesItems = $parseLegacyItems($card->rates_text);
                    $cashbackDetailsItems = $parseLegacyItems($card->cashback_details_text);

                    DB::table('cards')
                        ->where('id', $card->id)
                        ->update([
                            'conditions_items' => $conditionsItems === [] ? null : json_encode($conditionsItems, JSON_UNESCAPED_UNICODE),
                            'rates_items' => $ratesItems === [] ? null : json_encode($ratesItems, JSON_UNESCAPED_UNICODE),
                            'cashback_details_items' => $cashbackDetailsItems === [] ? null : json_encode($cashbackDetailsItems, JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('cards', 'atm_withdrawal_text') ? 'atm_withdrawal_text' : null,
                Schema::hasColumn('cards', 'conditions_items') ? 'conditions_items' : null,
                Schema::hasColumn('cards', 'rates_items') ? 'rates_items' : null,
                Schema::hasColumn('cards', 'cashback_details_items') ? 'cashback_details_items' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
