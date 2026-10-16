<?php

// ============================================================
// CONFIGURAÇÃO FIREBIRD
// ============================================================

$host    = '172.50.1.2';
$banco   = 'C:\\Reswincs\\banco\\RESULTH.FB';
$usuario = 'SYSDBA';
$senha   = 'masterkey';
$charset = 'WIN1252';

try {

    $pdo = new PDO(
        "firebird:dbname={$host}:{$banco};charset={$charset}",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    die(
        '<h3>Erro ao conectar no Firebird</h3>' .
        '<pre>' .
        htmlspecialchars($e->getMessage()) .
        '</pre>'
    );
}


// ============================================================
// FUNÇÕES
// ============================================================

function h($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatarData($data)
{
    if (!$data) {
        return '';
    }

    try {

        return (new DateTime($data))
            ->format('d/m/Y');

    } catch (Exception $e) {

        return (string)$data;
    }
}


function formatarPedido($pedido, $data)
{
    $pedido = trim(
        (string)$pedido
    );

    $numero =
        ltrim(
            $pedido,
            '0'
        );

    if ($numero === '') {
        $numero = '0';
    }

    try {

        $ano =
            (new DateTime($data))
                ->format('y');

    } catch (Exception $e) {

        $ano = date('y');
    }

    return
        $numero 
         
        ;
}


function formatarVendedor($vendedor)
{
    return str_pad(
        trim((string)$vendedor),
        3,
        '0',
        STR_PAD_LEFT
    );
}


// ============================================================
// PEDIDO
// ============================================================

if (!isset($_GET['pedido'])) {

    die(
        'Pedido não informado.'
    );
}


$codPedido =
    preg_replace(
        '/\D/',
        '',
        $_GET['pedido']
    );


if ($codPedido === '') {

    die(
        'Código de pedido inválido.'
    );
}


// ============================================================
// QUANTIDADE
// ============================================================

$qtd =
    isset($_GET['qtd'])
    ? (int)$_GET['qtd']
    : 1;


if ($qtd < 1) {
    $qtd = 1;
}


if ($qtd > 100) {
    $qtd = 100;
}


// ============================================================
// CONSULTA
// ============================================================

$sql = "
    SELECT
        PC.CODCLIENTE,
        CL.NOME,

        /* ENDEREÇO: primeiro tenta o endereço específico do pedido */
        CASE
            WHEN COALESCE(TRIM(PC.ENDERECO_LOCAL_ENTREGA), '') <> ''
                THEN PC.ENDERECO_LOCAL_ENTREGA
            ELSE CL.ENDERECO
        END AS ENDERECO,

        CASE
            WHEN COALESCE(TRIM(PC.NUMERO_LOCAL_ENTREGA), '') <> ''
                THEN PC.NUMERO_LOCAL_ENTREGA
            ELSE CL.NUMERO
        END AS NUMERO,

        CASE
            WHEN COALESCE(TRIM(PC.COMPLEMENTO_LOCAL_ENTREGA), '') <> ''
                THEN PC.COMPLEMENTO_LOCAL_ENTREGA
            ELSE CL.COMPLEMENTO
        END AS COMPLEMENTO,

        CASE
            WHEN COALESCE(TRIM(PC.BAIRRO_LOCAL_ENTREGA), '') <> ''
                THEN PC.BAIRRO_LOCAL_ENTREGA
            ELSE CL.BAIRRO
        END AS BAIRRO,

        CASE
            WHEN COALESCE(TRIM(PC.CEP_LOCAL_ENTREGA), '') <> ''
                 AND PC.CEP_LOCAL_ENTREGA <> '00000000'
                THEN PC.CEP_LOCAL_ENTREGA
            ELSE CL.CEP
        END AS CEP,

        CASE
            WHEN COALESCE(TRIM(PC.ESTADO_LOCAL_ENTREGA), '') <> ''
                THEN PC.ESTADO_LOCAL_ENTREGA
            ELSE CL.ESTADO
        END AS ESTADO,

        CASE
            WHEN COALESCE(TRIM(PC.CODCIDADE_LOCAL_ENTREGA), '') <> ''
                THEN PC.CODCIDADE_LOCAL_ENTREGA
            ELSE CL.CODCIDADE
        END AS CODCIDADE,

        CL.FONE AS TEL,

        PC.CODPEDIDO AS PED,
        PC.DATAPEDIDO AS DAT,
        PC.CODVENDEDOR AS VEND

    FROM PEDIDOC PC

    LEFT JOIN CLIENTE CL
        ON CL.CODCLIENTE = PC.CODCLIENTE

    WHERE
        PC.CODPEDIDO = LPAD(:CODPED, 8, '0')
        AND PC.TIPOPEDIDO = '55'
        AND PC.CODEMPRESA = '00'
";


try {

    $stmt =
        $pdo->prepare($sql);

    $stmt->execute([
        ':CODPED' => $codPedido
    ]);

    $pedido =
        $stmt->fetch();

} catch (PDOException $e) {

    die(
        '<h3>Erro ao consultar pedido</h3>' .
        '<pre>' .
        htmlspecialchars(
            $e->getMessage()
        ) .
        '</pre>'
    );
}


if (!$pedido) {

    die(
        'Pedido ' .
        h($codPedido) .
        ' não encontrado.'
    );
}


// ============================================================
// FORMATAÇÕES
// ============================================================

$numeroPedido =
    formatarPedido(
        $pedido['PED'],
        $pedido['DAT']
    );


$dataPedido =
    formatarData(
        $pedido['DAT']
    );


$vendedor =
    formatarVendedor(
        $pedido['VEND']
    );

?>
<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<title>
Etiqueta <?= h($numeroPedido) ?>
</title>


<style>

    /* ============================================================
    PAPEL
    ============================================================ */
    @page {
        size: 85mm 29mm;
        margin: 0;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        padding: 0;

        width: 85mm;

        background: #fff;

        font-family:
            Arial,
            Helvetica,
            sans-serif;
    }


    /* ============================================================
    ETIQUETA
    ============================================================ */

    .etiqueta {

        width: 84.5mm;

        /* Um pouco menor que os 29mm para evitar
        arredondamento do Chrome criando página extra */
        height: 28.5mm;

        margin: 0;

        padding:
            1mm
            1.5mm;

        display: flex;

        overflow: hidden;

        position: relative;

    }


    /* ============================================================
    DA SEGUNDA ETIQUETA EM DIANTE
    CRIA NOVA PÁGINA
    ============================================================ */

    .etiqueta + .etiqueta {

        page-break-before: always;

        break-before: page;

    }


    /* ============================================================
    LOGO
    ============================================================ */

    .logo {

        width: 19mm;

        height: 26.5mm;

        display: flex;

        align-items: center;

        justify-content: center;

        border-right:
            0.3mm solid #999;

        padding-right: 2mm;

    }


    .logo-caixa {

        text-align: center;

        font-family:
            Arial Black,
            Arial,
            sans-serif;

        font-weight: bold;

        line-height: 0.86;

    }


    .logo-casa {

        font-size: 11pt;

    }


    .logo-505 {

        font-size: 22pt;

    }


    /* ============================================================
    DADOS
    ============================================================ */

    .dados {

        width: 62.5mm;

        height: 26.5mm;

        position: relative;

        padding-left: 2.5mm;

    }


    .cliente {

        width: 100%;

        font-size: 10pt;

        line-height: 1.1;

        font-weight: bold;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    .endereco {

        width: 100%;

        margin-top: 1mm;

        padding-right: 1mm;

        font-size: 10pt;

        font-weight: 600;

        line-height: 1.15;

        max-height: 8mm;

        overflow: hidden;

    }


    .telefone {

        width: 100%;

        margin-top: 0.5mm;

        padding-right: 1mm;

        text-align: right;

        font-size: 10pt;

        font-weight: bold;

    }


    .rodape {

        position: absolute;

        left: 2.5mm;

        right: 0;

        bottom: 0;

        display: flex;

        align-items: flex-end;

        justify-content: space-between;

        gap: 1mm;

        white-space: nowrap;

        font-size: 8.5pt;

    }


    .numero-pedido,
    .data,
    .vendedor {

        font-weight: bold;

    }


    /* ============================================================
    IMPRESSÃO
    ============================================================ */

    @media print {

        html,
        body {

            margin: 0 !important;

            padding: 0 !important;

            width: 85mm;

        }


        .etiqueta {

            width: 84.5mm;

            height: 28.5mm;

            margin: 0;

            overflow: hidden;

        }

    }

  .bairro {

    width: 100%;

    margin-top: 0.4mm;

    padding-right: 1mm;

    font-size: 9.5pt;

    font-weight: bold;

    line-height: 1.05;

    }    

</style>

</head>


<body>


<?php
for (
    $i = 1;
    $i <= $qtd;
    $i++
):
?>


<div class="etiqueta">


    <div class="logo">

        <div class="logo-caixa">

            <div class="logo-casa">
                CASA
            </div>

            <div class="logo-505">
                505
            </div>

        </div>

    </div>


    <div class="dados">


        <div class="cliente">

            <?= h($pedido['CODCLIENTE']) ?>

            -

            <?= h($pedido['NOME']) ?>

        </div>


        <div class="endereco">

            <?= h($pedido['ENDERECO']) ?>

            <?php
            if (
                trim(
                    (string)$pedido['NUMERO']
                ) !== ''
            ):
            ?>

                N

                <?= h($pedido['NUMERO']) ?>

            <?php endif; ?>

        </div>


        <?php
        if (
            trim(
                (string)$pedido['BAIRRO']
            ) !== ''
        ):
        ?>

        <div class="bairro">

            B.:
            <?= h($pedido['BAIRRO']) ?>

        </div>

        <?php endif; ?>


        <?php
        if (
            trim(
                (string)$pedido['TEL']
            ) !== ''
        ):
        ?>

        <div class="telefone">
            Tel .:
            <?= h($pedido['TEL']) ?>

        </div>

        <?php endif; ?>


        <div class="rodape">


            <span class="numero-pedido">

                Nº
                <?= h($numeroPedido) ?>

            </span>


            <span class="data">

                <?= h($dataPedido) ?>

            </span>


            <span class="vendedor">

                Vend.:
                <?= h($vendedor) ?>

            </span>


        </div>


    </div>


</div>


<?php endfor; ?>


<script>
window.onload = function () {

    setTimeout(function () {

        window.print();

    }, 300);

};

window.onafterprint = function () {

    setTimeout(function () {

        window.close();

    }, 300);

};
</script>


</body>

</html>