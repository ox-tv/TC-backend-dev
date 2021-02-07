<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        // adding a user to auth
        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

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

    public function testVideoStore()
    {
        $this->withoutExceptionHandling();

        Storage::fake('videos');

        $videoFile = UploadedFile::fake()->create('video.mp4', 2500, 'video/mp4');

        $videoData = [
            'title' => $this->faker->text(50),
            'description' => $this->faker->paragraph(3),
            'video' => $videoFile
        ];

        $response = $this->json('POST', '/api/videos', $videoData);

        $response->assertStatus(201);

        $videoDataToCheck = $videoData;
        unset($videoDataToCheck['video']);

        $this->assertDatabaseHas('videos', $videoDataToCheck);

    }

    public function testVideoImportFromYoutubeWithValidData()
    {

        $videoData = [
            'title' => $this->faker->text(50),
            'description' => $this->faker->paragraph(3),
            'youtube_link' => "https://www.youtube.com/watch?v=u2jiRjyUbwA"
        ];

        $response = $this->json('POST', '/api/videos', $videoData);

        $response->assertStatus(201);

        $videoDataToCheck = $videoData;
        unset($videoDataToCheck['video']);

        $this->assertDatabaseHas('videos', $videoDataToCheck);

    }

    public function testVideoImportFromYoutubeWithInvalidLink()
    {
        $videoData = [
            'title' => $this->faker->text(50),
            'description' => $this->faker->paragraph(3),
            'youtube_link' => "https://someotherdomain.com/watch?v=u2jiRjyUbwA"
        ];

        $response = $this->json('POST', '/api/videos', $videoData);

        $response->assertStatus(422);

        $videoDataToCheck = $videoData;
        unset($videoDataToCheck['video']);

        $this->assertDatabaseMissing('videos', $videoDataToCheck);

    }

}
