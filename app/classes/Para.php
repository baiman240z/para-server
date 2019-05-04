<?php
use core\Config;
use core\Request;

class Para
{
    const CIPHER = 'AES-128-CBC';

    private static $_groups = array(
        "001" => array(
            "title" => "あゆみ",
            "dir" => "ayumi"
        ),
        "002" => array(
            "title" => "えみり",
            "dir" => "emiri"
        ),
        "003" => array(
            "title" => "みなみ",
            "dir" => "minami"
        ),
        "004" => array(
            "title" => "ももこ",
            "dir" => "momoko"
        ),
        "005" => array(
            "title" => "つくし",
            "dir" => "tsukushi"
        ),
        "006" => array(
            "title" => "うらら",
            "dir" => "urara"
        ),
        "007" => array(
            "title" => "わたせ",
            "dir" => "watase"
        ),
        "008" => array(
            "title" => "ゆかCA",
            "dir" => "yuka_ca"
        ),
        "009" => array(
            "title" => "ゆかJK",
            "dir" => "yuka_jk"
        )
    );

    public static function group()
    {
        $all = array();
        foreach (self::$_groups as $code => $title) {
            $_array = array(
                "code" => $code,
                "title" => $title["title"],
                "urls" => array()
            );

            $dir = Config::basedir() . '/photos/' . $title["dir"];
            foreach (glob($dir . "/*.jpg") as $file) {
                $_array["urls"][] = sprintf(
                    "http%s://%s%simg/%s/%s",
                    Request::server('HTTPS') ? 's' : '',
                    Config::hostname(),
                    Config::baseurl(),
                    $code,
                    urlencode(basename($file))
                );
            }

            $all[] = $_array;
        }
        return $all;
    }

    public static function getImage($code, $file, $width = null)
    {
        if (isset(self::$_groups[$code]) == false) throw new Exception("不正なタイトル番号");

        $cache_dir = sprintf(
            "%s/cache/%s",
            Config::basedir(),
            $code
        );

        $cache_file = md5($file) . "." . ($width ? $width : "none") . ".jpg";
        $cache_path = $cache_dir . "/" . $cache_file;

        if (file_exists($cache_path)) {
            return file_get_contents($cache_path);
        }

        $title = self::$_groups[$code];

        $path = sprintf(
            "%s/photos/%s/%s",
            Config::basedir(),
            $title["dir"],
            $file
        );

        $imagick = new Imagick($path);
        $imagick->stripImage();

        if (is_numeric($width) && $width > 0) {
            $sw = $imagick->getImageWidth();
            $sh = $imagick->getImageHeight();
            if ($sw > $width) {
                $dw = $width;
                $dh = $sh * ($dw / $sw);
                // 画像縮小
                $imagick->resizeImage($dw, $dh, imagick::FILTER_CUBIC, 1);
            }
        }

        $imagick->setImageFormat("jpeg");
        $img_data = self::encrypt($imagick->getImageBlob());

        if (file_exists($cache_dir) == false) {
            mkdir($cache_dir, 0777);
            @chmod($cache_dir, 0777);
        }
        file_put_contents($cache_path, $img_data);
        @chmod($cache_path, 0666);

        return $img_data;
    }

    public static function encrypt($data)
    {
        $config = Config::get('photo-encrypt');

        return openssl_encrypt(
            $data,
            self::CIPHER,
            $config['key'],
            OPENSSL_RAW_DATA,
            $config['iv']
        );
    }

    public static function createCache($width = 480)
    {
        foreach (self::$_groups as $code => $group) {
            $dir = Config::basedir() . "/cache/" . $group["dir"];
            foreach (glob($dir . "/*.jpg") as $file) {
                $file = basename($file);
                echo $width . ":" . $file . "\n";
                self::getImage($code, $file, $width);
            }
        }
    }

}
