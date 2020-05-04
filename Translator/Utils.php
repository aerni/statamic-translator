<?php

namespace Statamic\Addons\Translator;

class Utils
{
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

    public static function array_intersect_key_recursive(array $array1, array $array2)
    {
        $array1 = array_intersect_key($array1, $array2);

        foreach ($array1 as $key => &$value) {
            if (is_array($value)) {
                $value = is_array($array2[$key]) ? self::array_intersect_key_recursive($value, $array2[$key]) : $value;
            }
        }

        return $array1;
    }

    public static function array_keys_recursive(array $array)
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $keys[] = $key;

            if (is_array($array[$key])) {
                $keys = array_merge($keys, self::array_keys_recursive($array[$key]));
            }
        }

        return $keys;
    }

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

    public static function recursive_unset(&$array, $unwanted_key)
    {
        unset($array[$unwanted_key]);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::recursive_unset($value, $unwanted_key);
            }
        }
    }

    public static function array_walk_recursive_delete(array &$array, callable $callback, $userdata = null)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::array_walk_recursive_delete($value, $callback, $userdata);
            }
            if ($callback($value, $key, $userdata)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public static function has_number_keys(array $array)
    {
        return count(array_filter(array_keys($array), 'is_numeric')) > 0;
    }

    public static function search($array, $key, $value)
    {
        $results = [];

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, self::search($subarray, $key, $value));
            }
        }

        return $results;
    }

    public static function isHtml($string)
    {
        return $string != strip_tags($string) ? true : false;
    }
}
