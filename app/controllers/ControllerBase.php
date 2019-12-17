<?php

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    public function indexAction()
    {

    }

    protected function jsonResponse($data)
    {
        $origin = $this->request->getHeader("ORIGIN") ? $this->request->getHeader("ORIGIN") : '*';
        $this->response->setHeader("Access-Control-Allow-Origin", $origin)
            ->setHeader("Access-Control-Allow-Methods", 'GET,PUT,POST,DELETE,OPTIONS')
            ->setHeader("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization')
            ->setHeader("Access-Control-Allow-Credentials", true);
        $this->view->disable();
        $this->response->setJsonContent($data)->send();
    }
}
