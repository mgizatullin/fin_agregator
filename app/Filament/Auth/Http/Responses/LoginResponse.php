<?php

namespace App\Filament\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $intended = $request->session()->pull('url.intended');

        // Не редиректить на Livewire update (только POST) — иначе GET даёт MethodNotAllowedHttpException
        if ($intended && $this->isSafeRedirectUrl($intended)) {
            return redirect()->to($intended);
        }

        return redirect()->to(Filament::getUrl());
    }

    private function isSafeRedirectUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === '') {
            return false;
        }
        // Запретить редирект на маршруты livewire/.../update
        if (str_contains($path, '/livewire/') && str_contains($path, '/update')) {
            return false;
        }
        return true;
    }
}
