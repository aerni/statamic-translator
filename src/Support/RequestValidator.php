<?php

namespace Aerni\Translator\Support;

use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Entries\Entry;
use Illuminate\Http\Request;
use Statamic\Globals\GlobalSet;
use Aerni\Translator\Exceptions\TranslationFailed;

class RequestValidator
{
    /**
     * Check if the request includes all the necessary parameters
     * to process the translation.
     *
     * @param Request $request
     * @return bool
     * @throws TranslationFailed
     */
    public static function isValid(Request $request): bool
    {
        if (! $request->id) {
            throw TranslationFailed::missingId();
        }

        if (! Data::find($request->id)) {
            throw TranslationFailed::invalidId();
        }

        if (! $request->site) {
            throw TranslationFailed::missingSite();
        }

        if (! Site::get($request->site)) {
            throw TranslationFailed::invalidSite();
        }

        return true;
    }

    /**
     * Check if an entry can be translated.
     *
     * @param Entry $entry
     * @param string $site
     * @return bool
     * @throws TranslationFailed
     */
    public static function canProcessEntry(Entry $entry, string $site): bool
    {
        if ($entry->isRoot()) {
            throw TranslationFailed::canNotTranslateRoot();
        }

        if ($entry->locale() !== $site) {
            throw TranslationFailed::canNotTranslateRoot();
        }

        return true;
    }

    /**
     * Check if a global set can be translated.
     *
     * @param GlobalSet $entry
     * @param string $site
     * @return bool
     * @throws TranslationFailed
     */
    public static function canProcessGlobalSet(GlobalSet $entry, string $site): bool
    {
        if ($entry->localizations()->get($site)->origin() === null) {
            throw TranslationFailed::canNotTranslateRoot();
        }

        return true;
    }
}
