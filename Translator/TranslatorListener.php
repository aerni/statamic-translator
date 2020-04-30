<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\Listener;

class TranslatorListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        'cp.add_to_head' => 'addStyles',
    ];

    public function addStyles()
    {
        return $this->css->tag('translator.css');
    }
}
