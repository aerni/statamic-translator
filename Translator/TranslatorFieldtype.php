<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\Fieldtype;
use Statamic\Addons\Translator\Contracts\TranslationService;

class TranslatorFieldtype extends Fieldtype
{
    private $service;

    public $category = ['special'];

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
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
            'buttonText' => $this->getFieldConfig('button_text'),
            'supportedLanguages' => $this->service->supportedLanguages(),
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
        unset($data['supportedLanguages']);
        
        return $data;
    }
}
