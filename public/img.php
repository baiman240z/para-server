<?php
use core\Request;
use core\Response;

if (Request::isGet()) {
    $no = $file = null;
    if (preg_match("@img/([0-9]+)/([^?]+)@", $_SERVER["REQUEST_URI"], $_match)) {
        $no = $_match[1];
        $file = urldecode($_match[2]);
    } else {
        Response::notfound();
    }

    // 画像幅を端末に合わせて縮小するか検討中
    // $image_data = Para::getImage($no, $file, @$_GET["w"]);
    $image_data = Para::getImage($no, $file);

    header("Content-Type: image/jpeg");
    header("Content-Length: " . strlen($image_data));

    echo $image_data;
} else if (Request::isPost()) {
}


