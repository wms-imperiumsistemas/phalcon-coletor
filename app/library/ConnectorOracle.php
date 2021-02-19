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
        $conn = oci_connect(
            self::$__dataBaseConf->username,
            self::$__dataBaseConf->password,
            self::$__dataBaseConf->dbname);

        if (!$conn) {
            $e = oci_error();
            //trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            throw new Exception($e['message']);
        }

        $this->conn = $conn;
    }

    public function getConnection()
    {
        return self::getInstance()->conn;
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
            oci_execute($this->stmt, OCI_NO_AUTO_COMMIT);
            $this->queryState = 2;
        }

        return $this;
    }

    public function fetchFirstResult()
    {
        $res = self::fetchAll(1);
        return (!empty($res))? $res[0] : null;
    }

    public function fetchAll($maxRows = null)
    {
        if (empty($this->stmt)) throw new \Exception("The query statement is not be defined");
        if ($this->queryState === 1) {
            oci_execute($this->stmt);
            $this->queryState = 2;
        }
        oci_fetch_all($this->stmt, $res, null, $maxRows, OCI_FETCHSTATEMENT_BY_ROW);
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

    public function commit()
    {
        $conn = self::getConnection();
        $r = oci_commit($conn);
        if (!$r) {
            $e = oci_error($conn);
            throw new Exception($e['message']);
        }
    }

    public function rollback()
    {
        oci_rollback(self::getConnection());  // rollback changes to both tables
    }
}