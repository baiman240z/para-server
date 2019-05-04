<?php
namespace core;

class Response
{
    public static function redirect($url, $statusCode = 303)
    {
        switch ($statusCode) {
            case 301:
                header('HTTP/1.1 301 Moved Permanently');
                break;
            case 303:
                header('HTTP/1.1 303 See Other');
                break;
            case 302:
            default:
                header('HTTP/1.1 302 Found');
                break;
        }

        $location = $url;
        if (
            preg_match('@^https?://.*@', $url) == false &&
            preg_match('@^/@', $url) == false
        ) {
            $dir = substr(Request::server('REQUEST_URI'), -1) == "/" ? Request::server('REQUEST_URI') : dirname(Request::server('REQUEST_URI')) . "/";
            $location = $dir . $url;
        }

        header('Location: ' . $location);
        exit;
    }

    public static function notfound($msg = null)
    {
        header('HTTP/1.0 404 Not Found');
        if ($msg != null) { echo $msg; }
        exit;
    }

    public static function forbidden($msg = null)
    {
        header('HTTP/1.0 403 Forbidden');
        if ($msg != null) { echo $msg; }
        exit;
    }

    public static function error($msg = null)
    {
        header('HTTP/1.0 500 Internal Server Error');
        if ($msg != null) { echo $msg; }
        exit;
    }

    public static function lastModified($time)
    {
        $etag = md5(Request::server('REQUEST_URI') . $time);
        header("Etag: \"$etag\"");

        $headers = apache_request_headers();
        if (isset($headers['If-Modified-Since'])) {
            if (strtotime($headers['If-Modified-Since']) >= $time) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
        }

        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $time) . ' GMT');
    }

}
