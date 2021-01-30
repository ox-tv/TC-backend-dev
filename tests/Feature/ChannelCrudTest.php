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


    public function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testChannelList()
    {

        $response = $this->json('GET', '/api/channels');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function testChannelStore(){

        $coverPhoto = UploadedFile::fake()->image('faker-cover.png');

        $image = UploadedFile::fake()->image('image.png');

        $channelData = [
            'name' => $this->faker->text,
            'description' => $this->faker->paragraph(3),
            'cover' => $coverPhoto,
            'image' => $image,
        ];

        $response = $this->json('POST', '/api/channels', $channelData);

        $response->assertStatus(201);

        unset($channelData['cover']);
        unset($channelData['image']);


        $this->assertDatabaseHas('channels', $channelData);
    }

    public function testChannelUpdate(){

        $channel = Channel::factory()->create();

        $coverPhoto = UploadedFile::fake()->image('cover.png');
        $image = UploadedFile::fake()->image('image.png');

        $channelUpdatedData = [
            'name' => $this->faker->text,
            'description' => $this->faker->paragraph(3),
            'cover' => $coverPhoto,
            'image' => $image,
        ];

        $response = $this->json('PUT', "/api/channels/{$channel->id}", $channelUpdatedData);

        $response->assertStatus(200);

        unset($channelUpdatedData['cover']);
        unset($channelUpdatedData['image']);

        $this->assertDatabaseHas('channels', $channelUpdatedData);

        $channel->refresh();

        $this->assertEquals($channel->name, $channelUpdatedData['name']);
        $this->assertEquals($channel->description, $channelUpdatedData['description']);
    }

    public function testChannelDelete(){

        $channel = Channel::factory()->create();

        $response = $this->json('DELETE', "/api/channels/{$channel->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted($channel);

    }

}
