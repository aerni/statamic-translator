<?php

namespace Aerni\Translator;

use Aerni\Translator\Support\RequestValidator;
use Aerni\Translator\Data\DataTranslator;
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
        $this
            ->ensureValidRequest()
            ->translateData();

        return $this->successResponse();
    }

    protected function ensureValidRequest(): self
    {
        RequestValidator::isValid($this->request);

        return $this;
    }

    protected function translateData(): void
    {
        (new DataTranslator($this->request->id, $this->request->site))
            ->process()
            ->save();
    }

    protected function successResponse(): Response
    {
        return response()->json([
            'message' => __('translator::fieldtypes.translator.vue_component.success'),
        ], 200);
    }
}
