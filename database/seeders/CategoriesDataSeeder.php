<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            "Bitcoin",
            "Ethereum",
            "DeFi",
            "Altcoins",
            "Trending",
            "DApp",
            "Exchanges",
            "Mining",
            "Analysis",
            "Products",
            "Spend",
            "Wallets",
            "Develop",
            "Market News",
            "Token Sales",
            "Learn",
            "Politics",
            "Regulation",
            ];

        foreach ($categories as $category){

            Category::firstOrCreate([
                'name' => $category
                ],[
                    'name' => $category,
                    'slug' => Str::slug($category),
                    'status' => Category::STATUS_ACTIVE
                ]
            );

        }
    }
}
