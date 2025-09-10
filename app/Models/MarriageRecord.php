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
        'banas_number',
        'banas_church_1',
        'banas_date_1',
        'banas_church_2',
        'banas_date_2',
        'dispensation_from',
        'dispensation_given_by',
        'dispensation_impediment',
        'dispensation_date',
        'marriage_date',
        'marriage_church',
        'district',
        'province',
        'presence_of',
        'delegated_by',
        'delegation_date',
        'male_witness_name',
        'male_witness_father',
        'male_witness_clan',
        'female_witness_name',
        'female_witness_father',
        'female_witness_clan',
        'civil_marriage_certificate_number',
        'other_documents',
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
        'husband_parent_consent' => 'boolean',
        'wife_parent_consent' => 'boolean',
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
    
    // Generate unique record number
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
}
