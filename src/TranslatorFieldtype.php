<?php

namespace Aerni\Translator;

use Facades\Aerni\Translator\Contracts\TranslationService;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class TranslatorFieldtype extends Fieldtype
{
    protected $icon = 'translate';
    protected $categories = ['special'];

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

    protected function locales(): array
    {
        return Site::all()->mapWithKeys(function ($site, $key) {
            return [$key => $site->shortLocale()];
        })->all();
    }

    /**
     * The blank/default value.
     *
     * @return null
     */
    public function defaultValue()
    {
        return null;
    }

    /**
     * Pre-process the data before it gets sent to the publish page.
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function preProcess($data)
    {
        return $data;
    }

    /**
     * Process the data before it gets saved.
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function process($data)
    {
        return $data;
    }
}
