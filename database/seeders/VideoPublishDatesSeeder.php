<?php

namespace Database\Seeders;

use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Spreads the demo videos' publish dates randomly across the last ~30 days so
 * the relative dates ("X days/hours ago") look varied instead of clustered.
 *
 * Re-running re-randomizes the dates (expected for demo data).
 */
class VideoPublishDatesSeeder extends Seeder
{
    public function run()
    {
        $count = 0;

        Video::query()->orderBy('id')->each(function (Video $video) use (&$count) {
            $published = Carbon::now()
                ->subDays(random_int(0, 29))
                ->subHours(random_int(0, 23))
                ->subMinutes(random_int(0, 59));

            $video->published_at = $published;
            $video->save();
            $count++;
        });

        $this->command->info("Randomized publish date for {$count} videos across the last 30 days.");
    }
}
