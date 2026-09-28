<?php

namespace App\Models;

use App\Enums\StaffStatus;
use App\Models\Concerns\HasPublicUuid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Staff extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, HasPublicUuid, HasRoles, Notifiable, SoftDeletes;

    protected $table = 'staff';

    protected $guard_name = 'staff';

    protected $fillable = ['person_id', 'staff_number', 'password', 'status', 'password_changed_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['status' => StaffStatus::class, 'password' => 'hashed', 'email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'password_changed_at' => 'datetime'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function employmentDetail(): HasOne
    {
        return $this->hasOne(StaffEmploymentDetail::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'staff' && $this->status === StaffStatus::ACTIVE && $this->person !== null;
    }

    public function getFilamentName(): string
    {
        return $this->person?->full_name ?? $this->staff_number;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->person?->email;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->staff_number;
    }
}
