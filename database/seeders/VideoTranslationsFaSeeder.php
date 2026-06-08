<?php

namespace Database\Seeders;

use App\Models\Video;
use Illuminate\Database\Seeder;

/**
 * Overwrites the demo videos' title/description with Farsi translations.
 *
 * The translations live in database/seeders/data/video_translations_fa.json,
 * keyed by video id. Prose is translated; URLs, #hashtags, @handles, social
 * links and chapter timestamps are kept as-is. Idempotent — re-running just
 * re-applies the same values. Videos not present in the dataset are skipped.
 */
class VideoTranslationsFaSeeder extends Seeder
{
    public function run()
    {
        $path = database_path('seeders/data/video_translations_fa.json');

        if (!file_exists($path)) {
            $this->command->error("Translations file not found: {$path}");
            return;
        }

        $translations = json_decode(file_get_contents($path), true);

        if (!is_array($translations)) {
            $this->command->error('Could not parse video_translations_fa.json');
            return;
        }

        $updated = 0;
        foreach ($translations as $id => $fields) {
            $video = Video::find((int) $id);
            if (!$video) {
                continue;
            }

            $video->title = $fields['title'] ?? $video->title;
            $video->description = $fields['description'] ?? $video->description;
            $video->save();
            $updated++;
        }

        $this->command->info("Updated {$updated} video title/description to Farsi.");
    }
}
