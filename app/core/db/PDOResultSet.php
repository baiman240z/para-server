<?php
namespace core\db;

class PDOResultSet implements IResultSet
{
    private $sth = null;

    public function __construct($sth)
    {
        $this->sth = $sth;
    }

    public function row()
    {
        return $this->sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function total()
    {
        return $this->sth->rowCount();
    }
}
