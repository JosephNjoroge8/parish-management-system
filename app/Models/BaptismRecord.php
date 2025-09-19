<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaptismRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_number',
        'member_id',
        
        // BAPTISM CARD PERSONAL INFORMATION - As specified
        'father_name', // fathers name
        'mother_name', // mothers name
        'tribe', // Tribe
        'birth_village', // born on (village)
        'county', // county
        'birth_date', // date
        'residence', // residence
        
        // BAPTISM INFORMATION - As specified
        'baptism_location', // BAPTISM: At
        'baptism_date', // Date
        'baptized_by', // baptized by
        'sponsor', // sponsor
        
        // EUCHARIST INFORMATION - As specified
        'eucharist_location', // EUCHARIST: At
        'eucharist_date', // Date
        
        // CONFIRMATION INFORMATION - As specified
        'confirmation_location', // CONFIRMATION: At
        'confirmation_date', // Date
        'confirmation_register_number', // Reg.NO
        'confirmation_number', // Conf.No
        
        // MARRIAGE INFORMATION - As specified
        'marriage_spouse', // MARRIAGE: Together with
        'marriage_location', // At
        'marriage_date', // Date
        'marriage_register_number', // Reg.NO
        'marriage_number', // Marr.NO
        
        // SYSTEM RELATIONSHIPS - To avoid data redundancy
        'baptism_sacrament_id',
        'eucharist_sacrament_id',
        'confirmation_sacrament_id',
        'marriage_sacrament_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'baptism_date' => 'date',
        'eucharist_date' => 'date',
        'confirmation_date' => 'date',
        'marriage_date' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function baptismSacrament(): BelongsTo
    {
        return $this->belongsTo(Sacrament::class, 'baptism_sacrament_id');
    }

    public function eucharistSacrament(): BelongsTo
    {
        return $this->belongsTo(Sacrament::class, 'eucharist_sacrament_id');
    }

    public function confirmationSacrament(): BelongsTo
    {
        return $this->belongsTo(Sacrament::class, 'confirmation_sacrament_id');
    }

    public function marriageSacrament(): BelongsTo
    {
        return $this->belongsTo(Sacrament::class, 'marriage_sacrament_id');
    }

    /**
     * Generate a unique record number for baptism records.
     */
    public static function generateRecordNumber(): string
    {
        $prefix = 'BAP';
        $year = date('Y');
        $lastRecord = self::where('record_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING(record_number, -5) AS UNSIGNED) DESC')
            ->first();
            
        $nextNumber = 1;
        if ($lastRecord) {
            $parts = explode('-', $lastRecord->record_number);
            $nextNumber = (int) end($parts) + 1;
        }
        
        return sprintf("{$prefix}-%s-%05d", $year, $nextNumber);
    }

    /**
     * Get all baptism card data formatted for display.
     */
    public function getBaptismCardDataAttribute(): array
    {
        return [
            'personal_info' => [
                'name' => $this->member ? $this->member->first_name . ' ' . $this->member->last_name : 'N/A',
                'father_name' => $this->father_name,
                'mother_name' => $this->mother_name,
                'tribe' => $this->tribe,
                'birth_village' => $this->birth_village,
                'county' => $this->county,
                'birth_date' => $this->birth_date?->format('F j, Y'),
                'residence' => $this->residence,
            ],
            'baptism_info' => [
                'location' => $this->baptism_location,
                'date' => $this->baptism_date?->format('F j, Y'),
                'baptized_by' => $this->baptized_by,
                'sponsor' => $this->sponsor,
            ],
            'eucharist_info' => [
                'location' => $this->eucharist_location,
                'date' => $this->eucharist_date?->format('F j, Y'),
            ],
            'confirmation_info' => [
                'location' => $this->confirmation_location,
                'date' => $this->confirmation_date?->format('F j, Y'),
                'register_number' => $this->confirmation_register_number,
                'confirmation_number' => $this->confirmation_number,
            ],
            'marriage_info' => [
                'spouse' => $this->marriage_spouse,
                'location' => $this->marriage_location,
                'date' => $this->marriage_date?->format('F j, Y'),
                'register_number' => $this->marriage_register_number,
                'marriage_number' => $this->marriage_number,
            ]
        ];
    }

    /**
     * Search scope for baptism records.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('father_name', 'like', "%{$search}%")
              ->orWhere('mother_name', 'like', "%{$search}%")
              ->orWhere('record_number', 'like', "%{$search}%")
              ->orWhere('baptism_location', 'like', "%{$search}%")
              ->orWhere('birth_village', 'like', "%{$search}%")
              ->orWhere('tribe', 'like', "%{$search}%")
              ->orWhereHas('member', function ($memberQuery) use ($search) {
                  $memberQuery->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Filter by baptism year.
     */
    public function scopeByBaptismYear($query, $year)
    {
        return $query->whereYear('baptism_date', $year);
    }

    /**
     * Filter by location.
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('baptism_location', 'like', "%{$location}%");
    }

    /**
     * Filter by tribe.
     */
    public function scopeByTribe($query, $tribe)
    {
        return $query->where('tribe', 'like', "%{$tribe}%");
    }
}
