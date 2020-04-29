<?php

namespace Statamic\Addons\Translator;

use Illuminate\Http\Request;
use Statamic\Extend\Controller;

class TranslatorController extends Controller
{
    protected $googleTranslate;
    protected $translator;

    public function __construct(GoogleTranslate $googleTranslate, Translator $translator)
    {
        parent::__construct();
 
        $this->googleTranslate = $googleTranslate;
        $this->translator = $translator;
    }

    public function postTranslate(Request $request): array
    {
        $id = $request->id;
        $sourceLocale = $request->sourceLocale;
        $targetLocale = $request->targetLocale;

        if ($sourceLocale === $targetLocale) {
            return [
                'message' => 'Can not translate the default locale.',
            ];
        }

        $this->translator->translate($id, $targetLocale);

        return [
            'message' => 'Translation successful!',
        ];
    }

    public function getTranslate(string $id, string $sourceLocale, string $targetLocale): array
    {
        if ($sourceLocale === $targetLocale) {
            return [
                'message' => 'Can not translate the default locale.',
            ];
        }

        $this->translator->translate($id, $targetLocale);

        return [
            'message' => 'Translation successful!',
        ];
    }
}
