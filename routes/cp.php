<?php

use Aerni\Translator\TranslatorController;
use Illuminate\Support\Facades\Route;

Route::post('/translator/translate', [TranslatorController::class, 'postTranslate']);
