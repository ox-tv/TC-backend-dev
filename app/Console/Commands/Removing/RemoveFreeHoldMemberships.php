<?php

namespace App\Console\Commands\Removing;

use App\Models\Notification;
use App\Models\PricingUser;
use App\Models\User;
use App\Models\UserMeta;
use App\TCNotification\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use TCNotification;

class RemoveFreeHoldMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:users:remove-free-hodl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Free hodl membership';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $PaidUserIds = PricingUser::where('status', PricingUser::STATUS_COMPLETED)->distinct()->pluck('user_id')->toArray();

        // Add custom users to exclude removing
        //$PaidUserIds = array_merge($PaidUserIds, [1021,3587,58278]);
        $excludeUserIds = array_merge($PaidUserIds, [94,90,89,85,84,83,82,77,76,75,97,40,52263,188,38,39]);

        // Get users before update for Send Notification
        $users = User::whereNotIn('id', $excludeUserIds)
            ->where('status', User::STATUS_ACTIVE)
            ->where('hero_due_at', '>', Carbon::now()->addDays(6))
            ->get();

        User::whereNotIn('id', $excludeUserIds)
            ->where('status', User::STATUS_ACTIVE)
            ->where('hero_due_at', '>', Carbon::now()->addDays(6))
            ->update([
                'hero_due_at' => Carbon::now()->addDays(6)
            ]);

        $heroMembershipPageLink = config('general.MWA_BECOME_A_HERO_URL');
        $subject = "Your Hodl Membership is ending soon";
        $message = "We hope you enjoy Today’s Crypto and your Hodl Membership.

Time flies when having fun, and we want you to know that your Hodl Membership expires in six days. However, your membership will renew automatically on the expiry day if you have paid with a debit or credit card. Likewise, if you have paid with Crypto, you can renew your membership <a href=\'{$heroMembershipPageLink}\' target=\'_blank\'>HERE</a> if you find it valuable. By doing so, you also help our beloved publishers and us to become the best source for trusted crypto news and market updates.

Best wishes from the team at Today’s Crypto";

        TCNotification::Send($users, new GeneralNotification(
            Notification::TYPE_HERO_MEMBERSHIP_END_SOON,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $message, 'subject' => $subject]
        ));

        return 0;
    }
}
