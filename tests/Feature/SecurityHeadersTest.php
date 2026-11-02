<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Cabeceras de seguridad en la web y en la API. */
class SecurityHeadersTest extends TestCase
{
    public function test_web_pages_send_security_headers(): void
    {
        $response = $this->get('/login')->assertOk();

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $response->assertHeaderMissing('Strict-Transport-Security'); // solo con HTTPS
    }

    public function test_https_adds_hsts_and_the_api_also_gets_the_headers(): void
    {
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=15552000');
        $this->getJson('/api/v1/app/me')->assertUnauthorized()->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
