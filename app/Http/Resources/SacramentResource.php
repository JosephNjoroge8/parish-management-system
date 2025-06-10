<?php
// app/Http/Resources/SacramentResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SacramentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sacrament_type' => $this->sacrament_type,
            'sacrament_date' => $this->sacrament_date,
            'location' => $this->location,
            'officiant' => $this->officiant,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'member' => new MemberResource($this->whenLoaded('member')),
        ];
    }
}

// app/Http/Resources/ActivityResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'activity_name' => $this->activity_name,
            'description' => $this->description,
            'activity_date' => $this->activity_date,
            'location' => $this->location,
            'organizer' => $this->organizer,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'members' => MemberResource::collection($this->whenLoaded('members')),
            'participants_count' => $this->when($this->participants_count !== null, $this->participants_count),
        ];
    }
}

// app/Http/Resources/CommunityGroupResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommunityGroupResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'group_name' => $this->group_name,
            'description' => $this->description,
            'leader' => $this->leader,
            'meeting_day' => $this->meeting_day,
            'meeting_time' => $this->meeting_time,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'members' => MemberResource::collection($this->whenLoaded('members')),
            'members_count' => $this->when($this->members_count !== null, $this->members_count),
        ];
    }
}