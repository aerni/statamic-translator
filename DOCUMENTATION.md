## Installation
Install the addon using Composer.

```bash
composer require aerni/translator
```

Publish the config of the package.

```bash
php please vendor:publish --tag=translator-config
```

The following config will be published to `config/translator.php`.

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Translation Service
    |--------------------------------------------------------------------------
    |
    | Choose your prefered translation service.
    | Possible values: 'google_basic', 'google_advanced'
    |
    */

    'translation_service' => 'google_basic',

    /*
    |--------------------------------------------------------------------------
    | Google Translation API Key
    |--------------------------------------------------------------------------
    |
    | Your Google Translation API Key. This only works with 'google_basic'.
    |
    */

    'google_translation_api_key' => env('GOOGLE_TRANSLATION_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Google Application Credentials
    |--------------------------------------------------------------------------
    |
    | The path to your application credentials json.
    |
    */

    'google_application_credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Project
    |--------------------------------------------------------------------------
    |
    | Your Google Cloud Project.
    |
    */

    'google_cloud_project' => env('GOOGLE_CLOUD_PROJECT'),
];
```

## Configuration
Translator is pre-configured to use `Google Cloud Translation – Basic (v2)`. You can change your prefered service in the config. Set your authentication credentials in your `.env` file.

```env
GOOGLE_TRANSLATION_API_KEY=********************************
GOOGLE_CLOUD_PROJECT=********************************
GOOGLE_APPLICATION_CREDENTIALS=********************************
```

## Basic Usage
1. Add the `Translator` fieldtype to your blueprint.
2. Make sure to add `localizable: true` to the fields you want to translate.
3. Navigate to the Entry, Term or Global you want to translate.
4. Select the site you want to translate.
5. Hit the `Translate Content` button and wait for the translation to finish.

> **Important:** Give each field in your blueprint a unique handle. This will make sure that Translator only translates supported fields.

### Supported Fieldtypes
The following fieldtypes are supported for translation: `array`, `bard`, `grid`, `list`, `markdown`, `redactor`, `replicator`, `slug`, `table`, `tags`, `text`, `textarea`.

> **Note:** The `array` fieldtype can only be translated when the keys are predefined.

### Supported Content Types
Translator works with `Collections`, `Taxonomies` and `Globals`.

### Translator Fieldtype Options
You may customize the text of the "Translate Content" button by adding `button_label: My very special button text` to the Translator field in your blueprint. You can also customize the label in the blueprint editor in the CP.

## Translate Modifier
You can use the `translate` modifier to translate variables in your template. This can come in handy to translate fixed values and labels.

Translate a variable into the language of the currently active site:
```html
{{ variable | translate }}
```

You can also pass the desired target language to the modifier. The parameter has to be a supported `ISO-639-1` language code.
```html
{{ variable | translate:de }}
```

## Translate Tag
You may prefer to use the `translate` tag instead of the modifier.

Translate a variable into the language of the currently active site:
```html
<!-- The nice shorthand syntax -->
{{ translate:variable }}

<!-- The regular syntax -->
{{ translate :value="variable" }}
```

Translate any value instead of a variable:
```html
{{ translate value="This is a very special text!" }}
```

You can also pass the desired target language to the `locale` parameter. The value has to be a supported `ISO-639-1` language code.
```html
{{ translate:variable locale="de" }}
```
