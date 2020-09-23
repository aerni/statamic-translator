<?php

namespace Aerni\Translator;

use Aerni\Translator\Data\DataTranslator;
use Aerni\Translator\Support\RequestValidator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslatorProcessor
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function process(): Response
    {
        return $this
            ->ensureValidRequest()
            ->translate()
            ->successResponse();
    }

    protected function ensureValidRequest(): self
    {
        RequestValidator::isValid($this->request);

        return $this;
    }

    protected function translate(): self
    {
        (new DataTranslator($this->request->id, $this->request->site))
            ->process()
            ->save();

        return $this;
    }

    protected function successResponse(): Response
    {
        return response()->json([
            'message' => __('translator::fieldtypes.translator.vue_component.success'),
        ], 200);
    }
}
