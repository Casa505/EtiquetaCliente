<?php

$cliente = [
    'nome' => 'CLIENTE EXEMPLO LTDA',
    'endereco' => 'RUA DAS FLORES',
    'numero' => '123',
    'bairro' => 'CENTRO',
    'cidade' => 'DIVINOPOLIS',
    'estado' => 'MG',
    'cep' => '35500-000',
    'fone' => '(37) 99999-9999'
];

function e($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">

<title>Etiqueta Cliente</title>

<style>

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
    height: 29mm;

    font-family: Arial, Helvetica, sans-serif;
    }

    .etiqueta {

    width: 85mm;
    height: 29mm;

    padding: 2mm 3mm;

    overflow: hidden;

    display: flex;
    flex-direction: column;
    justify-content: center;
    }

    .nome {
    font-size: 11pt;
    font-weight: bold;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

    margin-bottom: 1mm;
    }

    .endereco {
    font-size: 9pt;
    font-weight: bold;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    }

    .complemento {
    font-size: 8pt;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    }

    .contato {
    font-size: 8pt;

    margin-top: 1mm;

    white-space: nowrap;
    overflow: hidden;
    }

    @media print {

    html,
    body {
        width: 85mm;
        height: 29mm;
    }

    .etiqueta {
        page-break-after: always;
        break-after: page;
    }

    }

</style>

</head>

<body>

<div class="etiqueta">

    <div class="nome">
        <?= e($cliente['nome']) ?>
    </div>

    <div class="endereco">
        <?= e($cliente['endereco']) ?>,
        <?= e($cliente['numero']) ?>
    </div>

    <div class="complemento">
        <?= e($cliente['bairro']) ?>
        -
        <?= e($cliente['cidade']) ?>/<?= e($cliente['estado']) ?>
    </div>

    <div class="complemento">
        CEP: <?= e($cliente['cep']) ?>
    </div>

    <div class="contato">
        Fone: <?= e($cliente['fone']) ?>
    </div>

</div>

<script>

window.onload = function () {

    window.print();

};

</script>

</body>
</html>