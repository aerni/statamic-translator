<?php

namespace Aerni\Translator;

class Utils
{
    /**
     * Recursively filter an array.
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function array_filter_recursive(array $array, callable $callback = null): array
    {
        $array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = call_user_func(__FUNCTION__, $value, $callback);
            }
        }

        return $array;
    }

    /**
     * Recursively check if a key exists in an array.
     *
     * @param mixed $key
     * @param array $array
     * @return bool
     */
    public static function array_key_exists_recursive($key, array $array): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        } else {
            foreach ($array as $nested) {
                if (is_array($nested) && self::array_key_exists_recursive($key, $nested)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Recursively map an array to a callback function.
     *
     * @param array $array
     * @param function $callback
     * @return array
     */
    public static function array_map_recursive(array $array, $callback): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $output[$key] = self::array_map_recursive($value, $callback);
            } else {
                $output[$key] = $callback($value, $key);
            }
        }

        return $output;
    }

    /**
     * Check if the provided string is HTML or not.
     *
     * @param string $string
     * @return bool
     */
    public static function isHtml(string $string): bool
    {
        return $string != strip_tags($string) ? true : false;
    }
}
