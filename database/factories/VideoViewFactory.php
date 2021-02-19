<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoViewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VideoView::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'video_id' => Video::count()>0 ? Video::all()->random(1)->first()->id : Video::factory()->create()->id,
            'user_id' => User::count()>0 ? User::all()->random(1)->first()->id : User::factory()->create()->id,
        ];
    }
}
