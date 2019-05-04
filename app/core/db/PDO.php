<?php
namespace core\db;

use core\Util;

class PDO extends Database
{
    private $handle = null;
    private $dsn = null;
    private $user = null;
    private $password = null;

    public function __construct($dsn, $user = null, $password = null)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
    }

    private function connect()
    {
        if ($this->handle == null) {
            try {
                $this->handle = new \PDO(
                    $this->dsn,
                    $this->user,
                    $this->password
                );
            } catch (\PDOException $ex) {
                Util::log($ex->getMessage(), 'fatal');
                throw $ex;
            }
        }
    }

    public function prepare($sql)
    {
        $this->connect();
        $sth = $this->handle->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        if ($sth == false) {
            throw new \Exception(
                implode(':', $this->handle->errorInfo()) . ' "' . $sql . '"'
            );
        }
        return $sth;
    }

    public function execute($sth, $values = array())
    {
        $sth->closeCursor();

        if (is_array($values) === false) {
            $values = array($values);
        }

        foreach ($values as $key => $value) {
            if (is_string($key) && substr($key, 0, 1) !== ':') {
                unset($values[$key]);
                $values[':' . $key] = $value;
            }
        }

        if ($sth->execute($values) === false) {
            $error = $sth->errorInfo();
            $message = implode(':', $error) . ':' . $sth->queryString;
            Util::log($message, 'error');
            throw new \Exception($message);
        }

        return $sth->rowCount();
    }

    public function all($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return IResultSet
     */
    public function result($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return new PDOResultSet($sth);
    }

    public function row($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function one($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        $array = $sth->fetch(\PDO::FETCH_NUM);
        return is_array($array) ? $array[0] : false;
    }

    public function begin()
    {
        $this->connect();
        $this->handle->beginTransaction();
    }

    public function commit()
    {
        $this->handle->commit();
    }

    public function rollback()
    {
        $this->handle->rollback();
    }

    public function lastInsertId()
    {
        if ($this->handle == null) {
            return false;
        }
        return $this->handle->lastInsertId();
    }

    public function exec($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return $sth->rowCount();
    }

    public function close()
    {
        $this->handle = null;
    }
}
