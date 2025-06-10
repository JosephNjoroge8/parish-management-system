<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommunityGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'leader',
        'meeting_day',
        'meeting_time',
        'status',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // Relationships
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'group_members')
                    ->withPivot(['joined_date', 'role', 'status'])
                    ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    // Accessors
    public function getMemberCountAttribute(): int
    {
        return $this->activeMembers()->count();
    }
}
