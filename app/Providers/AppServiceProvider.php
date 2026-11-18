<?php

namespace App\Providers;

use Carbon\CarbonInterface;
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
        view()->share('tanggalEpo', function (?CarbonInterface $tanggal = null, string $format = 'd F Y'): string {
            return $tanggal ? $tanggal->copy()->locale('id')->translatedFormat($format) : '-';
        });
    }
}
