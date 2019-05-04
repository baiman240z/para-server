<?php
namespace core;

class Config
{
    private static $configs = array();
    private static $baseurl = null;

    /**
     * @return string
     * @throws \Exception
     */
    public static function basedir()
    {
        $_match = null;
        if (
            preg_match('@^(.+?)/app/core@', __FILE__, $_match)
        ) {
            return $_match[1];
        } else {
            throw new \Exception('can\'t get base directory.');
        }
    }

    public static function docroot()
    {
        $docroot = Request::server('DOCUMENT_ROOT');
        if ($docroot !== false) {
            return rtrim($docroot, '/');
        } else {
            return self::get('docroot');
        }
    }

    public static function hostname()
    {
        $name = Request::server('SERVER_NAME');
        if (strlen($name) > 0) {
            return $name;
        }

        $name = Request::server('HTTP_HOST');
        if (strlen($name) > 0) {
            return $name;
        }

        return self::get('hostname');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function baseurl()
    {
        if (self::$baseurl != null) {
            return self::$baseurl;
        }

        self::$baseurl = self::get('baseurl');
        if (self::$baseurl != null) {
            return self::$baseurl;
        }

        $public = null;
        try {
            $public = self::basedir() . '/public';
        } catch (\Exception $ex) {
            die('Configuration error');
        }
        $scriptDir = dirname(Request::server('SCRIPT_FILENAME'));
        $dirs = explode('/', trim($scriptDir, '/'));

        $current = '';
        foreach ($dirs as $dir) {
            $current .= '/' . $dir;
            if (fileinode($public) == fileinode($current)) {
                self::$baseurl = substr($current, strlen(self::docroot())) . '/';
                return self::$baseurl;
            }
        }

        throw new \Exception("can't get base url");
    }

    public static function environment()
    {
        if (isset($_SERVER['PHP_ENV']) && $_SERVER['PHP_ENV'] != null) {
            return strtolower($_SERVER['PHP_ENV']);
        } else {
            return 'production';
        }
    }

    private static function load($name, $env = null)
    {
        $envKey = null;
        $envPath = null;
        if ($env === null) {
            $envKey = '_default_';
            $envPath = '';
        } else {
            $envKey = $env;
            $envPath = $env . '/';
        }

        if (isset(self::$configs[$envKey][$name])) {
            return;
        }

        if (isset(self::$configs[$envKey]) == false) {
            self::$configs[$envKey] = array();
        }

        $file = null;
        try {
            $file = self::basedir() . '/config/' . $envPath . $name . '.yaml';
        } catch (\Exception $ex) {
            die('Configuration error');
        }
        if (file_exists($file)) {
            self::$configs[$envKey][$name] = yaml_parse_file($file);
            if (self::$configs[$envKey][$name] == false) {
                unset(self::$configs[$envKey][$name]);
                Util::log(
                    'can\'t decode configration file: ' . $file,
                    'fatal'
                );
                return null;
            }
        } else {
            self::$configs[$envKey][$name] = array();
        }

    }

    public static function get($key, $name = 'application')
    {
        $env = self::environment();

        self::load($name, $env);
        if (isset(self::$configs[$env][$name][$key])) {
            return self::$configs[$env][$name][$key];
        }

        self::load($name);
        if (isset(self::$configs['_default_'][$name][$key])) {
            return self::$configs['_default_'][$name][$key];
        } else {
            return null;
        }
    }
}
