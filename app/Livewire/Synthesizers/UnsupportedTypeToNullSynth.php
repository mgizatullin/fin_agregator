<?php

namespace App\Livewire\Synthesizers;

use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;

/**
 * Livewire should never receive closures/resources as public state, but some
 * third-party integrations can leak them into nested payloads during uploads.
 * We dehydrate them as null to avoid hard crashes.
 */
class UnsupportedTypeToNullSynth extends Synth
{
    public static string $key = 'u0';

    public static function match($target): bool
    {
        if ($target instanceof \Closure || is_resource($target)) {
            return true;
        }

        // Some internal objects (and some PHP extensions) produce json_encode(...) === null
        // with JSON_ERROR_UNSUPPORTED_TYPE. Livewire's exception message will then display "[null]".
        if (is_object($target)) {
            json_encode($target);
            return json_last_error() === JSON_ERROR_UNSUPPORTED_TYPE;
        }

        return false;
    }

    public function dehydrate($target): array
    {
        return [null, []];
    }

    public function hydrate($value, $meta, $hydrateChild)
    {
        return null;
    }
}

