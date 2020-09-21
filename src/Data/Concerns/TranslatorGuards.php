<?php

namespace Aerni\Translator\Data\Concerns;

use Facades\Aerni\Translator\Contracts\TranslationService;
use Aerni\Translator\Utils;

trait TranslatorGuards
{
    protected function shouldProcessData(): bool
    {
        if ($this->shouldProcessEntry()) {
            return true;
        }

        if ($this->shouldProcessGlobalSet()) {
            return true;
        }

        return false;
    }

    protected function shouldProcessEntry(): bool
    {
        if ($this->contentType($this->entry) !== 'Entry') {
            return false;
        }

        if ($this->entry->isRoot()) {
            return false;
        }

        if ($this->targetSite !== $this->entry->locale()) {
            return false;
        }

        return true;
    }

    protected function shouldProcessGlobalSet(): bool
    {
        if ($this->contentType($this->entry) !== 'GlobalSet') {
            return false;
        }

        if ($this->entry->localizations()->get($this->targetSite)->origin() === null) {
            return false;
        }

        return true;
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
