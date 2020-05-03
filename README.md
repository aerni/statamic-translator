# Statamic Translator

## Translatable Fieldtypes
| Supported | Unsupported | Partially Supported |
|-----------|---------------------|-------------|
| `bard`, `grid`, `list`, `markdown`, `redactor`, `replicator`, `table`, `tags`, `text`, `textarea` | `assets`, `checkboxes`, `collection`, `collections`, `date`, `fieldset`, `form`, `hidden`, `integer`, `pages`, `radio`, `revealer`, `select`, `suggest`, `taxonomy`, `template`, `theme`, `time`, `toggle`, `users`, `video`, `yaml` | `array` |

LIMITATION: array with variable values can't be translated, because the key can't be found in the fieldset to compare to.
Maybe support: array, select

## Modifier
The modifier can come in handy to translate fixed values from within your template. Like labels of checkboxes, radio or select fieldtypes.

## Licensing

Statamic Translator is commercial software but has an open-source codebase. If you want to use Statamic Translator in production, you'll need to buy a license from the Statamic Marketplace.
