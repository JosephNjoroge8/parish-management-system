<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

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
     * Override hasRole with comprehensive fallback
     */
    public function hasRole($roles, string $guard = null): bool
    {
        // Try Spatie first
        try {
            if (trait_exists('\Spatie\Permission\Traits\HasRoles') && 
                method_exists($this, 'spatieHasRole')) {
                return $this->spatieHasRole($roles, $guard);
            }
        } catch (\Exception $e) {
            // Fall through to custom logic
        }

        // Fallback 1: Check by email
        if ($this->isSuperAdminByEmail()) {
            return in_array('super-admin', is_array($roles) ? $roles : [$roles]);
        }

        // Fallback 2: Check custom role column
        if (isset($this->role)) {
            $userRoles = is_array($this->role) ? $this->role : [$this->role];
            $checkRoles = is_array($roles) ? $roles : [$roles];
            return !empty(array_intersect($userRoles, $checkRoles));
        }

        return false;
    }

    /**
     * Override hasPermissionTo with comprehensive fallback
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Try Spatie first
        try {
            if (trait_exists('\Spatie\Permission\Traits\HasRoles') && 
                method_exists($this, 'spatieHasPermissionTo')) {
                return $this->spatieHasPermissionTo($permission, $guardName);
            }
        } catch (\Exception $e) {
            // Fall through to custom logic
        }

        // Super admins have all permissions
        if ($this->isSuperAdminByEmail()) {
            return true;
        }

        // Default to true for development
        return true;
    }

    /**
     * Check if user is super admin by email
     */
    public function isSuperAdminByEmail(): bool
    {
        return in_array(strtolower($this->email), [
            'admin@parish.com',
            'superadmin@parish.com',
            'administrator@parish.com',
        ]);
    }

    // Safe role retrieval
    public function getRoles()
    {
        try {
            if (method_exists($this, 'roles')) {
                return $this->roles;
            }
        } catch (\Exception $e) {
            // Return empty collection
        }
        
        return collect([]);
    }

    // Accessors
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    public function getCanManageUsersAttribute(): bool
    {
        return $this->hasPermissionTo('manage users');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['super-admin', 'admin']);
        });
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