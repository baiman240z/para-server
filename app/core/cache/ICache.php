<?php

namespace core\cache;

interface ICache
{
    public function save($key, $data, $ttl = 0);
    public function get($key);
    public function delete($key);
    public function keys();
    public function ttl($key);
}
