## Installation

1. Copy the `Translator` folder into `site/addons/`.
2. Run `php please update:addons`.
3. Configure your prefered translation service in the settings.

***

## Translation Services
Translator uses `Google Cloud Translation` for translation. You can choose between the `Basic` and `Advanced` Cloud Translation edition. They both deliver the same translation. Which one you choose really comes down to personal preference. The `Basic` edition can be set up using a simple `API Key`, while the set up of the `Advanced` edition is a little bit more complicated.

> Learn more about [Google Cloud Translation](https://cloud.google.com/translate/docs).

***

## Requirements
1. At least two locales set up in your Statamic installation.
2. The locale shorthands have to be `ISO-639-1` language codes.

> Here's a [complete list of supported languages](https://cloud.google.com/translate/docs/languages) and their `ISO-639-1` language codes.

***

## Configuration
Head to `Configure -> Addons -> Translator` in the CP and configure your prefered translation service. 

The addon is pre-configured to use `Google Cloud Translation – Basic (v2)`. It will also get your authentication credentials from your `.env` file by default:

```yaml
translation_service: 'google_basic'
google_translation_api_key: '{env:GOOGLE_TRANSLATION_API_KEY}'
google_application_credentials: '{env:GOOGLE_APPLICATION_CREDENTIALS}'
google_cloud_project: '{env:GOOGLE_CLOUD_PROJECT}'
```

Set the necessary variables in your `.env` file:

```env
GOOGLE_TRANSLATION_API_KEY=********************************
GOOGLE_CLOUD_PROJECT=********************************
GOOGLE_APPLICATION_CREDENTIALS=********************************
```

> You can change the default config by creating `site/settings/addons/translator.yaml` and adding the desired config keys and values.

***

## Basic Usage
1. Add the `Translator` fieldtype to your fieldset and make it `localizable`.
2. Make all fields `localizable` that you want to translate.
3. Navigate to the content you want to translate.
4. Select the locale you want to translate into.
5. Hit the Translator `Translate Content` button and wait for the translation to finish.

> **Important:** Give a unique name to each field variable in your fieldset. This will make sure that Translator only translates supported fields.

### Supported Fieldtypes
The following fieldtypes are supported for translation: `array`, `bard`, `grid`, `list`, `markdown`, `redactor`, `replicator`, `table`, `tags`, `text`, `textarea`.

> **Note:** The `array` fieldtype can only be translated when the keys are predefined.

### Supported Content Types
Translator works with the following content types: `Pages`, `Collections`, `Taxonomies`, `Globals`

### Translator Fieldtype Options
You can customize the text of the "Translate Content" button by adding `button_text: New Button Text` to the Translator field in your fieldset. You can also customize the text when editing the fieldset in the CP.

***

## Modifier
You can use the modifier to translate variables in your template. This can come in handy if you need to translate fixed values and labels.

### Basic Usage
Pass the target language as parameter to the modifier. The parameter has to be a supported `ISO-639-1` language code.

```html
<!-- Translate {{ variable }} to German -->
{{ variable | translator:de }}

<!-- Translate {{ variable }} to the current locale -->
{{ variable translator="{{ locale }}" }}
```
