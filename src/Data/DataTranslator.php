<?php

namespace Aerni\Translator\Data;

use Statamic\Facades\Data;
use Aerni\Translator\Contracts\Translator;
use Aerni\Translator\Data\CollectionEntryTranslator;
use Aerni\Translator\Data\GlobalSetTranslator;
use Aerni\Translator\Exceptions\TranslationFailed;

class DataTranslator implements Translator
{
    protected $id;
    protected $targetSite;

    public function __construct(string $id, string $targetSite)
    {
        $this->id = $id;
        $this->targetSite = $targetSite;
    }

    public function process()
    {
        $entry = Data::find($this->id);

        if ($entry instanceof \Statamic\Entries\Entry) {
            return (new EntryTranslator($entry, $this->targetSite))
                ->process();
        }

        if ($entry instanceof \Statamic\Globals\GlobalSet) {
            return (new GlobalSetTranslator($entry, $this->targetSite))
                ->process();
        }

        TranslationFailed::unsupportedContentType();
    }
}
