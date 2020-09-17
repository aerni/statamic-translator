<?php

namespace Aerni\Translator\Services;

use Illuminate\Support\Facades\Cache;
use Google\Cloud\Translate\V2\TranslateClient;
use Aerni\Translator\Contracts\TranslationService;

class GoogleBasicTranslationService implements TranslationService
{
    private $client;

    public function __construct(TranslateClient $client)
    {
        $this->client = $client;
    }

    /**
     * Translate the given content into the target language.
     *
     * @param string $content
     * @param string $targetLanguage
     * @param string $format
     * @return string
     */
    public function translateText(string $content, string $targetLanguage, string $format = 'html'): string
    {
        $format = $this->getFormat($format);

        $response = $this->client->translate(
            $content,
            [
                'target' => $targetLanguage,
                'format' => $format,
            ]
        );

        return $response['text'];
    }

    /**
     * Detect the language of the given content.
     *
     * @param string $content
     * @return string
     */
    public function detectLanguage(string $content): string
    {
        return $this->client->detectLanguage($content)['languageCode'];
    }

    /**
     * Get a list of supported languages.
     *
     * @return array
     */
    public function supportedLanguages(): array
    {
        return Cache::remember('supported_languages', 86400, function () {
            return $this->client->languages();
        });
    }

    /**
     * Return the format based on the $format.
     *
     * @param string $format
     * @return string
     */
    private function getFormat(string $format): string
    {
        if ($format === 'text') {
            return 'text';
        }

        return 'html';
    }
}
