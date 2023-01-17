<?php

namespace App\Repository\Eloquent;

use App\Models\LoyaltyPoint;
use App\Models\MonetizePoint;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class LoyaltyPointRepository
{
    public function add($data, $uniqueKeys = ['user_id', 'type', 'date']): LoyaltyPoint
    {
        // validations
        if (empty($data['user_id'])){
            throw new Exception('Something bad happened.');
        }
        if (empty($data['type'])){
            throw new Exception('Something bad happened.');
        }
        if (empty($data['amount'])){
            throw new Exception('Something bad happened.');
        }
        if (!empty($data['date']) && !($data['date'] instanceof Carbon)){
            throw new Exception('Something bad happened.');
        }

        // data modification
        $data['date'] = !empty($data['date'])? $data['date']->startOfDay() : Carbon::now()->startOfDay();
        $data['activated_at'] = $data['activated_at']?? null;
        $data['is_calculated'] = $data['is_calculated']?? false;
        $data['related_to_type'] = $data['related_to_type']?? null;
        $data['related_to_id'] = $data['related_to_id']?? null;

        // Get model or new
        $query = LoyaltyPoint::query();

        foreach ($uniqueKeys as $key){
            $query->where($key, $data[$key]);
        }

        $model = $query->firstOr(function () use ($data, $uniqueKeys) {
            return new LoyaltyPoint();
        });

        // Fill Data
        $model->user_id = $data['user_id'];
        $model->type = $data['type'];
        $model->date = $data['date'];
        $model->activated_at = $data['activated_at'];
        $model->is_calculated = $data['is_calculated'];
        $model->related_to_type = $data['related_to_type'];
        $model->related_to_id = $data['related_to_id'];
        $model->amount = $model->amount + $data['amount'];

        $model->save();

        return $model;
    }
}
