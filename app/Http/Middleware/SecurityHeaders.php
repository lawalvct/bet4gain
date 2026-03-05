<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add security headers to all responses.
 * Protects against XSS, clickjacking, content-type sniffing, etc.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = config('security.headers', []);

        if (!empty($headers['x_frame_options'])) {
            $response->headers->set('X-Frame-Options', $headers['x_frame_options']);
        }

        if (!empty($headers['x_content_type_options'])) {
            $response->headers->set('X-Content-Type-Options', $headers['x_content_type_options']);
        }

        if (!empty($headers['x_xss_protection'])) {
            $response->headers->set('X-XSS-Protection', $headers['x_xss_protection']);
        }

        if (!empty($headers['referrer_policy'])) {
            $response->headers->set('Referrer-Policy', $headers['referrer_policy']);
        }

        if (!empty($headers['permissions_policy'])) {
            $response->headers->set('Permissions-Policy', $headers['permissions_policy']);
        }

        // Only add HSTS on HTTPS connections
        if ($request->isSecure() && !empty($headers['strict_transport_security'])) {
            $response->headers->set('Strict-Transport-Security', $headers['strict_transport_security']);
        }

        if (!empty($headers['content_security_policy'])) {
            $response->headers->set('Content-Security-Policy', $headers['content_security_policy']);
        }

        // Remove server identification
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
