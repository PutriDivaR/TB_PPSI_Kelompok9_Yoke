<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\View;
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
        View::share('tanggalEpo', function ($tanggal, $format = 'd F Y') {
            return $tanggal
                ? Carbon::parse($tanggal)->locale('id')->translatedFormat($format)
                : '-';
        });
    }
}
