<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    /**
     * List videos test.
     *
     * @return void
     */
    public function testVideosList()
    {
        $response = $this->get('/api/videos');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

}
