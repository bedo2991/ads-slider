<?php

namespace App\Console\Commands;

use App\Models\Realm;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Artisan;

class FetchAllNina extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nina:fetchAll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the warning data from the the nina api, for all realms';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Realm::all()->each(function ($realm) {
            $ars = $realm->nina_ars;
            if (! $ars) {
                $this->error('No ARS provided for realm '.$realm->id);

                return;
            }

            Artisan::call('nina:fetch', ['--ars' => $ars, '--realm' => $realm->id]);
        });
    }
}
