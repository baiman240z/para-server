<?php
namespace core;

use core\session\Session;

class Util
{
    const CIPHER = 'BF-CBC';

    public static function encrypt($data)
    {
        if (is_string($data) && strlen($data) == 0) { return null; }
        $config = Config::get('encrypt');

        return bin2hex(openssl_encrypt(
            serialize($data),
            self::CIPHER,
            pack('H*', $config['key']),
            OPENSSL_RAW_DATA,
            pack('H*', $config['iv'])
        ));
    }

    public static function decrypt($encrypted)
    {
        if (strlen($encrypted) == 0) { return null; }
        $config = Config::get('encrypt');

        $decrypted = openssl_decrypt(
            pack('H*', $encrypted),
            self::CIPHER,
            pack('H*', $config['key']),
            OPENSSL_RAW_DATA,
            pack('H*', $config['iv'])
        );

        return unserialize($decrypted);
    }

    public static function trimSpace($str)
    {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        return mb_ereg_replace('(\s|\n|\r|　)+$', '', mb_ereg_replace('^(\s|\n|\r|　)+', '', $str));
    }

    public static function trimArray($params)
    {
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = self::trimArray($v);
            } else {
                $params[$k] = self::trimSpace($v);
            }
        }

        return $params;
    }

    public static function log($msg, $logName = 'debug')
    {
        $logDir = Config::get('log-dir');

        if ($logDir == null) {
            try {
                $logDir = Config::basedir() . '/log';
            } catch (\Exception $ex) {
                die('Configuration error');
            }
        }

        $logDir = sprintf(
            "%s/%s/%s",
            $logDir,
            date('Y'),
            date('m')
        );

        if (is_dir($logDir) == false) {
            @mkdir($logDir, 0777, true);
            @chmod($logDir, 0777);
        }

        $logFile = $logDir . '/' . $logName . '-' . date('d') . '.txt';

        if (is_string($msg) == false) { $msg = var_export($msg, true); }

        $log = sprintf(
            "[%s]%s",
            date('Y-m-d H:i:s'),
            trim($msg)
        );

        $result = @error_log($log . "\n", 3, $logFile);

        if ($result) {
            @chmod($logFile, 0666);
        } else {
            error_log($log, 4);
        }
    }

    public static function csvLine($columns)
    {
        foreach ($columns as $k => $v) {
            $columns[$k] = str_replace('"', '""', $v);
        }
        $line = '"' . implode('","', $columns) . '"';
        return mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
    }

    public static function setMessage($name, $message)
    {
        $session = Session::get('FLASH_MESSAGE');

        if (!is_array($session)) {
            $session = array();
        }

        if (!isset($session[$name])) {
            $session[$name] = array();
        }

        if (is_array($message)) {
            $session[$name] = array_merge($session[$name], $message);
        } else {
            $session[$name][] = $message;
        }

        Session::save('FLASH_MESSAGE', $session);
    }

    public static function showMessage($name)
    {
        $session = Session::get('FLASH_MESSAGE');
        if (is_array($session)) {
            if (isset($session[$name])) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $messages = $session[$name];
                include 'views/messages/' . $name . '.php';
            }
            unset($session[$name]);
            Session::save('FLASH_MESSAGE', $session);
        }
    }

    public static function wareki($year)
    {
        if ($year < 1989) {
            return '昭和' . ($year - 1925) . '年';
        } else {
            return '平成' . ($year - 1988) . '年';
        }
    }

    private static function bit2mask($bit)
    {
        $bit = intval($bit);
        return bindec(
            str_repeat('1', $bit) . str_repeat('0', 32 - $bit)
        );
    }

    /**
     * @param $ip
     * @param $ranges
     * @return bool
     */
    public static function isIncludeIp($ip, $ranges)
    {
        if (is_array($ranges) == false)
        {
            $ranges = array($ranges);
        }

        $ipLong = ip2long($ip);

        foreach ($ranges as $range) {
            @list($rangeIp, $bit) = explode('/', $range);
            if (strlen($bit)) {
                $rangeIpLong = ip2long($rangeIp);
                $mask = self::bit2mask($bit);
                if (($ipLong & $mask) == ($rangeIpLong & $mask)) {
                    return true;
                }
            } else if ($ip == $range) {
                return true;
            }
        }

        return false;
    }

    public static function makePassword($len = 8)
    {
        $str = '2,3,4,5,6,7,8,9,2,3,4,5,6,7,8,9,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,j,k,m,n,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,J,K,L,M,N,P,Q,R,S,T,U,V,W,X,Y,Z';
        $arr = explode(',', $str);
        srand((double)microtime() * 1000000);
        $password = '';
        for($i=0; $i<$len; $i++ ) {
            $password .= $arr[ rand(0, count($arr) - 1) ];
        }

        return $password;
    }

    public static function getHolidays()
    {
        $url = 'https://calendar.google.com/calendar/ical/japanese__ja%40holiday.calendar.google.com/public/full.ics';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $ics = curl_exec($ch);
        curl_close($ch);

        $holidays = array(); $day = null;
        foreach (explode("\n", $ics) as $line) {
            $line = trim($line);
            if (substr($line, 0, 8) == 'DTSTART;') {
                $array = explode(':', $line);
                $day = $array[1];
            }
            if (substr($line, 0, 8) == 'SUMMARY:') {
                $array = explode(':', $line);
                $holidays[$day] = $array[1];
            }
        }

        ksort($holidays);

        return $holidays;
    }

    public static function hidden($params)
    {
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $_val) {
                    printf(
                        '<input type="hidden" name="%s[]" value="%s">',
                        htmlspecialchars($key),
                        htmlspecialchars($_val)
                    );
                }
            } else {
                printf(
                    '<input type="hidden" name="%s" value="%s">',
                    htmlspecialchars($key),
                    htmlspecialchars($val)
                );
            }
        }
    }

}
