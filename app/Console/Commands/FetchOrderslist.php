<?php

namespace App\Console\Commands;

use App\Providers\OrderslistUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchOrderslist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orderslist:fetch
    {--R|realm= : the realm ID}
    {--L|orders_link= : the orders link}
    {--important : whether the current update is important}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch 1 orders list from the API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $important = $this->option('important');
        // Get the realm ID from the command options
        $realmID = $this->option('realm');
        if (! $realmID) {
            $this->error('No realm ID provided');

            return;
        }

        $orders_link = $this->option('orders_link');

        if (! $orders_link) {
            $this->error('No orders_link provided.');

            return;
        }

        $this->info("Pulling orderslist from from {$orders_link} …");
        $response = Http::withoutVerifying()->retry(3, 10000)->get($orders_link);
        $response->throw();
        if ($response->successful()) {
            $lock = Cache::lock("lock-orderslist-$realmID", 35);
            if ($lock->get()) {
                $path = $realmID.'/orderslist-'.md5($orders_link).'.json';

                $lastContent = Storage::disk('local')->get($path);
                if ($lastContent === $response->body()) {
                    $this->info('Orders list is the same as the last one, not broadcasting.');
                    // Update the file modified time to tell monitors that the file is still fresh
                    touch(Storage::disk('local')->path($path));
                    $lock->release();

                    return;
                }

                if (! Storage::disk('local')->put($path, $response->body())) {
                    $this->error('Could not store orders list to '.Storage::disk('local')->path($path));
                } else {
                    $this->info('New content, broadcasting.');
                    event(new OrderslistUpdated($response->json(), $realmID, $important));
                }
                $lock->release();
            }
        }
    }
}
