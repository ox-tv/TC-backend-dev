<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ChannelCrudTest extends TestCase
{

    use DatabaseTransactions, WithFaker;

    public function testChannelList()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $response = $this->json('GET', '/api/channels', [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function testChannelStore(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $coverPhoto = UploadedFile::fake()->image('faker-cover.png');

        $image = UploadedFile::fake()->image('image.png');

        $channelData = [
            'name' => $this->faker->text,
            'description' => $this->faker->paragraph(3),
            'cover' => $coverPhoto,
            'image' => $image,
        ];

        $response = $this->json('POST', '/api/channels', $channelData, [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(201);

        unset($channelData['cover']);
        unset($channelData['image']);


        $this->assertDatabaseHas('channels', $channelData);
    }

    public function testChannelUpdate(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $channel = Channel::factory()->create();

        $coverPhoto = UploadedFile::fake()->image('cover.png');
        $image = UploadedFile::fake()->image('image.png');

        $channelUpdatedData = [
            'name' => $this->faker->text,
            'description' => $this->faker->paragraph(3),
            'cover' => $coverPhoto,
            'image' => $image,
        ];

        $response = $this->json('PUT', "/api/channels/{$channel->id}", $channelUpdatedData, [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        unset($channelUpdatedData['cover']);
        unset($channelUpdatedData['image']);

        $this->assertDatabaseHas('channels', $channelUpdatedData);

        $channel->refresh();

        $this->assertEquals($channel->name, $channelUpdatedData['name']);
        $this->assertEquals($channel->description, $channelUpdatedData['description']);
    }

    public function testChannelDelete(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $channel = Channel::factory()->create();

        $response = $this->json('DELETE', "/api/channels/{$channel->id}", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertSoftDeleted($channel);

    }

}
