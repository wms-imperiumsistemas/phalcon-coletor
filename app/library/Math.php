<?php


namespace Library;


/*
 * Tratativas registradas:
 *
 * 01/08/19 Tarcísio César: Caso Math::resto(32.55, 1.05) [Valor esperado: 0; Valor obtido: 1.04]
 *      passar multiplicações ($x * $quantidade) e ($y * $quantidade) pela função strval() nas funções de operações ficando
 *      $x = strval($x * $quantidade);
 *      $y = strval($y * $quantidade);
 */

Class Math
{
    private static function maiorPrecisao($x, $y)
    {
        $precisaoA = 0;
        $precisaoB = 0;

        $a = explode('.',$x);
        $b = explode('.',$y);
        if (count($a) > 1) {
            $precisaoA = strlen($a[1]);
        }
        if (count($b) > 1) {
            $precisaoB = strlen($b[1]);
        }
        if ($precisaoA >= $precisaoB) {
            $maiorPrecisao = $precisaoA;
        } else {
            $maiorPrecisao = $precisaoB;
        }

        return str_pad(1 ,$maiorPrecisao + 1,'0');
    }


    public static function compare($x,$y,$oper = ">") {

        $x = strval($x);
        $y = strval($y);

        $quantidade = self::maiorPrecisao($x,$y);
        
        $x = $x * $quantidade;
        $y = $y * $quantidade;

        if ($oper == ">") {
            return $x > $y;
        } elseif ($oper == ">=") {
            return $x >= $y;
        } elseif ($oper == "<=") {
            return $x <= $y;
        } elseif ($oper == "<") {
            return $x < $y;
        } elseif ($oper == '==') {
            return $x == $y;
        }
    }

    public static function resto($x, $y)
    {
        if ($x == 0) return 0;

        $x = strval($x);
        $y = strval($y);

        $quantidade = self::maiorPrecisao($x, $y);
        if ($quantidade == 0) return 0;

        $x = strval($x * $quantidade);
        $y = strval($y * $quantidade);

        if ($x == 0 || $y == 0)
            return 0;

        return ($x % $y) / $quantidade;
    }

    public static function adicionar($x, $y)
    {
        $x = strval($x);
        $y = strval($y);
        
        $quantidade = self::maiorPrecisao($x,$y);

        $x = strval($x * $quantidade);
        $y = strval($y * $quantidade);

        return ($x + $y) / $quantidade;
    }

    public static function subtrair($x, $y)
    {
        $x = strval($x);
        $y = strval($y);
        
        $quantidade = self::maiorPrecisao($x,$y);

        $x = strval($x * $quantidade);
        $y = strval($y * $quantidade);

        return ($x - $y) / $quantidade;
    }

    public static function multiplicar($x, $y)
    {
        $x = strval($x);
        $y = strval($y);
        
        $quantidade = self::maiorPrecisao($x,$y);

        $x = strval($x * $quantidade);
        $y = strval($y * $quantidade);

        return ($x * $y) / ($quantidade * $quantidade);
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @return float|int
     */
    public static function dividir($x, $y)
    {
        $x = strval($x);
        $y = strval($y);

        $quantidade = self::maiorPrecisao($x, $y);

        $x = strval($x * $quantidade);
        $y = strval($y * $quantidade);

        if ($x == 0 || $y == 0)
            return 0;

        return $x / $y;
    }

    /**
     * @param $x
     * @return float|int
     */
    public static function decrementar($x)
    {
        return self::subtrair($x,$x);
    }

    /**
     * @param $x
     * @return float|int
     */
    public static function incrementar($x)
    {
        return self::adicionar($x,$x);
    }

    /**
     * @param $qtdBase
     * @param $qtdFator
     * @return array($qtdMultipla, $resto)
     */
    public static function getFatorMultiploResto ($qtdBase, $qtdFator) {

        $return = [];
        $return[1] = self::resto($qtdBase, $qtdFator);
        // Com isso identifico quanto de cada embalagem será possível e necessária para separar o item
        $return[0] = self::dividir(self::subtrair($qtdBase, $return[1]), $qtdFator);

        return $return;
    }
}