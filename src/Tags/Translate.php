<?php

namespace Aerni\Translator\Tags;

use Statamic\Tags\Tags;
use Statamic\Facades\Site;
use Illuminate\Support\Facades\Cache;
use Facades\Aerni\Translator\Contracts\TranslationService;

class Translate extends Tags
{
    /**
     * The {{ translate:value }} tag.
     *
     * @return string
     */
    public function wildcard($value): string
    {
        $value = $this->context->pull($value);
        $locale = $this->params->pull('locale') ?? Site::current()->shortLocale();

        return $this->translate($value, $locale);
    }

    /**
     * The {{ translate }} tag.
     *
     * @return string
     */
    public function index(): string
    {
        $value = $this->params->pull('value');
        $locale = $this->params->pull('locale') ?? Site::current()->shortLocale();

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
