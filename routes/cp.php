<?php

use Illuminate\Support\Facades\Route;

Route::post('/translator/translate', '\Aerni\Translator\TranslatorController')->name('translator.translate');
