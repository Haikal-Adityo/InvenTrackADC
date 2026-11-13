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
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Setiap notifikasi database baru → kirim juga sebagai Web Push ke semua perangkat user
        // (setelah response terkirim, supaya aksi approve/submit tidak melambat).
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Notifications\Events\NotificationSent::class,
            function (\Illuminate\Notifications\Events\NotificationSent $event): void {
                if ($event->channel !== 'database' || ! \App\Support\WebPush::enabled()) {
                    return;
                }
                $notifiable = $event->notifiable;
                $record = $event->response;
                app()->terminating(fn () => \App\Support\WebPush::fromDatabaseNotification($notifiable, $record));
            }
        );
    }
}
