<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\User;
use App\Models\Video;
use App\Models\UserVideo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommentLikeDislikeTest extends TestCase
{
    use DatabaseTransactions;

    public function testCommentCanBeLiked()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/like", [], [
            'Authorization' => "Bearer ".$apiToken
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);
    }

    public function testCommentCanBeDisliked()
    {
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);
    }

    public function testCommentCanBeLikedOnlyOnce(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/like", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $response = $this->json('GET', "/api/comments/{$comment->id}/like", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);
    }

    public function testCommentCanBeDislikedOnlyOnce(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);

    }

    public function testCanNotLikedAndDisliked(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/like", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);

        $this->assertDatabaseMissing('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);
    }

    public function testCanNotDislikedAndLiked(){
        // adding a user to auth
        $user = User::factory()->create();
        $apiToken = $user->createToken('access_token')->accessToken;

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);

        $response = $this->json('GET', "/api/comments/{$comment->id}/like", [], [
            'Authorization' => "Bearer {$apiToken}"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);

        $this->assertDatabaseMissing('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);
    }

}
