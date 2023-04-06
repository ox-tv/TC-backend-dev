<?php

namespace App\Repository\Eloquent;

use App\Models\TokenPoint;
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
        $data['activate_at'] = $data['activate_at']?? Carbon::now()->addHours(6);

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
}
