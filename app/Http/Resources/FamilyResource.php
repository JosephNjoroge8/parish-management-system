<?php
// app/Http/Resources/FamilyResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'family_name' => $this->family_name,
            'head_of_family' => $this->head_of_family,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'registration_date' => $this->registration_date,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'members' => MemberResource::collection($this->whenLoaded('members')),
            'members_count' => $this->when($this->members_count !== null, $this->members_count),
        ];
    }
}