<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Mustache\Engine::class, function () {
            return new \Mustache\Engine([
                'escape' => function ($value) {
                    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                },
                'entity_flags' => ENT_QUOTES
            ]);
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
