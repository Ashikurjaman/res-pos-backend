<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BANNED = 'banned';

    // Protected role name
    const ROLE_SUPER_ADMIN = 'superadmin';

    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'status',
        'outlet_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==================== ACCESSORS ====================

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    // Backward-compatible: first assigned role name
    public function getRoleAttribute()
    {
        return $this->getRoleNames()->first();
    }

    public function getRoleLabelAttribute()
    {
        return ucfirst($this->role ?? '');
    }

    // ==================== RELATIONSHIPS ====================

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByOutlet($query, $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    // ==================== ROLE CHECKS (backward-compatible wrappers) ====================

    public function isSuperAdmin()
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    public function isAuthor()
    {
        return $this->hasRole('author');
    }

    public function isStore()
    {
        return $this->hasRole('store');
    }

    public function isKitchen()
    {
        return $this->hasRole('kitchen');
    }

    public function isUser()
    {
        return $this->hasRole('user');
    }

    // ==================== STATUS CHECKS ====================

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBanned()
    {
        return $this->status === self::STATUS_BANNED;
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_BANNED => 'Banned',
        ];
    }
}
