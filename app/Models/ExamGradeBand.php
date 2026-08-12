<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamGradeBand extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'exam_id',
        'min_percent',
        'max_percent',
        'label',
        'label_en',
        'color',
        'sort_order',
    ];

    protected $appends = [
        'localized_label',
    ];

    protected $casts = [
        'min_percent' => 'float',
        'max_percent' => 'float',
        'sort_order' => 'integer',
    ];

    public function getLocalizedLabelAttribute(): string
    {
        return $this->bilingualString('label', 'label_en');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
