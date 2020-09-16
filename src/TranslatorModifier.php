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
     * Translate a value to a target locale
     *
     * @param mixed $value
     * @param array $params
     * @return string
     */
    public function index($value, $params): string
    {
        if (! array_get($params, 0)) {
            return $value;
        }

        $targetLocale = array_get($params, 0);

        return $this->service->translateText($value, $targetLocale);
    }
}
