<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankiApiService
{
    private const BASE_URL = 'https://www.banki.ru/openapi/cpa-leads-api/v1';

    private const TOKEN_CACHE_KEY = 'banki_api_token';

    private const TOKEN_CACHE_TTL = 86400; // 24 часа — по документации Banki.ru

    /** purposeCode: 1 = просто деньги, 7 = ипотека */
    private const PURPOSE_MAP = [
        'mortgage' => 7,
        'ипотека' => 7,
    ];

    private string $login;

    private string $password;

    public function __construct()
    {
        $this->login = (string) config('services.banki.login', '');
        $this->password = (string) config('services.banki.password', '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Публичный API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Получить Bearer-токен (кеш 24 ч).
     * Структура запроса: { "data": { "login": "...", "password": "..." } }
     * Структура ответа:  { "data": { "token": "...", "type": "Bearer" }, "result": { "status": "success" } }
     */
    public function getToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function (): string {
            $response = Http::timeout(15)->post(self::BASE_URL.'/tokens', [
                'data' => [
                    'login' => $this->login,
                    'password' => $this->password,
                ],
            ]);

            Log::info('Banki.ru /tokens response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Banki.ru: не удалось получить токен. HTTP '.$response->status()
                );
            }

            // Токен лежит в data.token
            $token = $response->json('data.token') ?? '';

            if (empty($token)) {
                $desc = $response->json('result.description') ?? 'нет токена в ответе';
                throw new \RuntimeException('Banki.ru: '.$desc);
            }

            return $token;
        });
    }

    /**
     * Отправить лид по кредиту.
     *
     * Правильная структура payload (по документации Banki.ru):
     * {
     *   "data": {
     *     "partnerCode": "<login>",
     *     "leadInfo": {
     *       "requestedAmount": 100000,
     *       "requestedTermValue": 12,
     *       "requestedTermUnitCode": 6,
     *       "purposeCode": 1,
     *       "clientData": {
     *         "personProfile": { "firstName": "Иван" },
     *         "phone": { "countryPrefixCode": 7, "number": "9099999999" },
     *         "addresses": [{ "typeCode": 4, "addressString": "...", "addressKladrCode": "..." }]
     *       }
     *     }
     *   }
     * }
     *
     * @param  array{purpose: string, amount: int|float, term: int, firstName: string, phone: string}  $data
     *                                                                                                        term — в месяцах
     * @return array{success: bool, status: int, resultStatus: string, data: array<mixed>}
     */
    public function sendCreditLead(array $data): array
    {
        $token = $this->getToken();
        $phoneNumber = $this->normalizePhone($data['phone']);

        $payload = [
            'data' => [
                'partnerCode' => $this->login, // partnerCode = логин партнёра (по документации)
                'leadInfo' => [
                    'requestedAmount' => (int) $data['amount'],
                    'requestedTermValue' => (int) $data['term'],
                    'requestedTermUnitCode' => 6, // 6 = месяцы
                    'purposeCode' => $this->mapPurposeCode($data['purpose'] ?? ''),
                    'clientData' => [
                        'personProfile' => [
                            'firstName' => trim($data['firstName']),
                        ],
                        'phone' => [
                            'countryPrefixCode' => 7, // integer, не строка
                            'number' => $phoneNumber,
                        ],
                        'addresses' => [
                            [
                                'typeCode' => 4, // 4 = населённый пункт выдачи кредита
                                'addressString' => 'Россия',
                                'addressKladrCode' => '7700000000000',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::timeout(20)
            ->withToken($token)
            ->post(self::BASE_URL.'/leads', $payload);

        // Маскируем телефон в логах
        $logPayload = $payload;
        $logPayload['data']['leadInfo']['clientData']['phone']['number'] = '***';

        Log::info('Banki.ru /leads response', [
            'status' => $response->status(),
            'result_status' => $response->json('result.status'),
            'body' => $response->json(),
            'payload' => $logPayload,
        ]);

        $resultStatus = $response->json('result.status') ?? '';

        return [
            'success' => $response->successful() && $resultStatus === 'success',
            'status' => $response->status(),
            'resultStatus' => $resultStatus,    // 'success' | 'error' | 'dublicate' | 'bad lead'
            'data' => $response->json() ?? [],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Вспомогательные
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Нормализовать телефон → 10 цифр без кода страны.
     * Принимает: +7 (909) 999-99-99, 89099999999, 9099999999
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    private function mapPurposeCode(string $purpose): int
    {
        return self::PURPOSE_MAP[mb_strtolower(trim($purpose))] ?? 1;
    }
}
