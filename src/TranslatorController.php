<?php

namespace Aerni\Translator;

use Aerni\Translator\TranslatorProcessor;
use Illuminate\Http\Request;

class TranslatorController
{
    public function __invoke(Request $request)
    {
        return (new TranslatorProcessor($request))->process();
    }
}
