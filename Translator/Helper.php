<?php

namespace Statamic\Addons\Translator;

class Helper
{
    static public function array_map_recursive($callback, $input) {
        $output = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $output[$key] = Self::array_map_recursive($callback, $value);
            } else {
                $output[$key] = $callback($value, $key);
            }
        }
        return $output;
    } 

    static public function multi_array_key_exists($key, array $array): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        } else {
            foreach ($array as $nested) {
                if (is_array($nested) && Self::multi_array_key_exists($key, $nested))
                    return true;
            }
        }
        return false;
    }

    static public function array_intersect_key_recursive(array $array1, array $array2) 
    {
        $array1 = array_intersect_key($array1, $array2);
        
        foreach ($array1 as $key => &$value) 
        {
            if (is_array($value)) 
            {
                $value = is_array($array2[$key]) ? Self::array_intersect_key_recursive($value, $array2[$key]) : $value;
            }
        }
        return $array1;
    }

    static public function array_keys_recursive(array $array)
    {
        $keys = array();

        foreach ($array as $key => $value) {
            $keys[] = $key;

            if (is_array($array[$key])) {
                $keys = array_merge($keys, Self::array_keys_recursive($array[$key]));
            }
        }

        return $keys;
    }

    static public function array_filter_recursive( array $array, callable $callback = null ) {
        $array = is_callable( $callback ) ? array_filter( $array, $callback ) : array_filter( $array );
        foreach ( $array as &$value ) {
            if ( is_array( $value ) ) {
                $value = call_user_func( __FUNCTION__, $value, $callback );
            }
        }
    
        return $array;
    }

    static public function recursive_unset(&$array, $unwanted_key) {
        unset($array[$unwanted_key]);
        foreach ($array as &$value) {
            if (is_array($value)) {
                Self::recursive_unset($value, $unwanted_key);
            }
        }
    }

    static public function array_walk_recursive_delete(array &$array, callable $callback, $userdata = null)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = Self::array_walk_recursive_delete($value, $callback, $userdata);
            }
            if ($callback($value, $key, $userdata)) {
                unset($array[$key]);
            }
        }
     
        return $array;
    }
}
