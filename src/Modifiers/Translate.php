<?php

namespace Aerni\Translator\Modifiers;

use Statamic\Facades\Site;
use Statamic\Modifiers\Modifier;
use Illuminate\Support\Facades\Cache;
use Facades\Aerni\Translator\Contracts\TranslationService;

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
        $locale = array_get($params, 0) ?? Site::current()->shortLocale();

        return $this->translate($value, $locale);
    }

    /**
     * Translate the value.
     *
     * @param string $value
     * @param string $locale
     * @return string
     */
    protected function translate(string $value, string $locale): string
    {
        if ($locale === Site::default()->shortLocale()) {
            return $value;
        }

        return Cache::rememberForever("{$value}_{$locale}", function () use ($value, $locale) {
            return TranslationService::translateText($value, $locale);
        });
    }
}
