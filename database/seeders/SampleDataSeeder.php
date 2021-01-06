<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory(10)->create();

        $categories = ['Bitcoin', 'Ethereum', 'Altcoins', 'Di-finance'];

        $categoryIds = [];

        foreach ($categories as $category){
            $categoryIds[] = Category::updateOrCreate([
                'name' => $category,
                'slug' => Str::slug($category),
                'status' => Category::STATUS_ACTIVE
            ]);
        }

        $video = Video::updateOrCreate([
            'title' => "Here's what different about bitcoin in 2020",
            'slug' => Str::slug("Here's what different about bitcoin in 2020"),
            'description' => "Jens Nordvig, Exante Data founder, joins 'Power Lunch' to discuss the bitcoin rally and whether it could ever be a substitute for the U.S. dollar. Subscribe to CNBC PRO for access to investor and analyst insights on bitcoin and more",
            'youtube_link' => "https://www.youtube.com/watch?v=zaj6Udy2saM",
            'upload_method' => Video::UPLOAD_METHOD_YOUTUBE,
            'status' => Video::STATUS_PUBLISHED,
            'user_id' => User::all()->random(1)->first()->id
            ]);

        $video->categories()->attach($categoryIds[0]);

        $video = Video::updateOrCreate([
            'title' => "Bitcoin Could Become The Digital Gold - Steve Forbes | What's Ahead | Forbes",
            'slug' => Str::slug("Bitcoin Could Become The Digital Gold - Steve Forbes | What's Ahead | Forbes"),
            'description' => "Bitcoin has become the king of cryptocurrencies, overshadowing all others and is seen by many as a hedge against inflation. Could Bitcoin actually be the new digital gold? Steve Forbes on what cryptocurrency enthusiasts aren't seeing and on how Bitcoin’s arbitrary supply limit will severely hinder its future usefulness.

                              What's Ahead featuring Steve Forbes provides his insights and perspective, to stay on top of what's happening in this ever-turbulent world with glimpses into the future. What’s Ahead airs Tuesdays, Thursdays and Fridays. ",
            'youtube_link' => "https://www.youtube.com/watch?v=DXdeUEeu97k",
            'upload_method' => Video::UPLOAD_METHOD_YOUTUBE,
            'status' => Video::STATUS_PUBLISHED,
            'user_id' => User::all()->random(1)->first()->id
        ]);

        $video->categories()->attach($categoryIds[0]);


    }
}
