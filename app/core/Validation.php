<?php
namespace core;

class Validation
{
    private $errors = null;

    public static function isEmail($email)
    {
        return preg_match("/^[a-z0-9\-\._\!\#\$\%\&\'\*\+\/\=\?\^\`\{\}\|\~]+@([0-9a-z\-]*\.)+[a-z]+$/i", $email);
    }

    public function __construct()
    {
        $this->errors = array();
    }

    public function isError()
    {
        return count($this->errors) > 0;
    }

    public function setError($column, $message)
    {
        $this->errors[$column] = $message;
    }

    public function getError($column)
    {
        return isset($this->errors[$column]) ? $this->errors[$column] : null;
    }

}
