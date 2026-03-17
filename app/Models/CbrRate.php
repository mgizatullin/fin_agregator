<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CbrRate extends Model
{
    protected $table = 'cbr_rates';

    protected $fillable = ['rate_date', 'usd', 'eur', 'cny', 'rates_json'];

    protected function casts(): array
    {
        return [
            'rate_date' => 'date',
            'usd' => 'decimal:4',
            'eur' => 'decimal:4',
            'cny' => 'decimal:4',
            'rates_json' => 'array',
        ];
    }

    /** Курс по коду из rates_json или из колонки (usd, eur, cny). */
    public function getRate(string $code): ?float
    {
        $code = strtoupper($code);
        $json = $this->rates_json;
        if (is_array($json) && isset($json[$code])) {
            return (float) $json[$code];
        }
        $col = strtolower($code);
        if (in_array($col, ['usd', 'eur', 'cny'], true)) {
            return (float) $this->{$col};
        }
        return null;
    }
}
