<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRootPostRequests
{
    /**
     * Log POST requests to root URL to find the source of MethodNotAllowedHttpException.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->is('/')) {
            logger()->error('POST to root detected', [
                'url' => $request->fullUrl(),
                'referer' => $request->headers->get('referer'),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('Content-Type'),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }
}
