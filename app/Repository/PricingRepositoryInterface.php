<?php


namespace App\Repository;


use App\Models\Pricing;
use App\Models\User;

interface PricingRepositoryInterface
{
    public function addPricingToUser(User $user, Pricing $pricing);
}