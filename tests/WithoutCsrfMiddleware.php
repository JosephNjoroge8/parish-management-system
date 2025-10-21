<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

trait WithoutCsrfMiddleware
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF protection for all tests using this trait
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }
}
