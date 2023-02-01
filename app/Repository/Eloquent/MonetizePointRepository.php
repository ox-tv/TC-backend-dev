<?php

namespace App\Repository\Eloquent;

use App\Models\MonetizePoint;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class MonetizePointRepository
{
    public function add($data, $uniqueKeys = ['channel_id', 'type', 'date']): MonetizePoint
    {
        // validations
        if (empty($data['channel_id'])){
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
        $data['activated_at'] = $data['activated_at']?? null;
        $data['is_calculated'] = $data['is_calculated']?? false;
        $data['related_to_type'] = $data['related_to_type']?? null;
        $data['related_to_id'] = $data['related_to_id']?? null;

        // Get model or new
        $query = MonetizePoint::query();

        foreach ($uniqueKeys as $key){
            $query->where($key, $data[$key]);
        }

        $model = $query->firstOr(function () use ($data, $uniqueKeys) {
            return new MonetizePoint();
        });

        // Fill Data
        $model->channel_id = $data['channel_id'];
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
