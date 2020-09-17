<?php

namespace Aerni\Translator;

use Statamic\Support\Str;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Aerni\Translator\Contracts\TranslationService;

class Translator
{
    protected $service;

    protected $supportedFieldtypes = [
        'array', 'bard', 'grid', 'list', 'markdown', 'replicator',
        'table', 'tags', 'text', 'textarea',
    ];

    protected $id;
    protected $targetSite;
    protected $targetLocale;

    protected $entry;
    protected $contentType;
    protected $rootData;
    protected $localizedData;

    protected $translatableFields;
    protected $translatableData;
    protected $fieldKeys;
    protected $dataToTranslate;

    protected $translatedData;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the translation.
     *
     * @param string $id
     * @param string $targetSite
     * @return void
     */
    public function handleTranslation(string $id, string $targetSite): void
    {
        $this->id = $id;
        $this->targetSite = $targetSite;

        $this->getData();
        $this->processData();

        $this->translateData();
        $this->translateSlug();
        $this->saveTranslation();
    }

    /**
     * Get the data by ID and target locale.
     *
     * @return void
     */
    protected function getData(): void
    {
        $this->data = Data::find($this->id);
        $this->contentType = $this->contentType($this->data);

        if ($this->contentType === 'Entry') {
            $this->targetLocale = $this->data->site()->shortLocale();
            $this->rootData = $this->data->root()->data()->toArray();
            $this->localizedData = $this->data->data()->toArray();
        }

        if ($this->contentType === 'GlobalSet') {
            $this->targetLocale = $this->shortLocale(
                $this->data->localizations()->get($this->targetSite)->locale()
            );
            $this->rootData = $this->data->inDefaultSite()->data()->toArray();
            $this->localizedData = $this->data->in($this->targetSite)->data()->toArray();
        }
    }

    /**
     * Get the type of content.
     *
     * @param mixed $data
     * @return string
     */
    protected function contentType($data): string
    {
        if ($data instanceof \Statamic\Globals\GlobalSet) {
            return 'GlobalSet';
        }

        if ($data instanceof \Statamic\Entries\Entry) {
            return 'Entry';
        }

        return 'undefined';
    }

    /**
     * Get the short locale from a site by handle.
     *
     * @param string $siteHandle
     * @return string
     */
    protected function shortLocale(string $siteHandle): string
    {
        return Site::get($siteHandle)->shortLocale();
    }

    /**
     * Process and prepare the data for translation.
     *
     * @return void
     */
    protected function processData(): void
    {
        $this->getTranslatableFields();
        $this->getTranslatableData();
        $this->getFieldKeys();
        $this->getDataToTranslate();
    }

    /**
     * Get the translatable fields.
     *
     * @return void
     */
    protected function getTranslatableFields(): void
    {
        $localizableFields = $this->getLocalizableFields();
        $this->translatableFields = $this->filterSupportedFieldtypes($localizableFields);
    }

    /**
     * Get the translatable data.
     *
     * @return void
     */
    protected function getTranslatableData(): void
    {
        $this->translatableData = array_intersect_key($this->rootData, $this->translatableFields);
    }

    /**
     * Get the keys of translatable fields.
     *
     * @return void
     */
    protected function getFieldKeys(): void
    {
        $this->fieldKeys = [
            'allKeys' => $this->getTranslatableFieldKeys($this->translatableFields),
            'setKeys' => $this->getTranslatableSetKeys($this->translatableFields),
        ];
    }

    /**
     * Get the data to translate.
     *
     * @return void
     */
    protected function getDataToTranslate(): void
    {
        // Merge translatable with localized data.
        $mergedData = array_replace_recursive($this->translatableData, $this->localizedData);

        // Unset fields that shouldn't be translated.
        $this->dataToTranslate = $this->unsetSpecialFields($mergedData);
    }

    /**
     * Get the fields based on "localizable: true".
     *
     * @return array
     */
    protected function getLocalizableFields(): array
    {
        // Get all localizable fields.
        $localizableFields = $this->data->blueprint()->fields()->localizable()->all();

        // Add the title field, so it can be translated.
        if ($this->contentType !== 'GlobalsSet' && ! $localizableFields->has('title')) {
            $localizableFields->put('title', [
                'type' => 'text',
            ]);
        }

        return $localizableFields->toArray();
    }

    /**
     * Unset fields that shouldn't be translated.
     *
     * @param array $array
     * @return array
     */
    protected function unsetSpecialFields(array $array): array
    {
        // Slug translation will be handled separately
        if ($this->contentType === 'Entry') {
            unset($array['slug']);
        }

        unset($array['updated_by']);
        unset($array['updated_at']);

        return $array;
    }

