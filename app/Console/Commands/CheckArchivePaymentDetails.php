<?php

namespace App\Console\Commands;

use App\Models\PaymentDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckArchivePaymentDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:payment-details:check-archive';

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
        PaymentDetails::nonArchived()
            ->whereIn('status', [PaymentDetails::STATUS_EXPIRED, PaymentDetails::STATUS_VERIFIED, PaymentDetails::STATUS_CANCELED])
            ->where('last_status_at', '<=', Carbon::now()->subDays(14))
            ->update([
                'is_archive' => true,
            ]);

        return 0;
    }
}
