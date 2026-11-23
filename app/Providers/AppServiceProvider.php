<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
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
        // Antes estaba en RouteServiceProvider::configureRateLimiting()
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Panel AdminLTE: nombre del usuario en la esquina superior derecha (solo texto)
        Event::listen(BuildingMenu::class, function (BuildingMenu $evento) {
            $usuario = auth('web')->user();
            if ($usuario) {
                $evento->menu->add([
                    'text' => $usuario->nombre,
                    'url' => '#',
                    'icon' => 'fas fa-fw fa-user-circle',
                    'topnav_right' => true,
                    'classes' => 'disabled text-dark font-weight-bold',
                ]);
            }
        });
    }
}
