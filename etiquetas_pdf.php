<?php

require __DIR__ . '/vendor/autoload.php';

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
        return (new DateTime($data))->format('d/m/Y');
    } catch (Exception $e) {
        return (string)$data;
    }
}

function formatarPedido($pedido, $data)
{
    $pedido = trim((string)$pedido);

    $numero = ltrim($pedido, '0');

    if ($numero === '') {
        $numero = '0';
    }

    try {
        $ano = (new DateTime($data))->format('y');
    } catch (Exception $e) {
        $ano = date('y');
    }

    return $numero . '/' . $ano;
}

function formatarVendedor($vendedor)
{
    return str_pad(
        trim((string)$vendedor),
        4,
        '0',
        STR_PAD_LEFT
    );
}


// ============================================================
// PEDIDO
// ============================================================

if (!isset($_GET['pedido'])) {
    die('Pedido não informado.');
}

$codPedido = preg_replace(
    '/\D/',
    '',
    $_GET['pedido']
);

if ($codPedido === '') {
    die('Código de pedido inválido.');
}


// ============================================================
// QUANTIDADE
// ============================================================

$qtd = isset($_GET['qtd'])
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
        CL.ENDERECO,
        CL.NUMERO,
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
";

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':CODPED' => $codPedido
    ]);

    $pedido = $stmt->fetch();

} catch (PDOException $e) {

    die(
        '<h3>Erro ao consultar pedido</h3>' .
        '<pre>' .
        htmlspecialchars($e->getMessage()) .
        '</pre>'
    );
}

if (!$pedido) {
    die('Pedido não encontrado.');
}


// ============================================================
// FORMATAÇÕES
// ============================================================

$numeroPedido = formatarPedido(
    $pedido['PED'],
    $pedido['DAT']
);

$dataPedido = formatarData(
    $pedido['DAT']
);

$vendedor = formatarVendedor(
    $pedido['VEND']
);

$telefone = trim(
    (string)$pedido['TEL']
);

$numeroEndereco = trim(
    (string)$pedido['NUMERO']
);

$endereco = trim(
    (string)$pedido['ENDERECO']
);

if ($numeroEndereco !== '') {
    $endereco .= ' N ' . $numeroEndereco;
}


// ============================================================
// PDF
// ============================================================

$mpdf = new \Mpdf\Mpdf([

    'mode' => 'utf-8',

    // primeira página
    'format' => [85, 29],

    'margin_left'   => 0,
    'margin_right'  => 0,
    'margin_top'    => 0,
    'margin_bottom' => 0,

    'margin_header' => 0,
    'margin_footer' => 0
]);

$mpdf->SetAutoPageBreak(false, 0);


// ============================================================
// CSS
// ============================================================

$css = '
<style>

@page {
    size: 85mm 29mm;
    margin: 0;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
}

.etiqueta {
    width: 84mm;
    height: 28mm;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

.tabela-principal {
    width: 84mm;
    height: 28mm;
    border-collapse: collapse;
    table-layout: fixed;
}

.col-logo {
    width: 19mm;
    height: 27mm;
    padding: 0;
    vertical-align: middle;
    text-align: center;
    border-right: 0.3mm solid #777;
}

.logo-casa {
    font-size: 11pt;
    line-height: 10pt;
    font-weight: bold;
}

.logo-505 {
    margin-top: 1mm;
    font-size: 23pt;
    line-height: 20pt;
    font-weight: bold;
}

.col-dados {
    width: 64mm;
    height: 27mm;
    padding: 1mm 1.5mm 0.5mm 2mm;
    vertical-align: top;
}

.cliente {
    font-size: 9.5pt;
    font-weight: bold;
    line-height: 10.5pt;
    white-space: nowrap;
    overflow: hidden;
}

.endereco {
    margin-top: 0.8mm;
    font-size: 9.5pt;
    font-weight: bold;
    line-height: 10.5pt;
}

.telefone {
    margin-top: 0.5mm;
    text-align: right;
    font-size: 9.5pt;
    font-weight: bold;
}

.espaco {
    height: 3mm;
}

.rodape {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: 0.5mm;
}

.rodape td {
    padding: 0;
    font-size: 8pt;
    font-weight: bold;
    white-space: nowrap;
}

.pedido {
    width: 33%;
    text-align: left;
}

.data {
    width: 34%;
    text-align: center;
}

.vendedor {
    width: 33%;
    text-align: right;
}

</style>
';

$mpdf->WriteHTML($css);


// ============================================================
// GERA ETIQUETAS
// ============================================================

for ($i = 1; $i <= $qtd; $i++) {

    $html = '

    <div class="etiqueta">

        <table class="tabela-principal">

            <tr>

                <td class="col-logo">

                    <div class="logo-casa">
                        CASA
                    </div>

                    <div class="logo-505">
                        505
                    </div>

                </td>


                <td class="col-dados">

                    <div class="cliente">
                        ' .
                        h($pedido['CODCLIENTE']) .
                        ' - ' .
                        h($pedido['NOME']) .
                        '
                    </div>

                    <div class="endereco">
                        ' .
                        h($endereco) .
                        '
                    </div>

                    ' .

                    (
                        $telefone !== ''
                        ?
                        '
                        <div class="telefone">
                            ' . h($telefone) . '
                        </div>
                        '
                        :
                        ''
                    )

                    . '

                    <div class="espaco"></div>

                    <table class="rodape">

                        <tr>

                            <td class="pedido">
                                Nº ' . h($numeroPedido) . '
                            </td>

                            <td class="data">
                                ' . h($dataPedido) . '
                            </td>

                            <td class="vendedor">
                                Vend.: ' . h($vendedor) . '
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

        </table>

    </div>
    ';

    $mpdf->WriteHTML($html);


    // ========================================================
    // PRÓXIMA PÁGINA
    // 29 x 85 + L = 85 x 29
    // ========================================================

    if ($i < $qtd) {
        $mpdf->WriteHTML('<pagebreak />');
    }
}


// ============================================================
// NOME DO PDF
// ============================================================

$nomeArquivo =
    'Etiqueta_' .
    str_replace(
        '/',
        '_',
        $numeroPedido
    ) .
    '.pdf';


// ============================================================
// SAÍDA
// ============================================================

$mpdf->Output(
    $nomeArquivo,
    \Mpdf\Output\Destination::INLINE
);