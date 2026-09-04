<?php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting()
    {
        // ==================== AUTHENTICATION (Security Critical) ====================

        // Signin - 5 attempts per minute per IP
        RateLimiter::for('signin', function ($job) {
            return Limit::perMinute(5)->by($job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again after 60 seconds.',
                        'retry_after' => 60
                    ], 429);
                });
        });


        RateLimiter::for('search', function ($job) {
                return Limit::perMinute(100)->by($job->user()?->id ?: $job->ip());
            });

        // Signup - 3 attempts per hour per IP
        RateLimiter::for('signup', function ($job) {
            return Limit::perMinute(3)->by($job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many registration attempts. Please try again later.',
                        'retry_after' => 3600
                    ], 429);
                });
        });

        // Password Reset - 3 attempts per hour per IP/email
        RateLimiter::for('password-reset', function ($job) {
            return Limit::perMinute(3)->by($job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many password reset attempts. Please try again later.',
                        'retry_after' => 3600
                    ], 429);
                });
        });

        // ==================== POS OPERATIONS ====================

        // Create Order/Sale - 100 per minute per user
        RateLimiter::for('pos-orders', function ($job) {
            return Limit::perMinute(100)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many orders. Please slow down.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Process Payment - 30 per minute per user
        RateLimiter::for('pos-payments', function ($job) {
            return Limit::perMinute(30)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many payment requests. Please wait a moment.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Print Receipt - 50 per minute per user
        RateLimiter::for('pos-print', function ($job) {
            return Limit::perMinute(50)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many print requests. Please wait.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Barcode Scan - 200 per minute per user
        RateLimiter::for('pos-scan', function ($job) {
            return Limit::perMinute(200)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many scan requests. Please slow down.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // ==================== INVENTORY OPERATIONS ====================

        // Stock Update - 50 per minute per user
        RateLimiter::for('stock-update', function ($job) {
            return Limit::perMinute(50)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many stock updates. Please wait a moment.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Bulk Import - 5 per day per user
        RateLimiter::for('bulk-import', function ($job) {
            return Limit::perDay(5)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bulk import limit exceeded. Please try again tomorrow.',
                        'retry_after' => 86400
                    ], 429);
                });
        });

        // Stock Transfer - 20 per hour per user
        RateLimiter::for('stock-transfer', function ($job) {
            return Limit::perHour(20)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many stock transfers. Please try again later.',
                        'retry_after' => 3600
                    ], 429);
                });
        });

        // ==================== PRODUCT MANAGEMENT ====================

        // Product Create/Update - 30 per minute per user
        RateLimiter::for('product-update', function ($job) {
            return Limit::perMinute(30)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many product updates. Please slow down.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Product Search - 500 per minute per user
        RateLimiter::for('product-search', function ($job) {
            return Limit::perMinute(500)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many search requests. Please wait a moment.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // ==================== REPORT GENERATION ====================

        // Generate Report - 20 per hour per user
        RateLimiter::for('report-generation', function ($job) {
            return Limit::perHour(20)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many report generations. Please try again later.',
                        'retry_after' => 3600
                    ], 429);
                });
        });

        // Export Data - 10 per hour per user
        RateLimiter::for('export-data', function ($job) {
            return Limit::perHour(10)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many exports. Please try again later.',
                        'retry_after' => 3600
                    ], 429);
                });
        });

        // Dashboard Refresh - 30 per minute per user
        RateLimiter::for('dashboard-refresh', function ($job) {
            return Limit::perMinute(30)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many dashboard refreshes. Please slow down.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // ==================== USER MANAGEMENT ====================

        // User Create/Update - 50 per minute per user
        RateLimiter::for('user-management', function ($job) {
            return Limit::perMinute(50)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many user operations. Please slow down.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // ==================== GLOBAL API ====================

        // Global API rate limit - 1000 per minute
        RateLimiter::for('api', function ($job) {
            return Limit::perMinute(1000)->by($job->user()?->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'API rate limit exceeded. Please try again later.',
                        'retry_after' => 60
                    ], 429);
                });
        });
    }
}
