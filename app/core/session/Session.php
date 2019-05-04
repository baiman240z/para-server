<?php
namespace core\session;

use core\Config;
use core\Util;

class Session
{
    private static $sessionStarted = false;

    public static function start()
    {
        if (self::$sessionStarted) { return; }

        if (Config::get('name', 'session') === null) {
            throw new \Exception('please set session name on config/session.yaml');
        }

        $driver = Config::get('driver', 'session');
        if ($driver == null) {
            throw new \Exception('please set session driver on config/session.yaml');
        }

        if ($driver == 'cookie') {
            session_set_save_handler(new Cookie(), true);
        } else if ($driver == 'redis') {
            session_set_save_handler(new Redis(), true);
        } else {
            throw new \Exception('unknown driver');
        }

        session_start();
        self::$sessionStarted = true;
    }

    public static function save($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key = null)
    {
        self::start();
        if ($key === null) {
            return $_SESSION;
        } else {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }
    }

    public static function delete($key)
    {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function clear()
    {
        self::start();
        session_destroy();
    }
}
