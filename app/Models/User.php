<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    public const TEACHER_APPROVAL_PENDING = 'pending';

    public const TEACHER_APPROVAL_APPROVED = 'approved';

    public const TEACHER_APPROVAL_REJECTED = 'rejected';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_DELETED = 'deleted';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'terms_accepted_at',
        'password',
        'role',
        'teacher_approval_status',
        'university',
        'study_year',
        'study_term',
        'university_id',
        'faculty_id',
        'study_year_id',
        'study_term_id',
        'sham_cash_code',
        'device_lock_enabled',
        'locked_device_id',
        'locked_device_at',
        'status',
        'account_lock_id',
        'frozen_at',
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
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
            'device_lock_enabled' => 'boolean',
            'locked_device_at' => 'datetime',
            'frozen_at' => 'datetime',
            'university_id' => 'integer',
            'faculty_id' => 'integer',
            'study_year_id' => 'integer',
            'study_term_id' => 'integer',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function isTeacherApproved(): bool
    {
        return strtolower((string) $this->role) !== 'teacher'
            || $this->teacher_approval_status === self::TEACHER_APPROVAL_APPROVED;
    }

    public function canTeachCourses(): bool
    {
        return strtolower((string) $this->role) === 'admin'
            || (strtolower((string) $this->role) === 'teacher' && $this->isTeacherApproved());
    }

    public function isAccountDeleted(): bool
    {
        return ($this->status ?? self::STATUS_ACTIVE) === self::STATUS_DELETED;
    }

    public function scopeAssignableAsTeacher(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('role', 'admin')
                ->orWhere(function (Builder $teacherQ): void {
                    $teacherQ->where('role', 'teacher')
                        ->where('teacher_approval_status', self::TEACHER_APPROVAL_APPROVED);
                });
        });
    }
}
