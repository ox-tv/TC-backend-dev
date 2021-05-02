<?php

namespace App\Http\Resources;

use App\Http\Resources\Channel\ChannelSubscriberItem;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ChannelSubscriberCollection extends ResourceCollection
{

    public $collects = ChannelSubscriberItem::class;

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
