<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Replaces the legacy crypto categories with a curated gaming category set and
 * assigns each demo video a main category plus a few via the category_video
 * pivot, derived from the video's title/description.
 *
 * Idempotent: re-running soft-deletes any category not in the curated set,
 * restores/creates the gaming ones, and re-syncs the demo video mappings.
 *
 * Note: the $map below targets the current demo dataset by video id; videos
 * that aren't present are skipped safely.
 */
class GamingCategoriesDataSeeder extends Seeder
{
    public function run()
    {
        // Curated gaming categories (English names).
        $categories = [
            'Reviews',
            'Trailers',
            'Gameplay',
            'Esports',
            'Previews',
            'Cinematics',
            'Behind the Scenes',
            'News & Updates',
            'Hardware & Tech',
            'PC Gaming',
            'Nintendo Switch',
            'RPG',
            'Shooter',
            'Strategy',
            'Action & Adventure',
            'Indie',
            'Racing',
            'Sports',
            'Platformer',
        ];

        // 1. Soft-delete any category that is not part of the curated set
        //    (removes the legacy crypto categories from the UI).
        Category::whereNotIn('name', $categories)->delete();

        // 2. Create or restore the curated categories; collect name => id.
        $ids = [];
        foreach ($categories as $name) {
            $category = Category::withTrashed()->updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'status' => Category::STATUS_ACTIVE,
                    'deleted_at' => null,
                ]
            );
            $ids[$name] = $category->id;
        }

        // 3. Per-video assignment: video id => [main category, ...additional].
        //    The first entry is used as the video's single "main" category.
        $map = [
            50 => ['Reviews', 'Hardware & Tech', 'Action & Adventure'],
            51 => ['Reviews', 'Hardware & Tech', 'Nintendo Switch', 'RPG'],
            52 => ['Reviews', 'Hardware & Tech', 'PC Gaming'],
            53 => ['Hardware & Tech', 'Reviews', 'PC Gaming'],
            54 => ['Hardware & Tech', 'News & Updates', 'Nintendo Switch'],
            55 => ['Esports', 'News & Updates'],
            56 => ['Esports', 'News & Updates'],
            57 => ['News & Updates', 'Gameplay'],
            58 => ['News & Updates', 'Gameplay'],
            59 => ['Esports', 'Shooter'],
            60 => ['Esports', 'Shooter', 'Gameplay'],
            61 => ['Esports', 'Shooter'],
            62 => ['Esports', 'Shooter', 'Gameplay'],
            63 => ['Esports', 'Shooter', 'Gameplay'],
            64 => ['Previews', 'Nintendo Switch', 'Platformer'],
            65 => ['Previews', 'Indie', 'Action & Adventure'],
            66 => ['Previews', 'Action & Adventure'],
            67 => ['Previews', 'Indie', 'RPG'],
            68 => ['Previews', 'Action & Adventure'],
            69 => ['Trailers', 'Action & Adventure'],
            70 => ['Trailers', 'Action & Adventure', 'RPG'],
            71 => ['News & Updates'],
            72 => ['Trailers', 'RPG', 'Gameplay'],
            73 => ['Trailers', 'Action & Adventure'],
            74 => ['Esports', 'Nintendo Switch'],
            75 => ['News & Updates', 'Nintendo Switch'],
            76 => ['Trailers', 'Sports', 'Nintendo Switch'],
            77 => ['Trailers', 'Nintendo Switch', 'Action & Adventure'],
            78 => ['Trailers', 'Nintendo Switch', 'RPG'],
            79 => ['News & Updates', 'PC Gaming'],
            80 => ['Gameplay', 'PC Gaming'],
            81 => ['Trailers', 'Strategy', 'PC Gaming'],
            82 => ['News & Updates', 'PC Gaming'],
            83 => ['News & Updates', 'PC Gaming'],
            84 => ['Behind the Scenes'],
            85 => ['Behind the Scenes'],
            86 => ['Behind the Scenes'],
            87 => ['Behind the Scenes'],
            88 => ['Behind the Scenes'],
            89 => ['News & Updates', 'Action & Adventure'],
            90 => ['News & Updates', 'Platformer'],
            91 => ['Trailers', 'Racing'],
            92 => ['Trailers', 'Shooter', 'PC Gaming'],
            93 => ['News & Updates'],
            94 => ['Cinematics', 'Shooter'],
            95 => ['Trailers', 'Shooter'],
            96 => ['News & Updates', 'Shooter'],
            97 => ['News & Updates', 'Shooter'],
            98 => ['Cinematics', 'Esports', 'Shooter'],
        ];

        foreach ($map as $videoId => $names) {
            $video = Video::find($videoId);
            if (!$video) {
                continue;
            }

            $categoryIds = [];
            foreach ($names as $name) {
                if (isset($ids[$name])) {
                    $categoryIds[] = $ids[$name];
                }
            }
            if (empty($categoryIds)) {
                continue;
            }

            // Single main category = first in the list.
            $video->category_id = $categoryIds[0];
            $video->save();

            // A few categories via the many-to-many pivot (includes the main one).
            $video->categories()->sync($categoryIds);
        }
    }
}
