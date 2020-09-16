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
        TranslatorModifier::class,
    ];

    protected $fieldtypes = [
        TranslatorFieldtype::class,
    ];

    protected $scripts = [
        __DIR__.'/../resources/dist/js/translator.js'
    ];

    public function boot(): void
    {
        parent::boot();

        $translationService = config('translator.translation_service');

        if ($translationService === 'google_basic') {
            $this->bindGoogleBasic();
        }

        if ($translationService === 'google_advanced') {
            $this->bindGoogleAdvanced();
        }

        $this->app->bind('TranslationService', TranslationService::class);
    }

    protected function bindGoogleBasic(): void
    {
        $this->app->singleton(TranslationService::class, GoogleBasicTranslationService::class);

        $this->app->singleton(TranslateClient::class, function () {
            return new TranslateClient([
                'key' => config('translator.google_translation_api_key'),
            ]);
        });
    }

    protected function bindGoogleAdvanced(): void
    {
        $this->app->singleton(TranslationService::class, function () {
            return new GoogleAdvancedTranslationService(
                $this->app->make(TranslationServiceClient::class),
                config('translator.google_cloud_project')
            );
        });

        $this->app->singleton(TranslationServiceClient::class, function () {
            return new TranslationServiceClient([
                'credentials' => config('translator.google_application_credentials'),
            ]);
        });
    }
}
