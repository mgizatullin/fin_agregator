<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Действия
            </x-slot>
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button wire:click="checkConnection" color="gray" size="sm">
                    Проверить соединение
                </x-filament::button>
                <x-filament::button wire:click="loadDictionaries" color="gray" size="sm">
                    Загрузить справочники
                </x-filament::button>
            </div>
            @if($connectionStatus !== null)
                <div class="mt-3 p-3 rounded-lg text-sm {{ $connectionStatus['success'] ?? false ? 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400' : 'bg-danger-50 dark:bg-danger-900/20 text-danger-700 dark:text-danger-400' }}">
                    {{ $connectionStatus['message'] ?? '' }}
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Фильтры
            </x-slot>
            {{ $this->form }}
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Офферы
            </x-slot>

            @if($loading)
                <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <x-filament::loading-indicator class="h-5 w-5" />
                    Загрузка…
                </div>
            @elseif(empty($this->offers))
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Нажмите <span class="font-medium">«Загрузить офферы»</span>, чтобы загрузить список.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600 dark:text-gray-300">
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">ID</th>
                            <th class="py-2 pr-4">Название</th>
                            <th class="py-2 pr-4">Страна</th>
                            <th class="py-2 pr-4">Логотип</th>
                            <th class="py-2 pr-4">Продукт</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->offers as $offer)
                            @php
                                $offerId = $offer['id'] ?? null;
                                $countryLabel = '';
                                $countries = $offer['countries'] ?? [];
                                if (is_array($countries)) {
                                    $codes = [];
                                    foreach ($countries as $c) {
                                        if (is_string($c)) { $codes[] = $c; }
                                        if (is_array($c) && isset($c['code']) && is_string($c['code'])) { $codes[] = $c['code']; }
                                    }
                                    $countryLabel = implode(', ', array_values(array_unique($codes)));
                                }

                                $products = $offer['products'] ?? [];
                                $primaryProduct = '—';
                                $logoUrl = null;

                                if (is_array($products) && ! empty($products)) {
                                    $first = $products[0];

                                    if (is_array($first)) {
                                        $primaryProduct = $first['name'] ?? $first['title'] ?? $first['id'] ?? '—';
                                        $logoUrl = $first['logo'] ?? $first['icon'] ?? $first['image'] ?? $first['logo_url'] ?? null;
                                    } elseif (is_string($first) && $first !== '') {
                                        $primaryProduct = $first;
                                    }
                                }

                                if (! $logoUrl) {
                                    $logoUrl = $offer['logo'] ?? $offer['icon'] ?? $offer['image'] ?? $offer['logo_url'] ?? null;
                                }
                            @endphp
                            <tr>
                                <td class="py-2 pr-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ $offerId }}
                                </td>
                                <td class="py-2 pr-4 min-w-[200px] text-gray-900 dark:text-gray-100">
                                    {{ $offer['name'] ?? $offer['title'] ?? '—' }}
                                </td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    {{ $countryLabel !== '' ? $countryLabel : '—' }}
                                </td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    @if(is_string($logoUrl) && $logoUrl !== '')
                                        <img
                                            src="{{ $logoUrl }}"
                                            alt=""
                                            loading="lazy"
                                            class="h-8 w-8 rounded bg-gray-100 dark:bg-gray-800 object-contain"
                                        />
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    {{ $primaryProduct }}
                                </td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    @if($offerId !== null)
                                        <x-filament::button size="sm" color="gray" wire:click="showOffer({{ json_encode($offerId) }})">
                                            Подробнее
                                        </x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::modal id="offer-details" width="5xl">
            <x-slot name="heading">
                Оффер #{{ $selectedOffer['id'] ?? '' }}
            </x-slot>

            @if($selectedOffer)
                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Название</div>
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedOffer['name'] ?? $selectedOffer['title'] ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500 dark:text-gray-400">default_url</div>
                        <div class="font-medium text-gray-900 dark:text-gray-100 break-words">
                            {{ $selectedOffer['default_url'] ?? $selectedOffer['url'] ?? '—' }}
                        </div>
                    </div>

                    @if(filled($selectedOffer['description'] ?? null))
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Описание</div>
                            <div class="prose dark:prose-invert max-w-none">{!! nl2br(e((string) $selectedOffer['description'])) !!}</div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Countries</div>
                            <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-3 text-xs overflow-x-auto whitespace-pre-wrap font-mono">{{ json_encode($selectedOffer['countries'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Products</div>
                            <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-3 text-xs overflow-x-auto whitespace-pre-wrap font-mono">{{ json_encode($selectedOffer['products'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Metrics</div>
                        <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-3 text-xs overflow-x-auto whitespace-pre-wrap font-mono">{{ json_encode($selectedOffer['metrics'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                    </div>

                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Goals</div>
                        <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-3 text-xs overflow-x-auto whitespace-pre-wrap font-mono">{{ json_encode($selectedOffer['goals'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @else
                <div class="text-sm text-gray-600 dark:text-gray-400">Нет данных.</div>
            @endif
        </x-filament::modal>

    </div>
</x-filament-panels::page>

