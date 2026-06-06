<?php

namespace App\Listeners\User;

use App\Events\User\CustomFeedFilled;
use App\Models\TokenPoint;
use App\Repository\Eloquent\TokenPointRepository;

class TokenPointsForCustomFeedFilled
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    public function handle(CustomFeedFilled $event)
    {
        $user = $event->user;

        if ($user->favoriteTags()->count() < 3){
            return true;
        }

        if (TokenPoint::where('user_id', $user->id)->whereIn('type', [TokenPoint::TYPE_CUSTOM_FEED_FIILED, TokenPoint::TYPE_CUSTOM_FEED_FIILED_AS_HERO])->exists()){
            return true;
        }

        $this->tokenPointRepository->add([
            'user_id' => $user->id,
            'type' => $user->is_hero? TokenPoint::TYPE_CUSTOM_FEED_FIILED_AS_HERO : TokenPoint::TYPE_CUSTOM_FEED_FIILED,
            'amount' => $user->is_hero? config('points.token.fill_custom_feed_as_hero') : config('points.token.fill_custom_feed'),
        ]);

        return true;
    }
}
