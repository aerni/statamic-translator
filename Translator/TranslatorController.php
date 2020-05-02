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
