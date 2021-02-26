<?php

namespace NewsML_G2\Plugin\Tools;

class ToolsDir
{
    /**
     * Recursive version of @see rmdir().
     *
     * @param $dir
     *  Path to dir.
     * @return bool
     *  Is removed.
     *
     * @see rmdir().
     *
     * @author Alexander Kucherov
     * @since 1.2.0
     */
    public static function rmdir_recursive($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!self::rmdir_recursive($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
