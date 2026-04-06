<?php

namespace App\Livewire;

use App\Services\BankiApiService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreditLeadModal extends Component
{
    // ── Поля формы подбора кредита ────────────────────────────────────────────

    public string $purpose = '';

    #[Validate('required|numeric|min:1|max:100000000')]
    public string|int $amount = 500000;

    #[Validate('required|integer|min:1|max:30')]
    public string|int $termYears = 5;

    // ── Поля модального окна ──────────────────────────────────────────────────

    #[Validate('required|string|min:2|max:100')]
    public string $firstName = '';

    #[Validate(['required', 'string', 'regex:/^[\d\s\+\(\)\-]{10,18}$/'])]
    public string $phone = '';

    // ── Состояние ─────────────────────────────────────────────────────────────

    public bool $showModal = false;

    public bool $submitted = false;

    public string $errorMessage = '';

    public bool $loading = false;

    // ── Обработчики ───────────────────────────────────────────────────────────

    /**
     * Нажата кнопка «Подобрать» — валидируем базовые поля и открываем модалку.
     */
    public function openModal(): void
    {
        $this->validateOnly('amount');
        $this->validateOnly('termYears');

        $this->errorMessage = '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->errorMessage = '';
    }

    /**
     * Нажата кнопка «Отправить» в модалке.
     */
    public function submit(BankiApiService $bankiApi): void
    {
        $this->validateOnly('firstName');
        $this->validateOnly('phone');
        $this->validateOnly('amount');

        $this->loading = true;

        try {
            $termMonths = (int) $this->termYears * 12;

            $result = $bankiApi->sendCreditLead([
                'purpose' => $this->purpose,
                'amount' => $this->amount,
                'term' => $termMonths,
                'firstName' => $this->firstName,
                'phone' => $this->phone,
            ]);

            if ($result['success']) {
                $this->showModal = false;
                $this->submitted = true;
                $this->reset(['firstName', 'phone', 'errorMessage']);
            } else {
                $this->handleApiError($result);
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Ошибка при отправке. Попробуйте позже.';
        } finally {
            $this->loading = false;
        }
    }

    // ── Вспомогательные ───────────────────────────────────────────────────────

    private function handleApiError(array $result): void
    {
        // Структура ответа Banki.ru: { "result": { "status": "dublicate"|"error"|"bad lead" } }
        $resultStatus = $result['resultStatus'] ?? '';
        $description = $result['data']['result']['description'] ?? '';

        $this->errorMessage = match (true) {
            $resultStatus === 'dublicate' => 'Вы уже отправляли заявку с этим номером телефона.',
            $resultStatus === 'bad lead' => 'К сожалению, заявка не прошла проверку. Попробуйте позже.',
            ! empty($description) => 'Ошибка: '.$description,
            default => 'Ошибка при отправке. Попробуйте позже.',
        };
    }

    public function render(): \Illuminate\View\View
    {
        $refinanceSlug = (string) (config('home_quick_picks.credit_category_slugs.refinance') ?? '');
        $businessSlug = (string) (config('home_quick_picks.credit_category_slugs.business') ?? '');

        return view('livewire.credit-lead-modal', compact('refinanceSlug', 'businessSlug'));
    }
}
