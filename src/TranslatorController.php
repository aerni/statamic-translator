<?php

namespace Aerni\Translator;

use Exception;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\Controller;

class TranslatorController extends Controller
{
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function postTranslate(Request $request)
    {
        try {
            $this->translator->handleTranslation($request->id, $request->targetLocale);

            return response()->json([
                'status' => 200,
                'message' => __('translator.fieldtype.success'),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
