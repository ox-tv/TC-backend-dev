<?php

namespace App\Console\Commands;

use App\Mail\HeroMembershipExipreSoonMail;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use TCNotification;

class CheckHeroMembershipExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:check-hero-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check hero memberships exipry';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::isHero()->where('hero_due_at', '<', Carbon::now()->addDays(7))->get();

        if ($users->isEmpty()){
            return 0;
        }

        $heroMembershipPageLink = config('general.MWA_BECOME_A_HERO_URL');
        $subject = "Your Hodl Membership is ending soon";
        $message = "We hope you enjoy Today’s Crypto and your Hero Membership.

Time flies when having fun, and we want you to know that your Hodl Membership expires in seven days. However, you can renew your membership <a href=\'{$heroMembershipPageLink}\' target=\'_blank\'>HERE</a> if you find it valuable. By doing so, you also help our beloved publishers and us to become the best source for trusted crypto news and market updates.

Best wishes from the team at Today’s Crypto";

        TCNotification::Send($users, new GeneralNotification(
            Notification::TYPE_HERO_MEMBERSHIP_END_SOON,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $message, 'subject' => $subject]
        ));

        foreach ($users as $user){
            Mail::to($user->email)
                ->queue(new HeroMembershipExipreSoonMail($heroMembershipPageLink));
        }

        return 0;
    }
}
