<?php
namespace core\db;

interface IResultSet
{
    public function __construct($sth);
    /**
     * @return array
     */
    public function row();

    /**
     * @return int
     */
    public function total();
}
