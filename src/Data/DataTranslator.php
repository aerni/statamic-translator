<?php

namespace Aerni\Translator\Data;

use Statamic\Facades\Data;
use Aerni\Translator\Contracts\Translator;
use Aerni\Translator\Data\CollectionEntryTranslator;
use Aerni\Translator\Data\GlobalSetTranslator;
use Aerni\Translator\Exceptions\TranslationFailed;

class DataTranslator implements Translator
{
    protected $entry;
    protected $targetSite;

    public function __construct(string $id, string $targetSite)
    {
        $this->entry = Data::find($id);
        $this->targetSite = $targetSite;
    }

    public function translate()
    {
        if ($this->entry instanceof \Statamic\Entries\Entry) {
            return (new CollectionEntryTranslator($this->entry, $this->targetSite))
                ->translate();
        }

        if ($this->entry instanceof \Statamic\Globals\GlobalSet) {
            return (new GlobalSetTranslator($this->entry, $this->targetSite))
                ->translate();
        }

        TranslationFailed::unsupportedContentType();
    }
}
