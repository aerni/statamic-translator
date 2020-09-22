<?php

namespace Aerni\Translator\Contracts;

interface Translator
{
    /**
     * Translate a collection entry or global set.
     *
     * @return Statamic\Entries\Entry
     * @return Statamic\Globals\GlobalSet
     */
    public function translate();
}
