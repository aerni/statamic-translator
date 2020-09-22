<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Data\BasicTranslator;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;

class EntryTranslator extends BasicTranslator
{
    public function process(): Entry
    {
        $this->entry->data($this->translatedData())
            ->slug($this->slug());

        return $this->entry;
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
