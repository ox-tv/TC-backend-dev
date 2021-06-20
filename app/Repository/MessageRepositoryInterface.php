<?php


namespace App\Repository;


interface MessageRepositoryInterface
{
    public function storeUser($related_user, array $payload);
}