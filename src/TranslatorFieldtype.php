<?php

namespace Aerni\Translator;

use Aerni\Translator\Facades\TranslationService;
use Statamic\Fields\Fieldtype;

class TranslatorFieldtype extends Fieldtype
{
    protected $icon = 'translate';
    protected $categories = ['special'];

    /**
     * Preload some data to be available in the vue component.
     *
     * @return array
     */
    public function preload(): array
    {
        return [
            'supportedLanguages' => TranslationService::supportedLanguages(),
        ];
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
