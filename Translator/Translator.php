<?php

namespace Statamic\Addons\Translator;

use Statamic\API\Content;
use Statamic\API\Str;
use Stichoza\GoogleTranslate\GoogleTranslate;

// TODO: Check if translation works for all content types like pages, collections etc.
// TODO: Add all fieldtypes that can be translated
// TODO: Add the option to force translate all content
// TODO: Add selection of fields that the user wants to translate.
// TODO: Ommit title and slug translation when "localization: false"

class Translator
{
    protected $googletranslate;

    protected $uri;
    protected $locale;

    protected $content;
    protected $localizedContent;

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
     * @param string $locale
     * @return boolean
     */
    public function translate(string $uri, string $locale): bool
    {
        $this->uri = $uri;
        $this->locale = $locale;

        // Get all the content associated with the URI.
        $this->content = Content::whereUri($uri);
        // Get all the content that has already been localized.
        $this->localizedContent = $this->content->dataForLocale($this->locale);

        // Get all the content to translate.
        $this->contentToTranslate = collect($this->getContentToTranslate());
        // Create a collection for the translated content.
        $this->translatedContent = collect();

        $this->translateContent();
        $this->translateSlug();
        
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

        // Get all the content that has not yet been localized.
        $contentToTranslate = array_diff_key($localizableContent, $this->localizedContent);

        return $contentToTranslate;
    }

    /**
     * Translate the content into the given locale.
     * Return true when the translation was successul.
     * 
     * @return boolean
     */
    public function translateContent(): bool
    {
        $this->contentToTranslate->each(function ($item, $key) {
            $this->translatedContent[$key] = $this->googletranslate->setTarget($this->locale)->translate($item);
        });

        return true;
    }

    /**
     * Translate the slug into the given locale.
     * Return true when the translation was successul.
     *
     * @return bool
     */
    public function translateSlug(): bool
    {
        // Return false if the slug has already been translated.
        if (array_key_exists('slug', $this->localizedContent)) {
            return false;
        }

        $desluggedUri = Str::deslugify($this->uri);
        $translation = $this->googletranslate->setTarget($this->locale)->translate($desluggedUri);
        $translatedSlug = Str::slug($translation);

        $this->translatedContent['slug'] = $translatedSlug;

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
        
        $localizableFields = $localizableFields->toArray();

        return $localizableFields;
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
            $this->content->in($this->locale)->set($key, $item);
        });

        $this->content->save();

        return true;
    }
}
