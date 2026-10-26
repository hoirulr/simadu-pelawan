<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Tangani permintaan HTTP masuk dan sematkan security headers standar industri.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Header perlindungan X-Frame-Options untuk mencegah serangan Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Header perlindungan MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Header XSS Protection untuk browser versi lama
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy untuk melindungi kebocoran URL sensitif
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy untuk membatasi akses API browser yang tidak dibutuhkan
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
