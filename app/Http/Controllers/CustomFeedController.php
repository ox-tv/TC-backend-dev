<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chapter\ChapterStore;
use App\Http\Requests\Chapter\ChapterUpdate;
use App\Http\Resources\Chapter\ChapterResource;
use App\Http\Resources\CryptoCurrency\CryptoCurrencyResource;
use App\Http\Resources\Tag\TagResource;
use App\Models\Chapter;
use App\Models\Option;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Video;
use App\Repository\Eloquent\ChannelRepository;
use App\Repository\Eloquent\TagRepository;
use App\Rules\CustomRule;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;

class CustomFeedController extends Controller
{
    private $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    public function show()
    {
        $result = [];
        $user = auth('api')->user();

        $result['custom_feed_setting'] = ($meta = $user->meta()->where('key', UserMeta::CustomFeedSetting)->first())? $meta->value : [];
        $result['favorite_crypto_currencies'] = CryptoCurrencyResource::collection($user->favoriteCryptoCurrencies);
        $result['favorite_tags'] = TagResource::collection($user->favoriteTags);

        return $result;
    }

    public function update(Request $request)
    {
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        $request->validate([
            'tag_names' => ['nullable', 'array'],
            'tag_names.*' => ['string', CustomRule::forbiddenWords($forbiddenWords), CustomRule::alphaSpace(), 'max:25'],
            'crypto_currency_ids' => ['nullable', 'array'],
            'crypto_currency_ids.*' => ['required', 'exists:crypto_currencies,id'],
            'crypto_currencies_content_based' => ['required', 'boolean'],
        ]);

        $user = auth('api')->user();

        // Store Favorite Coins
        $cryptoCurrencyIds = $request->get('crypto_currency_ids')? : [];
        $user->favoriteCryptoCurrencies()->sync($cryptoCurrencyIds);

        // Store Favorite Tags
        $tagNames = $request->get('tag_names')? : [];
        $tagIds = [];
        foreach ($tagNames as $tagName){
            $tag = $this->tagRepository->store([
                'name' => $tagName,
                'status' => Tag::STATUS_PUBLISHED,
                'creation_scope' => Tag::CREATION_SCOPE_USER,
            ]);

            $tagIds[] = $tag->id;
        }
        $user->favoriteTags()->sync($tagIds);

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::CustomFeedSetting],
            ['value' => json_encode(['crypto_currencies_content_based' => $request->get('crypto_currencies_content_based'),])]
        );

        return response()->json(['message' => 'ok']);
    }
}
