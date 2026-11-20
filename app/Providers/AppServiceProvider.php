<?php

namespace App\Providers;

use App\Contracts\AnchorStore;
use App\Services\BlockchainLedger;
use App\Services\DatabaseAnchorStore;
use App\Services\EvmAnchorStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AnchorStore::class, fn () => match (config('chain.driver')) {
            'evm' => new EvmAnchorStore,
            default => new DatabaseAnchorStore($this->app->make(BlockchainLedger::class)),
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
