<?php

if (!function_exists('description_ensure_html')) {
    /**
     * Приводит значение description к HTML (для сохранения в БД).
     * Если передан массив TipTap doc или JSON-строка — конвертирует в HTML, иначе возвращает строку как есть.
     *
     * @param mixed $value
     * @return string
     */
    function description_ensure_html(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value) && isset($value['type'], $value['content']) && $value['type'] === 'doc') {
            return tiptap_doc_to_html($value);
        }
        if (is_string($value)) {
            return description_to_html($value);
        }
        return '';
    }
}

if (!function_exists('description_to_html')) {
    /**
     * Преобразует значение description в HTML для вывода.
     * Если в базе сохранён TipTap JSON — конвертирует в HTML, иначе возвращает как есть.
     *
     * @param string|null $value
     * @return string
     */
    function description_to_html(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $trimmed = trim($value);
        if (
            str_starts_with($trimmed, '{"type":"doc"') ||
            str_starts_with($trimmed, '{"type": "doc"')
        ) {
            $data = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return $value;
            }
            return tiptap_doc_to_html($data);
        }
        return $value;
    }
}

if (!function_exists('tiptap_doc_to_html')) {
    /**
     * Рекурсивно конвертирует узел TipTap JSON в HTML.
     *
     * @param array|string $node
     * @return string
     */
    function tiptap_doc_to_html($node): string
    {
        if (is_string($node)) {
            return e($node);
        }
        if (!is_array($node)) {
            return '';
        }
        $type = $node['type'] ?? '';
        $content = $node['content'] ?? [];
        $inner = implode('', array_map('tiptap_doc_to_html', $content));

        if ($type === 'text') {
            $text = e($node['text'] ?? '');
            $marks = $node['marks'] ?? [];
            foreach ($marks as $mark) {
                $m = $mark['type'] ?? '';
                $attrs = $mark['attrs'] ?? [];
                if ($m === 'bold') {
                    $text = '<strong>' . $text . '</strong>';
                } elseif ($m === 'italic') {
                    $text = '<em>' . $text . '</em>';
                } elseif ($m === 'link') {
                    $href = $attrs['href'] ?? '#';
                    $text = '<a href="' . e($href) . '">' . $text . '</a>';
                } elseif ($m === 'code') {
                    $text = '<code>' . $text . '</code>';
                }
            }
            return $text;
        }

        switch ($type) {
            case 'doc':
                return $inner;
            case 'paragraph':
                return '<p>' . $inner . '</p>';
            case 'heading':
                $level = (int) ($node['attrs']['level'] ?? 2);
                $level = max(1, min(6, $level));
                return '<h' . $level . '>' . $inner . '</h' . $level . '>';
            case 'bulletList':
                return '<ul>' . $inner . '</ul>';
            case 'orderedList':
                return '<ol>' . $inner . '</ol>';
            case 'listItem':
                return '<li>' . $inner . '</li>';
            case 'hardBreak':
                return '<br>';
            default:
                return $inner;
        }
    }
}
