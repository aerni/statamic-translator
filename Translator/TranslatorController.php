<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\Controller;
use Statamic\API\Request;

class TranslatorController extends Controller
{
    protected $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->translator = $translator;
    }
    
    public function index(string $uri): void
    {
        $locale = Request::input('locale');

        if (empty($locale)) {
            return;
        }

        $this->translator->translate($uri, $locale);
    }
}
