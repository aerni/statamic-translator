<?php

namespace Aerni\Translator\Data;

use Statamic\Facades\Data;
use Aerni\Translator\Contracts\TranslateData as TranslateDataContract;
use Aerni\Translator\Data\TranslateCollectionEntry;
use Aerni\Translator\Data\TranslateGlobalSet;
use Aerni\Translator\Exceptions\TranslationFailed;

class TranslateData implements TranslateDataContract
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
            return (new TranslateCollectionEntry($this->entry, $this->targetSite))
                ->translate();
        }

        if ($this->entry instanceof \Statamic\Globals\GlobalSet) {
            return (new TranslateGlobalSet($this->entry, $this->targetSite))
                ->translate();
        }

        TranslationFailed::unsupportedContentType();
    }
}
