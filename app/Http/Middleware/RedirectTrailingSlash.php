<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectTrailingSlash
{
    /**
     * 301 redirect: нормализует путь — схлопывает повторяющиеся слеши и добавляет один завершающий слеш.
     * /kredity/ufa -> /kredity/ufa/
     * /kredity/ufa/// -> /kredity/ufa/
     * Корень "/" не меняется.
     *
     * Не применяется к админке (/admin) и к запросам Livewire (livewire-.../update),
     * чтобы не ломать Filament и отправку форм.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Никогда не редиректим не-idempotent запросы (POST/PUT/PATCH/DELETE),
        // иначе ломается отправка форм.
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->path();

        // Только фронт: не трогать админку и Livewire
        if (str_starts_with($path, 'admin') || str_contains($path, 'livewire-')) {
            return $next($request);
        }

        $requestUri = $request->server('REQUEST_URI');
        $pathFromUri = is_string($requestUri) ? parse_url($requestUri, PHP_URL_PATH) : '';
        $pathFromUri = is_string($pathFromUri) ? $pathFromUri : '';
        // Уже канонично: один завершающий слеш и нет двойных слешей (проверяем по REQUEST_URI, т.к. path() может обрезать слеш)
        if ($pathFromUri !== '' && str_ends_with($pathFromUri, '/') && ! str_contains($pathFromUri, '//')) {
            return $next($request);
        }
        $normalized = preg_replace('#/+#', '/', trim($path, '/'));
        if ($normalized !== '') {
            $normalized .= '/';
        }
        $url = $request->root() . '/' . $normalized;
        $query = $request->getQueryString();
        if ($query !== null && $query !== '') {
            $url .= '?' . $query;
        }

        return redirect()->to($url, 301);
    }
}
