<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class VideoPlaylistTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testVideoCanBeAddedToPlaylist()
    {

        $video = Video::factory()->create();

        $playlist = Playlist::factory()->create([
            'user_id' => Auth::user()->id
        ]);

        $response = $this->json('PUT', "/api/playlists/{$playlist->id}/add/{$video->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('playlist_video', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id
        ]);
    }

    public function testVideoCanBeDeletedFromPlaylist()
    {

        $video = Video::factory()->create();

        $playlist = Playlist::factory()->create([
            'user_id' => Auth::user()->id
        ]);


        $playlist->videos()->attach($video);


        $response = $this->json('PUT', "/api/playlists/{$playlist->id}/remove/{$video->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('playlist_video', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id
        ]);
    }

    public function testVideoCanOnlyBeAddedToOwnedPlaylist(){

        $anotherUser = User::factory()->create();

        $video = Video::factory()->create();

        $playlist = Playlist::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $response = $this->json('PUT', "/api/playlists/{$playlist->id}/add/{$video->id}");


        $response->assertStatus(404);

        $this->assertDatabaseMissing('playlist_video', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id
        ]);

    }

    public function testVideoCanOnlyBeRemovedFromOwnedPlaylist(){

        $anotherUser = User::factory()->create();

        $video = Video::factory()->create();

        $playlist = Playlist::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $playlist->videos()->attach($video);

        $response = $this->json('PUT', "/api/playlists/{$playlist->id}/remove/{$video->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('playlist_video', [
            'playlist_id' => $playlist->id,
            'video_id' => $video->id
        ]);

    }
}
