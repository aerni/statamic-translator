<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Contracts\Translator;
use Aerni\Translator\Exceptions\TranslationFailed;
use Statamic\Facades\Data;

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
        $entry = Data::find($this->id);

        if ($entry instanceof \Statamic\Entries\Entry) {
            return (new EntryTranslator($entry, $this->site))
                ->process();
        }

        if ($entry instanceof \Statamic\Globals\GlobalSet) {
            return (new GlobalSetTranslator($entry, $this->site))
                ->process();
        }

        TranslationFailed::unsupportedContentType();
    }
}
