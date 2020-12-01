<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    public function testCommentsList()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $response = $this->json('get','/api/comments', [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $response->assertJson([]);
    }

    public function testCommentStore()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $video = Video::factory()->create();

        $commentData = [
            'text' => $this->faker->text
        ];


        $response = $this->json('post', "/api/videos/{$video->id}/comments", $commentData, [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $commentData['user_id'] = $user->id;
        $commentData['video_id'] = $video->id;

        $this->assertDatabaseHas('comments', $commentData);
    }

    public function testCommentReply(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $commentReplyData = [
            'text' => $this->faker->text
        ];

        $response = $this->json('post', "/api/comments/{$comment->id}/reply", $commentReplyData, [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $commentReplyData['user_id'] = $user->id;
        $commentReplyData['video_id'] = $comment->video_id;
        $commentReplyData['parent_id'] = $comment->id;

        $this->assertDatabaseHas('comments', $commentReplyData);
    }

}
