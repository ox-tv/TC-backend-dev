<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\Message;
use Illuminate\Http\Resources\Json\JsonResource;

class UserItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withPublisherRequest = $request->is('api/admin/publisher-requests');

        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'eth_address' => $this->eth_address,
            'hero_member_at' => $this->hero_member_at,
            'hero_due_at' => $this->hero_due_at,
            'is_hero' => $this->is_hero,
            'is_mute' => $this->is_mute,
            'muted_until' => $this->muted_until,
            'role' => $this->role_name,
            'request_details' => $this->when(
                $withPublisherRequest,
                Message::where([
                    'user_id' => $this->id,
                    'department_id' => $publisherApplicationDepartmentId
                    ]
                )->orderBy('created_at', 'desc')->first()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
