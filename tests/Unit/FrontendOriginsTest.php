<?php

namespace Tests\Unit;

use App\Support\FrontendOrigins;
use Tests\TestCase;

class FrontendOriginsTest extends TestCase
{
    public function test_it_normalizes_a_dirty_origin_list(): void
    {
        $origins = FrontendOrigins::parse(
            ' https://app.example.com , https://APP.example.com/ignored , http://localhost:3000/ , ftp://bad.example.com, javascript:alert(1), , http://127.0.0.1:3000 ',
        );

        $this->assertSame([
            'https://app.example.com',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
        ], $origins);
    }

    public function test_testing_environment_keeps_local_frontend_origins_available_by_default(): void
    {
        $this->assertSame([
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ], config('cors.allowed_origins'));
    }

    public function test_it_builds_exact_match_patterns(): void
    {
        $this->assertSame([
            '#^https\://app\.example\.com$#',
            '#^http\://localhost\:3000$#',
        ], FrontendOrigins::patterns([
            'https://app.example.com',
            'http://localhost:3000',
        ]));
    }

    public function test_it_checks_if_an_origin_is_in_the_allowlist(): void
    {
        $this->assertTrue(FrontendOrigins::contains(
            ['https://app.example.com', 'http://localhost:3000'],
            'https://app.example.com/',
        ));

        $this->assertFalse(FrontendOrigins::contains(
            ['https://app.example.com', 'http://localhost:3000'],
            'https://evil-example.com',
        ));
    }
}
