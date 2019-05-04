<?php
namespace core\db;

abstract class Database
{
    abstract public function prepare($sql);
    abstract public function execute($sth, $values = array());
    abstract public function all($sql, $values = array());

    /**
     * @param $sql
     * @param array $values
     * @return IResultSet
     */
    abstract public function result($sql, $values = array());
    abstract public function row($sql, $values = array());
    abstract public function one($sql, $values = array());
    abstract public function begin();
    abstract public function commit();
    abstract public function rollback();
    abstract public function lastInsertId();
    abstract public function exec($sql, $values = array());
    abstract public function close();

    protected function prepareExecute($sql, $values)
    {
        if (is_array($values) === false) {
            $values = array($values);
        }

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $in = '(';
                foreach ($value as $_k => $_v) {
                    $subkey = $key . $_k;
                    $in .= ':' . $subkey . ',';
                    $values[$subkey] = $_v;
                }
                $in = rtrim($in, ',') . ')';
                $sql = str_replace(':' . $key, $in, $sql);
                unset($values[$key]);
            }
        }

        $sth = $this->prepare($sql);
        $this->execute($sth, $values);

        return $sth;
    }

    public function page($sql, $values = array(), $page = 1, $limit = 10, $numLinks = 20)
    {
        if (!is_numeric($page) || $page < 1) { $page = 1; }
        $sql = trim($sql);

        $total = $this->one(
            'SELECT COUNT(*) FROM (' . $sql . ') AS __counter__', $values
        );

        if ($total > 0) {
            $sql .= sprintf(
                ' LIMIT %d OFFSET %d',
                $limit,
                $limit * ($page - 1)
            );
        }

        $rows = $this->all($sql, $values);

        // page numbers
        $pageMax = ceil($total / $limit);
        $links = array();

        $pageStart = 1;
        if ($pageMax > $numLinks) {
            $pageStart = intval($page - ($numLinks / 2));
            if ($pageStart < 1) {
                $pageStart = 1;
            } else if (($pageMax - $pageStart + 1) < $numLinks) {
                $pageStart = $pageMax - $numLinks + 1;
            }
        }

        for ($i=0;$i<$numLinks;$i++) {
            if (($pageStart + $i) > $pageMax) { break; }
            $p = $pageStart + $i;
            $links[] = array(
                'number' => $p,
                'current' => $p == $page
            );
        }

        // start row number
        $rowStart = $total ? ($page - 1) * $limit + 1 : 0;

        // end row number
        $rowEnd = $rowStart + $limit > $total ? $total : $rowStart + $limit - 1;

        return array(
            'page' => array(
                'prev' => ($page - 1) > 0 ? ($page - 1) : false,
                'current' => $page,
                'next' => ($page + 1) > $pageMax ? false : ($page + 1),
                'max' => $pageMax,
                'links' => $links
            ),
            'start' => $rowStart,
            'end' => $rowEnd,
            'total' => $total,
            'hasmore' => ($page < $pageMax),
            'rows' => $rows
        );
    }

    public function pageLight($sql, $values = array(), $page = 1, $limit = 10)
    {
        if (!is_numeric($page) || $page < 1) { $page = 1; }
        $sql = trim($sql);

        $sql .= sprintf(
            ' LIMIT %d OFFSET %d',
            $limit + 1,
            $limit * ($page - 1)
        );

        $rows = $this->all($sql, $values);
        $hasmore = count($rows) > $limit;
        if ($hasmore) { array_pop($rows); }

        // start row number
        $rowStart = ($page - 1) * $limit + 1;

        // end row number
        $rowEnd = $rowStart + count($rows) - 1;

        return array(
            'page' => array(
                'prev' => ($page - 1) > 0 ? ($page - 1) : false,
                'current' => $page,
                'next' => $hasmore ? ($page + 1) : false
            ),
            'start' => $rowStart,
            'end' => $rowEnd,
            "rows" => $rows,
            "hasmore" => $hasmore
        );
    }
}
