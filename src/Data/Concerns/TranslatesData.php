<?php

namespace Aerni\Translator\Data\Concerns;

use Aerni\Translator\Support\Utils;
use Facades\Aerni\Translator\Contracts\TranslationService;
use Statamic\Facades\Site;
use Statamic\Support\Str;

trait TranslatesData
{
    protected function translatedData(): array
    {
        return Utils::array_map_recursive(
            $this->dataToTranslate(),
            function ($value, $key) {
                return $this->translateValue($value, $key);
            }
        );
    }

    /**
     * Translate a given string value.
     *
     * @param mixed $value
     * @param string $key
     * @return mixed
     */
    protected function translateValue($value, string $key)
    {
        if (! $this->isTranslatableKeyValuePair($value, $key)) {
            return $value;
        }

        if (Utils::isHtml($value)) {
            return TranslationService::translateText($value, $this->targetLanguage(), 'html');
        }

        return TranslationService::translateText($value, $this->targetLanguage(), 'text');
    }

    /**
     * Get the language for translation.
     *
     * @return string
     */
    protected function targetLanguage(): string
    {
        return Site::get($this->site)->shortLocale();
    }

    protected function slug(): string
    {
        $slug = $this->entry->slug();

        if (! array_key_exists('slug', $this->translatableFields())) {
            return $slug;
        }

        return TranslationService::translateText(Str::deslugify($slug), $this->targetLanguage(), 'text');
    }

    /**
     * Check if a key-value pair should be translated.
     *
     * @param mixed $value
     * @param string $key
     * @return bool
     */
    protected function isTranslatableKeyValuePair($value, string $key): bool
    {
        if (empty($value)) {
            return false;
        }

        if (is_numeric($value)) {
            return false;
        }

        if (is_bool($value)) {
            return false;
        }

        // Skip 'type: $value', where $value is a Bard/Replicator set key.
        if ($key === 'type' && Utils::array_key_exists_recursive($value, $this->fieldKeys()['setKeys'])) {
            return false;
        }

        // Skip if $key doesn't exists in the fieldset.
        if (! Utils::array_key_exists_recursive($key, $this->fieldKeys()['allKeys']) && ! is_numeric($key)) {
            return false;
        }

        // Skip if $value is in the target locale.
        if (TranslationService::detectLanguage($value) === $this->targetLanguage()) {
            return false;
        }

        return true;
    }
}
