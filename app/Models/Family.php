<?php
// app/Models/Family.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_name',
        'address',
        'phone',
        'email',
        'deanery',
        'parish',
        'head_of_family_id',
        'family_code',
        'parish_section',
        'created_by',
    ];

    /**
     * Get the head of family member
     */
    public function headOfFamily(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'head_of_family_id');
    }

    /**
     * Get all members in this family
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get the user who created this family
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
