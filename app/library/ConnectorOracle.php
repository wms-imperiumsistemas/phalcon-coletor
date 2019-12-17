<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Di;
use Phalcon\Config;

class ConnectorOracle
{
    protected static $_instance;

    private static $__dataBaseConf;

    private $conn;

    private $stmt;

    private $queryState = 1;

    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$__dataBaseConf = DI::getDefault()->getConfig()->database;
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->conn = oci_connect(
            self::$__dataBaseConf->username,
            self::$__dataBaseConf->password,
            self::$__dataBaseConf->dbname);
    }

    public function query($sql = "", $params = [])
    {
        if (empty($sql)) throw new \Exception("The SQL string cannot be empty ");

        $this->stmt = oci_parse($this->conn, $sql);
        $this->queryState = 1;

        return $this;
    }

    public function execute()
    {
        if (empty($this->stmt)) throw new \Exception("The query statement is not be defined");
        if ($this->queryState === 1) {
            oci_execute($this->stmt);
            $this->queryState = 2;
        }

        return $this;
    }

    public function executeQuery($sql)
    {
        $this->stmt = oci_parse($this->conn, $sql);
        return oci_execute($this->stmt);
    }

    public function fetchAll()
    {
        if (empty($this->stmt)) throw new \Exception("The query statement is not be defined");
        if ($this->queryState === 1) {
            oci_execute($this->stmt);
            $this->queryState = 2;
        }
        oci_fetch_all($this->stmt, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        return $res;
    }

    public function fetchArray()
    {
        if (empty($this->stmt)) throw new \Exception("The query statement is not be defined");
        if ($this->queryState === 1) {
            oci_execute($this->stmt);
            $this->queryState = 2;
        }
        return oci_fetch_array($this->stmt);
    }
}