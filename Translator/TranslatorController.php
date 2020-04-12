<?php

namespace Statamic\Addons\Translator;

use Statamic\Extend\Controller;

class TranslatorController extends Controller
{
    /**
     * Maps to your route definition in routes.yaml
     *
     * @return mixed
     */
    public function index()
    {
        return $this->view('index');
    }
}
