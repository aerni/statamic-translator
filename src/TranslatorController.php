<?php

namespace Aerni\Translator;

use Exception;
use Illuminate\Http\Request;
use Aerni\Translator\Data\TranslateData;
use Aerni\Translator\TranslatorProcessor;

class TranslatorController
{
    public function __invoke(Request $request)
    {
        return (new TranslatorProcessor($request))->process();
    }
}
