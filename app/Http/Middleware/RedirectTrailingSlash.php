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

        // path() может терять завершающий слеш, поэтому нормализуем по REQUEST_URI.
        // Правило: слеш обязателен только у "директорий" (без расширения файла в последнем сегменте).
        // Для файлов (sitemap.xml, *.html и любые другие расширения) завершающий слеш запрещён.
        $rawPath = $pathFromUri !== '' ? $pathFromUri : '/' . $path;
        $rawPath = is_string($rawPath) && $rawPath !== '' ? $rawPath : '/';

        $collapsedPath = preg_replace('#/+#', '/', $rawPath);
        $collapsedPath = is_string($collapsedPath) && $collapsedPath !== '' ? $collapsedPath : '/';

        if ($collapsedPath === '/') {
            // Корень всегда каноничен (и без двойных слешей он уже не может быть).
            return $next($request);
        }

        $endsWithSlash = str_ends_with($collapsedPath, '/');
        $trimmed = trim($collapsedPath, '/');
        $lastSegment = $trimmed === '' ? '' : basename($trimmed);
        $looksLikeFile = $lastSegment !== '' && str_contains($lastSegment, '.') && ! str_starts_with($lastSegment, '.');

        $canonicalPath = $looksLikeFile ? ('/' . $trimmed) : ('/' . $trimmed . '/');

        // Уже канонично?
        if ($collapsedPath === $canonicalPath) {
            return $next($request);
        }

        // Если запрос был к файлу со слешем на конце (например /sitemap.xml/),
        // или к директории без слеша на конце — делаем 301 на канон.
        $url = $request->root() . $canonicalPath;
        $query = $request->getQueryString();
        if ($query !== null && $query !== '') {
            $url .= '?' . $query;
        }

        return redirect()->to($url, 301);
    }
}
