<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                JSON импорт вкладов
            </x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Вставьте JSON-массив с вкладами, нажмите «Распознать» и проверьте отчёт. После этого можно выполнить импорт в раздел вкладов.
            </p>

            {{ $this->form }}

            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button wire:click="recognizeJson" color="primary">
                    Распознать
                </x-filament::button>

                <x-filament::button wire:click="importToDatabase" color="success" :disabled="empty($this->recognizedItems)">
                    Импорт
                </x-filament::button>
            </div>
        </x-filament::section>

        @if($recognizeReport)
            <x-filament::section>
                <x-slot name="heading">
                    Короткий отчёт
                </x-slot>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Всего элементов</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $recognizeReport['total'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Готово к импорту</div>
                        <div class="mt-1 text-2xl font-semibold text-success-600">{{ $recognizeReport['ready'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ошибок</div>
                        <div class="mt-1 text-2xl font-semibold text-danger-600">{{ $recognizeReport['errors'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Предупреждений</div>
                        <div class="mt-1 text-2xl font-semibold text-warning-600">{{ $recognizeReport['warnings'] ?? 0 }}</div>
                    </div>
                </div>
            </x-filament::section>
        @endif

        @if($recognizedItems !== [])
            <x-filament::section>
                <x-slot name="heading">
                    Предпросмотр распознавания
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600 dark:text-gray-300">
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">#</th>
                            <th class="py-2 pr-4">Элемент</th>
                            <th class="py-2 pr-4">Статус</th>
                            <th class="py-2 pr-4">Категории</th>
                            <th class="py-2 pr-4">Условия</th>
                            <th class="py-2 pr-4">Проблемы</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recognizedItems as $item)
                            <tr>
                                <td class="py-3 pr-4 align-top">{{ ($item['index'] ?? 0) + 1 }}</td>
                                <td class="py-3 pr-4 align-top">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $item['source_name'] ?? '—' }}</div>
                                </td>
                                <td class="py-3 pr-4 align-top">
                                    @if($item['ready'] ?? false)
                                        <span class="text-success-600">Готово</span>
                                    @else
                                        <span class="text-danger-600">Ошибка</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 align-top">
                                    <div class="space-y-1">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Найдено: {{ count($item['category_ids'] ?? []) }}
                                        </div>

                                        @if(!empty($item['matched_categories']))
                                            <div class="text-xs text-success-700 dark:text-success-400">
                                                {{ implode(', ', $item['matched_categories']) }}
                                            </div>
                                        @endif

                                        @if(!empty($item['unknown_categories']))
                                            <div class="text-xs text-warning-700 dark:text-warning-400">
                                                Не найдены: {{ implode(', ', $item['unknown_categories']) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 pr-4 align-top">
                                    <div class="text-xs text-gray-700 dark:text-gray-300">
                                        Валют: {{ count($item['currencies_structure'] ?? []) }}
                                    </div>
                                </td>
                                <td class="py-3 pr-4 align-top">
                                    @php
                                        $messages = array_merge($item['errors'] ?? [], $item['warnings'] ?? []);
                                    @endphp
                                    @if($messages === [])
                                        <span class="text-gray-500 dark:text-gray-400">Нет</span>
                                    @else
                                        <div class="space-y-1">
                                            @foreach($messages as $message)
                                                <div class="text-xs text-gray-700 dark:text-gray-300">{{ $message }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if($importResults !== [])
            <x-filament::section>
                <x-slot name="heading">
                    Результат импорта
                </x-slot>

                <div class="space-y-3">
                    @foreach($importResults as $result)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm font-medium {{ ($result['status'] ?? '') === 'created' ? 'text-success-600' : (($result['status'] ?? '') === 'updated' ? 'text-primary-600' : 'text-gray-500') }}">
                                    {{ $result['message'] ?? '' }}
                                </span>

                                @if(filled($result['edit_url'] ?? null))
                                    <a href="{{ $result['edit_url'] }}" class="text-sm text-primary-600 hover:underline">
                                        Открыть в админке
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

