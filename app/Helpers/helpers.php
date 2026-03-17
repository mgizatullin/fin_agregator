<?php

if (! function_exists('url_canonical')) {
    /**
     * Возвращает URL с завершающим слешем в path (канонический вид для фронта).
     * Используется для ссылок на разделы (vklady, zaimy, kredity, karty, banki, blog и т.д.),
     * чтобы не было редиректа 301 со стороны RedirectTrailingSlash.
     */
    function url_canonical(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        if ($path === '' || $path === '/') {
            return $url;
        }
        if (str_ends_with($path, '/')) {
            return $url;
        }
        $path .= '/';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $result = ($parsed['scheme'] ?? '') . '://' . $host . $port . $path;
        if (! empty($parsed['query'])) {
            $result .= '?' . $parsed['query'];
        }
        if (! empty($parsed['fragment'])) {
            $result .= '#' . $parsed['fragment'];
        }

        return $result;
    }
}

if (! function_exists('url_section')) {
    /**
     * Строит URL раздела с завершающим слешем (path без ведущего слеша, со слешем в конце).
     * Пример: url_section('vklady/' . $slug) => http://site.com/vklady/slug/
     */
    function url_section(string $path, array $extra = [], $secure = null): string
    {
        $path = trim($path, '/');
        if ($path !== '') {
            $path .= '/';
        }

        return url($path ? '/' . $path : '/', $extra, $secure);
    }
}

if (! function_exists('description_ensure_html')) {
    /**
     * Приводит описание к HTML перед сохранением (переносы в <p>, экранирование или разрешённые теги).
     */
    function description_ensure_html(?string $text): string
    {
        return description_to_html($text);
    }
}

if (! function_exists('description_to_html')) {
    /**
     * Преобразует текст описания в HTML: переносы строк в <p>, экранирование.
     * Если строка уже содержит теги — возвращает с разрешёнными тегами.
     */
    function description_to_html(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = trim($text);

        // Если уже есть HTML-теги — возвращаем с разрешёнными тегами (без зависимости от Laravel)
        if (str_contains($text, '<') || str_contains($text, '>')) {
            return strip_tags($text, '<p><br><strong><b><em><i><u><ul><ol><li><a>');
        }

        return '<p>' . nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), false) . '</p>';
    }
}
