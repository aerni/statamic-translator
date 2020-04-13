<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\ServiceProvider;

class TranslatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(GoogleTranslate::class, function ($app) {
            $apiKey = $this->getConfig('google_translate_api_key');
            return new GoogleTranslate($apiKey);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
