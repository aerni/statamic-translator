<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\Fieldtype;

class TranslatorFieldtype extends Fieldtype
{
    public $category = ['special'];
    
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
        $data['api_key'] = $this->getConfig('google_translation_api_key');

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
