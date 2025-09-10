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
        'father_name',
        'mother_name',
        'tribe',
        'birth_village',
        'county',
        'birth_date',
        'residence',
        'baptism_location',
        'baptism_date',
        'baptized_by',
        'sponsor',
        'eucharist_location',
        'eucharist_date',
        'confirmation_location',
        'confirmation_date',
        'confirmation_number',
        'confirmation_register_number',
        'marriage_spouse',
        'marriage_location',
        'marriage_date',
        'marriage_register_number',
        'marriage_number',
        'member_id',
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
    
    // Generate unique record number
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
}
