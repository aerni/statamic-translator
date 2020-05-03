<?php

namespace Statamic\Addons\Translator;

use Illuminate\Http\Request;
use Statamic\Extend\Controller;
use Statamic\Addons\Translator\Contracts\TranslationService;

class TranslatorController extends Controller
{
    protected $translator;
    protected $service;

    public function __construct(Translator $translator, TranslationService $service)
    {
        parent::__construct();
 
        $this->translator = $translator;
        $this->service = $service;
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

    public function getContract()
    {
        $translation = $this->service->translateText('<div class="i">I am the king!</div>', 'de');

        dd($translation);
    }

    public function getLanguages()
    {
        $langauges = $this->service->supportedLanguages();

        dd($langauges);
    }

    public function getLanguage()
    {
        $language = $this->service->detectLanguage('I am a cool guy!');

        dd($language);
    }
}
