<?php

namespace Statamic\Addons\Translator;

use Statamic\API\Content;
use Statamic\API\Str;
use Statamic\Addons\Translator\GoogleTranslate;

// TODO: Check if translation works for all content types like pages, collections etc.
// TODO: Add all fieldtypes that can be translated
// TODO: Add the option to force translate all content
// TODO: Add selection of fields that the user wants to translate.
// TODO: Batch translate content instead of translating all strings separately.

class Translator
{
    protected $googletranslate;

    protected $supportedFieldtypes = [
        'text',
        'textarea',
    ];

    protected $uri;
    protected $targetLocale;

    protected $content;
    protected $localizedContent;
    protected $sourceLocale;

    protected $contentToTranslate;
    protected $translatedContent;

    public function __construct(GoogleTranslate $googletranslate)
    {
        $this->googletranslate = $googletranslate;
    }

    /**
     * Translate the requested URI based on the requested locale.
     *
     * @param string $uri
     * @param string $targetLocale
     * @return boolean
     */
    public function translate(string $uri, string $targetLocale): bool
    {
        $this->uri = $uri;
        $this->targetLocale = $targetLocale;

        // Get all the content associated with the URI.
        $this->content = Content::whereUri($uri);
        // Get all the content that has already been localized.
        $this->localizedContent = $this->content->dataForLocale($this->targetLocale);
        // Get the source locale
        $this->sourceLocale = $this->content->locale();

        // Get all the content to translate.
        $this->contentToTranslate = collect($this->getContentToTranslate());
        // Create a collection for the translated content.
        $this->translatedContent = collect();

        $this->translateContent();
        $this->localizeSlug();
        
        $this->saveTranslation();

        return true;
    }

    /**
     * Prepare the content to be translated.
     *
     * @return array
     */
    public function getContentToTranslate(): array
    {
        // Get all the data of the content.
        $defaultData = $this->content->defaultData();
        // Get all the fields that are set to "localizable: true" in the fieldset.
        $localizableFields = $this->getLocalizableFields();

        // Get all the content that can be localized based on the default data and localizable fields.
        $localizableContent = array_intersect_key($defaultData, $localizableFields);

        // Get all the content that has not yet been translated.
        $contentToTranslate = array_diff_key($localizableContent, $this->localizedContent);

        return $contentToTranslate;
    }

    /**
     * Translate the content into the requested target locale.
     * Return true when the translation was successul.
     * 
     * @return boolean
     */
    public function translateContent(): bool
    {
        $this->contentToTranslate->each(function ($item, $key) {
            $this->translatedContent[$key] = $this->googletranslate->translate($item, $this->sourceLocale, $this->targetLocale)['text'];
        });

        return true;
    }

    /**
     * Localize the slug.
     * Return true when the translation was successul.
     *
     * @return bool
     */
    public function localizeSlug(): bool
    {   
        // Return false if the slug has already been translated.
        if (array_key_exists('slug', $this->localizedContent)) {
            return false;
        }

        // Get the title either from the already localized or the just translated content.
        if (array_key_exists('title', $this->localizedContent)) {
            $title = $this->localizedContent['title'];
        } else {
            $title = $this->translatedContent['title'];
        }

        $slug = Str::slug($title);

        $this->translatedContent['slug'] = $slug;

        return true;
    }

    /**
     * Get all the fields that are set to "localizable: true" in the fieldset.
     *
     * @return array
     */
    public function getLocalizableFields(): array
    {
        $fields = collect($this->content->fieldset()->fields());
        $localizableFields = $fields->where('localizable', true);

        /**
         * The title is always present and localizable in the CP.
         * It doesn't matter if the field is missing in the fieldset or if "localizable" is set to "false".
         * This adds the title field, so we can translate it later. 
         */
        if (! $localizableFields->has('title')) {
            $localizableFields->put('title', [
                'type' => 'text',
                'localizable' => true
            ]);
        }

        // Filter the fields by supported fieldtypes.
        $filteredFields = $localizableFields->map(function ($item) {
            if (in_array($item['type'], $this->supportedFieldtypes)) {
                return $item;
            }
        })->filter()->all();

        return $filteredFields;
    }

    /**
     * Save the translation to file.
     * Return true when saving was successful.
     *
     * @return boolean
     */
    public function saveTranslation(): bool
    {
        $this->translatedContent->each(function ($item, $key) {
            $this->content->in($this->targetLocale)->set($key, $item);
        });

        $this->content->save();

        return true;
    }
}
