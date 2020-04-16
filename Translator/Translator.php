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
        // 'array',
        // 'bard',
        'grid',
        // 'list',
        // 'markdown',
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

        dd($this->mapFieldSettings($this->content->defaultData(), $this->content->fieldset()->fields()));

        // Get all the content that has already been localized.
        $this->localizedContent = $this->content->dataForLocale($this->targetLocale);
        // Get the source locale
        $this->sourceLocale = $this->content->locale();

        // Get all the content to translate.
        $this->contentToTranslate = collect($this->getContentToTranslate());
        // Create a collection for the translated content.
        $this->translatedContent = collect();

        $this->translateContent();
        // $this->translateContentBatch();
        $this->localizeSlug();

        // dd($this->contentToTranslate);
        // dd($this->translatedContent);

        $this->saveTranslation();

        return true;
    }

    public function mapFieldSettings($content, $fieldset)
    {
        $contentWithKeys = [];

        foreach(Arr::dot($content) as $key => $item) {
            Arr::set($content, $key, $this->findFieldSettings($key, $fieldset));
        }

        return $content;
    }

    public function findFieldSettings($key, $fieldset)
    {
        return Arr::get($fieldset, $key . '.type');
    }

    /**
     * Prepare the content to be translated.
     *
     * @return array
     */
    public function getContentToTranslate(): array
    {
        // Get all the data from the content's .md file.
        $defaultData = $this->content->defaultData();

        // Get all the fields that arelocalizable fields.
        $localizableFields = $this->getLocalizableFields();
        $translatableFields = $this->getTranslatableFields($localizableFields);

        $translatableContent = $this->filterContent($defaultData, $translatableFields);
        // dd($translatableContent);

        // dd($translatableFields);
        // Get all the content that can be localized based on the default data and the translatable fields.
        // $translatableContent = array_intersect_key($defaultData, $translatableFields);
        // dd($translatableFields);

        // Get all the content that has not yet been translated.
        // There's some limitations with Replicator and Bard because of how they work.
        // It's not possible to only translate one set. Only to translate the whole Replicator/Bard.
        // $contentToTranslate = array_diff_key($translatableContent, $this->localizedContent);

        // dd($contentToTranslate);

        return $translatableContent;
    }

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
    private function getTranslatableFields(array $fields): array
    {
        return $this->filterFields($fields)->toArray();
    }

    /**
     * Filter the fields by supported fieldtypes.
     *
     * @param array $fields
     * @return void
     */
    private function filterFields(array $fields)
    {
        // dd($fields);
        $filteredFields = collect($fields)->map(function ($item) {
            switch ($item['type']) {
                case 'replicator':
                case 'bard':
                    $item['sets'] = collect($item['sets'] ?? [])
                        ->map(function ($set) {
                            $set['fields'] = $this->filterFields($set['fields'])->toArray();

                            return $set;
                        })
                        ->filter(function ($set) {
                            return count($set['fields']) > 0;
                        })
                        ->toArray();
                    break;
                case 'grid':
                    $item['fields'] = $this->filterFields($item['fields'] ?? [])->toArray();
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

        return $filteredFields;
    }

    private function filterContent(array $content, array $translatableFields, $returnKey = null)
    {
        // dd($data);
        // dd($this->translatableFields);

        // $filteredData = collect($data)->map(function ($item, $key) {
        //     if (is_array($item)) {
        //         $this->filterContent($item);
        //     }
        //     if (Helper::multi_array_key_exists($key, $this->translatableFields)) {
        //         return $item;
        //     }
        // })->filter();

        // return $filteredData;

        $toTranslate = [];

        // dump(['data' => $data, 'fields' => $translatableFields]);

        foreach ($content as $key => $data) {
            $field = Arr::get($translatableFields, $key);
            $type = Arr::get($field, 'type');

            dump([$key => $data]);

            if ($sets = Arr::get($translatableFields, 'replicator_set.sets')) {
                foreach (Arr::get($data, 'replicator_set', []) as $setIndex => $setData) {
                    $setType = Arr::get($setData, 'type');

                    $setFields = Arr::get($sets, $setType . '.fields');
                    // dump([
                    //     'set' => $setData,
                    //     'fields' => $setFields
                    // ]);
                    $toTranslate[$type][$key] = $this->filterContent(
                        $setData,
                        $setFields
                    );
                }
            }

            // dump(['key' => $key, 'data' => $data, 'fields' => $translatableFields]);

            if (!$field) {
                continue;
            }

            if (in_array($type, ['bard', 'grid', 'table'])) {
                continue;
            }

            if ($type === 'replicator') {
                foreach ($data as $index => $item) {
                    $content = $this->filterContent(
                        [$item['type'] => $item],
                        Arr::get($field, 'sets.' . $item['type'] . '.fields', []),
                        $item['type']
                    );

                    if ($content) {
                        $toTranslate[$key][$index] = $content;
                    } else {
                        $toTranslate[$key][$index] = $item;
                    }
                }
            } elseif ($type) {
                $toTranslate[$key] = $data;
            }
        }

        return $returnKey ? Arr::get($toTranslate, $returnKey) : $toTranslate;
    }

    /**
     * Translate the content into the requested target locale.
     * Return true when the translation was successul.
     * 
     * @return boolean
     */
    public function translateContent(): bool
    {
        $this->translatedContent = collect(Helper::array_map_recursive(
            array($this, "translateValue"),
            $this->contentToTranslate
        ));

        return true;
    }

    public function translateValue($value)
    {
        /**
         * Make sure to only translate when there's a value.
         * Otherwise Google Translate will throw an Exception.
         */
        if (empty($value)) {
            return;
        }

        return $this->googletranslate->translate($value, $this->sourceLocale, $this->targetLocale, 'text')['text'];
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
