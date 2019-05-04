<?php
namespace core\session;

use core\Config;
use core\Util;

class Cookie implements \SessionHandlerInterface
{
    private function decrypt()
    {
        $cookie = filter_input(INPUT_COOKIE, Config::get('name', 'session'));
        if (strlen($cookie) == 0) {
            return null;
        }
        $decrypted = Util::decrypt($cookie);

        return is_array($decrypted) ? $decrypted : null;
    }

    private static function setCookie($val, $expire = null)
    {
        $params = session_get_cookie_params();

        if ($expire === null) {
            $expire = $params['lifetime'] == 0 ? 0 : time() + $params['lifetime'];
        }

        setcookie(
            Config::get('name', 'session'),
            $val,
            $expire,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    public function close()
    {
        return true;
    }

    public function open($savePath, $name)
    {
        return true;
    }

    public function read($sessionId)
    {
        $decrypted = $this->decrypt();
        return $decrypted !== null ? $decrypted[0] : '';
    }

    public function write($sessionId, $data)
    {
        $cookie = array($data, time());

        if (headers_sent()) {
            Util::log('failed to write session', 'error');
            Util::log(debug_backtrace(), 'error');
            return false;
        }

        self::setCookie(Util::encrypt($cookie));

        return true;
    }

    public function destroy($sessionId)
    {
        self::setCookie('', time() - 3600);
        return true;
    }

    public function gc($maxlifetime)
    {
        $decrypted = $this->decrypt();
        if ($decrypted === null) {
            return true;
        }

        if ($decrypted[1] < time() - $maxlifetime) {
            self::setCookie('', time() - 3600);
        }

        return true;
    }
}
