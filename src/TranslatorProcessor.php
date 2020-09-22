<?php

namespace Aerni\Translator;

use Exception;
use Illuminate\Http\Request;
use Aerni\Translator\RequestValidator;
use Aerni\Translator\Data\DataTranslator;
use Symfony\Component\HttpFoundation\Response;
use Aerni\Translator\Exceptions\TranslationFailed;

class TranslatorProcessor
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function process()
    {
        return $this
            ->ensureValidRequest()
            ->processTranslation();
    }

    protected function ensureValidRequest(): self
    {
        RequestValidator::isValid($this->request);

        return $this;
    }

    protected function processTranslation(): Response
    {
        try {
            (new DataTranslator($this->request->id, $this->request->targetSite))
                ->process()
                ->save();
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }

        return $this->successResponse();
    }

    protected function successResponse(): Response
    {
        return response()->json([
            'message' => __('translator::fieldtypes.translator.vue_component.success'),
        ], 200);
    }

    protected function errorResponse(Exception $e): Response
    {
        return response()->json([
            'error' => json_decode($e->getMessage(), true)['error']
        ], $e->getCode());
    }
}
