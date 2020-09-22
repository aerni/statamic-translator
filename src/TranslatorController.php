<?php

namespace Aerni\Translator;

use Illuminate\Http\Request;
use Aerni\Translator\TranslatorProcessor;

class TranslatorController
{
    public function __invoke(Request $request)
    {
        return (new TranslatorProcessor($request))->process();
    }
}
