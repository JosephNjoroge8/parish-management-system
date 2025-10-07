<?php

// app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin', // Simple admin flag
        'is_active', // Active status
        'phone', // Phone number
        'last_login_at', // Last login timestamp
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Relationships - removed unused relationships

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
            return collect([(object) ['id' => 1, 'name' => 'admin']]);
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
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    // Methods
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

    /**
     * Update the user's last login timestamp
     */
    public function updateLastLogin(): void
    {
        try {
            $this->last_login_at = now();
            $this->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle case where last_login_at column doesn't exist in production
            if (str_contains($e->getMessage(), 'last_login_at') && str_contains($e->getMessage(), 'Unknown column')) {
                \Log::warning('last_login_at column not found in users table - skipping update', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);

                return;
            }
            // Re-throw if it's a different error
            throw $e;
        }
    }
}
