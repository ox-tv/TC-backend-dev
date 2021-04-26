<?php

namespace App\Http\Resources\Department;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DepartmentCollection extends ResourceCollection
{

    public $collects = DepartmentItem::class;

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
