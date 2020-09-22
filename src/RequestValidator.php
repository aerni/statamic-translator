<?php

namespace Aerni\Translator;

use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Illuminate\Http\Request;
use Aerni\Translator\Exceptions\TranslationFailed;

class RequestValidator
{
    /**
     * Check if the request includes all the necessary parameters
     * to process the translation.
     *
     * @param Request $request
     * @throws TranslationFailed
     * @return bool
     */
    public static function isValid(Request $request): bool
    {
        if (! $request->id) {
            throw TranslationFailed::missingId();
        }

        if (! Data::find($request->id)) {
            throw TranslationFailed::invalidId();
        }

        if (! $request->targetSite) {
            throw TranslationFailed::missingTargetSite();
        }

        if (! Site::get($request->targetSite)) {
            throw TranslationFailed::invalidTargetSite();
        }

        return true;
    }
}
