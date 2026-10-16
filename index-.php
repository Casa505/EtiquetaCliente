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
        '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>'
    );
}


// ============================================================
// FUNÇÕES
// ============================================================

function h($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
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
        3,
        '0',
        STR_PAD_LEFT
    );
}


// ============================================================
// FILTROS
// ============================================================

$tipoFiltro   = $_GET['filtro'] ?? 'hoje';
$nomeCliente  = trim($_GET['nome'] ?? '');
$codigoPedido = trim($_GET['pedido'] ?? '');
$dataFiltro   = trim($_GET['data'] ?? '');


// ============================================================
// SELECT BASE
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
        PC.CODVENDEDOR AS VEND,

        PC.FATURADO,
        PC.LIBERADO_ENT

    FROM PEDIDOC PC

    LEFT JOIN CLIENTE CL
        ON CL.CODCLIENTE = PC.CODCLIENTE

    WHERE
        PC.TIPOPEDIDO = '55' 

";

$parametros = [];


// ============================================================
// FILTRO HOJE
// ============================================================

if ($tipoFiltro === 'hoje') {

    $inicio = date('Y-m-d') . ' 00:00:00';

    $fim = date(
        'Y-m-d',
        strtotime('+1 day')
    ) . ' 00:00:00';

    $sql .= "
        AND PC.DATAPEDIDO >= :DATA_INICIAL
        AND PC.DATAPEDIDO <  :DATA_FINAL
    ";

    $parametros[':DATA_INICIAL'] = $inicio;
    $parametros[':DATA_FINAL']   = $fim;
}


// ============================================================
// FILTRO DATA
// ============================================================

elseif ($tipoFiltro === 'data') {

    if ($dataFiltro !== '') {

        $inicio = $dataFiltro . ' 00:00:00';

        $fim = date(
            'Y-m-d',
            strtotime($dataFiltro . ' +1 day')
        ) . ' 00:00:00';

    } else {

        $inicio = date('Y-m-d') . ' 00:00:00';

        $fim = date(
            'Y-m-d',
            strtotime('+1 day')
        ) . ' 00:00:00';
    }

    $sql .= "
        AND PC.DATAPEDIDO >= :DATA_INICIAL
        AND PC.DATAPEDIDO <  :DATA_FINAL
    ";

    $parametros[':DATA_INICIAL'] = $inicio;
    $parametros[':DATA_FINAL']   = $fim;
}


// ============================================================
// FILTRO CLIENTE
// ============================================================

elseif ($tipoFiltro === 'cliente') {

    if ($nomeCliente !== '') {

        $sql .= "
            AND UPPER(CL.NOME) LIKE UPPER(:NOME)
        ";

        $parametros[':NOME'] =
            '%' . $nomeCliente . '%';
    }
}


// ============================================================
// FILTRO PEDIDO
// ============================================================

elseif ($tipoFiltro === 'pedido') {

    $codigoPedido =
        preg_replace(
            '/\D/',
            '',
            $codigoPedido
        );

    if ($codigoPedido !== '') {

        $sql .= "
            AND PC.CODPEDIDO =
                LPAD(:CODPED, 8, '0')
        ";

        $parametros[':CODPED'] =
            $codigoPedido;
    }
}


// ============================================================
// ORDENAÇÃO
// ============================================================

$sql .= "
    ORDER BY
        PC.DATAPEDIDO DESC,
        PC.CODPEDIDO DESC
";


