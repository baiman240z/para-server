<?php
namespace core\cache;

use core\Config;

class Redis implements ICache
{
    /**
     * @var \Redis
     */
    private $redis = null;
    private static $prefix;

    private function connect()
    {
        if ($this->redis === null) {
            $dbConfig = Config::get('redis', 'cache');
            $this->redis = new \Redis();
            $this->redis->connect($dbConfig['host'], $dbConfig['port']);
            self::$prefix = Config::get('prefix', 'cache') . ':';
        }
    }

    public function save($key, $data, $ttl = 0)
    {
        $this->connect();

        if ($ttl === 0) {
            $this->redis->set(self::$prefix . $key, serialize($data));
        } else {
            $this->redis->setex(self::$prefix . $key, $ttl, serialize($data));
        }
    }

    public function get($key)
    {
        $this->connect();

        $data = $this->redis->get(self::$prefix . $key);
        if ($data !== false) {
            return unserialize($data);
        } else {
            return false;
        }
    }

    public function delete($key)
    {
        $this->connect();
        $this->redis->del(self::$prefix . $key);
    }

    public function keys()
    {
        $this->connect();

        $len = strlen(self::$prefix);
        $keys = array();
        foreach ($this->redis->keys(self::$prefix . '*') as $key) {
            $keys[] = substr($key, $len);
        }

        return $keys;
    }

    public function ttl($key)
    {
        $this->connect();
        return $this->redis->ttl(self::$prefix . $key);
    }

}
