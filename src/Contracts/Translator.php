<?php

namespace Aerni\Translator\Contracts;

interface Translator
{
    /**
     * Process the translation of a collection entry or global set.
     *
     * @return Statamic\Entries\Entry
     * @return Statamic\Globals\GlobalSet
     */
    public function process();
}
