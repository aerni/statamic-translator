<?php

namespace Aerni\Translator\Data;

use Aerni\Translator\Utils;
use Aerni\Translator\Contracts\TranslationService;
use Aerni\Translator\Exceptions\TranslationFailed;
use Aerni\Translator\Data\Concerns\TranslatorGuards;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class Translator
{
    use TranslatorGuards;

    protected $supportedFieldtypes = [
        'array', 'bard', 'grid', 'list', 'markdown', 'replicator',
        'slug', 'table', 'tags', 'text', 'textarea',
    ];

    protected $service;
    protected $entry;

    protected $targetSite;
    protected $targetLanguage;
    protected $rootData;
    protected $localizedData;

    protected $translatableFields;
    protected $translatableData;
    protected $fieldKeys;
    protected $dataToTranslate;

    protected $translatedData;
    protected $translatedEntry;

    public function __construct(TranslationService $service, string $id, string $targetSite)
    {
        $this->service = $service;
        $this->entry = Data::find($id);
        $this->targetSite = $targetSite;
    }

    /**
     * Prepare the data for translation.
     *
     * @return self
     */
    public function process(): self
    {
        if (! $this->shouldProcessData()) {
            throw TranslationFailed::canNotTranslateRoot();
        }

        $this->targetLanguage = $this->getTargetLanguage();
        $this->rootData = $this->getRootData();
        $this->localizedData = $this->getlocalizedData();

        $this->translatableFields = $this->getTranslatableFields();
        $this->translatableData = $this->getTranslatableData();
        $this->fieldKeys = $this->getFieldKeys();
        $this->dataToTranslate = $this->getDataToTranslate();

        $this
            ->translateEntry()
            ->save();

        return $this;
    }

    /**
     * Returns the translated Entry/GlobalSet.
     *
     * @return mixed
     */
    protected function translateEntry()
    {
        $translatedData = $this->translateData($this->dataToTranslate);

        if ($this->contentType($this->entry) === 'Entry') {
            return $this->entry->slug($this->slug())
                ->data($translatedData);
        }

        if ($this->contentType($this->entry) === 'GlobalSet') {
            return $this->entry->in($this->targetSite)
                ->data($translatedData);
        }
    }

    /**
     * Get the entry's root data.
     *
     * @return array
     */
    protected function getRootData(): array
    {
        if ($this->contentType($this->entry) === 'Entry') {
            return $this->entry->root()->data()->toArray();
        }

        if ($this->contentType($this->entry) === 'GlobalSet') {
            return $this->entry->inDefaultSite()->data()->toArray();
        }
    }

    /**
    * Get the entry's localized data.
    *
    * @return array
    */
    protected function getLocalizedData(): array
    {
        if ($this->contentType($this->entry) === 'Entry') {
            return $this->entry->data()->toArray();
        }

        if ($this->contentType($this->entry) === 'GlobalSet') {
            return $this->entry->in($this->targetSite)->data()->toArray();
        }
    }

    /**
     * Get the translatable fields. A field is considered translatable
     * when 'localizable' is set to 'true' in the blueprint and
     * the type of field is supported by Translator.
     *
     * @return array
     */
    protected function getTranslatableFields(): array
    {
        $localizableFields = $this->entry
            ->blueprint()
            ->fields()
            ->localizable()
            ->all()
            ->toArray();

        return $this->filterSupportedFieldtypes($localizableFields);
    }

    /**
    * Get the translatable data.
    *
    * @return array
    */
    protected function getTranslatableData(): array
    {
        return array_intersect_key($this->rootData, $this->translatableFields);
    }

    /**
     * Get the keys of translatable fields.
     *
     * @return array
     */
    protected function getFieldKeys(): array
    {
        return [
            'allKeys' => $this->getTranslatableFieldKeys($this->translatableFields),
            'setKeys' => $this->getTranslatableSetKeys($this->translatableFields),
        ];
    }

    /**
     * Get the data to translate.
     *
     * @return array
     */
    protected function getDataToTranslate(): array
    {
        // Merge translatable with localized data.
        $mergedData = array_replace_recursive($this->translatableData, $this->localizedData);

        // Unset fields that shouldn't be translated.
        return $this->unsetSpecialFields($mergedData);
    }

    /**
     * Get the type of content.
     *
     * @param mixed $class
     * @return string
     */
    protected function contentType($class): string
    {
        if ($class instanceof \Statamic\Globals\GlobalSet) {
            return 'GlobalSet';
        }

        if ($class instanceof \Statamic\Entries\Entry) {
            return 'Entry';
        }

        throw TranslationFailed::unsupportedContentType();
    }

    /**
     * Get the target language for translation.
     *
     * @return string
     */
    protected function getTargetLanguage(): string
    {
        return Site::get($this->targetSite)->shortLocale();
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
     * Unset fields that shouldn't be translated.
     *
     * @param array $array
     * @return array
     */
    protected function unsetSpecialFields(array $array): array
    {
        unset($array['updated_by']);
        unset($array['updated_at']);

        return $array;
    }

    /**
     * Translate the data.
     *
     * @return array
     */
    protected function translateData($data): array
    {
        return Utils::array_map_recursive(
            $data,
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
            return $this->service->translateText($value, $this->targetLanguage, 'html');
        }

        // Translate text
        return $this->service->translateText($value, $this->targetLanguage, 'text');
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
        if ($this->service->detectLanguage($value) === $this->targetLanguage) {
            return false;
        }

        return true;
    }

    /**
     * Returns a translated or untranslated slug.
     *
     * @return string
     */
    protected function slug(): string
    {
        $slug = $this->entry->slug();

        if (! array_key_exists('slug', $this->translatableFields)) {
            return $slug;
        }

        return $this->service->translateText(Str::deslugify($slug), $this->targetLanguage, 'text');
    }
}
