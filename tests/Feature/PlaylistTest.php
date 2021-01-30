<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testPlaylistIndex()
    {

        $response = $this->json('GET', '/api/playlists');

        $response->assertStatus(200);

        $response->assertJson([]);
    }

    public function testPlaylistStore(){

        $playlistData = [
            'name' => $this->faker->text(50)
        ];

        $response = $this->json('POST', '/api/playlists', $playlistData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('playlists', $playlistData);
    }

    public function testPlaylistUpdate(){

        $playlist = Playlist::factory()->create();

        $playlistUpdatedData = [
            'name' => $this->faker->text(50)
        ];

        $response = $this->json('PUT', "/api/playlists/{$playlist->id}", $playlistUpdatedData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('playlists', $playlistUpdatedData);

        $playlist->refresh();

        $this->assertEquals($playlist->name, $playlistUpdatedData['name']);

    }

    public function testPlaylistDelete(){

        $playlist = Playlist::factory()->create();

        $response = $this->json('DELETE', "/api/playlists/{$playlist->id}");

        $response->assertStatus(200);

        $this->assertDeleted('playlists', $playlist->toArray());

    }
}
