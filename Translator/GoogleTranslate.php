<?php

namespace Statamic\Addons\Translator;

use Exception;
use Google\Cloud\Translate\V2\TranslateClient;

class GoogleTranslate
{
    private $client;

    public function __construct(array $config)
    {
        $this->validateConfig($config);

        $this->client = new TranslateClient([
            'key' => $config['api_key'],
        ]);
    }

    public function translate(string $text, string $source, string $target, string $format): array
    {
        return $this->client->translate($text, ['source' => $source, 'target' => $target, 'format' => $format]);
    }

    public function detectLanguage(string $text): array
    {
        return $this->client->detectLanguage($text);
    }

    public function supportedLanguages(): array
    {
        return $this->client->localizedLanguages();
    }

    private function validateConfig(array $config)
    {
        if (! isset($config['api_key']) || $config['api_key'] === null) {
            throw new Exception('Google API Key is required. Please set a valid API key in your config.');
        }
    }
}