<?php


namespace App\Repository;


use App\Models\Pricing;
use App\Models\User;

interface NotificationRepositoryInterface
{
    public function store($users, $type, $scope, $userGroup, $payload = null, $entityType = null, $entityId = null, $from = null);
    public function store2($users, $data);
}