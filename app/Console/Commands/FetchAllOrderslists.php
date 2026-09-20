<?php

namespace App\Console\Commands;

use App\Models\Realm;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Artisan;

class FetchAllOrderslists extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orderslist:fetch-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all orders lists from the API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Realm::all()->each(function ($realm) {
            $ordersLink = $realm->orders_link;
            if (! $ordersLink) {
                $this->info("Realm {$realm->name}: no orders link set.");

                return;
            }
            if ($realm->orders_polling_frequency <= 0) {
                $this->info("Realm {$realm->name}: polling is disabled by its frequency.");

                return;
            }
            Artisan::call('orderslist:fetch', ['--realm' => $realm->id, '--orders_link' => $ordersLink]);
            $this->info("Realm {$realm->name}: orders list fetched successfully.");
        });
    }
}
