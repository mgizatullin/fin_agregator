<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Парсер банков Myfin
            </x-slot>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Парсер читает каталог банков Myfin и выводит результат только в JSON. Если сервер не может открыть `ru.myfin.by`,
                можно вставить HTML каталога вручную и распарсить его без сетевого доступа. После проверки JSON результат можно импортировать в базу.
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
                    <button
                        id="myfin-import-button"
                        type="button"
                        class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 rounded-lg bg-success-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-75 hover:bg-success-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-success-600 disabled:pointer-events-none disabled:opacity-70"
                        wire:click="importToDatabase"
                    >
                        Импорт в БД
                    </button>
                </div>
            </x-filament::section>
        @endif
    </div>

    @if(filled($this->jsonResult))
        <script>
            window.myfinImportBanks = async function (button) {
                if (!button || button.dataset.importing === '1') {
                    return;
                }

                const componentId = button.closest('[wire\\:id]')?.getAttribute('wire:id');
                if (!componentId || !window.Livewire?.find) {
                    return;
                }

                const component = window.Livewire.find(componentId);
                if (!component) {
                    return;
                }

                const banks = @js($this->parsedBanks);
                if (!Array.isArray(banks) || banks.length === 0) {
                    await component.call('importToDatabase');
                    return;
                }

                button.dataset.importing = '1';
                button.disabled = true;

                try {
                    const payload = [];

                    for (const bank of banks) {
                        const item = { ...bank, logo_data: null };

                        if (bank.logo && /^https?:\/\//i.test(bank.logo)) {
                            try {
                                const response = await fetch(bank.logo, { mode: 'cors', credentials: 'omit' });
                                if (response.ok) {
                                    const blob = await response.blob();
                                    item.logo_data = await new Promise((resolve, reject) => {
                                        const reader = new FileReader();
                                        reader.onload = () => resolve(reader.result);
                                        reader.onerror = reject;
                                        reader.readAsDataURL(blob);
                                    });
                                }
                            } catch (error) {
                                console.warn('Myfin logo fetch failed', bank.name, error);
                            }
                        }

                        payload.push(item);
                    }

                    await component.call('importBanksWithClientLogos', payload);
                } catch (error) {
                    console.error('Myfin import failed', error);
                    await component.call('importToDatabase');
                } finally {
                    button.dataset.importing = '0';
                    button.disabled = false;
                }
            };

            document.addEventListener('click', function (event) {
                const button = event.target.closest('#myfin-import-button');
                if (!button) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                window.myfinImportBanks(button);
            }, true);
        </script>
    @endif
</x-filament-panels::page>
