<?php

namespace core\cache;

use core\Config;

class Factory
{
    /**
     * @param null $driver
     * @return ICache
     * @throws \Exception
     */
    public static function create($driver = null)
    {
        if ($driver == null) {
            $driver = Config::get('default', 'cache');
        }

        if ($driver == 'redis') {
            return new Redis();
        } if ($driver == 'mongo') {
            return new Mongo();
        } else {
            throw new \Exception('unknown cache driver: ' . $driver);
        }
    }
}
