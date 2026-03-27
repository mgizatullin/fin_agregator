<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Импорт городов
            </x-slot>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Загрузите CSV-файл с городами. Исполняются последовательные операции: "Распознать", "Проверить", "Импортировать".
                Для корректной проверки город и регион должны строго совпадать с записями в базе. Повторы пропускаются.
                Склонения и население получаются автоматически через внешние API.
            </p>
            {{ $this->form }}
        </x-filament::section>

        @if($rowsCount !== null)
            <x-filament::section>
                <x-slot name="heading">
                    Статус файла
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Всего строк</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rowsCount }}</div>
                    </div>
                    @if($validRowsCount !== null)
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Допустимых строк</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $validRowsCount }}</div>
                        </div>
                    @endif
                    @if($importedRowsCount !== null)
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Импортировано</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $importedRowsCount }}</div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        @if(count($validationIssues) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Ошибки при проверке
                </x-slot>
                <div class="space-y-2">
                    @foreach($validationIssues as $issue)
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/40 p-3 text-sm text-red-700 dark:text-red-200">{{ $issue }}</div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        @if(count($previewRows) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Превью данных (до 8 строк)
                </x-slot>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-2 py-2 text-left font-semibold">#</th>
                                @foreach(array_keys($previewRows[0]) as $column)
                                    <th class="px-2 py-2 text-left font-semibold">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($previewRows as $index => $row)
                                <tr>
                                    <td class="px-2 py-2">{{ $index + 1 }}</td>
                                    @foreach($row as $value)
                                        <td class="px-2 py-2">{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>