<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Data\BasicTranslator;
use Illuminate\Support\Collection;
use Statamic\Globals\GlobalSet;

class GlobalSetTranslator extends BasicTranslator
{
    public function __construct(GlobalSet $entry, string $targetSite)
    {
        $this->entry = $entry;
        $this->targetSite = $targetSite;
    }

    public function translate(): GlobalSet
    {
        $this->entry->in($this->targetSite)
            ->data($this->translatedData());

        return $this->entry;
    }

    protected function rootData(): Collection
    {
        return $this->entry->inDefaultSite()->data();
    }

    protected function localizedData(): Collection
    {
        return $this->entry->in($this->targetSite)->data();
    }
}
