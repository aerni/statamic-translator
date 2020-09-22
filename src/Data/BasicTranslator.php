<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Contracts\Translator;
use Aerni\Translator\Data\Concerns\PreparesData;
use Aerni\Translator\Data\Concerns\TranslatesData;
use Illuminate\Support\Collection;

abstract class BasicTranslator implements Translator
{
    use PreparesData, TranslatesData;

    protected $entry;
    protected $site;

    public function __construct($entry, string $site)
    {
        $this->entry = $entry;
        $this->site = $site;
    }

    /**
     * Process the translation and return the translated entry.
     *
     * @return \Statamic\Entries\Entry
     * @return \Statamic\Globals\GlobalSet
     */
    public function process()
    {
        $this
            ->ensureCanProcess()
            ->translate();

        return $this->entry;
    }

    /**
     * Ensure that it can process the translation.
     *
     * @return self
     */
    abstract protected function ensureCanProcess(): self;

    /**
     * Translate the entry.
     *
     * @return void
     */
    abstract protected function translate(): void;

    /**
     * Get the entry's unlocalized root data.
     *
     * @return Collection
     */
    abstract protected function rootData(): Collection;

    /**
     * Get the entry's localized data.
     *
     * @return Collection
     */
    abstract protected function localizedData(): Collection;
}
