<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'text' => $this->faker->text(50),
            'user_id' => User::count()>0 ? User::all()->random(1)->first()->id : User::factory()->create()->id,
            'video_id' => Video::count()>0 ? Video::all()->random(1)->first()->id : Video::factory()->create()->id,
            'parent_id' => Comment::count()>0 ? Comment::all()->random(1)->first()->id : null,
        ];
    }
}
