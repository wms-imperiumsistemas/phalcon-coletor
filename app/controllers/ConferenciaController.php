<?php

use Library\Barcode;
use Services\ConferenciaService;
use Services\AbstractService;

class ConferenciaController extends ControllerBase
{
    public function executarAction()
    {
        try {
            $idMapa = $this->request->getPost("idMapa");
            $qtd = $this->request->getPost("qtd");
            $codBarras = $this->request->getPost("codigoBarras");
            $lote = $this->request->getPost("lote");
            $codPessoa = $this->request->getPost('cliente');
            $idExpedicao = $this->request->getPost("idExpedicao");
            $osId = $this->request->getPost("osID");
            $idVolume = $this->request->getPost("idVolume");
            $checkout = $this->request->getPost("chekcout");
            $uniqId = $this->request->getPost("identfier-conf");
            $cpfConf = explode("#*%*", $uniqId)[1];
            $paramsModeloSeparacao = array(
                'tipoDefaultEmbalado' => $this->request->getPost("tipoDefaultEmbalado"),
                'utilizaQuebra' => $this->request->getPost("utilizaQuebra"),
                'utilizaVolumePatrimonio' => $this->request->getPost("utilizaVolumePatrimonio")
            );
            $msg['msg'] = "";
            $volume = "";

            /*$idMapa = 1211736;
            $qtd = 1;
            $codBarras = '7897181939044';
            $lote = null;
            $codPessoa = null;
            $idExpedicao = 17725;
            $osId = 32975;
            $idVolume = null;
            $checkout = false;
            $uniqId = "(5786556751)#*%*05609679680";
            $cpfConf = explode("#*%*", $uniqId)[1];
            $paramsModeloSeparacao = array(
                'tipoDefaultEmbalado' => "P",
                'utilizaQuebra' => false,
                'utilizaVolumePatrimonio' => false
            );*/

            $codBarras = Barcode::adequaCodigoBarras($codBarras);
            /** @var ConferenciaService $confService */
            $confService = AbstractService::getService("Conferencia");
            $result = $confService->confereMapaProduto($paramsModeloSeparacao, $idExpedicao, $idMapa, $codBarras, $qtd, null, $cpfConf, $codPessoa, $osId, false, $lote);

            if(isset($result['checkout'])){
                $msg['msg'] = 'checkout';
                $msg['produto'] = $result['produto'];
            }else{
                $msg['msg'] = 'Quantidade conferida com sucesso';
                $msg['produto'] = $result['produto'];
            }

            $vetRetorno = array('retorno' => array('resposta' => 'success', 'message' => $msg['msg'], 'produto' => $msg['produto'], 'volumePatrimonio' => $volume));
            $this->jsonResponse($vetRetorno);
        } catch (Exception $e) {
            $this->jsonResponse(["status" => "Error", "retorno" => ['resposta' => 'error', 'message' => $e->getMessage(), 'produto' => '', 'volumePatrimonio' => '']]);
        }
    }

}

