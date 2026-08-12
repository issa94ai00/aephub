<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestionOption extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'question_id',
        'label',
        'label_en',
        'is_correct',
        'sort_order',
    ];

    protected $appends = [
        'localized_label',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getLocalizedLabelAttribute(): string
    {
        return $this->bilingualString('label', 'label_en');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
}
