<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Add any routes that should be exempt from CSRF protection
    ];

    /**
     * Determine if the session and input CSRF tokens match.
     */
    protected function tokensMatch($request): bool
    {
        // Disable CSRF protection during testing
        if (app()->environment('testing') || defined('PHPUNIT_RUNNING') || app()->runningUnitTests()) {
            return true;
        }

        return parent::tokensMatch($request);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     */
    protected function inExceptArray($request): bool
    {
        // Always allow during testing
        if (app()->environment('testing') || defined('PHPUNIT_RUNNING') || app()->runningUnitTests()) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
