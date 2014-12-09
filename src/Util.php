<?php
namespace ddliu\filecache;

class Util {
    public static function writeFile($path, $content) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                return false;
            }
        }

        return file_put_contents($path, $content);
    }

    /**
     * Remove directory recursively.
     * @param  string $path
     * @return boolean
     */
    public static function removeDir($path) {
        if (!is_dir($path)) {
            return true;
        }

        if (!$dirh = opendir($path)) {
            return false;
        }

        $result = true;
        while (($file = readdir($dirh)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $child = $path . '/' . $file;
            if (is_dir($child)) {
                $result = self::removeDir($child);
            } else {
                $result = unlink($child);
            }

            if (!$result) {
                break;
            }
        }

        closedir($dirh);
        if (!$result) {
            return false;
        }

        return rmdir($path);
    }
}