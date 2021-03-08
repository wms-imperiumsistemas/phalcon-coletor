<?php

use Library\Barcode;
use Services\ConferenciaService;
use Services\AbstractService;

class ConferenciaController extends ControllerBase
{
    public function executarAction()
    {
        /** @var ConferenciaService $confService */
        $confService = AbstractService::getService("Conferencia");
        $idMapa = $this->request->getPost("idMapa");
        $qtd = $this->request->getPost("qtd");
        $codBarras = $this->request->getPost("codigoBarras");
        $lote = $this->request->getPost("lote");
        $codPessoa = $this->request->getPost('cliente');
        $idExpedicao = $this->request->getPost("idExpedicao");
        $osId = $this->request->getPost("osID");
        $checkout = $this->request->getPost("checkout");
        $usuarioId = strrev(explode("#*%*", $this->request->getPost("identfier-conf"))[1]);
        $paramsModeloSeparacao = array(
            'tipoDefaultEmbalado' => $this->request->getPost("tipoDefaultEmbalado"),
            'utilizaQuebra' => $this->request->getPost("utilizaQuebra"),
            'utilizaVolumePatrimonio' => $this->request->getPost("utilizaVolumePatrimonio"),
            'exigeLote' => $this->request->getPost("exigeLote")
        );
        $cpfEmbalador = str_replace(array('.', '-'), '', $this->request->getPost("cpfEmbalador"));

        $bloqueioDeOs = $this->request->getPost("bloquearOs");

        $blockOsOnException = false;
        try {
            $confService->validaExpedicao($idExpedicao, $usuarioId, $bloqueioDeOs);
            if ($bloqueioDeOs == 'S') {
                $blockOsOnException = true;
            }

            $codBarras = Barcode::adequaCodigoBarras($codBarras);
            $result = $confService->confereMapaProduto($paramsModeloSeparacao, $idExpedicao, $idMapa, $codBarras, $qtd, null, $cpfEmbalador, $codPessoa, $osId, $checkout, $lote);

            $volume = "";

            if (isset($result['checkout'])) {
                $msg = 'checkout';
            } else {
                $msg = 'Quantidade conferida com sucesso';
            }

            $vetRetorno = array('retorno' => array('resposta' => 'success', 'message' => $msg, 'produto' => $result['produto'], 'volumePatrimonio' => $volume));
            $this->jsonResponse($vetRetorno);
        } catch (Exception $e) {
            $motivo = $e->getMessage();
            if ($bloqueioDeOs == 'S') {
                if ($blockOsOnException) {
                    $motivo = "OS bloqueada: $motivo";
                    $confService->bloquearOsMapa($osId, $motivo);
                    $confService->salvarAndamento($motivo, $idExpedicao, $usuarioId, $codBarras, $idMapa);
                }
                $vetRetorno = ["retorno" => ['resposta' => 'bloqued_os', 'message' => $motivo]];
            } else {
                $vetRetorno = ["retorno" => ['resposta' => 'error', 'message' => $motivo, 'produto' => '', 'volumePatrimonio' => '']];
            }
            $this->jsonResponse($vetRetorno);
        }
    }
}

