<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    public function handle($request, Closure $next)
    {
        if ($this->isLivewireUpdateRequest($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

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

    private function isLivewireUpdateRequest(Request $request): bool
    {
        if (! $request->isMethod('POST')) {
            return false;
        }

        $path = trim($request->path(), '/');

        return $path === 'livewire/update'
            || preg_match('#^livewire-[^/]+/update$#', $path) === 1;
    }
}

