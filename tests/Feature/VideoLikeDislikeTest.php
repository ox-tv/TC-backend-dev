<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VideoLikeDislikeTest extends TestCase
{
    use DatabaseTransactions;

    public function testVideoCanBeLiked()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('GET', "/api/videos/{$videoId}/like", [], [
            'Authorization' => "Bearer ".$apiToken
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => $user->id,
            'relation' => VideoUser::LIKED_RELATION
        ]);
    }
}
