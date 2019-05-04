<?php
namespace core\db;

class MySQLi extends Database
{
    private $handle = null;
    private $name = null;
    private $host = null;
    private $user = null;
    private $password = null;
    private $charset = null;
    private $port = null;
    private $maxAllowedPacket = null;
    private $tmpPlaceHolders = array();
    private $placeHolders = array();

    public function __construct($name, $host = 'localhost', $user = null, $password = null, $port = 3306, $charset = 'utf8')
    {
        $this->name = $name;
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
        $this->charset = $charset;
    }

    /**
     * @throws \Exception
     */
    private function connect()
    {
        if ($this->handle == null) {
            $this->handle = new \mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->name,
                $this->port
            );

            if ($this->handle->connect_error) {
                throw new \Exception(
                    $this->handle->connect_errno . ':' . $this->handle->connect_error
                );
            }

            $this->handle->set_charset($this->charset);

            $row = $this->row("show variables like 'max_allowed_packet'");
            $this->maxAllowedPacket = (int)$row['Value'];
        }
    }

    /**
     * @param $sql
     * @return bool|\mysqli_stmt
     * @throws \Exception
     */
    public function prepare($sql)
    {
        $this->connect();

        $this->tmpPlaceHolders = array();
        $sql = preg_replace_callback('/\:([a-z0-9_]+)/is', function($matched)
        {
            $this->tmpPlaceHolders[] = $matched[1];
            return '?';
        }, $sql);

        $sth = mysqli_prepare($this->handle, $sql);

        if ($sth === false) {
            throw new \Exception(
                $this->handle->errno . ':' . $this->handle->error . ':' . $sql
            );
        }

        $this->placeHolders[$sth->id] = $this->tmpPlaceHolders;

        return $sth;
    }

    /**
     * @param $sth
     * @param array $values
     * @return mixed
     * @throws \Exception
     */
    public function execute($sth, $values = array())
    {
        if (is_array($values) === false) {
            $values = array($values);
        }

        if (count($this->placeHolders[$sth->id])) {
            $sorted = array();
            foreach ($this->placeHolders[$sth->id] as $placeHolder) {
                if (array_key_exists($placeHolder, $values)) {
                    $sorted[] = $values[$placeHolder];
                } else {
                    throw new \Exception('no parameter:' . $placeHolder);
                }
            }
            $values = $sorted;
        }

        if (count($values)) {
            $types = '';
            $parameters = array();
            foreach ($values as $key => $value) {
                $type = 's';
                if (
                    $this->maxAllowedPacket &&
                    $this->maxAllowedPacket < strlen($value)
                ) {
                    $type = 'b';
                } else if (is_int($value)) {
                    $type = 'i';
                } else if (is_double($value)) {
                    $type = 'd';
                }
                $parameters[] = &$values[$key];
                $types .= $type;
            }

            array_unshift($parameters, $types);
            call_user_func_array(array($sth, 'bind_param'), $parameters);
        }

        if ($sth->execute() === false) {
            throw new \Exception($sth->errno . ':' . $sth->error);
        }

        return $this->handle->affected_rows ? $this->handle->affected_rows : $sth->num_rows;
    }

    public function all($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return $sth->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * @return IResultSet
     */
    public function result($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return new MySQLiResultSet($sth);
    }

    public function row($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return $sth->get_result()->fetch_array(MYSQLI_ASSOC);
    }

    public function one($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        $array = $sth->get_result()->fetch_array(MYSQLI_NUM);
        return is_array($array) ? $array[0] : false;
    }

    public function begin()
    {
        $this->connect();
        $this->handle->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
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
        return $this->handle->insert_id;
    }

    public function exec($sql, $values = array())
    {
        $sth = $this->prepareExecute($sql, $values);
        return $this->handle->affected_rows ? $this->handle->affected_rows : $sth->num_rows;
    }

    public function close()
    {
        $this->handle->close();
    }
}
