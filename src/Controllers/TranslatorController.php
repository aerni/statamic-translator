<?php

namespace Aerni\Translator\Controllers;

use Illuminate\Http\Request;
use Aerni\Translator\TranslatorProcessor;

class TranslatorController extends ApiController
{
    public function __invoke(Request $request)
    {
        return (new TranslatorProcessor($request))->process();
    }
}
