<?php

namespace Aerni\Translator\Contracts;

interface TranslateData
{
    /**
     * Translate a collection entry or global set.
     *
     * @return Statamic\Entries\Entry
     * @return Statamic\Globals\GlobalSet
     */
    public function translate();
}
