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

// DONE: Get localizable fields recursively from withing bard, grid and replicator.
// TODO: The translatable content has to be filtered too.
// TODO: Don't translate $key "type" when the $value is in $supportedFieldtypes.

class Translator
{
    protected $googletranslate;

    protected $supportedFieldtypes = [
        // 'date',
        // 'radio',
        // 'checkboxes',
        'array',
        'bard',
        'grid',
        'list',
        'markdown',
        // 'redactor',
        'replicator',
        // 'table',
        // 'tags',
        'text',
        'textarea',
    ];

    protected $uri;
    protected $targetLocale;

    protected $content;
    protected $localizedContent;
    protected $sourceLocale;
    protected $translatableFields;
    protected $fields;
    protected $sets;

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
        $this->contentToTranslate = $this->getContentToTranslate();
        // Create a collection for the translated content.
        $this->translatedContent = collect();

        $this->translatedContent = $this->translateContent();
        // $this->translateContentBatch();
        $this->localizeSlug();

        // dd($this->contentToTranslate);
        // dd($this->translatedContent);

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
        $localizableFields = $this->getLocalizableFields();
        // dd($localizableFields);

        // Get all the fields that can be translated
        $translatableFields = $this->getFieldsToTranslate($localizableFields);
        // dd($translatableFields);

        $translatableContent = $this->getTranslatableContent($translatableFields);

        // Get all the content that has not yet been translated.
        // There's some limitations with Replicator and Bard because of how they work.
        // It's not possible to only translate one set. Only to translate the whole Replicator/Bard.
        $contentToTranslate = array_diff_key($translatableContent, $this->localizedContent);

        dd($contentToTranslate);


        return $contentToTranslate;
    }

    public function getTranslatableContent(array $fields)
    {
        // Get all the data from the content's .md file.
        $defaultData = $this->content->defaultData();
        // dd($defaultData);
        // dd($fields);
        $topLevelContent = array_intersect_key($defaultData, $fields);
        // dd($topLevelContent);

        $this->fields = $fields;

        // Replicator & Bard: If it doesn't have a $key within "sets" that is represented within the content as "type: $key" – delete it.
        // Check in content: If key is array, check for $key "type", get its $value". Compare that value with the keys from the fieldset.
        // The search needs to know where to look for the keys. Otherwise all keys will be supported.
        // Type Array: If no defined "keys" in the fieldset, take whatever value is set in the content.

        // dd($fields);
        // $translatableContent = Helper::array_map_recursive(
        //     function ($value, $key) use ($fields) {
        //         // dd($key);
        //         if (is_numeric($key) || Helper::multi_array_key_exists($key, $fields)) {
        //             return $value;
        //         }
        //     },
        //     $topLevelContent,
        // );

        $this->sets = $this->getSetsRecursive($fields);
        // dd($this->sets);
        // $translatableContent = $this->mapContent($topLevelContent);

        // dd($translatableContent);

        $filtered = $this->filterTranslatableContent($topLevelContent, $this->sets->toArray());

        dd($filtered);

        return $filtered;

    }

    public function filterTranslatableContent(array $content, array $fields)
    {
        $filtered = Helper::array_map_recursive(
            function ($value, $key) use ($content) {
                if (Helper::multi_array_key_exists($key, $content)) {
                    dd($key, $value);
                }
            },
            $fields
        );

        return $filtered;
    }



    /**
     * This puts all the fields into the same structure as the content,
     * so we can compare it later.
     *
     * @param array $fields
     * @return void
     */
    public function getSetsRecursive(array $fields)
    {
        return collect($fields)->map(function ($item, $key) { 
            
            switch ($item['type']) {

                case 'bard':
                case 'replicator':
                    $item['sets'] = collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getSetsRecursive($set['fields'])->toArray();
                            return $set;
                        });
                    break;
                case 'grid':
                    $item['fields'] = $this->getSetsRecursive($item['fields'])->toArray();
                    break;

            }

            switch ($item['type']) {

                case 'bard':
                case 'replicator':
                    return collect($item['sets'])->map(function ($set, $key) {
                        $fields = $set['fields'];
                        return array_merge(['type' => $key], $fields);
                    })->values()->toArray();
                    break;
                case 'grid':
                    return array_values([$item['fields']]);
                    break;

            }

            return $key;

        })
        ->filter();
    }

    /**
     * This works for one level!!!
     *
     * @param array $fieldset
     * @return void
     */
    public function getSets(array $fieldset)
    {
        $bard = collect();

        foreach ($fieldset as $key => $value) {

            if (is_array($value) && array_key_exists('sets', $value) && array_search('bard', $value, true) === 'type') {

                $sets = collect($value['sets']);

                $sets = $sets->map(function ($item, $key) {

                    $fields = collect($item['fields'])->map(function ($item, $key) {
                        return $key;
                    })->toArray();

                    return array_merge(['type' => $key], $fields);

                })->values();

                $bard->put($key, $sets);

            }

        }
        
        return $bard;
    }

    public function mapContent(array $content)
    {
        foreach ($content as $key => $value) {
            // dd($value);
            if (is_array($value) && Helper::has_number_keys($value)) {
                $this->mapContent($value);
            }
            if (is_array($value)) {
                $translatableContent = Helper::array_map_recursive(
                function ($v, $k) use ($value) {
                    if (is_array($v)) {
                        $this->mapContent($k);
                    }
                    if (Helper::multi_array_key_exists($k, $this->fields)) {
                        dd($k);
                    }
                },
                $value,
        );
            }
            collect($value)->map(function ($item) {

                dd($item);
            });

            dd($value);
            // if (is_array($value)) {
            //     dd($value);
            // }
        }
    }

    // public function filterTranslatableContent(array $content)
    // {
    //     $filtered = Helper::array_filter_recursive($content, function ($item) {
    //         if (isset($item)) {
    //             return true;
    //         }
    //     });

    //     $results = Helper::array_walk_recursive_delete($filtered, function ($value, $key) {
    //         if (is_array($value)) {
    //             return empty($value);
    //         }
    //         return ($value === null);
    //     });
        
    //     return $filtered;
    // }

    /**
     * Get all the localizable fields based on "localizable: true".
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
    private function getFieldsToTranslate(array $fields): array
    {
        return $this->filterSupportedFieldtypes($fields)->toArray();
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
    public function translateValue(string $value, string $key): string
    {
        // Make sure to skip translation of values with the key of 'type'.
        if ($key === 'type') {
            return $value;
        }

        return $this->googletranslate->translate($value, $this->sourceLocale, $this->targetLocale, 'html')['text'];
    }

    /**
     * Translate the content in batch into the requested target locale.
     * Return true when the translation was successul.
     * 
     * @return boolean
     */
    public function translateContentBatch(): bool
    {
        $flatContent = $this->contentToTranslate->flatten()->toArray();
        // dd($flatContent);
        $arrayKeys = $this->contentToTranslate->keys()->toArray();
        // dd($arrayKeys);

        $translatedContentBatch = collect($this->googletranslate->translateBatch($flatContent, $this->sourceLocale, $this->targetLocale));
        // dd($translatedContentBatch);

        $flatTranslatedContent = $translatedContentBatch->map(function ($item) {
            return $item['text'];
        })->toArray();
        // dd($flatTranslatedContent);

        $this->translatedContent = collect(array_combine($arrayKeys, $flatTranslatedContent));
        // dd($this->translatedContent);

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

        $this->content->save();

        return true;
    }
}
