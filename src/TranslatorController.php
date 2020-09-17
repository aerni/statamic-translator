<?php

namespace Aerni\Translator;

use Exception;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\Controller;
use Facades\Aerni\Translator\Translator;

class TranslatorController extends Controller
{
    public function postTranslate(Request $request)
    {
        try {
            Translator::handleTranslation($request->id, $request->targetSite);

            return response()->json([
                'status' => 200,
                'message' => __('translator::fieldtypes.translator.vue_component.success'),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
