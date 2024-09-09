<?php

namespace OCA\Collaboration;

class Util
{
    /**
     * Returns (bool)true on $val:
     * bool true, int != 0, "true", "1"
     *
     */
    public static function isTrue($val, $return_null = false): bool
    {
        $boolval = (is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val);
        return ($boolval === null && !$return_null ? false : $boolval);
    }
}
