<?php

namespace Statamic\Addons\Translator;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\API\Content;
use Statamic\API\Str;
use Statamic\Addons\Translator\GoogleTranslate;
use Statamic\Addons\Translator\Helper;

// TODO: Check if translation works for all content types like pages, collections etc.
// TODO: Add all fieldtypes that can be translated
// TODO: Add the option to force translate all content
// TODO: Add selection of fields that the user wants to translate.
// TODO: Batch translate content instead of translating all strings separately.
// TODO: Make sure content to translate does check for valid type recursively. Check with bard, replicator and grid.

class Translator
{
    protected $googletranslate;

    protected $supportedFieldtypes = [
        'array',
        'bard',
        'date',
        'grid',
        'list',
        'markdown',
        'redactor',
        'replicator',
        'table',
        'tags',
        'text',
        'textarea',
    ];

    protected $sourceLocale;
    protected $targetLocale;

    protected $content;
    protected $localizedContent;

    protected $localizableFields;
    protected $translatableFields;

    protected $comparisonArrays;

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

        $this->comparisonArrays = $this->getComparisonArrays();

        $this->translatedContent = $this->translateContent();
        // $this->translateContentBatch();
        $this->localizeSlug();

        // dd($this->contentToTranslate);
        dd($this->translatedContent);

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
        // Get all the fields that are localizable.
        $this->localizableFields = $this->getLocalizableFields();
        
        // Get all the fields that can be translated.
        $this->translatableFields = $this->getTranslatableFields();

        // Get all the content that is translatable.
        $this->translatableContent = $this->getTranslatableContent();

        // Return all the content that has not yet been translated.
        return array_diff_key($this->translatableContent, $this->localizedContent);
    }
    
    private function getComparisonArrays(): array
    {
        return [
            'fieldKeys' => $this->getTranslatableFieldKeys($this->translatableFields),
            'setKeys' => $this->getBardAndReplicatorSetKeys($this->translatableFields),
        ];
    }

    private function getTranslatableContent(): array
    {
        // Get the data from the content's .md file.
        $defaultData = $this->content->defaultData();
        
        // Return the content that can be translated.
        // This only works on first level. Bard/Replicator sets won't be filtered.
        return array_intersect_key($defaultData, $this->translatableFields);
    }

    /**
     * This puts all the fields into the same structure as the content,
     * so we can compare it later.
     *
     * @param array $fields
     * @return void
     */
    // public function getSetsRecursive(array $fields): array
    // {
    //     return collect($fields)->map(function ($item, $key) { 
            
    //         switch ($item['type']) {

    //             case 'bard':
    //             case 'replicator':
    //                 $item['sets'] = collect($item['sets'])
    //                     ->map(function ($set) {
    //                         $set['fields'] = $this->getSetsRecursive($set['fields']);
    //                         return $set;
    //                     });
    //                 break;
    //             case 'grid':
    //                 $item['fields'] = $this->getSetsRecursive($item['fields']);
    //                 break;

    //         }

    //         switch ($item['type']) {

    //             case 'bard':
    //             case 'replicator':
    //                 return collect($item['sets'])->map(function ($set, $key) {
    //                     return $set['fields'];
    //                 })->toArray();
    //                 break;
    //             case 'grid':
    //                 return array_values([$item['fields']]);
    //                 break;

    //         }

    //         return $key;

    //     })->filter()->toArray();
    // }

    public function getTranslatableFieldKeys(array $fields): array
    {
        // dd($fields);
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

    public function getBardAndReplicatorSetKeys(array $fields): array
    {
        $sets = collect($fields)->map(function ($item) { 

            switch ($item['type']) {

                case 'bard':
                case 'replicator':
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getBardAndReplicatorSetKeys($set['fields']);
                            return $set['fields'];
                        });
                    break;

            }

        })->toArray();

        $arrays = array_values(Helper::array_filter_recursive($sets, function ($item) {
            return is_array($item);
        }));

        $keys = Helper::array_keys_recursive($arrays);

        $stringValues = array_values(array_filter($keys, function ($item) {
            return !is_numeric($item);
        }));

        $setKeys = array_flip($stringValues);

        return $setKeys;
    }

    /**
     * Get all the localizable fieldset fields based on "localizable: true".
     *
     * @return array
     */
    public function getLocalizableFields(): array
    {
        // Get all the fields from the fieldset.
        $fields = collect($this->content->fieldset()->fields());

        // Get all the fields where "localizable: true".
        $localizableFields = $fields->where('localizable', true);

        /**
         * The title is always present and localizable in the CP.
         * It doesn't matter if the field is missing in the fieldset or if "localizable" is set to "false".
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
    public function translateContent(): array
    {
        $translatedContent = Helper::array_map_recursive(
            function ($value, $key) {
                return $this->translateValue($value, $key);
            },
            $this->contentToTranslate
        );

        return $translatedContent;
    }

    /**
     * Translate a given string value.
     *
     * @param string $value
     * @return string
     */
    public function translateValue($value, string $key)
    {

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

        // Skip "type: $value", where $value is a Bard/Replicator set key.
        if ($key === 'type' && array_key_exists($value, $this->comparisonArrays['setKeys'])) {
            return false;
        }

        // Skip when $key doesn't exists in the fieldset.
        if (! Helper::multi_array_key_exists($key, $this->comparisonArrays['fieldKeys']) && ! is_numeric($key)) {
            return false;
        }

        return true;
    }

    /**
     * Localize the slug. Return true when successul.
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
     * Save the translation to file.
     * Return true when saving was successful.
     *
     * @return boolean
     */
    public function saveTranslation(): bool
    {
        foreach ($this->translatedContent as $key => $value) {
            $this->content->in($this->targetLocale)->set($key, $value);
        }

        $this->content->in($this->targetLocale)->save();

        return true;
    }
}
