<?php

namespace Aerni\Translator;

use Aerni\Translator\Contracts\TranslationService;
use Aerni\Translator\Services\GoogleAdvancedTranslationService;
use Aerni\Translator\Services\GoogleBasicTranslationService;
use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Statamic\Providers\AddonServiceProvider;

class TranslatorServiceProvider extends AddonServiceProvider
{
    protected $modifiers = [
        \Aerni\Translator\TranslatorModifier::class,
    ];

    protected $fieldtypes = [
        \Aerni\Translator\TranslatorFieldtype::class,
    ];

    protected $scripts = [
        __DIR__.'/../public/js/translator.js'
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'translator');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/translator.php' => config_path('translator.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/translator'),
            ], 'lang');
        }

        $translationService = config('translator.translation_service');

        if ($translationService === 'google_basic') {
            $this->app->singleton(TranslationService::class, GoogleBasicTranslationService::class);

            $this->app->singleton(TranslateClient::class, function ($app) {
                return new TranslateClient([
                    'key' => config('translator.google_translation_api_key'),
                ]);
            });
        }

        if ($translationService === 'google_advanced') {
            $this->app->singleton(TranslationService::class, function ($app) {
                return new GoogleAdvancedTranslationService(
                    $this->app->make(TranslationServiceClient::class),
                    config('translator.google_cloud_project')
                );
            });

            $this->app->singleton(TranslationServiceClient::class, function ($app) {
                return new TranslationServiceClient([
                    'credentials' => config('translator.google_application_credentials'),
                ]);
            });
        }
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
