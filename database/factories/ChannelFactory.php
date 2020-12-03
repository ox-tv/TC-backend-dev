<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Channel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->text(50),
            'slug' => $this->faker->slug(3),
            'url_hash' => md5($this->faker->text(50)),
            'description' => $this->faker->paragraph(3),
            'user_id' => User::count()>0 ? User::all()->random(1)->first()->id : User::factory()->create()->id
        ];
    }
}
