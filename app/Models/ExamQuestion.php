<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    use HasBilingualStrings;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_SHORT_ANSWER = 'short_answer';

    protected $fillable = [
        'exam_id',
        'type',
        'prompt',
        'prompt_en',
        'points',
        'sort_order',
        'explanation',
        'explanation_en',
        'accepted_answers',
        'case_sensitive',
    ];

    protected $appends = [
        'localized_prompt',
        'localized_explanation',
    ];

    protected $casts = [
        'points' => 'float',
        'sort_order' => 'integer',
        'accepted_answers' => 'array',
        'case_sensitive' => 'boolean',
    ];

    public function getLocalizedPromptAttribute(): string
    {
        return $this->bilingualString('prompt', 'prompt_en');
    }

    public function getLocalizedExplanationAttribute(): string
    {
        return $this->bilingualString('explanation', 'explanation_en');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ExamQuestionOption::class, 'question_id')->orderBy('sort_order')->orderBy('id');
    }
}
