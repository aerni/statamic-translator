<?php

namespace Aerni\Translator\Data\Concerns;

use Aerni\Translator\Support\Utils;
use Illuminate\Support\Collection;

trait PreparesData
{
    /**
     * Get the translatable fields. A field is considered translatable
     * when 'localizable' is set to 'true' in the blueprint and
     * the type of field is supported by Translator.
     *
     * @return array
     */
    protected function translatableFields(): array
    {
        return $this->filterSupportedFieldtypes($this->localizableFields());
    }

    /**
     * Get the blueprint fields that are localizable.
     *
     * @return array
     */
    protected function localizableFields(): array
    {
        return $this->entry->blueprint()
            ->fields()
            ->localizable()
            ->all()
            ->toArray();
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
                $supportedFieldtypes = [
                    'array', 'bard', 'grid', 'list', 'markdown', 'replicator',
                    'slug', 'table', 'tags', 'text', 'textarea',
                ];

                $supported = in_array($item['type'], $supportedFieldtypes);

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
    * Get the translatable data.
    *
    * @return Collection
    */
    protected function translatableData(): Collection
    {
        return $this->rootData()->intersectByKeys($this->translatableFields());
    }

    /**
     * Get the keys of translatable fields.
     *
     * @return array
     */
    protected function fieldKeys(): array
    {
        return [
            'allKeys' => $this->getTranslatableFieldKeys($this->translatableFields()),
            'setKeys' => $this->getTranslatableSetKeys($this->translatableFields()),
        ];
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
     * Get the data to translate.
     *
     * @return array
     */
    protected function dataToTranslate(): array
    {
        return $this->translatableData()
            ->replaceRecursive($this->localizedData())
            ->except(['updated_by', 'updated_at'])
            ->toArray();
    }
}
