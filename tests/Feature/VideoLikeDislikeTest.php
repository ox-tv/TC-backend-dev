<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\UserVideo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class VideoLikeDislikeTest extends TestCase
{
    use DatabaseTransactions;


    public function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testVideoCanBeLiked()
    {

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('PUT', "/api/videos/{$videoId}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::LIKED_RELATION
        ]);
    }

    public function testVideoCanBeDisliked()
    {

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('PUT', "/api/videos/{$videoId}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::DISLIKED_RELATION
        ]);
    }

    public function testVideoCanBeLikedOnlyOnce(){

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('PUT', "/api/videos/{$videoId}/like");

        $response->assertStatus(200);

        $response = $this->json('PUT', "/api/videos/{$videoId}/like");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::LIKED_RELATION
        ]);
    }

    public function testVideoCanBeDislikedOnlyOnce(){

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('PUT', "/api/videos/{$videoId}/dislike");

        $response->assertStatus(200);

        $response = $this->json('PUT', "/api/videos/{$videoId}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::DISLIKED_RELATION
        ]);

    }

    public function testCanNotLikedAndDisliked(){

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('PUT', "/api/videos/{$videoId}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::LIKED_RELATION
        ]);

        $response = $this->json('PUT', "/api/videos/{$videoId}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::DISLIKED_RELATION
        ]);

        $this->assertDatabaseMissing('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::LIKED_RELATION
        ]);
    }

    public function testCanNotDislikedAndLiked(){

        $video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);

        $videoId = $video->id;

        $response = $this->json('PUT', "/api/videos/{$videoId}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::DISLIKED_RELATION
        ]);

        $response = $this->json('PUT', "/api/videos/{$videoId}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::LIKED_RELATION
        ]);

        $this->assertDatabaseMissing('user_video', [
            'video_id' => $videoId,
            'user_id' => Auth::user()->id,
            'relation' => UserVideo::DISLIKED_RELATION
        ]);
    }

}
