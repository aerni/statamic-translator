<?php

namespace Statamic\Addons\Translator\Services;

use Google\Cloud\Translate\V2\TranslateClient;
use Statamic\Addons\Translator\Contracts\TranslationService;

class GoogleBasicTranslationService implements TranslationService
{
    private $client;

    public function __construct(TranslateClient $client)
    {
        $this->client = $client;
    }

    public function translateText(string $content, string $targetLanguage, string $format = 'html'): string
    {
        $format = $this->getFormat($format);

        $response = $this->client->translate(
            $content, 
            [
                'target' => $targetLanguage, 
                'format' => $format
            ]
        );
        
        return $response['text'];
    }

    public function detectLanguage(string $content): string
    {
        return $this->client->detectLanguage($content)['languageCode'];
    }

    public function supportedLanguages(): array
    {
        return $this->client->languages();
    }

    private function getFormat(string $format): string
    {
        if ($format === 'text') {
            return 'text';
        }

        return 'html';
    }
}