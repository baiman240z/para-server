<?php
namespace core;

class Assets
{
    private static function getPath($path)
    {
        $absolutePath = null;
        try {
            $absolutePath = Config::baseurl() . $path;
        } catch (\Exception $ex) {
            Util::log($ex->getMessage());
            return '';
        }

        $filepath = Config::docroot() . $absolutePath;
        if (file_exists($filepath)) {
            return $absolutePath . '?' . filemtime($filepath);
        } else {
            return $absolutePath;
        }
    }

    private static function getAttr($attrs)
    {
        if (count($attrs) == 0) {
            return '';
        }

        $ret = '';
        foreach ($attrs as $name => $val) {
            $ret .= ' ' . sprintf('%s="%s"', $name, $val);
        }

        return $ret;
    }

    public static function js($path, $attrs = array('type' => 'text/javascript'))
    {
        return sprintf(
            '<script%s src="%s"></script>',
            self::getAttr($attrs),
            self::getPath($path)
        ) . "\n";
    }

    public static function css($path, $attrs = array('type' => 'text/css', 'rel' => 'stylesheet'))
    {
        return sprintf(
            '<link%s href="%s">',
            self::getAttr($attrs),
            self::getPath($path)
        ) . "\n";
    }
}
