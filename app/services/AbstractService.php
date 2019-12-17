<?php

namespace Services;

abstract class AbstractService
{
    /**
     * @var false|resource
     */
    protected $conn;

    public function __construct()
    {
        $this->conn = \ConnectorOracle::getInstance();
    }

    /**
     * @param string $serviceName
     * @return AbstractService
     */
    public static function getService($serviceName = "Abstract")
    {
        $service = "Services\\".$serviceName."Service";
        return new $service();
    }
}