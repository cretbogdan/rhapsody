<?php

namespace Rhapsody\Query;

class FilterUtils
{
    public static function cleanValue($value, $comparison)
    {
        if (is_bool($value)) {
            $value = (int) $value;
        }

        if ('in' == strtolower($comparison) && is_array($value)) {
            $value = implode(',', $value);
        }

        return $value;
    }
}
