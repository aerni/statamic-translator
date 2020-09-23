<?php

namespace Aerni\Translator\Data;

use Statamic\Facades\Data;
use Aerni\Translator\Contracts\Translator;
use Aerni\Translator\Support\RequestValidator;
use Aerni\Translator\Exceptions\TranslationFailed;

class DataTranslator implements Translator
{
    protected $id;
    protected $site;

    public function __construct(string $id, string $site)
    {
        $this->id = $id;
        $this->site = $site;
    }

    public function process()
    {
        $this->entry = Data::find($this->id);

        return $this
            ->ensureSupportedType()
            ->translate();
    }

    protected function ensureSupportedType(): self
    {
        RequestValidator::isSupportedType($this->entry);

        return $this;
    }

    protected function translate()
    {
        if ($this->entry instanceof \Statamic\Entries\Entry) {
            return (new EntryTranslator($this->entry, $this->site))
                ->process();
        }

        if ($this->entry instanceof \Statamic\Globals\GlobalSet) {
            return (new GlobalSetTranslator($this->entry, $this->site))
                ->process();
        }

        if ($this->entry instanceof \Statamic\Taxonomies\LocalizedTerm) {
            return (new TermTranslator($this->entry, $this->site))
                ->process();
        }
    }
}
