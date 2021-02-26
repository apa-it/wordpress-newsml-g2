<?php

namespace NewsML_G2\Plugin\Tools;

class ToolsArray
{
    /**
     * Recursive version of @see array_diff().
     *
     * @param $aArray1
     *   Array.
     * @param $aArray2
     *   Array.
     *
     * @author Alexander Kucherov
     * @since 1.2.0
     */
    public static function array_recursive_diff(&$aArray1, $aArray2): void
    {
        foreach ($aArray1 as $key => &$value) {
            if (in_array($value, $aArray2)) {
                unset($aArray1[$key]);
            } elseif (is_array($value)) {
                self::array_recursive_diff($value, $aArray2);
            }
        }
        // Clean-up after yourself.
        unset($value);
    }
}
