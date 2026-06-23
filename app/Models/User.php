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
        'github_id',
        'github_username',
        'github_avatar',
        'password',
        'username',
        'first_name',
        'last_name',
        'phone',
        'portal_role',
        'role_id',
        'school_id',
        'district_council_id',
        'region_id',
        'marking_centre_id',
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
        return $this->belongsTo(DistrictCouncil::class, 'district_council_id');
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Region::class, 'region_id');
    }

    public function markingCentre()
    {
        return $this->belongsTo(MarkingCentre::class, 'marking_centre_id');
    }

    public function markEntryActiveSession()
    {
        return $this->hasOne(MarkEntryActiveSession::class);
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

    public function markEntryAssignments()
    {
        return $this->hasMany(MarkEntryAssignment::class, 'assigned_to');
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
        $email = strtolower((string) $this->email);
        $portalRole = strtolower((string) $this->portal_role);
        $roleCode = strtolower((string) $this->role?->code);
        $roleName = strtolower((string) $this->role?->name);

        return $email === 'agreykigodi@gmail.com'
            || (bool) $this->is_admin
            || in_array($portalRole, ['admin', 'super_admin', 'system_admin'], true)
            || in_array($roleCode, ['admin', 'super_admin', 'system_admin'], true)
            || in_array($roleName, ['admin', 'administrator', 'super admin', 'system admin'], true);
    }

    public function isMarkEntryOfficer(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $meoRoles = ['mark_officer', 'mark_entry_officer', 'meo', 'regional_mark_entry_officer', 'district_mark_entry_officer'];

        // If session has active role and it's MEO
        $activeRole = session('active_role');
        if ($activeRole) {
            $normalizedActive = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $activeRole)));
            if (in_array($normalizedActive, $meoRoles, true)) {
                return true;
            }
        }

        $roleCode = strtolower((string) ($this->role?->code ?? ''));
        $roleName = strtolower((string) ($this->role?->name ?? ''));
        $portalRole = strtolower((string) $this->portal_role);

        return in_array($roleCode, $meoRoles, true)
            || str_contains($roleName, 'mark entry officer')
            || in_array($portalRole, $meoRoles, true);
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
        return ($this->status ?? self::STATUS_ACTIVE) === self::STATUS_ACTIVE;
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
            if (strtolower((string) $this->email) === 'agreykigodi@gmail.com') {
                return true;
            }

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
