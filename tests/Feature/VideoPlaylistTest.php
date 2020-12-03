<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VideoPlaylistTest extends TestCase
{
    use DatabaseTransactions;

    public function testVideoCanBeAddedToPlaylist()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $video = Video::factory()->create();

        $playlist = Playlist::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->json('PUT', "/api/playlist/{$playlist->id}/add/{$video->id}", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('playlist_video', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id
        ]);
    }

    public function testVideoCanBeDeletedFromPlaylist()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $video = Video::factory()->create();

        $playlist = Playlist::factory()->create([
            'user_id' => $user->id
        ]);


        $playlist->videos()->attach($video);


        $response = $this->json('PUT', "/api/playlist/{$playlist->id}/remove/{$video->id}", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('playlist_video', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id
        ]);
    }
}
