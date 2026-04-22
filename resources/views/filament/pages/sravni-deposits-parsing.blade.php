<x-filament-panels::page>
    <div class="space-y-6">
        @if(filled($this->lastParsedAt))
            <x-filament::section>
                <x-slot name="heading">
                    Последний парсинг
                </x-slot>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Последние данные загружены из БД. Время: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $this->lastParsedAt }}</span>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                Парсер вкладов Sravni (JSON)
            </x-slot>

            <div class="flex flex-wrap gap-2 mb-4">
                <x-filament::button color="primary" wire:click="startUrlsBulk">
                    Парсить URLы (массово)
                </x-filament::button>
                <x-filament::button color="gray" wire:click="recognizeCatalogJsonFromUpload">
                    Распознать JSON
                </x-filament::button>
                <x-filament::button color="info" wire:click="buildDepositUrlsFromUpload">
                    Сформировать URLы
                </x-filament::button>
                @if(filled($this->activeRunId))
                    <x-filament::button color="danger" wire:click="stopActiveRun">
                        Остановить
                    </x-filament::button>
                @endif
            </div>

            @if(filled($this->activeRunId))
                <div wire:poll.2000ms="refreshProgress" class="mb-4">
                    @php
                        $found = (int) ($stats['found'] ?? 0);
                        $processed = (int) ($stats['processed'] ?? 0);
                        $percent = $found > 0 ? min(100, (int) floor(($processed / $found) * 100)) : 0;
                    @endphp
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        Прогресс: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $processed }}</span> / {{ $found }}
                        ({{ $percent }}%)
                        · Успешно: {{ (int) ($stats['success'] ?? 0) }}
                        · Ошибок: {{ (int) ($stats['errors'] ?? 0) }}
                        · Создано: {{ (int) ($stats['created'] ?? 0) }}
                        · Обновлено: {{ (int) ($stats['updated'] ?? 0) }}
                        · Пропущено: {{ (int) ($stats['skipped'] ?? 0) }}
                        · Банков создано: {{ (int) ($stats['banks_created'] ?? 0) }}
                    </div>
                    <div class="w-full h-2 rounded bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <div class="h-2 bg-primary-600" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endif

            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Парсер загружает страницу, извлекает JSON из <code>__NEXT_DATA__</code> (или fallback) и строит нормализованный
                массив для дальнейшей загрузки в БД/калькулятор.
            </p>

            {{ $this->form }}
        </x-filament::section>

        @if($stats)
            <x-filament::section>
                <x-slot name="heading">
                    Статистика
                </x-slot>
                <div class="grid gap-4 md:grid-cols-5">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Всего найдено</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $stats['found'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Обработано</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $stats['processed'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Успешно</div>
                        <div class="mt-1 text-2xl font-semibold text-success-600">{{ $stats['success'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ошибок</div>
                        <div class="mt-1 text-2xl font-semibold text-danger-600">{{ $stats['errors'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">В JSON (после фильтра)</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $stats['filtered'] ?? ($stats['success'] ?? 0) }}</div>
                    </div>
                </div>
            </x-filament::section>
        @endif

        @if(filled($this->logOutput))
            <x-filament::section>
                <x-slot name="heading">
                    Лог выполнения
                </x-slot>
                <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-sm overflow-x-auto whitespace-pre-wrap font-mono">{{ $this->logOutput }}</pre>
            </x-filament::section>
        @endif

        @if(filled($this->jsonResult))
            <x-filament::section>
                <x-slot name="heading">
                    Результат (JSON)
                </x-slot>
                <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-sm overflow-x-auto overflow-y-auto max-h-[600px] whitespace-pre-wrap font-mono">{{ $this->jsonResult }}</pre>
            </x-filament::section>
        @endif

        @if($importResults !== [])
            <x-filament::section>
                <x-slot name="heading">
                    Результат импорта
                </x-slot>
                <div class="space-y-2">
                    @foreach($importResults as $row)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm">
                            {{ $row['message'] ?? '—' }}
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

