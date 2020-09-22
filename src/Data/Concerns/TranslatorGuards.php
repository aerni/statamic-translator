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
}
