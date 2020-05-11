<?php

namespace Aerni\Translator;

use Aerni\Translator\Contracts\TranslationService;
use Statamic\Modifiers\Modifier;

class TranslatorModifier extends Modifier
{
    private $service;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    /**
     * Modify a value.
     *
     * @param mixed  $value    The value to be modified
     * @param array  $params   Any parameters used in the modifier
     * @param array  $context  Contextual values
     * @return mixed
     */
    public function index($value, $params, $context)
    {
        if (! array_get($params, 0)) {
            return $value;
        }

        $targetLocale = array_get($params, 0);

        return $this->service->translateText($value, $targetLocale);
    }
}
