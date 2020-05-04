<?php

namespace Statamic\Addons\Translator;

use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Statamic\Addons\Translator\Contracts\TranslationService;
use Statamic\Addons\Translator\Services\GoogleAdvancedTranslationService;
use Statamic\Addons\Translator\Services\GoogleBasicTranslationService;
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
        $translationService = $this->getConfig('translation_service');

        if ($translationService === 'google_basic') {
            $this->app->singleton(TranslationService::class, GoogleBasicTranslationService::class);

            $this->app->singleton(TranslateClient::class, function ($app) {
                return new TranslateClient([
                    'key' => $this->getConfig('google_translation_api_key'),
                ]);
            });
        }

        if ($translationService === 'google_advanced') {
            $this->app->singleton(TranslationService::class, function ($app) {
                return new GoogleAdvancedTranslationService(
                    $this->app->make(TranslationServiceClient::class),
                    $this->getConfig('google_cloud_project')
                );
            });

            $this->app->singleton(TranslationServiceClient::class, function ($app) {
                return new TranslationServiceClient([
                    'credentials' => $this->getConfig('google_application_credentials'),
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
