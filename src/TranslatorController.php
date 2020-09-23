<?php

namespace Aerni\Translator;

use Illuminate\Http\Request;

class TranslatorController
{
    public function __invoke(Request $request)
    {
        return (new TranslatorProcessor($request))->process();
    }
}
