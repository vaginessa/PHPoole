<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

/**
 * Class Util
 * @package PHPoole
 */
class Util
{
    /**
     * @param $filename
     * @param $content
     * @throws \Exception
     */
    public static function writeFile($filename, $content)
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            @mkdir($dir);
        } elseif (!is_writable($dir)) {
            throw new \Exception(sprintf('Unable to write to the "%s" directory.', $dir));
        }
        $tmpFile = tempnam($dir, basename($filename));
        if (false === @file_put_contents($tmpFile, $content)) {
            throw new \Exception(sprintf('Failed to write file "%s".', $filename));
        }
        @rename($tmpFile, $filename, true);
    }

    /**
     * Recursively remove a directory
     *
     * @param $dirname
     * @param bool $followSymlinks
     * @return bool
     * @throws \Exception
     */
    public static function rmDir($dirname, $followSymlinks=false)
    {
        if (is_dir($dirname) && !is_link($dirname)) {
            if (!is_writable($dirname)) {
                throw new \Exception(sprintf('%s is not writable!', $dirname));
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dirname),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    if (!$iterator->isWritable()) {
                        throw new \Exception(sprintf(
                            '%s is not writable!',
                            $iterator->getPathName()
                        ));
                    }
                    if ($iterator->isLink() && $followLinks === false) {
                        $iterator->next();
                    }
                    if ($iterator->isFile()) {
                        @unlink($iterator->getPathName());
                    }
                    elseif ($iterator->isDir()) {
                        @rmdir($iterator->getPathName());
                    }
                }
                $iterator->next();
            }
            unset($iterator);
     
            return @rmdir($dirname);
        }
        else {
            throw new \Exception(sprintf('%s does not exist!', $dirname));
        }
    }

    /**
     * Copy a dir, and all its content from source to dest
     *
     * @param $source
     * @param $dest
     */
    public static function copy($source, $dest)
    {
        if (!is_dir($dest)) {
            @mkdir($dest);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $source,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @mkdir($dest . DS . $iterator->getSubPathName());
            }
            else {
                @copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }

    /**
     * Execute git commands
     *
     * @param $wd
     * @param $commands
     */
    public static function runGitCmd($wd, $commands)
    {
        $cwd = getcwd();
        chdir($wd);
        exec('git config core.autocrlf false');
        foreach ($commands as $cmd) {
            //printf("> git %s\n", $cmd);
            exec(sprintf('git %s', $cmd));
        }
        chdir($cwd);
    }

    /**
     * Check if current OS is Windows
     *
     * @return bool
     */
    public static function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public static function slugify($string)
    {    
        return md5($string);

        $separator = '-';
        $string = preg_replace('/
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
            /', '', $string);
        // @see https://github.com/cocur/slugify/blob/master/src/Cocur/Slugify/Slugify.php
     
        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        // replace non letter or digits by seperator
        $string = preg_replace('#[^\\pL\d]+#u', $separator, $string);
        // trim
        $string = trim($string, $separator);
        // lowercase
        $string = (defined('MB_CASE_LOWER')) ? mb_strtolower($string) : strtolower($string);
        // remove unwanted characters
        $string = preg_replace('#[^-\w]+#', '', $string);

        return $string;
    }
}