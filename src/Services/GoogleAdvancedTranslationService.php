<?php

namespace Aerni\Translator\Services;

use Aerni\Translator\Contracts\TranslationService;
use Google\Cloud\Translate\V3\TranslationServiceClient;

class GoogleAdvancedTranslationService implements TranslationService
{
    private $client;
    private $parent;

    public function __construct(TranslationServiceClient $client, string $project)
    {
        $this->client = $client;
        $this->parent = $this->client->locationName($project, 'global');
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
        $mimeType = $this->getMimeType($format);

        try {
            $response = $this->client->translateText(
                [$content],
                $targetLanguage,
                $this->parent,
                [
                    'mimeType' => $mimeType,
                ]
            );

            return $response->getTranslations()[0]->getTranslatedText();
        } finally {
            $this->client->close();
        }
    }

    /**
     * Detect the language of the given content.
     *
     * @param string $content
     * @return string
     */
    public function detectLanguage(string $content): string
    {
        try {
            $response = $this->client->detectLanguage($this->parent, ['content' => $content]);

            return $response->getLanguages()[0]->getLanguageCode();
        } finally {
            $this->client->close();
        }
    }

    /**
     * Get a list of supported languages.
     *
     * @return array
     */
    public function supportedLanguages(): array
    {
        try {
            $response = $this->client->getSupportedLanguages($this->parent);

            foreach ($response->getLanguages() as $language) {
                $supportedLanguages[] = $language->getLanguageCode();
            }

            return $supportedLanguages;
        } finally {
            $this->client->close();
        }
    }

    /**
     * Return the MIME type based on the $format.
     *
     * @param string $format
     * @return string
     */
    private function getMimeType(string $format): string
    {
        if ($format === 'text') {
            return 'text/plain';
        }

        return 'text/html';
    }
}
