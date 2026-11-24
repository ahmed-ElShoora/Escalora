<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    public const ONLINE_TIMEOUT_SECONDS = 300;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'company_id',
        'workspace_id',
        'invitation_token',
        'invitation_expires_at',
        'email_verified_at',
        'is_available',
        'last_seen_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'invitation_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_available' => 'boolean',
            'last_seen_at' => 'datetime',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function supportConversations(): HasMany
    {
        return $this->hasMany(SupportConversation::class, 'agent_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isPendingInvitation(): bool
    {
        return $this->status === UserStatus::Pending;
    }

    public function hasValidInvitation(): bool
    {
        return $this->isPendingInvitation()
            && filled($this->invitation_token)
            && $this->invitation_expires_at !== null
            && $this->invitation_expires_at->isFuture();
    }

    public function isOnline(): bool
    {
        return $this->isAgent()
            && $this->isActive()
            && $this->is_available
            && $this->last_seen_at?->isAfter(now()->subSeconds(self::ONLINE_TIMEOUT_SECONDS));
    }
}
