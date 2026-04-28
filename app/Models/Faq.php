<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'question',
        'question_en',
        'answer',
        'answer_en',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getLocalizedQuestionAttribute(): string
    {
        return $this->bilingualString('question', 'question_en');
    }

    public function getLocalizedAnswerAttribute(): string
    {
        return $this->bilingualString('answer', 'answer_en');
    }
}
