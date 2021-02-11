<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CommentUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'comment_id' => Comment::count()>0 ? Comment::all()->random(1)->first()->id : Comment::factory()->create()->id,
            'user_id' => User::count()>0 ? User::all()->random(1)->first()->id : User::factory()->create()->id,
            'relation' => $this->faker->randomElement([1,-1]),
        ];
    }
}
