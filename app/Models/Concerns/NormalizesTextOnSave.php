<?php

namespace App\Models\Concerns;

use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Esegue la normalizzazione testo prima degli altri callback saving del modello
 * (registrato dal boot del trait, tipicamente prima di booted() del modello).
 *
 * Override:
 * - scalarOptionalTextAttributes(): stringa vuota dopo trim → null
 * - scalarRequiredTextAttributes(): trim + Zs; stringa vuota resta ''
 * - jsonArrayTextNormalizableAttributes(): array/JSON, solo foglie stringa
 */
trait NormalizesTextOnSave
{
    protected static function bootNormalizesTextOnSave(): void
    {
        static::saving(function (Model $model) {
            if (! $model instanceof self) {
                return;
            }

            $model->normalizeTranslatableAttributes();
            $model->normalizeScalarTextAttributes();
            $model->normalizeJsonArrayTextAttributes();
        });
    }

    protected function normalizeTranslatableAttributes(): void
    {
        if (! method_exists($this, 'getTranslatableAttributes')) {
            return;
        }

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $translations = $this->getTranslations($attribute);
            if ($translations === []) {
                continue;
            }

            foreach ($translations as $locale => $value) {
                if (is_string($value)) {
                    $translations[$locale] = TextNormalizer::normalize($value) ?? '';
                }
            }

            $this->setTranslations($attribute, $translations);
        }
    }

    protected function normalizeScalarTextAttributes(): void
    {
        foreach ($this->scalarOptionalTextAttributes() as $attribute) {
            if (! array_key_exists($attribute, $this->getAttributes())) {
                continue;
            }

            $value = $this->getAttribute($attribute);
            if ($value !== null && ! is_string($value)) {
                continue;
            }

            $this->setAttribute(
                $attribute,
                TextNormalizer::normalizeOptional(is_string($value) ? $value : null)
            );
        }

        foreach ($this->scalarRequiredTextAttributes() as $attribute) {
            if (! array_key_exists($attribute, $this->getAttributes())) {
                continue;
            }

            $value = $this->getAttribute($attribute);
            if ($value === null) {
                continue;
            }
            if (! is_string($value)) {
                continue;
            }

            $this->setAttribute($attribute, TextNormalizer::normalize($value) ?? '');
        }
    }

    protected function normalizeJsonArrayTextAttributes(): void
    {
        foreach ($this->jsonArrayTextNormalizableAttributes() as $attribute) {
            $value = $this->getAttribute($attribute);
            if (! is_array($value)) {
                continue;
            }

            $this->setAttribute($attribute, TextNormalizer::normalizeArrayLeaves($value));
        }
    }

    /** @return list<string> */
    protected function scalarOptionalTextAttributes(): array
    {
        return [];
    }

    /** @return list<string> */
    protected function scalarRequiredTextAttributes(): array
    {
        return [];
    }

    /** @return list<string> */
    protected function jsonArrayTextNormalizableAttributes(): array
    {
        return [];
    }
}
