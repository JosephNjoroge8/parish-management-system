<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Optimized Member Model - Removes redundant fields and improves performance
 * This is the recommended structure after cleanup
 */
class OptimizedMember extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        // Core personal information
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'id_number',
        'phone',
        'email',
        'residence',

        // Church information
        'local_church',
        'small_christian_community',
        'church_group',
        'additional_church_groups',

        // Membership information
        'membership_status',
        'membership_date',
        'matrimony_status',
        'marriage_type',
        'occupation',
        'education_level',

        // Family and relationships
        'family_id',
        'parent_id',
        'godparent_id',
        'minister_id',
        'tribe',
        'clan',

        // Disability information
        'is_differently_abled',
        'disability_description',

        // Location information
        'birth_village',
        'county',
        'district',
        'province',

        // Notes
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'membership_date' => 'date',
        'additional_church_groups' => 'array',
        'is_differently_abled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants
    const EDUCATION_LEVELS = [
        'none' => 'No Formal Education',
        'primary' => 'Primary Education',
        'kcpe' => 'KCPE',
        'secondary' => 'Secondary Education',
        'kcse' => 'KCSE',
        'certificate' => 'Certificate',
        'diploma' => 'Diploma',
        'degree' => 'Degree',
        'masters' => 'Masters Degree',
        'phd' => 'PhD/Doctorate',
    ];

    const MARRIAGE_TYPES = [
        'church' => 'Church Wedding',
        'civil' => 'Civil Marriage',
        'customary' => 'Customary Marriage',
        'come_we_stay' => 'Come We Stay',
    ];

    const MEMBERSHIP_STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'transferred' => 'Transferred',
        'deceased' => 'Deceased',
    ];

    const CHURCH_GROUPS = [
        'PMC' => 'PMC (Pontifical Missionary Childhood)',
        'Youth' => 'Youth',
        'Young Parents' => 'Young Parents',
        'C.W.A' => 'C.W.A (Catholic Women Association)',
        'CMA' => 'CMA (Catholic Men Association)',
        'Choir' => 'Choir',
        'Catholic Action' => 'Catholic Action',
        'Pioneer' => 'Pioneer',
    ];

    const MATRIMONY_STATUSES = [
        'single' => 'Single',
        'married' => 'Married',
        'widowed' => 'Widowed',
        'separated' => 'Separated',
    ];

    // Relationships
    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function parent()
    {
        return $this->belongsTo(Member::class, 'parent_id');
    }

    public function godparent()
    {
        return $this->belongsTo(Member::class, 'godparent_id');
    }

    public function minister()
    {
        return $this->belongsTo(Member::class, 'minister_id');
    }

    public function children()
    {
        return $this->hasMany(Member::class, 'parent_id');
    }

    public function godchildren()
    {
        return $this->hasMany(Member::class, 'godparent_id');
    }

    public function sacraments()
    {
        return $this->hasMany(Sacrament::class);
    }

    public function tithes()
    {
        return $this->hasMany(Tithe::class);
    }

    public function baptismRecord()
    {
        return $this->hasOne(BaptismRecord::class);
    }

    public function marriageRecords()
    {
        return $this->hasMany(MarriageRecord::class, 'husband_id')
            ->orWhere('wife_id', $this->id);
    }

    public function activityParticipations()
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function groupMemberships()
    {
        return $this->hasMany(GroupMember::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getAllChurchGroupsAttribute()
    {
        $groups = [$this->church_group];

        if ($this->additional_church_groups && is_array($this->additional_church_groups)) {
            $groups = array_merge($groups, $this->additional_church_groups);
        }

        return array_filter(array_unique($groups));
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('membership_status', 'active');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function scopeByChurch($query, $church)
    {
        return $church ? $query->where('local_church', $church) : $query;
    }

    public function scopeByGroup($query, $group)
    {
        if ($group) {
            return $query->where(function ($q) use ($group) {
                $q->where('church_group', $group)
                    ->orWhereJsonContains('additional_church_groups', $group);
            });
        }

        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        return $status ? $query->where('membership_status', $status) : $query;
    }

    public function scopeByGender($query, $gender)
    {
        return $gender ? $query->where('gender', ucfirst(strtolower($gender))) : $query;
    }

    // Static methods for statistics
    public static function getStatistics()
    {
        return [
            'total' => self::count(),
            'active' => self::where('membership_status', 'active')->count(),
            'inactive' => self::where('membership_status', 'inactive')->count(),
            'transferred' => self::where('membership_status', 'transferred')->count(),
            'deceased' => self::where('membership_status', 'deceased')->count(),
            'male' => self::where('gender', 'Male')->count(),
            'female' => self::where('gender', 'Female')->count(),
            'new_this_month' => self::whereMonth('created_at', now()->month)->count(),
        ];
    }
}
