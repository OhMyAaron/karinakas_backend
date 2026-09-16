<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pembatas laju BERNAMA (NFR 3.4.4.5). Tanpa nama, `throttle:5,1` memakai
        // kunci "domain|IP" sehingga SELURUH rute autentikasi berbagi satu jatah:
        // memuat halaman penyetelan ulang beberapa kali dapat ikut memblokir
        // login pengguna. Dengan pembatas bernama, tiap kelompok punya jatah sendiri.
        RateLimiter::for('masuk', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('lupa-sandi', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('reset-sandi', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // Admin & Super Admin diberi semua kemampuan (authorize()) di controller.
        // Akses tetap dibatasi middleware role pada level route.
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super_admin'])) {
                return true;
            }
            return null;
        });
    }
}