    /**
     * Filter the fields by supported fieldtypes.
     *
     * @param array $fields
     * @return array
     */
    protected function filterSupportedFieldtypes(array $fields): array
    {
        return collect($fields)
            ->map(function ($item) {
                switch ($item['type']) {
                    case 'replicator':
                    case 'bard':
                        $item['sets'] = collect($item['sets'] ?? [])
                            ->map(function ($set) {
                                $set['fields'] = $this->filterSupportedFieldtypes($set['fields']);

                                return $set;
                            })
                            ->filter(function ($set) {
                                return count($set['fields']) > 0;
                            })
                            ->toArray();
                        break;
                    case 'grid':
                        $item['fields'] = $this->filterSupportedFieldtypes($item['fields'] ?? []);
                        break;
                }

                return $item;
            })
            ->filter(function ($item) {
                $supported = in_array($item['type'], $this->supportedFieldtypes);

                if (! $supported) {
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
            })->toArray();
    }

    /**
     * Get the keys of translatable fields.
     *
     * @param array $fields
     * @return array
     */
    protected function getTranslatableFieldKeys(array $fields): array
    {
        return collect($fields)->map(function ($item, $key) {
            switch ($item['type']) {

                case 'bard':
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getTranslatableFieldKeys($set['fields']);

                            return $set['fields'];
                        })->put('text', []);
                    break;

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
     * Get the keys of translatable Bard/Replicator sets.
     *
     * @param array $fields
     * @return array
     */
    protected function getTranslatableSetKeys(array $fields): array
    {
        $sets = collect($fields)->map(function ($item) {
            switch ($item['type']) {

                case 'bard':
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getTranslatableSetKeys($set['fields']);

                            return $set['fields'];
                        })
                        ->put('text', []);
                    break;

                case 'replicator':
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getTranslatableSetKeys($set['fields']);

                            return $set['fields'];
                        });
                    break;

            }
        })->toArray();

        $arrays = array_values(Utils::array_filter_recursive($sets, function ($item) {
            return is_array($item);
        }));

        return $arrays;
    }

    /**
     * Translate the data.
     *
     * @return void
     */
    protected function translateData(): void
    {
        $this->translatedData = Utils::array_map_recursive(
            $this->dataToTranslate,
            function ($value, $key) {
                return $this->translate($value, $key);
            }
        );
    }

    /**
     * Translate a given string value.
     *
     * @param mixed $value
     * @param string $key
     * @return mixed
     */
    protected function translate($value, string $key)
    {
        // Check if '$key: $value' should be translated.
        if (! $this->isTranslatableKeyValuePair($value, $key)) {
            return $value;
        }

        // Translate HTML
        if (Utils::isHtml($value)) {
            return $this->service->translateText($value, $this->targetLocale, 'html');
        }

        // Translate text
        return $this->service->translateText($value, $this->targetLocale, 'text');
    }

    /**
     * Check if the '$key: $value' should be translated.
     *
     * @param mixed $value
     * @param string $key
     * @return bool
     */
    protected function isTranslatableKeyValuePair($value, string $key): bool
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

        // Skip 'type: $value', where $value is a Bard/Replicator set key.
        if ($key === 'type' && Utils::array_key_exists_recursive($value, $this->fieldKeys['setKeys'])) {
            return false;
        }

        // Skip if $key doesn't exists in the fieldset.
        if (! Utils::array_key_exists_recursive($key, $this->fieldKeys['allKeys']) && ! is_numeric($key)) {
            return false;
        }

        // Skip if $value is in the target locale.
        if ($this->service->detectLanguage($value) === $this->targetLocale) {
            return false;
        }

        return true;
    }

    /**
     * Translate the slug.
     *
     * @return void
     */
    protected function translateSlug(): void
    {
        if ($this->slugShouldBeTranslated()) {
            // Deslugged slug from the unlocalized default content.
            $desluggedSlug = Str::deslugify($this->data->slug());

            // Translate the deslugged slug.
            $translation = $this->service->translateText($desluggedSlug, $this->targetLocale, 'text');

            // Save the translated slug to the translated content.
            $this->translatedData['slug'] = Str::slug($translation);
        }
    }

    /**
     * Determine if the slug should be translated.
     *
     * @return bool
     */
    protected function slugShouldBeTranslated(): bool
    {
        // Globals shouldn't have a slug saved to file.
        if ($this->contentType === 'GlobalSet') {
            return false;
        }

        // Return false if the slug has already been localized.
        if (array_key_exists('slug', $this->localizedData)) {
            return false;
        }

        return true;
    }

    /**
     * Save the translation to file.
     *
     * @return void
     */
    protected function saveTranslation(): void
    {
        foreach ($this->translatedData as $key => $value) {
            $this->data->in($this->targetSite)->set($key, $value);
        }

        $this->data->in($this->targetSite)->save();
    }
}
