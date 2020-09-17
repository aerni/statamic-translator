<?php

return [

    'translator' => [

        'config_fields' => [
            'button_label' => [
                'title' => 'Button Label',
                'instructions' => 'Customize the label of the translate button',
                'default' => 'Translate Content',
            ],
        ],

        'vue_component' => [
            'error_default_locale' => 'You can not translate the default locale',
            'error_source_locale' => 'The default locale is not supported for translation',
            'error_target_locale' => 'The current locale is not supported for translation',
            'error_unavailable' => 'Translator unavailable',
            'translating_title' => 'Translation in progress',
            'translating_message' => 'Please wait for the translation to finish',
            'reload' => 'The page will reload in 3 seconds',
            'success' => 'Translation successful',
            'error_general' => 'An error occured',
            'error_console' => 'Please check the console for further information',
        ],

    ],

];
