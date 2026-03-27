<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Парсер кредитных карт brobank.ru
            </x-slot>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Парсер читает каталог кредитных карт Brobank и выводит результат только в JSON. Если сервер не может открыть
                <code>brobank.ru</code>, можно вставить HTML каталога вручную и распарсить его без сетевого доступа.
            </p>
            {{ $this->form }}
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Прогресс
            </x-slot>
            <div class="w-full h-6 bg-gray-200 dark:bg-gray-700 rounded overflow-hidden flex items-center">
                <div class="h-full bg-indigo-500 rounded transition-all duration-200"
                     style="width: {{ $parserProgressPercent ?? 0 }}%;"></div>
                <span class="ml-2 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">
                    Прогресс: {{ $parserProgressPercent ?? 0 }}%
                </span>
            </div>
        </x-filament::section>

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
                    Результаты (JSON)
                </x-slot>
                <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-sm overflow-x-auto overflow-y-auto max-h-[600px] whitespace-pre-wrap font-mono">{{ $this->jsonResult }}</pre>

                <div class="mt-4">
                    <x-filament::button wire:click="importToDatabase" color="success">
                        Импорт в БД
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
