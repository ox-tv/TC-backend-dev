<?php

namespace Tests\Feature;

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
        $response = $this->json('get','/api/comments');

        $response->assertStatus(200);
    }

}
