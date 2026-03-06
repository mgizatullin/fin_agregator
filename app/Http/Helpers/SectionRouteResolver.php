<?php

namespace App\Http\Helpers;

use App\Models\City;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SectionRouteResolver
{
    /**
     * Resolve city by slug (only active cities).
     */
    public static function resolveCity(?string $slug): ?\App\Models\City
    {
        if (blank($slug)) {
            return null;
        }

        return City::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Parse a template string with placeholders and conditional blocks.
     * Placeholders: {service_name}, {category_name}, {city}, {city.g}, {city.p}
     * Conditionals: [if variable]...[/if] or [if city.p]...[/if] — output only if variable exists and is non-empty.
     *
     * @param array<string, mixed> $variables  e.g. ['service_name' => '...', 'category_name' => '...', 'city' => '...', 'city.g' => '...', 'city.p' => '...']
     */
    public static function parseSeoTemplate(?string $template, array $variables): string
    {
        if (blank($template)) {
            return '';
        }

        // 1) Process [if variable]...[/if] blocks
        $result = preg_replace_callback(
            '/\[if\s+([^\]]+)\](.*?)\[\/if\]/s',
            function (array $m) use ($variables): string {
                $varKey = trim($m[1]);
                $content = $m[2];
                $value = $variables[$varKey] ?? null;
                if ($value !== null && $value !== '') {
                    return $content;
                }
                return '';
            },
            $template
        );

        // 2) Replace variables {name} with values
        $replacements = [];
        $placeholders = [];
        foreach ($variables as $key => $value) {
            $placeholders[] = '{' . $key . '}';
            $replacements[] = (string) ($value ?? '');
        }
        return str_replace($placeholders, $replacements, (string) $result);
    }

    /**
     * Parse a template string with placeholders.
     * Placeholders: {service_name}, {city}, {city.g}, {city.p}
     */
    public static function parseTemplate(?string $template, object $section, ?\App\Models\City $city): string
    {
        if (blank($template)) {
            return '';
        }

        $serviceName = $section->title ?? '';
        $cityName = $city ? $city->name : '';
        $cityGenitive = $city ? ($city->name_genitive ?? $city->name) : '';
        $cityPrepositional = $city ? ($city->name_prepositional ?? $city->name) : '';

        return str_replace(
            ['{service_name}', '{city}', '{city.g}', '{city.p}'],
            [$serviceName, $cityName, $cityGenitive, $cityPrepositional],
            $template
        );
    }

    /**
     * Build section title with optional city (for future template support).
     */
    public static function sectionTitle(object $section, ?\App\Models\City $city): string
    {
        $title = $section->title ?? '';

        if ($city) {
            $title .= ' в ' . ($city->name_prepositional ?? $city->name);
        }

        return $title;
    }

    /**
     * Build SEO description with optional city suffix (for future template support).
     */
    public static function sectionDescription(?string $baseDescription, ?\App\Models\City $city): ?string
    {
        if (blank($baseDescription)) {
            return null;
        }

        if ($city) {
            return rtrim($baseDescription, '. ') . ' в ' . ($city->name_prepositional ?? $city->name) . '.';
        }

        return $baseDescription;
    }
}
