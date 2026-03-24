<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Парсер вкладов Sravni (JSON)
            </x-slot>

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
                <div class="grid gap-4 md:grid-cols-4">
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

