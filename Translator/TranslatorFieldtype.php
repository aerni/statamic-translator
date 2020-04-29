<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\Fieldtype;
use Statamic\Addons\Translator\GoogleTranslate;

class TranslatorFieldtype extends Fieldtype
{
    protected $googleTranslate;

    public $category = ['special'];

    public function __construct(GoogleTranslate $googleTranslate)
    {
        $this->googleTranslate = $googleTranslate;
    }
    
    /**
     * The blank/default value
     *
     * @return array
     */
    public function blank()
    {
        return null;
    }

    /**
     * Pre-process the data before it gets sent to the publish page
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function preProcess($data)
    {
        $data = [
            'apiKey' => $this->getConfig('google_translation_api_key'),
            'supportedLanguages' => $this->googleTranslate->supportedLanguages(),
        ];

        return $data;
    }

    /**
     * Process the data before it gets saved
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function process($data)
    {
        return $data;
    }
}
