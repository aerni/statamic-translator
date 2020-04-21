<?php

namespace Statamic\Addons\Translator;

use Illuminate\Support\Collection;
use Statamic\API\Content;
use Statamic\API\Str;
use Statamic\Addons\Translator\GoogleTranslate;
use Statamic\Addons\Translator\Helper;

class Translator
{
    protected $googletranslate;

    protected $supportedFieldtypes = [
        'array', 'bard', 'grid', 'list', 'markdown', 'redactor', 'replicator',
        'table', 'tags', 'text', 'textarea',
    ];

    protected $sourceLocale;
    protected $targetLocale;

    protected $content;
    protected $localizedContent;

    protected $localizableFields;
    protected $translatableFields;

    protected $fieldKeys;

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
        // Get all the content associated with the URI.
        $this->content = Content::whereUri($uri);

        // Get the source locale to translate from.
        $this->sourceLocale = $this->content->locale();
        // Get the target locale to translate into.
        $this->targetLocale = $targetLocale;
        
        // Get the content that has already been localized into the target locale.
        $this->localizedContent = $this->content->dataForLocale($this->targetLocale);
        
        // Get the content to translate.
        $this->contentToTranslate = $this->getContentToTranslate();

        // Create a collection for the translated content.
        $this->translatedContent = collect();

        $this->fieldKeys = $this->getFieldKeys();

        $this->translatedContent = $this->translateContent();
        
        $this->localizeSlug();

        $this->saveTranslation();

