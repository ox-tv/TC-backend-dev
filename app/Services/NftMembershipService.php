<?php

namespace App\Services;


use App\Libraries\TCPolygonClient;
use App\Models\User;

class NftMembershipService
{
    public function updateHeroDataByWalletAddress($walletAddress)
    {
        $user = User::where('auth_wallet', $walletAddress)->first();

        if (!$user) {
            return;
        }

        $client = new TCPolygonClient();
        $response = $client->getNFTsByOwner($walletAddress);

        if (!$response['success']){
            // send email to admins and exit
            return;
        }

        // return if there is no nft assigned to wallet address
        if (count($response['data']) == 0) {
            $user->hero_type = null;
            $user->hero_multiplier = null;
            $user->save();
            return;
        }

        // Calc hero type and multiplier
        $user->hero_multiplier = 1;

        $nftTokens = array_filter($response['data'], function($value) {
            return $value['contractAddress'] == config('user.nft_contract_addresses.black');
        });

        if (!empty($nftTokens)) {
            // We have black edition
            $user->hero_type = User::HERO_TYPE_BLACK;
        }else{
            // So all is white
            $nftTokens = $response['data'];
            $user->hero_type = User::HERO_TYPE_WHITE;
        }

        foreach ($nftTokens as $row) {
            if (
                !empty($row['metadata']['attributes'][0]['value'])
                && $row['metadata']['attributes'][0]['value'] > $user->hero_multiplier
            ) {
                $user->hero_multiplier = $row['metadata']['attributes'][0]['value'];
            }
        }

        $user->save();

        return;
    }
}