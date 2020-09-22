<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Data\BasicTranslator;
use Aerni\Translator\Support\RequestValidator;
use Illuminate\Support\Collection;

class EntryTranslator extends BasicTranslator
{
    protected function ensureCanProcess(): self
    {
        RequestValidator::canProcessEntry($this->entry, $this->site);

        return $this;
    }

    protected function translate(): void
    {
        $this->entry->data($this->translatedData())
            ->slug($this->slug());
    }

    protected function rootData(): Collection
    {
        return $this->entry->root()->data();
    }

    protected function localizedData(): Collection
    {
        return $this->entry->data();
    }
}
