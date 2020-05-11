<?php

return [
  
    /*
    |--------------------------------------------------------------------------
    | Translation Service
    |--------------------------------------------------------------------------
    |
    | Choose your prefered translation service. 
    | Possible values: 'google_basic', 'google_advanced'
    |
    */

    'translation_service' => 'google_basic',

    /*
    |--------------------------------------------------------------------------
    | Google Translation API Key
    |--------------------------------------------------------------------------
    |
    | Your Google Translation API Key. This only works with 'google_basic'.
    |
    |--------------------------------------------------------------------------
    */

    'google_translation_api_key' => env('GOOGLE_TRANSLATION_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Google Application Credentials
    |--------------------------------------------------------------------------
    |
    | The path to your application credentials json.
    |
    |--------------------------------------------------------------------------
    */

    'google_application_credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Project
    |--------------------------------------------------------------------------
    |
    | Your Google Cloud Project.
    |
    |--------------------------------------------------------------------------
    */

    'google_cloud_project' => env('GOOGLE_CLOUD_PROJECT'),
];
