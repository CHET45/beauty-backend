<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('public-api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('admin-login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });
    }
}
