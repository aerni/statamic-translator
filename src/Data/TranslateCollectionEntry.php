<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Data\Translator;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;

class TranslateCollectionEntry extends Translator
{
    public function __construct(Entry $entry, string $targetSite)
    {
        $this->entry = $entry;
        $this->targetSite = $targetSite;
    }

    public function translate(): Entry
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
