<?php

namespace Statamic\Addons\Translator;

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

    public function postTranslate(Request $request): array
    {
        $this->translator->translate($request->id, $request->targetLocale);

        return [
            'message' => 'Translation successful!',
        ];
    }

    public function getTranslate(string $id, string $targetLocale): array
    {
        $this->translator->translate($id, $targetLocale);

        return [
            'message' => 'Translation successful!',
        ];
    }
}
