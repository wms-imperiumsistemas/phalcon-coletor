<?php

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        try {
            $conn = \ConnectorOracle::getInstance();
            $this->view->dbOk = true;
        } catch (Exception $e) {
            $this->view->dbOk = false;
            $this->view->exception = $e->getMessage();
        }
    }

}

