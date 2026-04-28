<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'provider',
        'status',
        'amount_paid_cents',
        'progress_percent',
        'university',
        'university_id',
        'faculty_id',
        'study_year',
        'study_year_id',
        'study_term',
        'study_term_id',
        'subject_name',
        'receipt_storage_disk',
        'receipt_path',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'amount_paid_cents' => 'integer',
        'progress_percent' => 'integer',
        'university_id' => 'integer',
        'faculty_id' => 'integer',
        'study_year_id' => 'integer',
        'study_term_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
