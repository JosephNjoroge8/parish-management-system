<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Sacrament extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'sacrament_type',
        'sacrament_date',
        'location',
        'celebrant',
        'witness_1',
        'witness_2',
        'godparent_1',
        'godparent_2',
        'certificate_number',
        'book_number',
        'page_number',
        'notes',
        'recorded_by',
        'detailed_record_type',
        'detailed_record_id',
    ];

    protected $casts = [
        'sacrament_date' => 'date',
    ];

    // Only 3 sacraments as requested
    const SACRAMENT_TYPES = [
        'baptism' => 'Baptism',
        'confirmation' => 'Confirmation',
        'marriage' => 'Marriage'
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
    
    public function detailedRecord(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function baptismRecord()
    {
        return $this->hasOne(BaptismRecord::class, 'baptism_sacrament_id');
    }
    
    public function marriageRecord()
    {
        return $this->hasOne(MarriageRecord::class, 'sacrament_id');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('sacrament_type', $type);
    }

    public function scopeInYear($query, $year)
    {
        return $query->whereYear('sacrament_date', $year);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('sacrament_date', [$startDate, $endDate]);
        }
        
        if ($startDate) {
            return $query->where('sacrament_date', '>=', $startDate);
        }
        
        if ($endDate) {
            return $query->where('sacrament_date', '<=', $endDate);
        }
        
        return $query;
    }

    public function getSacramentTypeNameAttribute(): string
    {
        return self::SACRAMENT_TYPES[$this->sacrament_type] ?? ucfirst($this->sacrament_type);
    }

    // Helper method to get sacrament type options for forms
    public static function getSacramentTypeOptions()
    {
        return array_map(function($key, $value) {
            return ['value' => $key, 'label' => $value];
        }, array_keys(self::SACRAMENT_TYPES), self::SACRAMENT_TYPES);
    }

    // Get members by sacrament type
    public static function getMembersBySacrament($type)
    {
        return self::with(['member:id,first_name,middle_name,last_name,date_of_birth,gender,phone,email,local_church,church_group'])
            ->where('sacrament_type', $type)
            ->orderBy('sacrament_date', 'desc')
            ->get();
    }

    // Get sacrament statistics
    public static function getStatistics()
    {
        $stats = [];
        
        foreach (self::SACRAMENT_TYPES as $type => $name) {
            $stats[$type] = [
                'name' => $name,
                'total' => self::where('sacrament_type', $type)->count(),
                'this_year' => self::where('sacrament_type', $type)
                    ->whereYear('sacrament_date', date('Y'))
                    ->count(),
                'this_month' => self::where('sacrament_type', $type)
                    ->whereYear('sacrament_date', date('Y'))
                    ->whereMonth('sacrament_date', date('m'))
                    ->count(),
            ];
        }
        
        return $stats;
    }
}
