<?php

function h($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function upper($valor)
{
    $valor = trim((string)$valor);

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($valor, 'UTF-8');
    }

    return strtoupper($valor);
}

// ============================================================
// DADOS RECEBIDOS
// ============================================================

$nome       = upper($_POST['nome'] ?? '');
$endereco   = upper($_POST['endereco'] ?? '');
$numero     = upper($_POST['numero'] ?? '');
$bairro     = upper($_POST['bairro'] ?? '');
$telefone   = upper($_POST['telefone'] ?? '');
$observacao = upper($_POST['observacao'] ?? '');

$qtd = isset($_POST['qtd']) ? (int)$_POST['qtd'] : 1;

if ($qtd < 1) $qtd = 1;
if ($qtd > 100) $qtd = 100;

if ($nome === '') {
    die('Informe o nome.');
}

// ============================================================
// ENDEREÇO COMPLETO
// ============================================================

$enderecoCompleto = $endereco;

if ($numero !== '') {
    if ($enderecoCompleto !== '') {
        $enderecoCompleto .= ' ';
    }

    $enderecoCompleto .= 'N ' . $numero;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Etiqueta Avulsa</title>

<style>
@page {
    size: 85mm 29mm;
    margin: 0;
}

* { box-sizing: border-box; }

html,
body {
    margin: 0;
    padding: 0;
    width: 85mm;
    background: #fff;
    font-family: Arial, Helvetica, sans-serif;
}

.etiqueta {
    width: 84.5mm;
    height: 28.5mm;
    margin: 0;
    padding: 1mm 1.5mm;
    display: flex;
    overflow: hidden;
    position: relative;
}

.etiqueta + .etiqueta {
    page-break-before: always;
    break-before: page;
}

.logo {
    width: 19mm;
    height: 26.5mm;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 0.3mm solid #999;
    padding-right: 2mm;
}

.logo-caixa {
    text-align: center;
    font-family: Arial Black, Arial, sans-serif;
    font-weight: bold;
    line-height: .86;
}

.logo-casa { font-size: 11pt; }
.logo-505  { font-size: 22pt; }

.dados {
    width: 62.5mm;
    height: 26.5mm;
    position: relative;
    padding-left: 2.5mm;
}

.nome {
    width: 100%;

    font-size: 10pt;
    line-height: 1.05;

    font-weight: bold;

    white-space: normal;
    overflow: visible;
    text-overflow: unset;

    word-wrap: break-word;
    overflow-wrap: break-word;

    max-height: 7mm;
}

.endereco {
    width: 100%;

    margin-top: 0.7mm;

    padding-right: 1mm;

    font-size: 9.5pt;
    font-weight: 600;

    line-height: 1.05;

    white-space: normal;

    word-wrap: break-word;
    overflow-wrap: break-word;

    max-height: 7mm;
    overflow: hidden;
}

.bairro {
    width: 100%;
    margin-top: .4mm;
    padding-right: 1mm;
    font-size: 9pt;
    font-weight: 600;
    line-height: 1.05;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.telefone {
    width: 100%;
    margin-top: .4mm;
    padding-right: 1mm;
    text-align: right;
    font-size: 9.5pt;
    font-weight: bold;
}

.observacao {
    position: absolute;
    left: 2.5mm;
    right: 1mm;
    bottom: .5mm;
    font-size: 8.5pt;
    font-weight: bold;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

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
</style>
</head>

<body>

<?php for ($i = 1; $i <= $qtd; $i++): ?>

<div class="etiqueta">

    <div class="logo">
        <div class="logo-caixa">
            <div class="logo-casa">CASA</div>
            <div class="logo-505">505</div>
        </div>
    </div>

    <div class="dados">

        <div class="nome">
            <?= h($nome) ?>
        </div>

        <?php if ($enderecoCompleto !== ''): ?>
            <div class="endereco">
                <?= h($enderecoCompleto) ?>
            </div>
        <?php endif; ?>

        <?php if ($bairro !== ''): ?>
            <div class="bairro">
                BAIRRO: <?= h($bairro) ?>
            </div>
        <?php endif; ?>

        <?php if ($telefone !== ''): ?>
            <div class="telefone">
                <?= h($telefone) ?>
            </div>
        <?php endif; ?>

        <?php if ($observacao !== ''): ?>
            <div class="observacao">
                <?= h($observacao) ?>
            </div>
        <?php endif; ?>

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
