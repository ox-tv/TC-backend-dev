<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    public function testPlaylistIndex()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $response = $this->json('GET', '/api/playlists', [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $response->assertJson([]);
    }

    public function testPlaylistStore(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $playlistData = [
            'name' => $this->faker->text(50)
        ];

        $response = $this->json('POST', '/api/playlists', $playlistData, [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('playlists', $playlistData);
    }

    public function testPlaylistUpdate(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $playlist = Playlist::factory()->create();

        $playlistUpdatedData = [
            'name' => $this->faker->text(50)
        ];

        $response = $this->json('PUT', "/api/playlists/{$playlist->id}", $playlistUpdatedData, [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('playlists', $playlistUpdatedData);

        $playlist->refresh();

        $this->assertEquals($playlist->name, $playlistUpdatedData['name']);

    }

    public function testPlaylistDelete(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $playlist = Playlist::factory()->create();

        $response = $this->json('DELETE', "/api/playlists/{$playlist->id}", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDeleted('playlists', $playlist->toArray());

    }
}
