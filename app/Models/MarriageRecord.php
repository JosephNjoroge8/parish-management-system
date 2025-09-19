<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_number',
        // Husband information - comprehensive as specified
        'husband_name',
        'husband_father_name',
        'husband_mother_name',
        'husband_tribe',
        'husband_clan',
        'husband_birth_place',
        'husband_domicile',
        'husband_baptized_at',
        'husband_baptism_date',
        'husband_widower_of',
        'husband_parent_consent',
        // Wife information - comprehensive as specified
        'wife_name',
        'wife_father_name',
        'wife_mother_name',
        'wife_tribe',
        'wife_clan',
        'wife_birth_place',
        'wife_domicile',
        'wife_baptized_at',
        'wife_baptism_date',
        'wife_widow_of',
        'wife_parent_consent',
        // Banas information - as specified
        'banas_number',
        'banas_church_1',
        'banas_date_1',
        'banas_church_2',
        'banas_date_2',
        'dispensation_from',
        'dispensation_given_by',
        // Dispensation information - as specified
        'dispensation_impediment',
        'dispensation_authority',
        'dispensation_date',
        // Marriage contract information - as specified
        'marriage_date',
        'marriage_month',
        'marriage_year',
        'marriage_church',
        'district',
        'province',
        'presence_of',
        'delegated_by',
        'delegation_date',
        // Signatures - as specified
        'husband_signature',
        'wife_signature',
        // Witness information - comprehensive as specified
        'male_witness_full_name',
        'male_witness_father',
        'male_witness_clan',
        'female_witness_full_name',
        'female_witness_father',
        'female_witness_clan',
        'male_witness_signature',
        'female_witness_signature',
        // Additional documents and signatures - as specified
        'other_documents',
        'parish_priest_signature',
        'civil_marriage_certificate_number',
        'parish_stamp',
        // System relationships
        'parish_priest_id',
        'husband_id',
        'wife_id',
        'sacrament_id',
    ];

    protected $casts = [
        'husband_baptism_date' => 'date',
        'wife_baptism_date' => 'date',
        'banas_date_1' => 'date',
        'banas_date_2' => 'date',
        'dispensation_date' => 'date',
        'marriage_date' => 'date',
        'delegation_date' => 'date',
        'husband_parent_consent' => 'string', // Enum: Yes/No
        'wife_parent_consent' => 'string', // Enum: Yes/No
    ];

    public function husband(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'husband_id');
    }

    public function wife(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'wife_id');
    }

    public function sacrament(): BelongsTo
    {
        return $this->belongsTo(Sacrament::class, 'sacrament_id');
    }

    public function parishPriest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parish_priest_id');
    }
    
    // Generate unique record number for marriage records
    public static function generateRecordNumber(): string
    {
        $prefix = 'MAR';
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

    // Accessor for full marriage location details
    public function getFullMarriageLocationAttribute(): string
    {
        $location = $this->marriage_church;
        if ($this->district) {
            $location .= ', District of ' . $this->district;
        }
        if ($this->province) {
            $location .= ', Province of ' . $this->province;
        }
        return $location;
    }

    // Accessor for witness information
    public function getWitnessesInfoAttribute(): array
    {
        return [
            'male' => sprintf(
                '%s, son of %s, %s clan',
                $this->male_witness_full_name ?? '',
                $this->male_witness_father ?? '',
                $this->male_witness_clan ?? ''
            ),
            'female' => sprintf(
                '%s, daughter of %s, %s clan',
                $this->female_witness_full_name ?? '',
                $this->female_witness_father ?? '',
                $this->female_witness_clan ?? ''
            )
        ];
    }

    // Accessor for husband full information
    public function getHusbandFullInfoAttribute(): string
    {
        return sprintf(
            '%s, son of %s and %s, %s clan, born in %s, domicile %s',
            $this->husband_name ?? '',
            $this->husband_father_name ?? '',
            $this->husband_mother_name ?? '',
            $this->husband_clan ?? '',
            $this->husband_birth_place ?? '',
            $this->husband_domicile ?? ''
        );
    }

    // Accessor for wife full information
    public function getWifeFullInfoAttribute(): string
    {
        return sprintf(
            '%s, daughter of %s and %s, %s clan, born in %s, domicile %s',
            $this->wife_name ?? '',
            $this->wife_father_name ?? '',
            $this->wife_mother_name ?? '',
            $this->wife_clan ?? '',
            $this->wife_birth_place ?? '',
            $this->wife_domicile ?? ''
        );
    }

    // Scope for searching records
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('husband_name', 'like', "%{$search}%")
              ->orWhere('wife_name', 'like', "%{$search}%")
              ->orWhere('record_number', 'like', "%{$search}%")
              ->orWhere('marriage_church', 'like', "%{$search}%");
        });
    }

    // Scope for filtering by year
    public function scopeByYear($query, $year)
    {
        return $query->whereYear('marriage_date', $year);
    }

    // Scope for filtering by church
    public function scopeByChurch($query, $church)
    {
        return $query->where('marriage_church', 'like', "%{$church}%");
    }
}
