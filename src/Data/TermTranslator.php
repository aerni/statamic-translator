<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Support\RequestValidator;
use Illuminate\Support\Collection;

class TermTranslator extends BasicTranslator
{
    protected function ensureCanProcess(): self
    {
        RequestValidator::canProcessTerm($this->entry, $this->site);

        return $this;
    }

    protected function translate(): void
    {
        $this->entry->in($this->site)
            ->data($this->translatedData());
    }

    protected function rootData(): Collection
    {
        return $this->entry->inDefaultLocale()->data();
    }

    protected function localizedData(): Collection
    {
        return $this->entry->in($this->site)->data();
    }
}
