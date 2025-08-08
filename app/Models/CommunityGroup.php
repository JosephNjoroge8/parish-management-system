<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommunityGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description', 
        'group_type',
        'leader_id',
        'meeting_day',
        'meeting_time',
        'meeting_location',
        'is_active', // Use is_active instead of status
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meeting_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Group types enum
    const GROUP_TYPES = [
        'youth' => 'Youth',
        'women' => 'Women',
        'men' => 'Men',
        'children' => 'Children',
        'choir' => 'Choir',
        'prayer' => 'Prayer Group',
        'bible_study' => 'Bible Study',
        'other' => 'Other',
    ];

    // Meeting days enum
    const MEETING_DAYS = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    // Relationships
    public function leader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'group_members')
                    ->withPivot(['joined_date', 'role', 'status', 'notes'])
                    ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        if ($type) {
            return $query->where('group_type', $type);
        }
        return $query;
    }

    // Accessors
    public function getGroupTypeNameAttribute()
    {
        return self::GROUP_TYPES[$this->group_type] ?? ucfirst($this->group_type);
    }

    public function getMeetingDayNameAttribute()
    {
        return self::MEETING_DAYS[$this->meeting_day] ?? ucfirst($this->meeting_day);
    }

    public function getStatusAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }
}
