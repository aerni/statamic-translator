<?php

namespace Statamic\Addons\Translator;

use Google\Cloud\Translate\V2\TranslateClient;

class GoogleTranslate
{
    private $client;

    public function __construct(string $apiKey)
    {
        $this->client = new TranslateClient([
            'key' => $apiKey,
        ]);
    }

    public function translate(string $text, string $source, string $target)
    {
        return $this->client->translate($text, ['source' => $source, 'target' => $target]);
    }
}