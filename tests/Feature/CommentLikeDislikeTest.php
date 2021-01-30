<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CommentLikeDislikeTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function testCommentCanBeLiked()
    {

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);
    }

    public function testCommentCanBeDisliked()
    {
        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);
    }

    public function testCommentCanBeLikedOnlyOnce(){

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/like");

        $response->assertStatus(200);

        $response = $this->json('GET', "/api/comments/{$comment->id}/like");

        $response->assertStatus(422);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);
    }

    public function testCommentCanBeDislikedOnlyOnce(){

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike");

        $response->assertStatus(200);

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike");

        $response->assertStatus(422);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);

    }

    public function testCanNotLikedAndDisliked(){

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);

        $this->assertDatabaseMissing('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);
    }

    public function testCanNotDislikedAndLiked(){

        $comment = Comment::factory()->create();

        $response = $this->json('GET', "/api/comments/{$comment->id}/dislike");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);

        $response = $this->json('GET', "/api/comments/{$comment->id}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::LIKED_RELATION
        ]);

        $this->assertDatabaseMissing('comment_user', [
            'comment_id' => $comment->id,
            'user_id' => Auth::user()->id,
            'relation' => CommentUser::DISLIKED_RELATION
        ]);
    }

}
