<?php

namespace Tests;

trait WithoutCsrfMiddleware
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF protection for all tests using this trait
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }
}
