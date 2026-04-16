<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Импорт описаний
            </x-slot>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Вставьте JSON массив объектов вида
                <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">{"url":"...","description":"..."}</code>.
                Поддерживаются страницы: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">/kredity/{slug}</code>,
                <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">/vklady/{slug}</code>,
                <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">/karty/{slug}</code>,
                <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">/zaimy/{slug}</code>,
                <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">/banki/{slug}</code>.
                Скрипт найдёт запись по URL и перезапишет поле «Описание».
            </p>
            {{ $this->form }}
        </x-filament::section>

        @if($rowsCount !== null)
            <x-filament::section>
                <x-slot name="heading">
                    Результат импорта
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Обработано</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rowsCount }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Обновлено</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $updatedCount }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Не найдено</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $notFoundCount }}</div>
                    </div>
                </div>
            </x-filament::section>
        @endif

        @if(count($issues) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Сообщения
                </x-slot>
                <div class="space-y-2">
                    @foreach($issues as $issue)
                        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/40 p-3 text-sm text-amber-800 dark:text-amber-200">{{ $issue }}</div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

