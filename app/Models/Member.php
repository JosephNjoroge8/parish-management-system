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
        'emergency_contact',
        'emergency_phone',
        'local_church',
        'church_group',
        'membership_status',
        'membership_date',
        'baptism_date',
        'confirmation_date',
        'matrimony_status',
        'occupation',
        'education_level',
        'family_id',
        'parent',
        'sponsor',
        'minister',
        'tribe',
        'clan',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'membership_date' => 'date',
        'baptism_date' => 'date',
        'confirmation_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function children()
    {
        return $this->hasMany(Member::class, 'parent_id');
    }

    public function sponsor()
    {
        return $this->belongsTo(Member::class, 'sponsor_id');
    }

    public function minister()
    {
        return $this->belongsTo(Member::class, 'minister_id');
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

    // Mutators
    public function setGenderAttribute($value)
    {
        $this->attributes['gender'] = strtolower($value);
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
                  ->orWhere('id_number', 'like', "%{$search}%");
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
            return $query->where('church_group', $group);
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
            return $query->where('gender', $gender);
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
}
