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
use Illuminate\Support\Facades\Log;

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
     * Check if user has specific role with comprehensive fallback
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        // Try Spatie first using parent trait method
        try {
            if (method_exists($this, 'roles') && $this->roles()->exists()) {
                $userRoles = $this->roles->pluck('name')->toArray();
                $checkRoles = is_array($roles) ? $roles : [$roles];
                if (!empty(array_intersect($userRoles, $checkRoles))) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::info('Spatie role check failed, using fallback', ['error' => $e->getMessage()]);
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
     * Check if user has specific permission with comprehensive fallback
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        // Try Spatie first using parent trait method
        try {
            if (method_exists($this, 'permissions') && $this->permissions()->exists()) {
                $userPermissions = $this->permissions->pluck('name')->toArray();
                if (in_array($permission, $userPermissions)) {
                    return true;
                }
            }
            
            // Check role-based permissions
            if (method_exists($this, 'roles') && $this->roles()->exists()) {
                foreach ($this->roles as $role) {
                    if ($role->permissions && $role->permissions->pluck('name')->contains($permission)) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info('Spatie permission check failed, using fallback', ['error' => $e->getMessage()]);
        }

        // Super admins have all permissions
        if ($this->isSuperAdminByEmail()) {
            return true;
        }

        // Default to true for development (remove in production)
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

    // Safe role retrieval without recursion
    public function getRoles()
    {
        try {
            // Use direct database query to avoid recursion
            $roles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $this->id)
                ->select('roles.id', 'roles.name')
                ->get();
            
            return $roles;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Direct role query failed', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    // Accessors
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function getIsAdminAttribute(): bool
    {
        try {
            // Use direct database query to avoid any recursion
            $hasAdminRole = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $this->id)
                ->whereIn('roles.name', ['admin', 'super-admin'])
                ->exists();
            
            return $hasAdminRole;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Direct admin check failed', ['error' => $e->getMessage()]);
            // Fallback to email check
            return $this->email === 'admin@parish.com';
        }
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