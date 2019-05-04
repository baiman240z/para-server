<?php
namespace core\db;

class MySQLiResultSet implements IResultSet
{
    /**
     * @var \mysqli_stmt
     */
    private $sth = null;
    /**
     * @var \mysqli_result
     */
    private $result = null;

    public function __construct($sth)
    {
        $this->sth = $sth;
        $this->result = $sth->get_result();
    }

    public function row()
    {
        $assoc =  $this->result->fetch_assoc();
        if ($assoc === null) {
            $this->result->free();
            $this->sth->close();
        }
        return $assoc;
    }

    public function total()
    {
        return $this->result->num_rows;
    }
}
