<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->text(30),
            'scope' => $this->faker->randomElement(array_keys(Notification::SCOPE_TEXT)),
            'user_group' => $this->faker->randomElement(array_keys(Notification::USER_GROUP_TEXT)),
            'entity_type' => null,
            'entity_id' => null,
            'sender_id' => null,
            'payload' => ['message' => $this->faker->paragraph()],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Notification $notification) {
            $users = User::inRandomOrder()->take(rand(1,10))->get();
            $notification->users()->attach($users->pluck('id'));
        });
    }
}
