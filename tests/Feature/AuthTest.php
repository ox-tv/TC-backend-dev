<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function testRegister()
    {
        $userData = [
            'email' => $this->faker->email,
            'password' => $this->faker->password
        ];

        $response = $this->json('POST', '/api/register', $userData);

        $response->assertStatus(200);

        // unset password to check later
        $userDataPassword = $userData['password'];
        unset($userData['password']);

        // after registration user must be deactive
        // later that user verifies with a verification code
        // his/her status will change into active
        $userData['status'] = User::STATUS_DEACTIVE;

        $this->assertDatabaseHas('users', $userData);

        // check if user is not stored as active user
        $userData['status'] = User::STATUS_ACTIVE;

        $this->assertDatabaseMissing('users', $userData);

        $user = User::where('email', $userData['email'])->first();

        $this->assertTrue(Hash::check($userDataPassword, $user->password));
        $this->assertFalse(Hash::check(Str::random(8), $user->password));

    }
}
