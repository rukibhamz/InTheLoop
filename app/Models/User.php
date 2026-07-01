<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'password',
        'azure_object_id',
        'auth_method',
        'department',
        'shared_mailbox_email',
        'bio',
        'notification_preferences',
        'is_ldap',
        'is_approver',
        'is_admin',
        'is_active',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_ldap' => 'boolean',
            'is_approver' => 'boolean',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * @return array<string, array{email: bool, app: bool}>
     */
    public function defaultNotificationPreferences(): array
    {
        return [
            'new_report_assigned' => ['email' => true, 'app' => true],
            'comment_replies' => ['email' => true, 'app' => false],
            'approval_required' => ['email' => true, 'app' => true],
            'status_changes' => ['email' => false, 'app' => true],
            'security_alerts' => ['email' => true, 'app' => true],
            'weekly_digest' => ['email' => false, 'app' => false],
        ];
    }

    public function notificationPreferences(): array
    {
        return array_replace_recursive(
            $this->defaultNotificationPreferences(),
            $this->notification_preferences ?? []
        );
    }

    public function notificationEnabled(string $key, string $channel): bool
    {
        return (bool) ($this->notificationPreferences()[$key][$channel] ?? false);
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (! filled($user->shared_mailbox_email) && filled($user->email)) {
                $user->shared_mailbox_email = $user->email;
            }
        });
    }

    public function effectiveMailboxEmail(): ?string
    {
        return $this->shared_mailbox_email ?: $this->email;
    }

    /**
     * Apply an Azure/Graph primary mail address when the mailbox is unset or still matches login email.
     */
    public function syncSharedMailboxFromAzure(string $azureMail): void
    {
        if (! filled($azureMail)) {
            return;
        }

        if (
            ! filled($this->shared_mailbox_email)
            || strcasecmp($this->shared_mailbox_email, $this->email) === 0
        ) {
            $this->shared_mailbox_email = $azureMail;
        }
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isApprover(): bool
    {
        return (bool) $this->is_approver || $this->isAdmin();
    }
}
