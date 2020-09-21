<?php

namespace Aerni\Translator;

use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Illuminate\Http\Request;

class RequestValidator
{
    /**
     * Check if the request includes all the necessary parameters
     * to process the translation.
     *
     * @param Request $request
     * @return bool
     */
    public function isValid(Request $request): bool
    {
        if (! $request->id) {
            return false;
        }

        if (! Data::find($request->id)) {
            return false;
        }

        if (! $request->targetSite) {
            return false;
        }

        if (! Site::get($request->targetSite)) {
            return false;
        }

        return true;
    }
}
