<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testCommentsList()
    {

        $response = $this->json('get','/api/comments');

        $response->assertStatus(200);

        $response->assertJson([]);
    }

    public function testCommentStore()
    {

        $video = Video::factory()->create();

        $commentData = [
            'text' => $this->faker->text
        ];


        $response = $this->json('post', "/api/videos/{$video->id}/comments", $commentData);

        $response->assertStatus(200);

        $commentData['user_id'] = Auth::user()->id;
        $commentData['video_id'] = $video->id;

        $this->assertDatabaseHas('comments', $commentData);
    }

    public function testCommentReply(){

        $comment = Comment::factory()->create();

        $commentReplyData = [
            'text' => $this->faker->text
        ];

        $response = $this->json('post', "/api/comments/{$comment->id}/reply", $commentReplyData);

        $response->assertStatus(200);

        $commentReplyData['user_id'] = Auth::user()->id;
        $commentReplyData['video_id'] = $comment->video_id;
        $commentReplyData['parent_id'] = $comment->id;

        $this->assertDatabaseHas('comments', $commentReplyData);
    }

}
