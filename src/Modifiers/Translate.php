<?php

namespace Aerni\Translator\Modifiers;

use Facades\Aerni\Translator\Contracts\TranslationService;
use Illuminate\Support\Facades\Cache;
use Statamic\Modifiers\Modifier;

class Translate extends Modifier
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
        $targetLocale = array_get($params, 0);

        if (is_null($targetLocale)) {
            return $value;
        }

        return Cache::rememberForever("{$value}_{$targetLocale}", function () use ($value, $targetLocale) {
            return TranslationService::translateText($value, $targetLocale);
        });
    }
}
