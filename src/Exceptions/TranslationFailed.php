<?php

namespace Aerni\Translator\Exceptions;

use Exception;

class TranslationFailed extends Exception
{
    public static function invalidRequest(): TranslationFailed
    {
        return new static('The translation request is invalid.');
    }

    public static function invalidTargetSite(): TranslationFailed
    {
        return new static('The requested target site does not exists.');
    }

    public static function invalidId(): TranslationFailed
    {
        return new static('The requested id does not exists.');
    }

    public static function missingTargetSite(): TranslationFailed
    {
        return new static('The target site is missing.');
    }

    public static function missingId(): TranslationFailed
    {
        return new static('The id is missing.');
    }

    public static function unsupportedContentType(): TranslationFailed
    {
        return new static('This content type is not supported for translation.');
    }

    public static function canNotTranslateRoot(): TranslationFailed
    {
        return new static('Can not translate the root locale.');
    }
}
