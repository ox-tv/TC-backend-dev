<?php

namespace App\Console\Commands;

use App\Models\AuthKey;
use App\Models\PaymentDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemoveOldAuthKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:auth-keys:remove-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired pasyment details and change status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        AuthKey::where('created_at', '<=', Carbon::now()->subDay())->delete();

        return 0;
    }
}
