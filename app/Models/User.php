<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'first_name',
        'last_name',
        'phone',
        'portal_role',
        'role_id',
        'is_admin',
        'password_reset_required',
        'status',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'password_reset_required' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * RELATIONSHIPS
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function scope()
    {
        return $this->hasOne(UserScope::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function council()
    {
        return $this->belongsTo(DistrictCouncil::class);
    }

    public function authenticationAuditLogs()
    {
        return $this->hasMany(AuthenticationAuditLog::class);
    }

    public function governanceAuditLogs()
    {
        return $this->hasMany(GovernanceAuditLog::class);
    }

    public function auditLogsAsAdmin()
    {
        return $this->hasMany(GovernanceAuditLog::class, 'admin_id');
    }

    /**
     * HELPERS - Role checks
     */
    public function hasRole($roleCode)
    {
        return $this->role && $this->role->code === $roleCode;
    }

    public function isAdmin()
    {
        return $this->portal_role === 'admin' || $this->hasRole(Role::CODE_ADMIN);
    }

    public function isRegionalOfficer()
    {
        return $this->hasRole(Role::CODE_REGIONAL_OFFICER);
    }

    public function isDistrictDataEntryOfficer()
    {
        return $this->hasRole(Role::CODE_DISTRICT_DATA_ENTRY_OFFICER);
    }

    public function isDistrictSupervisor()
    {
        return $this->hasRole(Role::CODE_DISTRICT_SUPERVISOR);
    }

    public function isSchoolRegistrar()
    {
        return $this->hasRole(Role::CODE_SCHOOL_REGISTRAR);
    }

    /**
     * HELPERS - Status checks
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended()
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * HELPERS - Role code retrievers
     */
    public function roleCode(): ?string
    {
        return $this->role?->code;
    }

    public function roleName(): ?string
    {
        return $this->role?->name;
    }

    /**
     * HELPERS - Scope checks
     */
    public function getScopeType()
    {
        return $this->scope?->scope_type;
    }

    public function getScopeId()
    {
        return $this->scope?->scope_id;
    }

    public function getDistrictId()
    {
        if ($this->getScopeType() === UserScope::SCOPE_DISTRICT) {
            return $this->getScopeId();
        }
        return null;
    }

    public function getRegionId()
    {
        if ($this->getScopeType() === UserScope::SCOPE_REGION) {
            return $this->getScopeId();
        }
        return null;
    }

    public function getSchoolId()
    {
        if ($this->getScopeType() === UserScope::SCOPE_SCHOOL) {
            return $this->getScopeId();
        }
        return null;
    }

    /**
     * HELPERS - Scope access checks
     */
    public function canAccessRegion($regionId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->getRegionId() === (int) $regionId;
    }

    public function canAccessDistrict($districtId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->getDistrictId() === (int) $districtId;
    }

    public function canAccessSchool($schoolId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->getSchoolId() === (int) $schoolId;
    }

    /**
     * Filament: Authorize access to panel
     * 
     * Only active ADMINISTRATORS can access the admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isActive() && $this->isAdmin();
        }

        return $this->isActive();
    }

    /**
     * Filament: Get user avatar URL
     */
    public function getFilamentAvatarUrl(): ?string
    {
        // Can be extended to load from avatar_url column or storage
        // For now, let Filament use ui-avatars.com
        return null;
    }

    /**
     * Filament: Get user display name
     */
    public function getFilamentName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
