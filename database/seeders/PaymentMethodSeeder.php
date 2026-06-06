<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=PaymentMethodSeeder
     * @return void
     */
    public function run()
    {
        $paymentMethods = [
            [
                'name' => 'Stripe',
                'description' => 'Stripe payment method',
            ],
        ];

        foreach ($paymentMethods as $paymentMethod){

            PaymentMethod::firstOrCreate([
                    'name' => $paymentMethod['name']
                ],[
                    'name' => $paymentMethod['name'],
                    'description' => $paymentMethod['description'],
                ]
            );
        }

    }
}
