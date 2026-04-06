<?php

namespace App\Http\Controllers;

use App\Models\ContactPageSetting;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $settings = ContactPageSetting::getInstance();

        return view('contact-us', [
            'contactSettings' => $settings,
            'title' => 'Контакты',
            'seo_title' => $settings->title ?: 'Контакты',
            'seo_description' => 'Свяжитесь с нами через форму обратной связи.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\+7\s\(\d{3}\)\s\d{3}\-\d{2}\-\d{2}$/'],
            'message' => ['nullable', 'string', 'max:5000'],
            'personal_data_consent' => ['accepted'],
        ]);

        ContactRequest::query()->create($data);

        $siteSettings = \App\Models\SiteSettings::getInstance();
        $recipient = $siteSettings->applications_email ?: $siteSettings->email;

        if (! filled($recipient)) {
            Log::warning('Contact form recipient email is empty.');

            return back()
                ->withInput()
                ->withErrors([
                    'form' => 'Не задан email для заявок в настройках сайта.',
                ]);
        }

        try {
            $this->sendContactEmail($data, $recipient, $siteSettings);
        } catch (\Throwable $e) {
            Log::error('Contact form email send failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => 'Заявка сохранена, но письмо не отправилось. Проверьте настройки почты.',
                ]);
        }

        return back()->with('contact_form_success', 'Спасибо! Ваша заявка отправлена.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendContactEmail(array $data, string $recipient, \App\Models\SiteSettings $siteSettings): void
    {
        $send = function ($mailer = null) use ($data, $recipient): void {
            $mail = $mailer ? Mail::mailer($mailer) : Mail::getFacadeRoot();

            $mail->send('emails.contact-request', ['data' => $data], function ($message) use ($recipient, $data): void {
                $message
                    ->to($recipient)
                    ->subject('Новая заявка с формы контактов');

                if (! empty($data['email'])) {
                    $message->replyTo((string) $data['email'], (string) ($data['full_name'] ?? ''));
                }
            });
        };

        if (($siteSettings->mail_transport_mode ?? 'default') !== 'smtp') {
            $send();
            return;
        }

        $required = [
            'smtp_host' => (string) ($siteSettings->smtp_host ?? ''),
            'smtp_port' => (string) ($siteSettings->smtp_port ?? ''),
            'smtp_username' => (string) ($siteSettings->smtp_username ?? ''),
            'smtp_password' => (string) ($siteSettings->smtp_password ?? ''),
            'smtp_from_address' => (string) ($siteSettings->smtp_from_address ?? ''),
        ];

        foreach ($required as $key => $value) {
            if (trim($value) === '') {
                throw new \RuntimeException("Поле {$key} не заполнено для SMTP отправки.");
            }
        }

        Config::set('mail.mailers.contact_smtp', [
            'transport' => 'smtp',
            'host' => (string) $siteSettings->smtp_host,
            'port' => (int) $siteSettings->smtp_port,
            'encryption' => filled($siteSettings->smtp_encryption) ? (string) $siteSettings->smtp_encryption : null,
            'username' => (string) $siteSettings->smtp_username,
            'password' => (string) $siteSettings->smtp_password,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        Config::set('mail.from.address', (string) $siteSettings->smtp_from_address);
        Config::set(
            'mail.from.name',
            filled($siteSettings->smtp_from_name) ? (string) $siteSettings->smtp_from_name : config('app.name')
        );

        $send('contact_smtp');
    }
}