// ============================================================
// EXECUTA
// ============================================================

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($parametros);

    $pedidos = $stmt->fetchAll();

} catch (PDOException $e) {

    die(
        '<h3>Erro na consulta</h3>' .
        '<pre>' .
        htmlspecialchars($e->getMessage()) .
        '</pre>' .
        '<hr>' .
        '<pre>' .
        htmlspecialchars($sql) .
        '</pre>'
    );
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    
<link rel="icon" type="image/png" href="icons/icon-192.png">

<link rel="apple-touch-icon" href="icons/icon-192.png">

<link rel="manifest" href="manifest.json">

<meta name="theme-color" content="#0755b5">

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
Impressão de Etiquetas
</title>

<style>

.btn-imprimir-bloqueado {

    background: #dc3545;

    color: #fff;

    padding: 8px 14px;

    border: 0;

    border-radius: 6px;

    font-size: 13px;

    font-weight: bold;

    cursor: not-allowed;

    opacity: 0.90;
}

.btn-imprimir-bloqueado:hover {

    background: #dc3545;

}


.btn-avulsa {
    background: #7c3aed;
    color: #fff;
}

.btn-avulsa:hover {
    background: #6d28d9;
}    

.busca-pedido-ver {

    display: flex;
    align-items: center;
    gap: 8px;


    margin-left: auto;
}

.busca-pedido-ver label {

    font-weight: bold;
}

.busca-pedido-ver input {

    width: 140px;

    padding: 9px 10px;

    border:
        1px solid
        #cbd5e1;

    border-radius: 6px;
    

    font-size: 14px;
}

.busca-pedido-ver input:focus {

    outline: none;

    border-color: #2563eb;

    box-shadow:
        0 0 0 2px
        rgba(37,99,235,0.15);
}

.btn-ver-pedido {

    padding:
        9px
        16px;

    border: none;

    border-radius: 6px;

    background: #2563eb;
    color: white;

    font-weight: bold;

    cursor: pointer;
}

.btn-ver-pedido:hover {

    background: #1d4ed8;
}

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    background: #f4f6f8;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color: #222;
}

.container {

    max-width: 1200px;

    margin: 25px auto;

    padding: 0 20px;
}

.card {

    background: #fff;

    border-radius: 10px;

    box-shadow:
        0 2px 8px rgba(0,0,0,.10);

    overflow: hidden;
}

.header {

    padding: 20px 25px;

    border-bottom: 1px solid #ddd;
}

.header h1 {

    margin: 0;

    font-size: 24px;
}

.header p {

    margin: 6px 0 0;

    color: #666;
}

.filtros {

    padding: 20px 25px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    background: #fafafa;

    border-bottom: 1px solid #ddd;
}

.btn {

    border: 0;

    border-radius: 6px;

    padding: 11px 18px;

    cursor: pointer;

    font-size: 14px;

    font-weight: bold;

    text-decoration: none;

    display: inline-block;
}

.btn-filtro {

    background: #e7ebef;

    color: #333;
}

.btn-filtro:hover {

    background: #d9dfe5;
}

.btn-ativo {

    background: #222;

    color: #fff;
}

.btn-imprimir {

    background: #198754;

    color: #fff;

    padding: 8px 14px;
}

.btn-imprimir:hover {

    background: #157347;
}

.filtro-atual {

    padding: 12px 25px;

    border-bottom: 1px solid #eee;

    font-size: 14px;
}

.tabela-container {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;
}

th {

    background: #f1f3f5;

    text-align: left;

    padding: 12px;

    font-size: 13px;

    border-bottom: 1px solid #ddd;
}

td {

    padding: 12px;

    border-bottom: 1px solid #eee;

    font-size: 14px;
}

tbody tr:hover {

    background: #f8f9fa;
}

.numero-pedido {

    font-weight: bold;

    white-space: nowrap;
}

.cliente {

    font-weight: 600;
}

.telefone {

    white-space: nowrap;
}

.vendedor {

    text-align: center;
}

.acoes {

    text-align: right;

    white-space: nowrap;
}

.vazio {

    text-align: center;

    padding: 40px;

    color: #777;
}


/* ============================================================
   MODAL
   ============================================================ */

.modal {

    display: none;

    position: fixed;

    z-index: 999;

    left: 0;
    top: 0;

    width: 100%;
    height: 100%;

    background:
        rgba(0,0,0,.45);

    align-items: center;

    justify-content: center;
}

.modal.aberto {

    display: flex;
}

.modal-box {

    width: 420px;

    max-width:
        calc(100% - 30px);

    background: #fff;

    border-radius: 10px;

    padding: 25px;

    box-shadow:
        0 8px 30px rgba(0,0,0,.25);
}

.modal-box h2 {

    margin-top: 0;

    font-size: 20px;
}

.campo {

    margin-bottom: 18px;
}

.campo label {

    display: block;

    margin-bottom: 6px;

    font-weight: bold;

    font-size: 14px;
}

.campo input {

    width: 100%;

    height: 42px;

    padding: 8px 10px;

    border: 1px solid #bbb;

    border-radius: 5px;

    font-size: 16px;
}

