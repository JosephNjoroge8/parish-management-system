<?php
// filepath: app/Models/FamilyRelationship.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'member_id',
        'relationship_type',
        'primary_contact',
        'emergency_contact',
        'notes',
    ];

    protected $casts = [
        'primary_contact' => 'boolean',
        'emergency_contact' => 'boolean',
    ];

    const RELATIONSHIP_TYPES = [
        'head' => 'Head of Family',
        'spouse' => 'Spouse',
        'child' => 'Child',
        'parent' => 'Parent',
        'sibling' => 'Sibling',
        'grandparent' => 'Grandparent',
        'grandchild' => 'Grandchild',
        'uncle_aunt' => 'Uncle/Aunt',
        'nephew_niece' => 'Nephew/Niece',
        'cousin' => 'Cousin',
        'other' => 'Other',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function getRelationshipTypeNameAttribute(): string
    {
        return self::RELATIONSHIP_TYPES[$this->relationship_type] ?? ucfirst($this->relationship_type);
    }
}