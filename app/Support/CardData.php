<?php

namespace App\Support;

class CardData
{
    /**
     * @return array<int, array{parameter: string, value: string}>
     */
    public static function normalizeDetailItems(mixed $items): array
    {
        if (is_string($items)) {
            return static::parseLegacyText($items);
        }

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $key => $item) {
            if (is_array($item) && array_is_list($item)) {
                continue;
            }

            if (is_array($item)) {
                $parameter = trim((string) ($item['parameter'] ?? $item['label'] ?? $key));
                $value = trim((string) ($item['value'] ?? ''));
            } else {
                $parameter = trim((string) $key);
                $value = trim((string) $item);
            }

            if ($parameter === '' || $value === '') {
                continue;
            }

            $normalized[] = [
                'parameter' => $parameter,
                'value' => $value,
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  array<string, mixed>|null  $accordion
     * @return array<int, array{parameter: string, value: string}>
     */
    public static function extractAccordionItems(mixed $accordion, string|array $sectionTitles): array
    {
        if (! is_array($accordion)) {
            return [];
        }

        foreach ((array) $sectionTitles as $sectionTitle) {
            $section = $accordion[$sectionTitle] ?? null;

            if (! is_array($section)) {
                continue;
            }

            return static::normalizeDetailItems($section);
        }

        return [];
    }

    public static function findValueByParameter(mixed $items, string|array $parameterNames): ?string
    {
        $parameterNames = array_map([static::class, 'normalizeLabel'], (array) $parameterNames);

        foreach (static::normalizeDetailItems($items) as $item) {
            if (in_array(static::normalizeLabel($item['parameter']), $parameterNames, true)) {
                return $item['value'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{parameter: string, value: string}>
     */
    public static function parseLegacyText(?string $text): array
    {
        if (! is_string($text) || trim($text) === '') {
            return [];
        }

        $items = [];

        foreach (preg_split('/\r\n|\r|\n/u', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s*[:\-]\s*/u', $line, 2);
            if (! is_array($parts) || count($parts) < 2) {
                continue;
            }

            $parameter = trim((string) $parts[0]);
            $value = trim((string) $parts[1]);

            if ($parameter === '' || $value === '') {
                continue;
            }

            $items[] = [
                'parameter' => $parameter,
                'value' => $value,
            ];
        }

        return $items;
    }

    private static function normalizeLabel(string $value): string
    {
        $value = str_replace(['Ё', 'ё'], ['Е', 'е'], $value);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? $value;

        return mb_strtolower($value);
    }
}
