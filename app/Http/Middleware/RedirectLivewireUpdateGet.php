<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLivewireUpdateGet
{
    /**
     * Redirect GET requests to Livewire update endpoint.
     * Livewire sometimes issues a GET to the update URL after POST (e.g. after redirect);
     * that route only allows POST, so we redirect GET to the settings page to avoid 405.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        $pathInfo = $request->getPathInfo();

        // POST to root "/" -> route only allows GET/HEAD; redirect 303 so browser retries as GET
        $isRoot = $pathInfo === '/' || $pathInfo === '' || trim($pathInfo, '/') === '' || $path === '/' || $path === '';
        if ($request->isMethod('POST') && $isRoot) {
            return redirect()->to('/', 303);
        }

        // POST to any /admin/* (native form submit) -> redirect 303 GET same URL
        if ($request->isMethod('POST') && str_starts_with($path, 'admin/')) {
            return redirect()->to('/' . $path, 303);
        }

        // GET to Livewire update endpoint -> redirect to referer or admin to avoid 405
        if (
            $request->isMethod('GET')
            && str_contains($path, 'livewire-')
            && str_contains($path, '/update')
        ) {
            $referer = $request->header('Referer');
            $url = $referer && str_contains($referer, '/admin') ? $referer : '/admin';
            return redirect()->to($url, 303);
        }

        return $next($request);
    }
}
