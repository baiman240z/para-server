<?php
namespace core;

class Request
{
    public static function post($name = null, $default = null)
    {
        if ($name === null) {
            return $_POST;
        } else if (isset($_POST[$name])) {
            return $_POST[$name];
        } else {
            return $default;
        }
    }

    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return $_GET;
        } else if (isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            return $default;
        }
    }

    public static function cookie($name = null, $default = null)
    {
        $cookie = filter_input(INPUT_COOKIE, $name);
        return $cookie == null ? $default : $cookie;
    }

    public static function server($name = null)
    {
        return $name !== null ? filter_input(INPUT_SERVER, $name) : filter_input_array(INPUT_SERVER);
    }

    public static function isGet()
    {
        return self::server('REQUEST_METHOD') == 'GET';
    }

    public static function isPost()
    {
        return self::server('REQUEST_METHOD') == 'POST';
    }

    public static function file($name)
    {
        if (isset($_FILES[$name]) == false) {
            throw new \Exception('no parameter: ' . $name);
        }

        $data = null;

        if (is_uploaded_file($_FILES[$name]['tmp_name'])) {
            $data = file_get_contents($_FILES[$name]['tmp_name']);
        }

        return array(
            'name' => $_FILES[$name]['name'],
            'type' => $_FILES[$name]['type'],
            'size' => $_FILES[$name]['size'],
            'data' => $data
        );
    }

    public static function clientIp()
    {
        if (
            self::server('HTTP_X_FORWARDED_FOR') &&
            preg_match("/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/", self::server('HTTP_X_FORWARDED_FOR'), $_match)
        ) {
            return $_match[0];
        }

        return self::server('REMOTE_ADDR');
    }
}
