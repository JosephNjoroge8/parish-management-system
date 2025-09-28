<?php
// app/Models/Member.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'id_number',
        'phone',
        'email',
        'residence',
        'local_church',
        'small_christian_community',
        'church_group',
        'additional_church_groups',
        'membership_status',
        'membership_date',
        'baptism_date',
        'confirmation_date',
        'matrimony_status',
        'marriage_type',
        'occupation',
        'education_level',
        'family_id',
        'parent_id',
        'godparent_id',
        'minister_id',
        'tribe',
        'clan',
        'is_differently_abled',
        'disability_description',
        'notes',
        
        // Comprehensive Baptism Record Fields
        'birth_village',
        'county',
        'baptism_location',
        'baptized_by',
        'sponsor',
        'father_name',
        'mother_name',
        
        // Optional Sacrament Fields
        'eucharist_location',
        'eucharist_date',
        'confirmation_location',
        'confirmation_register_number',
        'confirmation_number',
        
        // Marriage Record Fields
        'marriage_date',
        'marriage_location',
        'married_by',
        'spouse_name',
        'witness_1_name',
        'witness_2_name',
        'marriage_register_number',
        'marriage_certificate_number',
        
        // Additional Fields
        'godfather_name',
        'godmother_name',
        'parent',
        'godparent',
        'minister',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'membership_date' => 'date',
        'baptism_date' => 'date',
        'confirmation_date' => 'date',
        'eucharist_date' => 'date',
        'marriage_date' => 'date',
        'additional_church_groups' => 'array',
        'is_differently_abled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants for education levels (Kenyan system)
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
        'phd' => 'PhD/Doctorate'
    ];

    // Marriage types for better categorization
    const MARRIAGE_TYPES = [
        'church' => 'Church Wedding',
        'civil' => 'Civil Marriage',
        'customary' => 'Customary Marriage',
        'come_we_stay' => 'Come We Stay'
    ];

    // Membership status options
    const MEMBERSHIP_STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'transferred' => 'Transferred',
        'deceased' => 'Deceased'
    ];

    /**
     * Generate comprehensive report data with all necessary information
     */
    public static function generateComprehensiveReport(array $filters = [])
    {
        $query = self::query()
            ->select([
                'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
                'gender', 'phone', 'email', 'residence', 'local_church', 'church_group',
                'small_christian_community', 'membership_status', 'membership_date',
                'baptism_date', 'confirmation_date', 'matrimony_status', 'marriage_type',
                'occupation', 'education_level', 'tribe', 'clan', 'id_number',
                'additional_church_groups', 'created_at', 'updated_at'
            ]);

        // Apply filters
        if (!empty($filters['local_church'])) {
            $query->where('local_church', $filters['local_church']);
        }

        if (!empty($filters['church_group'])) {
            $query->where('church_group', $filters['church_group']);
        }

        if (!empty($filters['membership_status'])) {
            $query->where('membership_status', $filters['membership_status']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        // Add calculated fields
        return $query->get()->map(function ($member) {
            // Calculate age
            $member->age = $member->date_of_birth 
                ? Carbon::parse($member->date_of_birth)->age 
                : null;

            // Create full name
            $member->full_name = collect([
                $member->first_name,
                $member->middle_name,
                $member->last_name
            ])->filter()->implode(' ');

            // Get all church groups (including additional)
            $allGroups = [$member->church_group];
            if (!empty($member->additional_church_groups)) {
                $additional = is_string($member->additional_church_groups) 
                    ? json_decode($member->additional_church_groups, true) 
                    : $member->additional_church_groups;
                if (is_array($additional)) {
                    $allGroups = array_merge($allGroups, $additional);
                }
            }
            $member->all_church_groups = array_filter($allGroups);

            // Get education level name
            $member->education_level_name = self::EDUCATION_LEVELS[$member->education_level] ?? $member->education_level;

            // Get marriage type name
            $member->marriage_type_name = self::MARRIAGE_TYPES[$member->marriage_type] ?? $member->marriage_type;

            // Format dates
            $member->baptism_date = $member->baptism_date ? Carbon::parse($member->baptism_date) : null;
            $member->confirmation_date = $member->confirmation_date ? Carbon::parse($member->confirmation_date) : null;
            $member->membership_date = $member->membership_date ? Carbon::parse($member->membership_date) : null;
            $member->date_of_birth = $member->date_of_birth ? Carbon::parse($member->date_of_birth) : null;

            return $member;
        });
    }

    // Constants for church groups (7 groups only - Young Parents removed)
    const CHURCH_GROUPS = [
        'PMC' => 'PMC (Pontifical Missionary Childhood)',
        'Youth' => 'Youth',
        'C.W.A' => 'C.W.A (Catholic Women Association)',
        'CMA' => 'CMA (Catholic Men Association)', 
        'Choir' => 'Choir',
        'Catholic Action' => 'Catholic Action',
        'Pioneer' => 'Pioneer'
    ];

    // Constants for matrimony status
    const MATRIMONY_STATUSES = [
        'single' => 'Single',
        'married' => 'Married',
        'widowed' => 'Widowed',
        'separated' => 'Separated'
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


    
    public function baptismRecord()
    {
        return $this->hasOne(BaptismRecord::class);
    }
    
    public function marriageRecordAsHusband()
    {
        return $this->hasOne(MarriageRecord::class, 'husband_id');
    }
    
    public function marriageRecordAsWife()
    {
        return $this->hasOne(MarriageRecord::class, 'wife_id');
    }

    public function sacraments()
    {
        return $this->hasMany(Sacrament::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        $names = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name
        ]);
        
        return implode(' ', $names);
    }

    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return $this->date_of_birth->age;
    }

    public function getAllChurchGroupsAttribute()
    {
        $groups = [$this->church_group];
        
        if ($this->additional_church_groups && is_array($this->additional_church_groups)) {
            $groups = array_merge($groups, $this->additional_church_groups);
        }
        
        return array_filter(array_unique($groups));
    }

    public function getEducationLevelNameAttribute()
    {
        return self::EDUCATION_LEVELS[$this->education_level] ?? $this->education_level;
    }

    public function getMarriageTypeNameAttribute()
    {
        return self::MARRIAGE_TYPES[$this->marriage_type] ?? $this->marriage_type;
    }

    // Mutators
    public function setGenderAttribute($value)
    {
        $this->attributes['gender'] = ucfirst(strtolower(trim($value)));
    }

    public function setDateOfBirthAttribute($value)
    {
        if ($value && is_string($value)) {
            // Handle ISO format dates and convert to Y-m-d
            if (str_contains($value, 'T')) {
                $this->attributes['date_of_birth'] = date('Y-m-d', strtotime($value));
            } else {
                $this->attributes['date_of_birth'] = $value;
            }
        } else {
            $this->attributes['date_of_birth'] = $value;
        }
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhere('small_christian_community', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    public function scopeByChurch($query, $church)
    {
        if ($church) {
            return $query->where('local_church', $church);
        }
        
        return $query;
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

    public function scopeBySmallChristianCommunity($query, $community)
    {
        if ($community) {
            return $query->where('small_christian_community', $community);
        }
        
        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('membership_status', $status);
        }
        
        return $query;
    }

    public function scopeByGender($query, $gender)
    {
        if ($gender) {
            // Ensure consistent capitalization
            $formattedGender = ucfirst(strtolower(trim($gender)));
            return $query->where('gender', $formattedGender);
        }
        
        return $query;
    }

    public function scopeByAgeGroup($query, $ageGroup)
    {
        if (!$ageGroup) {
            return $query;
        }

        $now = now();
        
        return match($ageGroup) {
            'children' => $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) BETWEEN 0 AND 12', [$now]),
            'youth' => $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) BETWEEN 13 AND 24', [$now]),
            'adults' => $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) BETWEEN 25 AND 59', [$now]),
            'seniors' => $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) >= 60', [$now]),
            default => $query,
        };
    }

    public function scopeByEducationLevel($query, $level)
    {
        if ($level) {
            return $query->where('education_level', $level);
        }
        
        return $query;
    }

    public function scopeByTribe($query, $tribe)
    {
        if ($tribe) {
            return $query->where('tribe', $tribe);
        }
        
        return $query;
    }

    // Validation for church group and gender restrictions
    public function validateChurchGroupGender()
    {
        if ($this->church_group === 'C.W.A' && $this->gender !== 'Female') {
            throw new \InvalidArgumentException('C.W.A membership is restricted to female members only.');
        }
        
        if ($this->church_group === 'CMA' && $this->gender !== 'Male') {
            throw new \InvalidArgumentException('CMA membership is restricted to male members only.');
        }
    }

    // Auto-inherit family data for children
    public function inheritFamilyData()
    {
        if ($this->family_id && $this->family) {
            // Get head of family or any adult family member for inheritance
            $familyHead = $this->family->members()
                ->whereNotNull('tribe')
                ->whereNotNull('clan')
                ->first();
                
            if ($familyHead && $this->age && $this->age < 18) {
                if (!$this->tribe && $familyHead->tribe) {
                    $this->tribe = $familyHead->tribe;
                }
                
                if (!$this->clan && $familyHead->clan) {
                    $this->clan = $familyHead->clan;
                }
                
                if (!$this->small_christian_community && $familyHead->small_christian_community) {
                    $this->small_christian_community = $familyHead->small_christian_community;
                }
            }
        }
    }

    // Helper methods for reporting
    public static function getChurchGroupOptions()
    {
        return array_map(function($key, $value) {
            return ['value' => $key, 'label' => $value];
        }, array_keys(self::CHURCH_GROUPS), self::CHURCH_GROUPS);
    }

    public static function getEducationLevelOptions()
    {
        return array_map(function($key, $value) {
            return ['value' => $key, 'label' => $value];
        }, array_keys(self::EDUCATION_LEVELS), self::EDUCATION_LEVELS);
    }

    public static function getMembershipStatusOptions()
    {
        return array_map(function($key, $value) {
            return ['value' => $key, 'label' => $value];
        }, array_keys(self::MEMBERSHIP_STATUSES), self::MEMBERSHIP_STATUSES);
    }

    // Enhanced reporting scopes and methods
    public static function getActiveMembers()
    {
        return self::where('membership_status', 'active')->count();
    }
    
    public static function getInactiveMembers()
    {
        return self::where('membership_status', 'inactive')->count();
    }
    
    public static function getTransferredMembers()
    {
        return self::where('membership_status', 'transferred')->count();
    }
    
    public static function getDeceasedMembers()
    {
        return self::where('membership_status', 'deceased')->count();
    }
    
    public static function getMembersByChurch($church = null)
    {
        $query = self::query();
        if ($church) {
            $query->where('local_church', $church);
        }
        return $query->get()->groupBy('local_church');
    }
    
    public static function getMembersByGroup($group = null)
    {
        $query = self::query();
        if ($group) {
            $query->byGroup($group);
        }
        return $query->get()->groupBy('church_group');
    }
    
    public static function getMembersBySmallCommunity($community = null)
    {
        $query = self::query();
        if ($community) {
            $query->where('small_christian_community', $community);
        }
        return $query->get()->groupBy('small_christian_community');
    }
    
    public static function getMembersByAgeGroup($ageGroup = null)
    {
        $now = now();
        $query = self::query();
        
        if ($ageGroup) {
            switch($ageGroup) {
                case 'children':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) BETWEEN 0 AND 12', [$now]);
                    break;
                case 'youth':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) BETWEEN 13 AND 24', [$now]);
                    break;
                case 'adults':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) BETWEEN 25 AND 59', [$now]);
                    break;
                case 'seniors':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, ?) >= 60', [$now]);
                    break;
            }
        }
        
        return $query->get();
    }
    
    public static function getMembersByGender($gender = null)
    {
        $query = self::query();
        if ($gender) {
            $query->where('gender', ucfirst(strtolower($gender)));
        }
        return $query->get()->groupBy('gender');
    }
    
    public static function getMembersByEducationLevel($level = null)
    {
        $query = self::query();
        if ($level) {
            $query->where('education_level', $level);
        }
        return $query->get()->groupBy('education_level');
    }
    
    public static function getMembersByTribe($tribe = null)
    {
        $query = self::query();
        if ($tribe) {
            $query->where('tribe', $tribe);
        }
        return $query->get()->groupBy('tribe');
    }
    

    
    // Get downloadable baptism certificate data
    public function getBaptismCertificateData()
    {
        return [
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->format('F j, Y'),
            'baptism_date' => $this->baptism_date?->format('F j, Y'),
            'baptized_by' => $this->minister,
            'godparent' => $this->godparent,
            'local_church' => $this->local_church,
            'father_name' => $this->parent,
            'certificate_generated_date' => now()->format('F j, Y'),
        ];
    }
}
