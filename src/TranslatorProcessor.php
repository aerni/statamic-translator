<?php

namespace Aerni\Translator;

use Illuminate\Http\Request;
use Aerni\Translator\Data\Translator;
use Illuminate\Support\Facades\App;
use Aerni\Translator\RequestValidator;
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
        $this
            ->ensureValidRequest()
            ->processTranslation();

        // TODO: Add exception handling like in the old controller.

        return $this->createResponse();
    }

    protected function ensureValidRequest(): self
    {
        $isValid = resolve(RequestValidator::class)->isValid($this->request);

        if (! $isValid) {
            throw TranslationFailed::invalidRequest();
        }

        return $this;
    }

    protected function processTranslation(): self
    {
        App::makeWith(Translator::class, [
            'id' => $this->request->id,
            'targetSite' => $this->request->targetSite
        ])->process();

        return $this;
    }

    protected function createResponse(): Response
    {
        return response()->json([
            'message' => __('translator::fieldtypes.translator.vue_component.success'),
        ], 200);
    }
}
