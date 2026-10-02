<?php

namespace App\Providers;

use App\Support\Navigation\NavigationBuilder;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Тот же состав меню, что у Inertia-шелла (shared prop `nav`).
        View::composer(
            ['layouts._nav', 'layouts._footer'],
            fn($view) => $view->with('nav', app(NavigationBuilder::class)->build()),
        );
    }
}
