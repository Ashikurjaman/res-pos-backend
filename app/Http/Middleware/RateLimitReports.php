// app/Http/Middleware/RateLimitReports.php

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitReports
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'reports_' . ($request->user()?->id ?: $request->ip());

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many report requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        // Increment attempts
        RateLimiter::hit($key, 3600); // 1 hour

        return $next($request);
    }
}
