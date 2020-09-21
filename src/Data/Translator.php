<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Data\Concerns\PreparesData;
use Aerni\Translator\Data\Concerns\TranslatesData;
use Illuminate\Support\Collection;

abstract class Translator
{
    use PreparesData, TranslatesData;

    protected $entry;
    protected $targetSite;

    abstract public function translate();

    abstract protected function rootData(): Collection;

    abstract protected function localizedData(): Collection;
}
