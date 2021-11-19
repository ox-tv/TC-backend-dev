<?php

namespace App\Http\Resources\Tag;

use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class TagItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isAdmin = $request->is('api/admin/*')? true : false;

        $include = explode(',', $request->get('include', ''));

        $withFavoritedCount = in_array('favorited_count', $include);
        $withVideosCount = in_array('videos_count', $include);

        $favoritedCount = ($isAdmin && $withFavoritedCount)? $this->favoritedByUsers()->count():0;
        $videosCount = ($isAdmin && $withVideosCount)? $this->videos()->count():0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->when($isAdmin, Tag::STATUS_TEXT[$this->status]?? null),
            'creation_scope' => $this->when($isAdmin, Tag::CREATION_SCOPE_TEXT[$this->creation_scope]?? null),
            'favorited_count' => $this->when($isAdmin && $withFavoritedCount, $favoritedCount),
            'videos_count' => $this->when($isAdmin && $withVideosCount, $videosCount),
        ];
    }
}
