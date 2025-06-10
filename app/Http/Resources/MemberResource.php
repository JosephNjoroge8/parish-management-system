<?php
// app/Http/Resources/MemberResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'occupation' => $this->occupation,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'family' => new FamilyResource($this->whenLoaded('family')),
            'sacraments' => SacramentResource::collection($this->whenLoaded('sacraments')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'community_groups' => CommunityGroupResource::collection($this->whenLoaded('communityGroups')),
        ];
    }
}