        return true;
    }

    /**
     * Prepare the content to be translated.
     *
     * @return array
     */
    private function getContentToTranslate(): array
    {
        // Get all the fields that are localizable.
        $this->localizableFields = $this->getLocalizableFields();
        
        // Get all the fields that can be translated.
        $this->translatableFields = $this->getTranslatableFields();

        // Get all the content that is translatable.
        $this->translatableContent = $this->getTranslatableContent();

        // Merge localized and translatable content.
        return array_replace_recursive($this->translatableContent, $this->localizedContent);
        
        // Return all the content that has not yet been translated.
        // return array_diff_key($this->translatableContent, $this->localizedContent);
    }

    /**
     * Get the translatable content.
     *
     * @return array
     */
    private function getTranslatableContent(): array
    {
        // Get the data from the content's .md file.
        $defaultData = $this->content->defaultData();
        
        // Return the content that can be translated.
        return array_intersect_key($defaultData, $this->translatableFields);
    }

    /**
     * Get array of the keys of translatable fields.
     *
     * @return array
     */
    private function getFieldKeys(): array
    {
        return [
            'allKeys' => $this->getTranslatableFieldKeys($this->translatableFields),
            'setKeys' => $this->getTranslatableSetKeys($this->translatableFields),
        ];
    }

    /**
     * Get all the keys of translatable fields.
     *
     * @param array $fields
     * @return array
     */
    private function getTranslatableFieldKeys(array $fields): array
    {
        return collect($fields)->map(function ($item, $key) { 

            switch ($item['type']) {

                case 'bard':
                case 'replicator':
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getTranslatableFieldKeys($set['fields']);
                            return $set['fields'];
                        });
                    break;
                case 'grid':
                    $item['fields'] = $this->getTranslatableFieldKeys($item['fields']);
                    return $item['fields'];
                    break;
                case 'array':
                    if (array_key_exists('keys', $item)) {
                        return $item['keys'];
                    }
                    break;

            }

            return $key;

        })->toArray();
    }

    /**
     * Get all the keys of translatable Bard/Replicator sets.
     *
     * @param array $fields
     * @return array
     */
    private function getTranslatableSetKeys(array $fields): array
    {
        $sets = collect($fields)->map(function ($item) { 

            switch ($item['type']) {

                case 'bard':
                case 'replicator':
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getTranslatableSetKeys($set['fields']);
                            return $set['fields'];
                        });
                    break;

            }

        })->toArray();

        $arrays = array_values(Helper::array_filter_recursive($sets, function ($item) {
            return is_array($item);
        }));

        return $arrays;
    }

    /**
     * Get all the localizable fieldset fields based on "localizable: true".
     *
     * @return array
     */
    private function getLocalizableFields(): array
    {
        // Get all the fields from the fieldset.
        $fields = collect($this->content->fieldset()->fields());

        // Get all the fields where "localizable: true".
        $localizableFields = $fields->where('localizable', true);

        /**
         * The title is always present and localizable in the CP.
         * This adds the title field, so we can translate it later. 
         */
        if (!$localizableFields->has('title')) {
            $localizableFields->put('title', [
                'type' => 'text',
                'localizable' => true
            ]);
        };

        return $localizableFields->toArray();
    }

    /**
     * Get all the translatable fields.
     *
     * @param array $fields
     * @return array
     */
    private function getTranslatableFields(): array
    {
        return $this->filterSupportedFieldtypes($this->localizableFields)->toArray();
    }

    /**
     * Filter the fields by supported fieldtypes.
     *
     * @param array $fields
     * @return collection
     */
    private function filterSupportedFieldtypes(array $fields): collection
    {
        return collect($fields)
            ->map(function ($item) {
                switch ($item['type']) {
                    case 'replicator':
                    case 'bard':
                        $item['sets'] = collect($item['sets'] ?? [])
                            ->map(function ($set) {
                                $set['fields'] = $this->filterSupportedFieldtypes($set['fields'])->toArray();

                                return $set;
                            })
                            ->filter(function ($set) {
                                return count($set['fields']) > 0;
                            })
                            ->toArray();
                        break;
                    case 'grid':
                        $item['fields'] = $this->filterSupportedFieldtypes($item['fields'] ?? [])->toArray();
                        break;
                }

                return $item;
            })
            ->filter(function ($item) {
                $supported = in_array($item['type'], $this->supportedFieldtypes);

                if (!$supported) {
                    return false;
                }

                switch ($item['type']) {
                    case 'replicator':
                        return count($item['sets'] ?? []) > 0;
                        break;
                    case 'grid':
                        return count($item['fields'] ?? []) > 0;
                        break;
                    default:
                        break;
                }

                return true;
            });
    }

    /**
     * Translate the content into the requested target locale.
     * Return true when the translation was successul.
     * 
     * @return array
     */
    private function translateContent(): array
    {
        return Helper::array_map_recursive(
            function ($value, $key) {
                return $this->translateValue($value, $key);
            },
            $this->contentToTranslate
        );
    }

    /**
     * Translate a given string value.
     *
     * @param string $value
     * @return string
     */
    private function translateValue($value, string $key)
    {
        // Check if '$key: $value' should be translated.
        if (! $this->isTranslatableKeyValuePair($value, $key)) {
            return $value;
        }

        // Translate HTML
        if (Helper::isHtml($value)) {
            return $this->googletranslate->translate($value, $this->sourceLocale, $this->targetLocale, 'html')['text'];
        };

        // Translate text
        return $this->googletranslate->translate($value, $this->sourceLocale, $this->targetLocale, 'text')['text'];
    }

    /**
     * Check if the '$key: $value' should be translated.
     *
     * @param any $value
     * @param string $key
     * @return boolean
     */
    private function isTranslatableKeyValuePair($value, string $key): bool
    {
        // Skip empty $value.
        if (empty($value)) {
            return false;
        }

        // Skip numeric $value.
        if (is_numeric($value)) {
            return false;
        }

        // Skip boolean $value.
        if (is_bool($value)) {
            return false;
        }

        // Skip 'type: text', which is Bard's default set.
        if ($key === 'type' && $value === 'text') {
            return false;
        }

        // Skip 'type: $value', where $value is a Bard/Replicator set key.
        if ($key === 'type' && Helper::multi_array_key_exists($value, $this->fieldKeys['setKeys'])) {
            return false;
        }

        // Skip if $key doesn't exists in the fieldset.
        if (! Helper::multi_array_key_exists($key, $this->fieldKeys['allKeys']) && ! is_numeric($key)) {
            return false;
        }

        // Skip if $value is in the target locale.
        if ($this->googletranslate->detectLanguage($value)['languageCode'] === $this->targetLocale) {
            return false;
        }

        return true;
    }

    /**
     * Localize the slug. Return true when successul.
     *
     * @return bool
     */
    private function localizeSlug(): bool
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
     * Save the translation to file.
     * Return true when saving was successful.
     *
     * @return boolean
     */
    private function saveTranslation(): bool
    {
        foreach ($this->translatedContent as $key => $value) {
            $this->content->in($this->targetLocale)->set($key, $value);
        }

        $this->content->in($this->targetLocale)->save();

        return true;
    }
}
