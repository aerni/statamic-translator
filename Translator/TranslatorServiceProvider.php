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

            $config = [
                'api_key' => $this->getConfig('google_translate_api_key'),
            ];
            
            return new GoogleTranslate($config);
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
