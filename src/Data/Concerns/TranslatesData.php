<?php

namespace Aerni\Translator\Data\Concerns;

use Statamic\Support\Str;
use Statamic\Facades\Site;
use Aerni\Translator\Utils;
use Aerni\Translator\Data\Concerns\TranslatorGuards;
use Facades\Aerni\Translator\Contracts\TranslationService;

trait TranslatesData
{
    use TranslatorGuards;

    protected function translatedData(): array
    {
        return Utils::array_map_recursive(
            $this->dataToTranslate(),
            function ($value, $key) {
                return $this->translateValue($value, $key);
            }
        );
    }

    /**
     * Translate a given string value.
     *
     * @param mixed $value
     * @param string $key
     * @return mixed
     */
    protected function translateValue($value, string $key)
    {
        if (! $this->isTranslatableKeyValuePair($value, $key)) {
            return $value;
        }

        if (Utils::isHtml($value)) {
            return TranslationService::translateText($value, $this->targetLanguage(), 'html');
        }

        return TranslationService::translateText($value, $this->targetLanguage(), 'text');
    }

    /**
     * TODO: Implement caching.
     * Get the language for translation.
     *
     * @return string
     */
    protected function targetLanguage(): string
    {
        return Site::get($this->targetSite)->shortLocale();
    }

    protected function slug(): string
    {
        $slug = $this->entry->slug();

        if (! array_key_exists('slug', $this->translatableFields())) {
            return $slug;
        }

        return $this->service->translateText(Str::deslugify($slug), $this->targetLanguage(), 'text');
    }
}
