<?php

namespace Aerni\Translator;

use Facades\Aerni\Translator\Contracts\TranslationService;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class TranslatorFieldtype extends Fieldtype
{
    protected $icon = 'translate';
    protected $categories = ['special'];

    /**
     * Add config fields to the fieldtype.
     *
     * @return array
     */
    public function configFieldItems(): array
    {
        return [
            'button_label' => [
                'type' => 'text',
                'title' => __('translator::fieldtypes.translator.config_fields.button_label.title'),
                'instructions' => __('translator::fieldtypes.translator.config_fields.button_label.instructions'),
                'default' => __('translator::fieldtypes.translator.config_fields.button_label.default'),
                'width' => 50,
            ],
        ];
    }

    /**
     * Preload some data to be available in the vue component.
     *
     * @return array
     */
    public function preload(): array
    {
        return [
            'locales' => $this->locales(),
            'defaultLocale' => Site::default()->shortLocale(),
            'supportedLanguages' => TranslationService::supportedLanguages(),
        ];
    }

    /**
     * Get all the locales from the sites array.
     *
     * @return array
     */
    protected function locales(): array
    {
        return Site::all()->mapWithKeys(function ($site, $key) {
            return [$key => $site->shortLocale()];
        })->all();
    }
}
