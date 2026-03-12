@php
    use App\Services\DepositConditionsMapper\DepositRatesMatrix;
    $currencies = $record?->currencies ?? collect();
@endphp
@if($record && $currencies->isNotEmpty())
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex items-center gap-x-3 p-4">
            <h3 class="fi-section-header-heading text-base font-semibold">Превью: таблица ставок (как на сайте)</h3>
        </div>
        <div class="fi-section-content p-4 pt-0">
            @foreach($currencies as $currency)
                @php
                    $matrix = DepositRatesMatrix::forCurrency($currency);
                    $currencyLabels = ['RUB' => 'RUB (₽)', 'USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'CNY' => 'CNY (¥)'];
                    $currencyLabel = $currencyLabels[$currency->currency_code] ?? $currency->currency_code;
                @endphp
                @if(!empty($matrix['columns']))
                    <div class="mb-6 last:mb-0">
                        <p class="font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $currencyLabel }}</p>
                        <div class="deposit-rates deposit-rates--desc deposit-rates--autoheight overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-gray-600">Сумма вклада</th>
                                        @foreach($matrix['columns'] as $termDays)
                                            <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-gray-600">{{ DepositRatesMatrix::formatTermLabel($termDays) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matrix['rows'] as $rowIndex => $row)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-3 py-2 font-medium">{{ $row['amount_label'] }}</td>
                                            @foreach($matrix['columns'] as $colIndex => $termDays)
                                                @php $rate = $matrix['grid'][$rowIndex][$colIndex] ?? null; @endphp
                                                <td class="px-3 py-2">
                                                    @if($rate !== null)
                                                        <div>{{ number_format($rate, 1, '.', '') }} %</div>
                                                    @else
                                                        <div>—</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
