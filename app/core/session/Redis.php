<?php
namespace core\session;

use core\Config;

class Redis implements \SessionHandlerInterface
{
    /**
     * @var \Redis
     */
    private $redis = null;
    private static $prefix;

    private function connect()
    {
        if ($this->redis === null) {
            $dbConfig = Config::get('redis', 'session');
            $this->redis = new \Redis();
            $this->redis->connect($dbConfig['host'], $dbConfig['port']);
            self::$prefix = Config::get('name', 'session') . ':';
        }
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        $this->connect();
        $this->redis->delete(self::$prefix . $session_id);
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        $this->connect();
        $serialized = $this->redis->get(self::$prefix . $session_id);
        return strlen($serialized) > 0 ? unserialize($serialized) : '';
    }

    public function write($session_id, $session_data)
    {
        $this->connect();
        $ttl = (int)ini_get('session.gc_maxlifetime');
        $this->redis->setex(self::$prefix . $session_id, $ttl, serialize($session_data));

        return true;
    }

}
