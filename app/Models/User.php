<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'is_admin', // Simple admin flag
        'email_verified_at',
        'last_login_at',
        'profile_photo_path',
        'created_by',
        'date_of_birth',
        'gender',
        'address',
        'occupation',
        'emergency_contact',
        'emergency_phone',
        'notes',
        'role', // Fallback role column
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Check if user has specific role - simplified for single admin system
     */
    // SIMPLIFIED: No role/permission system - just admin flag
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->isSuperAdminByEmail();
    }

    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        // Return true for admin users, false otherwise
        return $this->isSuperAdminByEmail();
    }

    /**
     * Check if user is admin by database flag or email
     */
    public function isSuperAdminByEmail(): bool
    {
        // First priority: Check is_admin column if it exists
        if (isset($this->attributes['is_admin'])) {
            return (bool) $this->attributes['is_admin'];
        }
        
        // Second priority: Check by admin email patterns
        $adminEmails = [
            'admin@parish.com',
            'admin@parishmanagement.com',
            'administrator@parish.com',
        ];
        
        return in_array($this->email, $adminEmails) || 
               str_contains(strtolower($this->email), 'admin') ||
               $this->id === 1; // First user is admin
    }

    // Simplified role retrieval for single admin system
    public function getRoles()
    {
        // In simplified system, return admin role for admin users
        if ($this->isSuperAdminByEmail()) {
            return collect([(object)['id' => 1, 'name' => 'admin']]);
        }
        
        return collect([]);
    }

    // SIMPLIFIED: Admin by database flag only  
    public function getIsSuperAdminAttribute(): bool
    {
        return (bool) $this->attributes['is_admin'] ?? false;
    }

    public function getCanManageUsersAttribute(): bool
    {
        return (bool) $this->attributes['is_admin'] ?? false;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('email', [
            'admin@parish.com',
            'superadmin@parish.com', 
            'administrator@parish.com'
        ]);
    }

    // Methods
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function hasAccessTo(string $module): bool
    {
        return $this->hasPermissionTo("access {$module}") || $this->hasRole('super-admin');
    }

    public function canManage(string $resource): bool
    {
        return $this->hasPermissionTo("manage {$resource}") || $this->hasRole('super-admin');
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}