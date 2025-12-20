<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\Business;
use App\Policies\AuditPolicy;
use App\Policies\BusinessPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Business::class => BusinessPolicy::class,
        Audit::class => AuditPolicy::class,
    ];

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
        //
    }
}
