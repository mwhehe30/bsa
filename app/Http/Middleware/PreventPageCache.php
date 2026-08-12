<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventPageCache
{
    /**
     * Pastikan halaman HTML tidak pernah disimpan oleh cache browser, proxy,
     * maupun CDN (mis. Cloudflare).
     *
     * Halaman HTML yang di-cache membawa CSRF token basi milik sesi lain.
     * Saat siswa mengirim jawaban, token itu ditolak Laravel dengan status
     * 419 (CSRF token mismatch) sehingga semua POST gagal dan jawaban tidak
     * tersimpan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
