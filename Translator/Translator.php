<?php

namespace Statamic\Addons\Translator;

use Statamic\Addons\Translator\Contracts\TranslationService;
use Statamic\API\Content;
use Statamic\API\Str;

class Translator
{
    private $service;

    private $supportedFieldtypes = [
        'array', 'bard', 'grid', 'list', 'markdown', 'redactor', 'replicator',
        'table', 'tags', 'text', 'textarea',
    ];

    private $id;
    private $targetLocale;

    private $content;
    private $contentType;
    private $defaultData;
    private $localizedData;

    private $translatableFields;
    private $translatableData;
    private $fieldKeys;
    private $dataToTranslate;

    private $translatedData;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the translation.
     *
     * @param string $id
     * @param string $targetLocale
     * @return void
     */
    public function handleTranslation(string $id, string $targetLocale): void
    {
        $this->id = $id;
        $this->targetLocale = $targetLocale;

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
    private function getData(): void
    {
        // Get the content by ID.
        $this->content = Content::find($this->id);
        // Get the type of the content.
        $this->contentType = $this->content->contentType();
        // Get the data from the default locale.
        $this->defaultData = $this->content->defaultData();
        // Get the data for the target locale.
        $this->localizedData = $this->content->dataForLocale($this->targetLocale);
    }

    /**
     * Process and prepare the data for translation.
     *
     * @return void
     */
    private function processData(): void
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
    private function getTranslatableFields(): void
    {
        $localizableFields = $this->getLocalizableFields();
        $this->translatableFields = $this->filterSupportedFieldtypes($localizableFields);
    }

    /**
     * Get the translatable data.
     *
     * @return void
     */
    private function getTranslatableData(): void
    {
        $this->translatableData = array_intersect_key($this->defaultData, $this->translatableFields);
    }

    /**
     * Get the keys of translatable fields.
     *
     * @return void
     */
    private function getFieldKeys(): void
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
    private function getDataToTranslate(): void
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
    private function getLocalizableFields(): array
    {
        // Get the fields from the fieldset.
        $fields = collect($this->content->fieldset()->fields());

        // Get the fields where "localizable: true".
        $localizableFields = $fields->where('localizable', true);

        // Add the title field, so it can be translated.
        if ($this->contentType !== 'globals' && ! $localizableFields->has('title')) {
            $localizableFields->put('title', [
                'type' => 'text',
                'localizable' => true,
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
    private function unsetSpecialFields(array $array): array
    {
        // Slug translation will be handled separately
        if ($this->contentType === 'entry') {
            unset($array['slug']);
        }

        // Slug translation will be handled separately
        if ($this->contentType === 'page') {
            unset($array['slug']);
        }

        // The ID should never be translated
        unset($array['id']);

        return $array;
    }

    /**
     * Filter the fields by supported fieldtypes.
     *
     * @param array $fields
     * @return array
     */
    private function filterSupportedFieldtypes(array $fields): array
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
    private function getTranslatableFieldKeys(array $fields): array
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
    private function getTranslatableSetKeys(array $fields): array
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
    private function translateData(): void
    {
        $this->translatedData = Utils::array_map_recursive(
            function ($value, $key) {
                return $this->translate($value, $key);
            },
            $this->dataToTranslate
        );
    }

    /**
     * Translate a given string value.
     *
     * @param mixed $value
     * @param string $key
     * @return mixed
     */
    private function translate($value, string $key)
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
    private function translateSlug(): void
    {
        if ($this->slugShouldBeTranslated()) {
            // Deslugged slug from the unlocalized default content.
            $desluggedSlug = Str::deslugify($this->content->slug());

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
    private function slugShouldBeTranslated(): bool
    {
        // Globals shouldn't have a slug saved to file.
        if ($this->contentType === 'globals') {
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
    private function saveTranslation(): void
    {
        foreach ($this->translatedData as $key => $value) {
            $this->content->in($this->targetLocale)->set($key, $value);
        }

        $this->content->in($this->targetLocale)->save();
    }
}
