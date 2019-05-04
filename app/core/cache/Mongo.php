<?php
namespace core\cache;

use core\Config;

/*
 * db.{prefix name}.ensureIndex({key: 1},{unique: true})
 * db.{prefix name}.ensureIndex({expire: 1},{expireAfterSeconds: 0})
 */
class Mongo implements ICache
{
    /**
     * @var \MongoCollection
     */
    private $mongo = null;

    private function connect()
    {
        if ($this->mongo === null) {
            $dbConfig = Config::get('mongo', 'cache');
            $client = new \MongoClient(sprintf(
                'mongodb://%s:%d',
                $dbConfig['host'],
                $dbConfig['port']
            ));
            $this->mongo = new \MongoCollection(
                $client->selectDB($dbConfig['name']),
                Config::get('prefix', 'cache')
            );
        }
    }

    public function save($key, $data, $ttl = 0)
    {
        $this->connect();
        $doc = array(
            'key' => $key,
            'value' => serialize($data),
            "expire" => new \MongoDate(time() + $ttl)
        );
        $this->mongo->update(array('key' => $key), $doc, array('upsert' => true));
    }

    public function get($key)
    {
        $this->connect();
        $doc = $this->mongo->findOne(array('key' => $key));
        return isset($doc['value']) ? unserialize($doc['value']) : null;
    }

    public function delete($key)
    {
        $this->connect();
        $this->mongo->remove(array('key' => $key));
    }

    public function keys()
    {
        $this->connect();

        $keys = array();
        foreach ($this->mongo->find() as $doc) {
            $keys[] = $doc['key'];
        }

        return $keys;
    }

    public function ttl($key)
    {
        $this->connect();
        $doc = $this->get($key);
        return $doc['expire']->sec - time();
    }

}
