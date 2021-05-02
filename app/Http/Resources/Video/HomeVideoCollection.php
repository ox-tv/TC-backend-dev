<?php

namespace App\Http\Resources\Video;

use Illuminate\Http\Resources\Json\ResourceCollection;

class HomeVideoCollection extends ResourceCollection
{

    public $collects = HomeVideoItem::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection;
    }
}
