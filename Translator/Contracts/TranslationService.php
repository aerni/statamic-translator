<?php

namespace Statamic\Addons\Translator\Contracts;

interface TranslationService
{
    /**
     * Translate the given content into the target language.
     *
     * @param string $content
     * @param string $targetLanguage
     * @param string $format
     * @return string
     */
    public function translateText(string $content, string $targetLanguage, string $format = null): string;

    /**
     * Detect the language of the given content.
     *
     * @param string $content
     * @return string
     */
    public function detectLanguage(string $content): string;
    
    /**
     * Get a list of supported languages.
     *
     * @return array
     */
    public function supportedLanguages(): array;
}