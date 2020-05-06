<?php

namespace Statamic\Addons\Translator;

class Utils
{
    public static function array_filter_recursive(array $array, callable $callback = null)
    {
        $array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = call_user_func(__FUNCTION__, $value, $callback);
            }
        }

        return $array;
    }
    
    public static function array_map_recursive($callback, $input)
    {
        $output = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $output[$key] = self::array_map_recursive($callback, $value);
            } else {
                $output[$key] = $callback($value, $key);
            }
        }

        return $output;
    }


    public static function multi_array_key_exists($key, array $array): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        } else {
            foreach ($array as $nested) {
                if (is_array($nested) && self::multi_array_key_exists($key, $nested)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isHtml($string)
    {
        return $string != strip_tags($string) ? true : false;
    }
}
