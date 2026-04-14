<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Livewire sends CSRF tokens, but on some deployments (reverse proxy / mixed hosts)
     * the session cookie can be intermittently missing which results in 419 responses
     * and breaks Filament pages. These endpoints are still protected by auth.
     *
     * @var array<int, string>
     */
    protected $except = [
        'livewire/*',
        'livewire-*/update',
    ];
}