.modal-botoes {

    display: flex;

    justify-content: flex-end;

    gap: 8px;
}

.btn-cancelar {

    background: #ddd;

    color: #222;
}

.btn-pesquisar {

    background: #0d6efd;

    color: #fff;
}

.quantidade-box {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    margin: 20px 0;
}

.quantidade-box button {

    width: 42px;
    height: 42px;

    font-size: 22px;

    border: 0;

    border-radius: 6px;

    cursor: pointer;
}

.quantidade-box input {

    width: 80px;

    height: 42px;

    text-align: center;

    font-size: 18px;

    border: 1px solid #bbb;

    border-radius: 5px;
}

@media (max-width: 700px) {

    .container {

        margin: 10px auto;

        padding: 0 10px;
    }

    .header {

        padding: 15px;
    }

    .filtros {

        padding: 15px;
    }
}

.acoes {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-ver {
    display: inline-block;
    padding: 8px 14px;

    background: #2563eb;
    color: white;

    border: none;
    border-radius: 6px;

    text-decoration: none;

    font-size: 13px;
    font-weight: bold;

    cursor: pointer;
}

.btn-ver:hover {
    background: #1d4ed8;
}

</style>

</head>

<body>

<div class="container">

<div class="card">


<div class="header">

<h1>
Impressão de Etiquetas
</h1>

<p>
Pedidos de clientes
</p>

</div>


<div class="filtros">

<a
    href="?filtro=hoje"
    class="
        btn
        btn-filtro
        <?= $tipoFiltro === 'hoje'
            ? 'btn-ativo'
            : '' ?>
    "
>
Pedidos de Hoje
</a>



<button
    type="button"
    class="
        btn
        btn-filtro
        <?= $tipoFiltro === 'cliente'
            ? 'btn-ativo'
            : '' ?>
    "
    onclick="abrirModal('modalCliente')"
>
Cliente
</button>


<button
    type="button"
    class="
        btn
        btn-filtro
        <?= $tipoFiltro === 'pedido'
            ? 'btn-ativo'
            : '' ?>
    "
    onclick="abrirModal('modalPedido')"
>
Nº Pedido
</button>


<button
    type="button"
    class="
        btn
        btn-filtro
        <?= $tipoFiltro === 'data'
            ? 'btn-ativo'
            : '' ?>
    "
    onclick="abrirModal('modalData')"
>
Data
</button>

<a
    href="etiqueta_avulsa.php"
    class="btn btn-avulsa"
>
    Etiqueta Avulsa
</a>

<div class="busca-pedido-ver">

    <label>
        Nº.:
    </label>

    <input
        type="text"
        id="pedidoVer"
        placeholder="Número do pedido"
        inputmode="numeric"
        autocomplete="off"
    >

    <button
        type="button"
        onclick="verPedidoDigitado()"
        class="btn-ver-pedido"
    >
        Ver
    </button>

</div>


</div>


<div class="filtro-atual">

<?php if ($tipoFiltro === 'hoje'): ?>

Filtro atual:
<strong>
Pedidos de Hoje -
<?= date('d/m/Y') ?>
</strong>


<?php elseif ($tipoFiltro === 'cliente'): ?>

Filtro atual:
Cliente contendo:

<strong>
<?= h($nomeCliente) ?>
</strong>


<?php elseif ($tipoFiltro === 'pedido'): ?>

Filtro atual:
Pedido:

<strong>
<?= h($codigoPedido) ?>
</strong>


<?php elseif ($tipoFiltro === 'data'): ?>

Filtro atual:
Data:

<strong>

<?php

if ($dataFiltro !== '') {

    echo h(
        formatarData(
            $dataFiltro
        )
    );

}

?>

</strong>

<?php endif; ?>

</div>


<div class="tabela-container">

<table>

<thead>

<tr>

<th>Pedido</th>
<th>Cliente</th>
<th>Endereço</th>
<th>Telefone</th>
<th>Data</th>
<th>Vendedor</th>
<th>Ação</th>

</tr>

</thead>


<tbody>

<?php if (count($pedidos) === 0): ?>

<tr>

<td
    colspan="7"
    class="vazio"
>
Nenhum pedido encontrado.
</td>

</tr>


<?php else: ?>


<?php foreach ($pedidos as $pedido): ?>


<?php

$numeroFormatado =
    formatarPedido(
        $pedido['PED'],
        $pedido['DAT']
    );

?>


<tr>

<td class="numero-pedido">

<?= h($numeroFormatado) ?>

</td>


<td class="cliente">

<?= h($pedido['CODCLIENTE']) ?>

-

<?= h($pedido['NOME']) ?>

</td>


<td>

<?= h($pedido['ENDERECO']) ?>

<?php if (
    trim((string)$pedido['NUMERO']) !== ''
): ?>

,
<?= h($pedido['NUMERO']) ?>

<?php endif; ?>

</td>


<td class="telefone">

<?= h($pedido['TEL']) ?>

</td>


<td>

<?= h(
    formatarData(
        $pedido['DAT']
    )
) ?>

</td>


<td class="vendedor">

<?= h(
    formatarVendedor(
        $pedido['VEND']
    )
) ?>

</td>


<td class="acoes">



    <button
        class="btn-imprimir"
        onclick="abrirQuantidade('<?= h($pedido['PED']) ?>')"
    >
        Imprimir
    </button>

    <a
        href="ver_pedido.php?pedido=<?= urlencode($pedido['PED']) ?>"
        class="btn-ver"
    >
        Ver
    </a>



</td>

</tr>


<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>


<!-- ==========================================================
     MODAL CLIENTE
     ========================================================== -->

<div
    id="modalCliente"
    class="modal"
>

<div class="modal-box">

<h2>
Pesquisar Cliente
</h2>

<form method="get">

<input
    type="hidden"
    name="filtro"
    value="cliente"
>

<div class="campo">

<label>
Nome do cliente
</label>

<input
    type="text"
    name="nome"
    value="<?= h($nomeCliente) ?>"
    autocomplete="off"
    placeholder="Digite parte do nome"
>

</div>

<div class="modal-botoes">

<button
    type="button"
    class="btn btn-cancelar"
    onclick="fecharModal('modalCliente')"
>
Cancelar
</button>

<button
    type="submit"
    class="btn btn-pesquisar"
>
Pesquisar
</button>

</div>

</form>

</div>

</div>


<!-- ==========================================================
     MODAL PEDIDO
     ========================================================== -->

<div
    id="modalPedido"
    class="modal"
>

<div class="modal-box">

<h2>
Localizar Pedido
</h2>

<form method="get">

<input
    type="hidden"
    name="filtro"
    value="pedido"
>

<div class="campo">

<label>
Número do pedido
</label>

<input
    type="text"
    name="pedido"
    value="<?= h($codigoPedido) ?>"
    autocomplete="off"
    placeholder="Ex.: 828117"
>

</div>

<div class="modal-botoes">

<button
    type="button"
    class="btn btn-cancelar"
    onclick="fecharModal('modalPedido')"
>
Cancelar
</button>

<button
    type="submit"
    class="btn btn-pesquisar"
>
Pesquisar
</button>

</div>

</form>

</div>

</div>


<!-- ==========================================================
     MODAL DATA
     ========================================================== -->

<div
    id="modalData"
    class="modal"
>

<div class="modal-box">

<h2>
Pedidos por Data
</h2>

<form method="get">

<input
    type="hidden"
    name="filtro"
    value="data"
>

<div class="campo">

<label>
Data
</label>

<input
    type="date"
    name="data"
    value="<?=
        h(
            $dataFiltro !== ''
            ? $dataFiltro
            : date('Y-m-d')
        )
    ?>"
>

</div>

<div class="modal-botoes">

<button
    type="button"
    class="btn btn-cancelar"
    onclick="fecharModal('modalData')"
>
Cancelar
</button>

<button
    type="submit"
    class="btn btn-pesquisar"
>
Pesquisar
</button>

</div>

</form>

</div>

</div>


<!-- ==========================================================
     MODAL QUANTIDADE
     ========================================================== -->

<div
    id="modalQuantidade"
    class="modal"
>

<div class="modal-box">

<h2>
Quantidade de Etiquetas
</h2>

<p>
Quantas etiquetas deseja imprimir?
</p>

<input
    type="hidden"
    id="pedidoImpressao"
>

<div class="quantidade-box">

<button
    type="button"
    onclick="alterarQuantidade(-1)"
>
-
</button>

<input
    type="number"
    id="qtdEtiquetas"
    value="1"
    min="1"
    max="100"
>

<button
    type="button"
    onclick="alterarQuantidade(1)"
>
+
</button>

</div>

<div class="modal-botoes">

<button
    type="button"
    class="btn btn-cancelar"
    onclick="fecharModal('modalQuantidade')"
>
Cancelar
</button>

<button
    type="button"
    class="btn btn-imprimir"
    onclick="confirmarImpressao()"
>
Imprimir
</button>

</div>

</div>

</div>




<script>


document.addEventListener(
    'DOMContentLoaded',
    function() {

        const campo =
            document.getElementById('pedidoVer');

        if (
            sessionStorage.getItem(
                'limparPedidoVer'
            ) === '1'
        ) {

            if (campo) {

                campo.value = '';

                campo.focus();

            }

            sessionStorage.removeItem(
                'limparPedidoVer'
            );

        }

    }
);



function verPedidoDigitado() {

    let pedido =
        document
        .getElementById('pedidoVer')
        .value
        .trim();

    // deixa somente números
    pedido =
        pedido.replace(/\D/g, '');

    if (pedido === '') {

        alert('Digite o número do pedido.');

        document
            .getElementById('pedidoVer')
            .focus();

        return;

    }

    window.location.href =
        'ver_pedido.php?pedido=' +
        encodeURIComponent(pedido);
}


// ENTER também pesquisa
document.addEventListener('DOMContentLoaded', function () {

    const campo =
        document.getElementById('pedidoVer');

    if (campo) {

        campo.addEventListener(
            'keydown',
            function(event) {

                if (event.key === 'Enter') {

                    event.preventDefault();

                    verPedidoDigitado();

                }

            }
        );

    }

});





function abrirModal(id)
{
    document
        .getElementById(id)
        .classList
        .add('aberto');
}


function fecharModal(id)
{
    document
        .getElementById(id)
        .classList
        .remove('aberto');
}


function abrirQuantidade(pedido)
{
    document
        .getElementById('pedidoImpressao')
        .value = pedido;

    document
        .getElementById('qtdEtiquetas')
        .value = 1;

    abrirModal(
        'modalQuantidade'
    );

    setTimeout(function () {

        document
            .getElementById('qtdEtiquetas')
            .focus();

        document
            .getElementById('qtdEtiquetas')
            .select();

    }, 100);
}


function alterarQuantidade(valor)
{
    const input =
        document.getElementById(
            'qtdEtiquetas'
        );

    let qtd =
        parseInt(input.value) || 1;

    qtd += valor;

    if (qtd < 1) {
        qtd = 1;
    }

    if (qtd > 100) {
        qtd = 100;
    }

    input.value = qtd;
}


function confirmarImpressao()
{
    const pedido =
        document
            .getElementById(
                'pedidoImpressao'
            )
            .value;

    let qtd =
        parseInt(
            document
                .getElementById(
                    'qtdEtiquetas'
                )
                .value
        );

    if (
        isNaN(qtd) ||
        qtd < 1
    ) {

        alert(
            'Informe uma quantidade válida.'
        );

        return;
    }

    if (qtd > 100) {
        qtd = 100;
    }

    window.open(
        'etiquetas.php?pedido=' +
        encodeURIComponent(pedido) +
        '&qtd=' +
        qtd,
        '_blank'
    );

    fecharModal(
        'modalQuantidade'
    );
}


// FECHA CLICANDO FORA

document
    .querySelectorAll('.modal')
    .forEach(function (modal) {

        modal.addEventListener(
            'click',
            function (e) {

                if (e.target === modal) {

                    modal
                        .classList
                        .remove(
                            'aberto'
                        );

                }

            }
        );

    });


// ESC

document.addEventListener(
    'keydown',
    function (e) {

        if (e.key === 'Escape') {

            document
                .querySelectorAll(
                    '.modal.aberto'
                )
                .forEach(
                    function (modal) {

                        modal
                            .classList
                            .remove(
                                'aberto'
                            );

                    }
                );

        }

    }
);

</script>

</body>

</html>