<?php
// filepath: app/Models/Tithe.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tithe extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'amount',
        'tithe_type',
        'payment_method',
        'date_given',
        'purpose',
        'receipt_number',
        'reference_number',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_given' => 'date',
    ];

    const TITHE_TYPES = [
        'tithe' => 'Tithe (10%)',
        'offering' => 'Offering',
        'special_collection' => 'Special Collection',
        'donation' => 'Donation',
        'thanksgiving' => 'Thanksgiving',
        'project_contribution' => 'Project Contribution',
    ];

    const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'check' => 'Check',
        'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Credit/Debit Card',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('tithe_type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_given', [$startDate, $endDate]);
    }

    public function scopeInYear($query, $year)
    {
        return $query->whereYear('date_given', $year);
    }

    public function scopeInMonth($query, $year, $month)
    {
        return $query->whereYear('date_given', $year)
                    ->whereMonth('date_given', $month);
    }

    public function getTitheTypeNameAttribute(): string
    {
        return self::TITHE_TYPES[$this->tithe_type] ?? ucfirst($this->tithe_type);
    }

    public function getPaymentMethodNameAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }
}