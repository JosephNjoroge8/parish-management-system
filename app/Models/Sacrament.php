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

    const SACRAMENT_TYPES = [
        'baptism' => 'Baptism',
        'eucharist' => 'First Holy Communion',
        'confirmation' => 'Confirmation',
        'reconciliation' => 'Reconciliation',
        'anointing' => 'Anointing of the Sick',
        'marriage' => 'Marriage',
        'holy_orders' => 'Holy Orders'
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

    public function getSacramentTypeNameAttribute(): string
    {
        return self::SACRAMENT_TYPES[$this->sacrament_type] ?? ucfirst($this->sacrament_type);
    }
}
