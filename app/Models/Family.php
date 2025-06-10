<?php
// app/Models/Family.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function headOfFamily()
    {
        return $this->belongsTo(Member::class, 'head_of_family_id');
    }

    public function getFullNameAttribute()
    {
        return $this->family_name . ' Family';
    }

    public function scopeByDeanery($query, $deanery)
    {
        return $query->where('deanery', $deanery);
    }

    public function scopeByParish($query, $parish)
    {
        return $query->where('parish', $parish);
    }
}
