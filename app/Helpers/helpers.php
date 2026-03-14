<?php

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
