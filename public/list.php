<?php
use core\Request;

if (Request::isGet()) {
    $json = json_encode(Para::group());
    header('Content-Type: application/json');
    header("Content-Length: " . strlen($json));
    echo $json;
} else if (Request::isPost()) {
}
