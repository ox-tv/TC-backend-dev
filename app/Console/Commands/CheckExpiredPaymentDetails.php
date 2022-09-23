<?php

namespace App\Console\Commands;

use App\Models\PaymentDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiredPaymentDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:payment-details:check-expired';

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
        PaymentDetails::status(PaymentDetails::STATUS_CODE_SENT)->nonArchived()->where('code_sent_at', '<=', Carbon::now()->subDays(60))->update([
            'status' => PaymentDetails::STATUS_EXPIRED,
            'last_status_at' => Carbon::now()
        ]);

        return 0;
    }
}
