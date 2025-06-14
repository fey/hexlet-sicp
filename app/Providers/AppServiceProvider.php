<?php

namespace App\Providers;

use App;
use App\Github\Github;
use App\Github\GithubInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use URL;
use DB;
use Blade;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        GithubInterface::class => Github::class,
    ];

    /**
     * Register any application services.
     *
     */
    public function register(): void
    {
        if (config('logging.log_sql_queries')) {
            DB::listen(function ($query): void {
                    info($query->sql, [
                            'bind' => $query->bindings,
                            'time' => $query->time,
                        ]);
            });
        }
    }

    /**
     * Bootstrap any application services.
     *
     */
    public function boot(): void
    {
        Model::preventLazyLoading(
            !App::isProduction()
        );
        Paginator::useBootstrap();
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Blade::include('components.comments', 'comments');
        Blade::include('components.solutions', 'solutions');
        Blade::include("components.solution", 'solution');
        Blade::include("components.hreflang_tags", 'hreflang_tags');
    }
}
