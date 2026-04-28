<?php

namespace App\Models\Concerns;

trait HasBilingualStrings
{
    /**
     * Arabic (or primary) column first; English nullable column second.
     * When locale is `en` and the English value is non-empty, it is returned; otherwise the primary value.
     */
    protected function bilingualString(string $primaryColumn, string $englishColumn, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en') {
            $en = $this->getAttribute($englishColumn);
            if (is_string($en) && trim($en) !== '') {
                return $en;
            }
        }

        $primary = $this->getAttribute($primaryColumn);

        return $primary === null ? '' : (string) $primary;
    }
}
