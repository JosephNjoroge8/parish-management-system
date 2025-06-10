<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'activity_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'organizer',
        'community_group_id',
        'max_participants',
        'registration_required',
        'registration_deadline',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'registration_deadline' => 'datetime',
        'registration_required' => 'boolean',
    ];

    // Activity types enum values (matching your migration)
    const ACTIVITY_TYPES = [
        'mass' => 'Mass/Liturgy',
        'meeting' => 'Meeting',
        'event' => 'Parish Event',
        'workshop' => 'Workshop/Training',
        'retreat' => 'Retreat',
        'social' => 'Social Activity',
        'fundraising' => 'Fundraising',
        'community_service' => 'Community Service',
        'youth' => 'Youth Activity',
        'choir' => 'Choir Practice',
        'prayer' => 'Prayer Service',
        'celebration' => 'Celebration',
    ];

    // Status enum values (matching your migration)
    const STATUSES = [
        'planned' => 'Planned',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'postponed' => 'Postponed',
    ];

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', Carbon::today())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->orderBy('start_date');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'active']);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('start_date', Carbon::now()->month)
                    ->whereYear('start_date', Carbon::now()->year);
    }

    // Relationships
    public function communityGroup(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class);
    }

    /**
     * All participants (registered members) for this activity
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'activity_participants')
                    ->withPivot(['registered_at', 'attended', 'role', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Only members who actually attended the activity
     */
    public function attendees(): BelongsToMany
    {
        return $this->participants()->wherePivot('attended', true);
    }

    /**
     * Activity organizers and leaders
     */
    public function organizers(): BelongsToMany
    {
        return $this->participants()->wherePivotIn('role', ['organizer', 'leader']);
    }

    // Accessors
    public function getParticipantCountAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getAttendeeCountAttribute(): int
    {
        return $this->attendees()->count();
    }

    public function getFormattedDateTimeAttribute(): string
    {
        $dateString = $this->start_date->format('M d, Y');
        
        if ($this->start_time) {
            $dateString .= ' at ' . Carbon::parse($this->start_time)->format('g:i A');
        }
        
        return $dateString;
    }
}
