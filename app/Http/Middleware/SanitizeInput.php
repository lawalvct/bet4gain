<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanitize user input to prevent XSS attacks.
 * Strips dangerous HTML/JS from string inputs on POST/PUT/PATCH requests.
 * Does not sanitize: passwords, tokens, HTML editor fields.
 */
class SanitizeInput
{
    /**
     * Fields to skip sanitization (passwords, tokens, etc.)
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        '_token',
        'two_factor_code',
        'recovery_code',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize an array of inputs.
     */
    private function sanitizeArray(array $data, string $prefix = ''): array
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (in_array($key, $this->except)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $fullKey);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize a single string value.
     * Strips script tags, event handlers, and dangerous protocols.
     */
    private function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Strip script tags and their contents
        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);

        // Strip event handlers (onload, onclick, onerror etc.)
        $value = preg_replace('#\s*on\w+\s*=\s*["\'][^"\']*["\']#i', '', $value);
        $value = preg_replace('#\s*on\w+\s*=\s*\S+#i', '', $value);

        // Strip javascript: and data: protocols in href/src
        $value = preg_replace('#(href|src)\s*=\s*["\']?\s*javascript:#i', '$1=""', $value);
        $value = preg_replace('#(href|src)\s*=\s*["\']?\s*data:#i', '$1=""', $value);

        // Strip dangerous HTML tags
        $value = preg_replace('#<(iframe|object|embed|applet|form|input|textarea|button|select|meta|link|base)[^>]*>#is', '', $value);

        return $value;
    }
}
