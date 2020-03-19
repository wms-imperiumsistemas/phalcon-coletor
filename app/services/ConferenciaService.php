<?php

namespace Services;

use Library\Math;
use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;

class ConferenciaService extends AbstractService
{
    public function confereMapaProduto($paramsModeloSeparaco, $idExpedicao, $idMapa, $codBarras, $qtd, $volumePatrimonioEn, $cpfEmbalador, $codPessoa = null, $ordemServicoId = null, $checkout = false, $lote = Lote::NCL) {

        try {
            $idVolumePatrimonio = null;
            if ($volumePatrimonioEn != null) {
                $idVolumePatrimonio = $volumePatrimonioEn->getId();
            }

            $parametrosConferencia = array(
                'idVolumePatrimonio' => $idVolumePatrimonio,
                'codPessoa' => $codPessoa,
                'qtd' => $qtd,
                'codBarras' => $codBarras,
                'idMapa' => $idMapa,
                'idExpedicao' => $idExpedicao,
                'lote' => $lote
            );

            $conferencia = $this->conferenciaAlternativa($parametrosConferencia,$paramsModeloSeparaco);

            $idMapaSepEmb = "NULL";
            if (!empty($codPessoa)) {
                $sql = "SELECT * FROM MAPA_SEPARACAO_EMB_CLIENTE WHERE COD_MAPA_SEPARACAO = $idMapa AND COD_PESSOA = $codPessoa ORDER BY COD_MAPA_SEPARACAO_EMB_CLIENTE DESC";
                $mapaSeparacaoEmbalado = $this->conn->query($sql)->fetchFirstResult();
                if (empty($mapaSeparacaoEmbalado)) {
                    $osEmbalamento = self::getOsMapaConfEmbalagem($cpfEmbalador, $idExpedicao, true);
                    $idMapaSepEmb = self::saveMapaEmb($idMapa, $codPessoa,  $osEmbalamento);
                } else {
                    if (in_array($mapaSeparacaoEmbalado['COD_STATUS'], [569, 570])) {
                        $osEmbalamento = self::getOsMapaConfEmbalagem($cpfEmbalador, $idExpedicao, true);
                        $idMapaSepEmb = self::saveMapaEmb($idMapa, $codPessoa,  $osEmbalamento);
                    } else {
                        $idMapaSepEmb = $mapaSeparacaoEmbalado['COD_MAPA_SEPARACAO_EMB_CLIENTE'];
                    }
                }
            } else {
                $codPessoa = "NULL";
            }

            $dataConferencia = (new \DateTime())->format("d/m/Y H:i:s");

            foreach ($conferencia as $conf) {

                $idVolume = (!empty($conf['codPrdutoVolume']))? $conf['codPrdutoVolume'] : 'NULL';
                $idEmbalagem = (!empty($conf['codProdutoEmbalagem']))? $conf['codProdutoEmbalagem'] : 'NULL';

                $sql = "
                    INSERT INTO MAPA_SEPARACAO_CONFERENCIA 
                        (
                         COD_MAPA_SEPARACAO_CONFERENCIA, 
                         COD_MAPA_SEPARACAO, 
                         COD_PRODUTO, 
                         DSC_GRADE, 
                         COD_PRODUTO_VOLUME, 
                         COD_PRODUTO_EMBALAGEM, 
                         QTD_EMBALAGEM, 
                         QTD_CONFERIDA, 
                         COD_OS, 
                         NUM_CONFERENCIA, 
                         DTH_CONFERENCIA, 
                         COD_VOLUME_PATRIMONIO, 
                         COD_MAPA_SEPARACAO_EMBALADO, 
                         COD_PESSOA, 
                         DSC_LOTE
                         ) 
                     VALUES (
                             SQ_MAPA_SEPARACAO_CONF_01.nextval,
                             $conf[codMapaSeparacao],
                             '$conf[codProduto]',
                             '$conf[dscGrade]',
                             $idVolume,
                             $idEmbalagem,
                             $conf[qtdEmbalagem],
                             $conf[quantidade],
                             $ordemServicoId,
                             $conf[numConferencia],
                             TO_DATE('$dataConferencia', 'DD/MM/YYYY HH24:MI:SS'),
                             NULL,
                             $idMapaSepEmb,
                             $codPessoa,
                             '$conf[lote]'
                             ) 
                ";
                $this->conn->query($sql)->execute();
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollback();
            throw $e;
        }

        return true;
    }

    public function conferenciaAlternativa($dadosConferencia, $paramsModeloSeparacao, $checkout = false)
    {

        $ncl = "NAO_CONTROLA_LOTE";
        $idExpedicao = $dadosConferencia['idExpedicao'];
        $idMapa = $dadosConferencia['idMapa'];
        $codBarras = $dadosConferencia['codBarras'];
        $qtd = $dadosConferencia['qtd'];
        $codPessoa = $dadosConferencia['codPessoa'];
        $lote = (!empty($dadosConferencia['lote']) && $dadosConferencia['lote'] != $ncl) ? $dadosConferencia['lote'] : null ;

        $utilizaQuebra = $paramsModeloSeparacao['utilizaQuebra'];
        $tipoDefaultEmbalado = $paramsModeloSeparacao['tipoDefaultEmbalado'];
        $utilizaVolumePatrimonio = $paramsModeloSeparacao['utilizaVolumePatrimonio'];

        $whereMSPEmbalado = "";
        $whereMSCEmbalado = "";
        $whereOnNaoConsolidado = "";
        if ($codPessoa != null) {
            $whereMSPEmbalado = "
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                WHERE P.COD_PESSOA = " . $codPessoa;
            $whereMSCEmbalado = "
                WHERE COD_PESSOA = " . $codPessoa;
        } else {
            $whereOnNaoConsolidado = "AND MSQ.IND_TIPO_QUEBRA <> 'T'";
        }

        //SE O INDICADOR DE EMBALADO NAO FOR O PRODUTO E SIM A EMBALAGEM FRACIONADA, ENTÂO JA RETORNA ISSO NA QUERY
        $SQLFields = "";
        $SQLJoin = "";
        if ($tipoDefaultEmbalado != "P") {
            $SQLFields = " PEP.QTD_EMBALAGEM as QTD_EMBALAGEM_PADRAO, ";
            $SQLJoin   = " LEFT JOIN PRODUTO_EMBALAGEM PEP ON PEP.COD_PRODUTO = MSP.COD_PRODUTO AND PEP.DSC_GRADE = MSP.DSC_GRADE AND PEP.IND_PADRAO = 'S'";
        }

        //QUERY PRINCIPAL PARA VALIDAÇÃO DE CONFERENCIA
        $SQL = "SELECT DISTINCT $SQLFields
                       MS.COD_MAPA_SEPARACAO,
                       CASE WHEN MS.COD_MAPA_SEPARACAO = $idMapa THEN 0 ELSE 1 END as ORDENADOR,
                       MSP.QTD_SEPARAR,
                       P.DSC_PRODUTO,
                       P.COD_PRODUTO,
                       P.DSC_GRADE,
                       MSP.DSC_LOTE,
                       P.IND_FRACIONAVEL,
                       PE.COD_PRODUTO_EMBALAGEM,
                       PV.COD_PRODUTO_VOLUME,
                       NVL(PE.DSC_EMBALAGEM,PV.DSC_VOLUME) as DSC_EMBALAGEM,
                       NVL(PE.QTD_EMBALAGEM,1) as QTD_EMBAlAGEM,
                       NVL(CONF.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                       NVL(PE.IND_EMBALADO,'N') as IND_EMBALADO,
                       NVL(PE.IS_EMB_FRACIONAVEL_DEFAULT, 'N') as IS_EMB_FRACIONAVEL_DEFAULT,
                       NVL(PE.IS_EMB_EXPEDICAO_DEFAULT, 'N') as IS_EMB_EXP_DEFAULT
                  FROM MAPA_SEPARACAO MS
                  INNER JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MS.COD_MAPA_SEPARACAO = MSQ.COD_MAPA_SEPARACAO
                  INNER JOIN (SELECT COD_MAPA_SEPARACAO, MSP.COD_PRODUTO, MSP.DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) COD_PRODUTO_VOLUME,
                                    SUM((QTD_EMBALAGEM * QTD_SEPARAR) - NVL(QTD_CORTADO,0)) as QTD_SEPARAR, NVL(MSP.DSC_LOTE, '$ncl') DSC_LOTE
                               FROM MAPA_SEPARACAO_PRODUTO MSP
                               $whereMSPEmbalado
                              GROUP BY COD_MAPA_SEPARACAO, MSP.COD_PRODUTO, MSP.DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0), NVL(MSP.DSC_LOTE, '$ncl')) MSP ON MS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                  LEFT JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) COD_PRODUTO_VOLUME, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA, 
                              NVL(DSC_LOTE, '$ncl') DSC_LOTE
                               FROM MAPA_SEPARACAO_CONFERENCIA
                               $whereMSCEmbalado
                             GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0), NVL(DSC_LOTE, '$ncl')) CONF
                         ON CONF.COD_PRODUTO = MSP.COD_PRODUTO
                        AND CONF.DSC_GRADE = MSP.DSC_GRADE
                        AND CONF.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                        AND CONF.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                        AND CONF.DSC_LOTE = MSP.DSC_LOTE
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = MSP.COD_PRODUTO AND PE.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                  $SQLJoin
                 WHERE 1 = 1
                    $whereOnNaoConsolidado
                    AND ((PE.COD_BARRAS = '$codBarras' AND PE.DTH_INATIVACAO IS NULL) OR (PV.COD_BARRAS = '$codBarras' AND PV.DTH_INATIVACAO IS NULL))";

        //SE UTIILIZAR QUEBRA NA CONFERENCIA ENTÃO COMPARO APENAS COM O MAPA INFORMADO, CASO CONTRARIO COMPARO COM TODOS OS MAPAS DA EXPEDIÇÃO
        if ($utilizaQuebra == "S") {
            $SQL = $SQL . " AND MSP.COD_MAPA_SEPARACAO = $idMapa";
        } else {
            $SQL = $SQL . " AND MS.COD_EXPEDICAO = $idExpedicao";
        }

        $SQL .= " ORDER BY ORDENADOR";

        $result = $this->conn->query($SQL)->fetchAll();

        $fatorCodBarrasBipado = $result[0]['QTD_EMBALAGEM'];
        $codBarrasEmbalado = $result[0]['IND_EMBALADO'];
        $codProdutoEmbalagem = $result[0]['COD_PRODUTO_EMBALAGEM'];
        $codProdutoVolume = $result[0]['COD_PRODUTO_VOLUME'];
        $dscProduto = $result[0]['DSC_PRODUTO'];
        $codProduto = $result[0]['COD_PRODUTO'];
        $dscGrade = $result[0]['DSC_GRADE'];
        $dscEmbalagem = $result[0]['DSC_EMBALAGEM'] . "($fatorCodBarrasBipado)";
        $prodFracionavel = $result[0]['IND_FRACIONAVEL'];
        $isEmbExpDefault = $result[0]['IS_EMB_EXP_DEFAULT'];

        if ($prodFracionavel == 'S') {
//            /** @var EmbalagemRepository $embalagemRepo */
//            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
//            /** @var Embalagem $embExpDefault */
//            $embExpDefault = $embalagemRepo->findOneBy(['codProduto' => $codProduto, 'grade' => $dscGrade, 'isEmbExpDefault' => 'S']);
//            if (!empty($embFracDefault) && $isEmbExpDefault != 'S') {
//                throw new \Exception("Este produto $codProduto - $dscGrade só pode ser expedido na embalagem " . $embExpDefault->getDescricao());
//            }
        } else {
            if (Math::resto($qtd, 1) > 0) {
                throw new \Exception("O produto $codProduto - $dscGrade não pode ser expedido em uma fração da menor embalagem!");
            }
        }

        //CALCULO A QUANTIDADE PENDENTE DE CONFERENCIA PARA CADA MAPA, SE UTILIZAR QUEBRA O FILTRO VAI TRAZER APENAS UM MAPA
        $qtdConferidoTotal = 0;
        $qtdMapaTotal = 0;
        $qtdInformada = Math::multiplicar($qtd, $fatorCodBarrasBipado);

        $qtdConferenciaGravar = array();
        $qtdRestante = $qtdInformada;
        foreach ($result as $mapa) {
            //CASO SEJA CONFERÊNCIA DE EMBALADO NÃO SOMA AS QTDS DO MESMO ITEM DE TODOS OS MAPAS
            if (!empty($codPessoa) && $mapa['COD_MAPA_SEPARACAO'] != $idMapa) continue;

            //CASO O PRODUTO CONTROLE LOTE, SÓ CALCULA O LOTE ESPECÍFICO
            if (!empty($lote) && $mapa["DSC_LOTE"] != $lote) continue;

            $qtdMapaTotal = Math::adicionar($qtdMapaTotal, $mapa['QTD_SEPARAR']);
            $qtdConferidoTotal = Math::adicionar($qtdConferidoTotal, $mapa['QTD_CONFERIDA']);
            $qtdPendenteConferenciaMapa = Math::subtrair($mapa['QTD_SEPARAR'], $mapa['QTD_CONFERIDA']);

            $codMapa = $mapa['COD_MAPA_SEPARACAO'];

            if (Math::compare($qtdRestante, $qtdPendenteConferenciaMapa, "<=")) {
                $qtdConferir = $qtdRestante;
            } else {
                $qtdConferir = (!$checkout) ? $qtdPendenteConferenciaMapa: $qtdRestante ;
            }

            $qtdConferidoTotalEmb = $qtdConferidoTotal;
            if ($checkout && $qtdConferidoTotal > 0) {
//                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
//                $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($codProduto, $dscGrade, $qtdConferidoTotal);
//                $qtdConferidoTotalEmb = implode(' + ', $vetSeparar);
            }
            if ($qtdConferir > 0) {
                $qtdConferenciaGravar[] = array(
                    'codMapaSeparacao' => $codMapa,
                    'codProduto' => $codProduto,
                    'dscGrade' => $dscGrade,
                    'numConferencia' => 1,
                    'codProdutoEmbalagem' => $codProdutoEmbalagem,
                    'codPrdutoVolume' => $codProdutoVolume,
                    'qtdEmbalagem' => $fatorCodBarrasBipado,
                    'qtdConferidaTotalEmb' => $qtdConferidoTotalEmb,
                    'quantidade' => Math::dividir($qtdConferir, $fatorCodBarrasBipado),
                    'lote' => (!empty($lote))? $lote : $ncl
                );

                $qtdRestante = Math::subtrair($qtdRestante, $qtdConferir);
            }
        }

        if (Math::compare($qtdRestante, 0, ">") && !$checkout) {
            $strLote = (!empty($lote)) ? " lote: '$lote'" : "";
            throw new \Exception("A quantidade de $qtdInformada para o produto $codProduto ($dscProduto) - $dscGrade$strLote excede o solicitado!");
        }

        //VERIFICO SE O PRODUTO JA FOI COMPELTAMENTE CONFERIDO NO MAPA OU NA EXPEDIÇÃO DE ACORDO COM O PARAMETRO DE UTILIZAR QUEBRA NA CONFERENCIA
        if($checkout == true) {
            if ($qtdMapaTotal == $qtdConferidoTotal) {
                return array('produto' => $qtdConferenciaGravar, 'checkout' => 'checkout');
            }else{
                return array('produto' => $qtdConferenciaGravar);
            }
        }
        if ($qtdMapaTotal == $qtdConferidoTotal) {
            $msgErro = "O produto $dscProduto já se encontra totalmente conferido ";
            if ($codPessoa != null) {
                $msgErro .= "para o cliente selecionado";
            } else {
                if ($utilizaQuebra == "S") {
                    $msgErro .= "no mapa " . $idMapa;
                } else {
                    $msgErro .= "na expedicao " . $idExpedicao;
                }
            }
            throw new \Exception($msgErro);
        } elseif (Math::compare($qtdInformada, Math::subtrair($qtdMapaTotal,$qtdConferidoTotal), '>')) {
            throw new \Exception("A quantidade de $qtdInformada excede o solicitado!");
        }

        //VERIFCO SE O PRODUTO É EMBALADO E ESTA UTILIZANDO VOLUME PATRIMONIO
        $embalado = false;
        if ($tipoDefaultEmbalado == "P") {
            if ($codBarrasEmbalado == "S") {
                $embalado = true;
            }
        } else {
            $QtdPadraoRecebimento = $result[0]['QTD_EMBALAGEM_PADRAO'];
            if ($fatorCodBarrasBipado < $QtdPadraoRecebimento) {
                $embalado = true;
            }
        }


        if ($utilizaVolumePatrimonio == 'S') {
            if ((!(isset($dadosConferencia['idVolumePatrimonio'])) || ($dadosConferencia['idVolumePatrimonio'] == null)) && ($embalado == true)) {
                throw new \Exception("O produto $codProduto / $dscGrade - $dscProduto - $dscEmbalagem é embalado");
            }
        }

        return $qtdConferenciaGravar;
    }

    public function saveMapaEmb($idMapa, $codPessoa, $os)
    {
        $idEmbalado = "14". $this->conn->query("SELECT SQ_MAPA_SEPARACAO_EMBALADO_01.nextval ID_EMBALADO FROM DUAL")->fetchFirstResult()['ID_EMBALADO'];
        $sequencia = $this->conn->query("SELECT (NVL(MAX(NUM_SEQUENCIA), 0) + 1) AS SEQ 
                                   FROM MAPA_SEPARACAO_EMB_CLIENTE 
                                   WHERE COD_MAPA_SEPARACAO = $idMapa AND COD_PESSOA = $codPessoa")->fetchFirstResult()['SEQ'];

        var_dump($sequencia);

        $sql = "INSERT INTO MAPA_SEPARACAO_EMB_CLIENTE 
                   (COD_MAPA_SEPARACAO_EMB_CLIENTE, 
                    COD_PESSOA, 
                    COD_MAPA_SEPARACAO, 
                    COD_STATUS, 
                    NUM_SEQUENCIA, 
                    IND_ULTIMO_VOLUME, 
                    COD_OS)
                VALUES (
                        $idEmbalado,
                        $codPessoa,
                        $idMapa,
                        567,
                        $sequencia,
                        'N',
                        $os
                )";

        $this->conn->query($sql)->execute();

        return $idEmbalado;
    }

    public function getOsMapaConfEmbalagem($cpfEmbalador, $idExpedicao, $cine = false)
    {

        $pessoa = self::getPessoaByCpf($cpfEmbalador);
        if (empty($pessoa)) throw new \Exception("Nenhum usuário encontrado com esse CPF: $cpfEmbalador");

        $idPessoa = $pessoa['COD_PESSOA'];
        $sql = "SELECT * FROM ORDEM_SERVICO WHERE COD_PESSOA = $idPessoa AND COD_ATIVIDADE = 18 AND COD_EXPEDICAO = $idExpedicao AND DTH_FINAL_ATIVIDADE IS NULL";

        $os = $this->conn->query($sql)->fetchFirstResult();

        if (!empty($os)) {
            return $os['COD_OS'];
        }

        if ($cine) {
            return self::addNewOsEmbalagemHardCode($idPessoa, $idExpedicao);
        }

        throw new \Exception("Nenhuma Ordem de Serviço aberta para embalamento de checkout foi encontrada para essa pessoa nessa expedição");

    }

    public function addNewOsEmbalagemHardCode($idPessoa, $idExpedicao)
    {

        $dthOs = (new \DateTime())->format("d/m/Y H:i:s");
        $idOs = $this->conn->query("SELECT SQ_ORDEM_SERVICO_01.nextval ID_OS FROM DUAL")->fetchFirstResult()['ID_OS'];

        $sql = "INSERT INTO ORDEM_SERVICO 
                    (
                     COD_OS, 
                     DTH_INICIO_ATIVIDADE, 
                     COD_ATIVIDADE, 
                     DSC_OBSERVACAO,
                     COD_PESSOA, 
                     COD_FORMA_CONFERENCIA,
                     COD_EXPEDICAO
                     ) VALUES (
                       $idOs,
                       TO_DATE('$dthOs', 'DD/MM/YYYY HH24:MI:SS'),
                       18,
                       'Embalamento no Checkout',
                       $idPessoa,
                       'M',
                       $idExpedicao
                     )";
        $this->conn->query($sql)->execute();

        return $idOs;
    }

    public function getPessoaByCpf($cpf)
    {

        $sql = "SELECT P.NOM_PESSOA, P.COD_PESSOA
                FROM PESSOA P
                INNER JOIN PESSOA_FISICA PF ON PF.COD_PESSOA = P.COD_PESSOA
                WHERE PF.NUM_CPF = '$cpf'";

        return $this->conn->query($sql)->fetchFirstResult();
    }
}