<?php


use Phalcon\Di;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\View;

class ConferenciaController extends ControllerBase
{
    public function executarAction()
    {

        $config = $this->di->getConfig();
        $userName = $config->database->username;
        $password = $config->database->password;
        $dbName = $config->database->dbname;

        $conn = oci_connect($userName, $password, $dbName);

        $sql = "SELECT * FROM PRODUTO WHERE COD_PRODUTO = '8080'";
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        oci_fetch_all($stmt, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);

        var_dump($res);

        exit;

        //$invoices = $query->execute();

        //$this->view->setVar('teste', $invoices);
    }

    public function indexAction()
    {
        parent::indexAction(); // TODO: Change the autogenerated stub
        $this->response->setJsonContent(array('status' => 'ok', 'messages' => 'Entrou aqui'));
    }
}

