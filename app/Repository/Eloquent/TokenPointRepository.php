<?php

namespace App\Repository\Eloquent;

use App\Models\TokenPoint;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Throwable;

class TokenPointRepository
{
    public function add($data, $uniqueKeys = ['user_id', 'type', 'date']): TokenPoint
    {
        // validations
        if (empty($data['user_id'])){
            throw new Exception('Something bad happened.');
        }
        if (empty($data['type'])){
            throw new Exception('Something bad happened.');
        }
        if (!isset($data['amount'])){
            throw new Exception('Something bad happened.');
        }
        if (!empty($data['date']) && !($data['date'] instanceof Carbon)){
            throw new Exception('Something bad happened.');
        }

        // data modification
        $data['date'] = !empty($data['date'])? $data['date']->startOfDay() : Carbon::now()->startOfDay();
        $data['activate_at'] = $data['activate_at']?? Carbon::now();

        // Get model or new
        $query = TokenPoint::query();

        foreach ($uniqueKeys as $key){
            $query->where($key, $data[$key]);
        }

        $model = $query->firstOr(function () use ($data, $uniqueKeys) {
            return new TokenPoint();
        });

        // Fill Data
        $model->user_id = $data['user_id'];
        $model->type = $data['type'];
        $model->date = $data['date'];
        $model->activate_at = $data['activate_at'];
        $model->amount = $model->amount + $data['amount'];

        $model->save();

        return $model;
    }

    public function maximumEarnForVideoByUser($user)
    {
        if ($user->is_old_hero_type){
            return 180;
        }

        $multiplier = str_replace('.',',',floatval($user->hero_multiplier));
        return config("user.max_token_for_watching_video_per_day.{$user->hero_type_text}_{$multiplier}") ?? 10;
    }

}
