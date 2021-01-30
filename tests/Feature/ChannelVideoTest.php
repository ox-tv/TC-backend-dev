<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ChannelVideoTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testVideoCanBeAddedToChannel()
    {

        $channel = Channel::factory()->create([
            'user_id' => Auth::user()->id
        ]);
        $video = Video::factory()->create();

        $response = $this->json('PUT', "/api/channels/{$channel->id}/add/{$video->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('channel_video', [
            'channel_id' => $channel->id,
            'video_id' => $video->id
        ]);
    }

    public function testVideoCanBeRemovedFromChannel(){

        $channel = Channel::factory()->create([
            'user_id' => Auth::user()->id
        ]);
        $video = Video::factory()->create();

        $channel->videos()->attach($video);

        $response = $this->json('PUT', "/api/channels/{$channel->id}/remove/{$video->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('channel_video', [
            'channel_id' => $channel->id,
            'video_id' => $video->id
        ]);
    }

    public function testVideoCanOnlyBeAddedToOwnedChannel(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $anotherUser = User::factory()->create();

        $video = Video::factory()->create();

        $channel = Channel::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $response = $this->json('PUT', "/api/channels/{$channel->id}/add/{$video->id}", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('channel_video', [
            'channel_id' => $channel->id,
            'video_id' => $video->id
        ]);

    }

    public function testVideoCanOnlyBeRemovedFromOwnedChannel(){

        $anotherUser = User::factory()->create();

        $video = Video::factory()->create();

        $channel = Channel::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $channel->videos()->attach($video);

        $response = $this->json('PUT', "/api/channels/{$channel->id}/remove/{$video->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('channel_video', [
            'channel_id' => $channel->id,
            'video_id' => $video->id
        ]);

    }


}
