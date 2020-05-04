<?php

namespace Statamic\Addons\Translator;

use Exception;
use Illuminate\Http\Request;
use Statamic\Extend\Controller;

class TranslatorController extends Controller
{
    protected $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();
 
        $this->translator = $translator;
    }

    public function postTranslate(Request $request)
    {
        try {

            $this->translator->translate($request->id, $request->targetLocale);

            return response()->json([
                'status' => 200,
                'message' => translate('addons.Translator::fieldtype.success')
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());

        }
    }

    public function getTranslate(string $id, string $targetLocale)
    {
        try {

            $this->translator->translate($id, $targetLocale);

            return response()->json([
                'status' => 200,
                'message' => translate('addons.Translator::fieldtype.success')
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());

        }
    }
}
