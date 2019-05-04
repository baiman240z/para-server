<?php
namespace core\db;

use core\Config;

class Factory
{
    private static $instances = array();

    /**
     * @param string $instanceName
     * @return Database
     * @throws \Exception
     */
    public static function create($instanceName = 'default')
    {
        $config = Config::get($instanceName, 'database');

        if (isset($config['driver']) == false) {
            throw new \Exception('pls set database driver in config/database.json');
        }

        if ($config['driver'] == 'pdo') {
            return new PDO(
                $config['dsn'],
                $config['user'],
                $config['password']
            );
        } else if ($config['driver'] == 'mysqli') {
            return new MySQLi(
                $config['name'],
                $config['host'],
                $config['user'],
                $config['password'],
                $config['port'],
                $config['charset']
            );
        } else {
            throw new \Exception('unknown driver:' . $config['driver']);
        }
    }

    /**
     * @param string $instanceName
     * @return Database
     * @throws \Exception
     */
    public static function singleton($instanceName = 'default')
    {
        if (isset(self::$instances[$instanceName]) == false) {
            self::$instances[$instanceName] = self::create($instanceName);
        }
        return self::$instances[$instanceName];
    }
}
