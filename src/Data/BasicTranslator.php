<?php

namespace Aerni\Translator\Data;

use Illuminate\Support\Collection;
use Aerni\Translator\Contracts\Translator;
use Aerni\Translator\Data\Concerns\PreparesData;
use Aerni\Translator\Data\Concerns\TranslatesData;

abstract class BasicTranslator implements Translator
{
    use PreparesData, TranslatesData;

    protected $entry;
    protected $targetSite;

    public function __construct($entry, string $targetSite)
    {
        $this->entry = $entry;
        $this->targetSite = $targetSite;
    }

    abstract public function process();

    abstract protected function rootData(): Collection;

    abstract protected function localizedData(): Collection;
}
