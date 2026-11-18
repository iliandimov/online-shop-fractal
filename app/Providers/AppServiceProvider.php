<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Filament\Panel;
use Lunar\Facades\CartSession;
use Lunar\Admin\Support\Facades\LunarPanel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LunarPanel::panel(fn (Panel $panel): Panel => $panel->path(config('lunar.panel.path', 'admin')))->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('store.*', function ($view): void {
            $cart = CartSession::current(calculate: false);
            $cartCount = $cart?->lines()->sum('quantity') ?: collect(session('cart', []))->sum('quantity');
            $view->with('cartCount', $cartCount);
        });
    }
}
