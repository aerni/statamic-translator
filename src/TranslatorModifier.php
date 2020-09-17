<?php

namespace Aerni\Translator;

use Facades\Aerni\Translator\Contracts\TranslationService;
use Statamic\Modifiers\Modifier;

class TranslatorModifier extends Modifier
{
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

        return TranslationService::translateText($value, $targetLocale);
    }
}
