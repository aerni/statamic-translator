<?php

namespace Statamic\Addons\Translator;

use Illuminate\Support\Collection;
use Statamic\API\Content;
use Statamic\API\Str;
use Statamic\Addons\Translator\Contracts\TranslationService;
use Statamic\Addons\Translator\Utils;

class Translator
{
    protected $service;

    protected $supportedFieldtypes = [
        'array', 'bard', 'grid', 'list', 'markdown', 'redactor', 'replicator',
        'table', 'tags', 'text', 'textarea',
    ];

    protected $targetLocale;

    protected $content;
    protected $contentType;
    protected $defaultContent;
    protected $localizedContent;

    protected $localizableFields;
    protected $supportedFields;

    protected $fieldKeys;

    protected $contentToTranslate;
    protected $translatedContent;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Translate the content associated with the requested ID.
     *
     * @param string $id
     * @param string $targetLocale
     * @return boolean
     */
    public function translate(string $id, string $targetLocale): bool
    {
        // Get all the content associated with the ID.
        $this->content = Content::find($id);
        // Get the type of the content.
        $this->contentType = $this->content->contentType();

        // Get the target locale to translate into.
        $this->targetLocale = $targetLocale;
        
        // Get the unlocalized default content.
        $this->defaultContent = $this->content->defaultData();
        // Get the content that has already been localized into the target locale.
        $this->localizedContent = $this->content->dataForLocale($this->targetLocale);

        // Get the content to translate.
        $this->contentToTranslate = $this->getContentToTranslate();

        // Get the keys of translatable fields.
        $this->fieldKeys = $this->getFieldKeys();

        // Get the translated content.
        $this->translatedContent = $this->translateContent();
        
        // Translate the slug.
        $this->translateSlug();

        // Save the translation.
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
        
        // Get all the supported fields that can be translated.
        $this->supportedFields = $this->getSupportedFields();

        // Get all the content that is translatable.
        $this->translatableContent = $this->getTranslatableContent();

        // Merge localized and translatable content.
        $mergedContent = array_replace_recursive($this->translatableContent, $this->localizedContent);

        // Unset fields that shouldn't be translated.
        $mergedContent = $this->unsetFields($mergedContent);

        return $mergedContent;
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
        if (!$localizableFields->has('title') && $this->contentType !== 'globals') {
            $localizableFields->put('title', [
                'type' => 'text',
                'localizable' => true
            ]);
        };

        return $localizableFields->toArray();
    }

    /**
     * Get all the fields supported for translation.
     *
     * @param array $fields
     * @return array
     */
    private function getSupportedFields(): array
    {
        return $this->filterSupportedFieldtypes($this->localizableFields)->toArray();
    }

    /**
     * Get the translatable content.
     *
     * @return array
     */
    private function getTranslatableContent(): array
    {        
        // Return the content that can be translated. Only first level. No recursion into Bard/Replicator sets.
        return array_intersect_key($this->defaultContent, $this->supportedFields);
    }

    /**
     * Unset fields that shouldn't be translated.
     *
     * @param array $array
     * @return array
     */
    private function unsetFields(array $array): array
    {
        // Remove slug from the content to translate.
        if ($this->contentType === 'entry') {
            unset($array['slug']);
        }
        
        // Remove slug from the content to translate.
        if ($this->contentType === 'page') {
            unset($array['slug']);
        }
        
        // Remove ID from the content to translate.
        unset($array['id']);

        return $array;
    }

    /**
     * Get array of the keys of translatable fields.
     *
     * @return array
     */
    private function getFieldKeys(): array
    {
        return [
            'allKeys' => $this->getTranslatableFieldKeys($this->supportedFields),
            'setKeys' => $this->getTranslatableSetKeys($this->supportedFields),
        ];
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
                    return collect($item['sets'])
                        ->map(function ($set) {
                            $set['fields'] = $this->getTranslatableFieldKeys($set['fields']);
                            return $set['fields'];
                        })->put('text', []);;
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
     * Translate the content into the requested target locale.
     * Return true when the translation was successul.
     * 
     * @return array
     */
    private function translateContent(): array
    {
        return Utils::array_map_recursive(
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
        if (Utils::isHtml($value)) {
            return $this->service->translateText($value, $this->targetLocale, 'html');
        };

        // Translate text
        return $this->service->translateText($value, $this->targetLocale, 'text');
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

        // Skip 'type: $value', where $value is a Bard/Replicator set key.
        if ($key === 'type' && Utils::multi_array_key_exists($value, $this->fieldKeys['setKeys'])) {
            return false;
        }

        // Skip if $key doesn't exists in the fieldset.
        if (! Utils::multi_array_key_exists($key, $this->fieldKeys['allKeys']) && ! is_numeric($key)) {
            return false;
        }

        // Skip if $value is in the target locale.
        if ($this->service->detectLanguage($value) === $this->targetLocale) {
            return false;
        }

        return true;
    }

    /**
     * Translate the slug. Return true when successul.
     *
     * @return bool
     */
    private function translateSlug(): bool
    {
        // Globals shouldn't have a slug saved to file.
        if ($this->contentType === 'globals') {
            return false;
        }

        // Return false if the slug has already been localized.
        if (array_key_exists('slug', $this->localizedContent)) {
            return false;
        }

        // Deslugged slug from the unlocalized default content.
        $desluggedSlug = Str::deslugify($this->content->slug());

        // Translate the deslugged slug.
        $translation = $this->service->translateText($desluggedSlug, $this->targetLocale, 'text');

        // Save translated slug to the translated content.
        $this->translatedContent['slug'] = Str::slug($translation);

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
