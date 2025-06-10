<?php
// app/Models/Member.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'id_number',
        'address',
        'occupation',
        'marital_status',
        'membership_date',
        'membership_status', // Use this instead of 'status'
        'family_id',
        'relationship_to_head',
        'special_needs',
        'notes',
        'member_type',
        'baptism_date',
        'confirmation_date',
        'first_communion_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'membership_date' => 'date',
        'baptism_date' => 'date',
        'confirmation_date' => 'date',
        'first_communion_date' => 'date',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function sacraments(): HasMany
    {
        return $this->hasMany(Sacrament::class);
    }

    public function communityGroups(): BelongsToMany
    {
        return $this->belongsToMany(CommunityGroup::class, 'group_members')
                    ->withPivot(['joined_date', 'role', 'status'])
                    ->withTimestamps();
    }

    // New Activity relationships
    /**
     * All activities this member has registered for
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_participants')
                    ->withPivot(['registered_at', 'attended', 'role', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Activities this member has attended
     */
    public function attendedActivities(): BelongsToMany
    {
        return $this->activities()->wherePivot('attended', true);
    }

    /**
     * Activities this member organized or led
     */
    public function organizedActivities(): BelongsToMany
    {
        return $this->activities()->wherePivotIn('role', ['organizer', 'leader']);
    }

    // Scopes
    public function scopeActive($query)
    {
        // Check which column exists and use it
        if (Schema::hasColumn('members', 'membership_status')) {
            return $query->where('membership_status', 'active');
        } elseif (Schema::hasColumn('members', 'status')) {
            return $query->where('status', 'active');
        }
        return $query; // Return all if no status column
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeYouth($query)
    {
        return $query->where('member_type', 'youth');
    }

    public function scopeAdult($query)
    {
        return $query->where('member_type', 'adult');
    }

    public function scopeChildren($query)
    {
        return $query->where('member_type', 'child');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    // Get status regardless of column name
    public function getStatusAttribute()
    {
        if (isset($this->attributes['membership_status'])) {
            return $this->attributes['membership_status'];
        } elseif (isset($this->attributes['status'])) {
            return $this->attributes['status'];
        }
        return 'active'; // default
    }
}
