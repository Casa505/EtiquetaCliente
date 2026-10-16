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

function formatarPedido($pedido)
{
    $pedido = trim((string)$pedido);

    $numero = ltrim($pedido, '0');

    return $numero === ''
        ? '0'
        : $numero;
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
    die('Pedido inválido.');
}


// ============================================================
// CABEÇALHO
// ============================================================

$sqlCabecalho = "
    SELECT
        PC.CODPEDIDO,
        CL.NOME,

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
            WHEN COALESCE(TRIM(PC.BAIRRO_LOCAL_ENTREGA), '') <> ''
                THEN PC.BAIRRO_LOCAL_ENTREGA
            ELSE CL.BAIRRO
        END AS BAIRRO,

        CASE
            WHEN COALESCE(TRIM(PC.CODCIDADE_LOCAL_ENTREGA), '') <> ''
                THEN CI_PED.CIDADE || '-' || PC.ESTADO_LOCAL_ENTREGA
            ELSE CI.CIDADE || '-' || CI.ESTADO
        END AS CID_UF,

        PC.LIBERADO_ENT

    FROM PEDIDOC PC

    LEFT JOIN CLIENTE CL
        ON CL.CODCLIENTE = PC.CODCLIENTE

    LEFT JOIN CIDADES CI
        ON CI.CODCIDADE = CL.CODCIDADE

    LEFT JOIN CIDADES CI_PED
        ON CI_PED.CODCIDADE = PC.CODCIDADE_LOCAL_ENTREGA

    WHERE
        PC.CODEMPRESA = '00'
        AND PC.TIPOPEDIDO = '55'
        AND PC.CODPEDIDO = LPAD(:PEDIDO, 8, '0')
";

try {

    $stmt = $pdo->prepare($sqlCabecalho);

    $stmt->execute([
        ':PEDIDO' => $codPedido
    ]);

    $cabecalho = $stmt->fetch();

} catch (PDOException $e) {

    die(
        '<h3>Erro ao consultar pedido</h3>' .
        '<pre>' .
        h($e->getMessage()) .
        '</pre>'
    );
}

if (!$cabecalho) {

    $pedidoDigitado = ltrim($codPedido, '0');

    if ($pedidoDigitado === '') {
        $pedidoDigitado = '0';
    }

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <title>Pedido não encontrado</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;

                display: flex;
                align-items: center;
                justify-content: center;

                background: #f3f6f9;

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;
            }

            .card-erro {

                width: 420px;
                max-width: calc(100% - 30px);

                background: #fff;

                border-radius: 14px;

                padding: 35px;

                text-align: center;

                box-shadow:
                    0 8px 30px
                    rgba(0,0,0,0.10);

                border-top:
                    5px solid #dc2626;
            }

            .icone {

                width: 70px;
                height: 70px;

                margin:
                    0 auto
                    20px;

                border-radius: 50%;

                display: flex;
                align-items: center;
                justify-content: center;

                background: #fee2e2;
                color: #dc2626;

                font-size: 36px;
                font-weight: bold;
            }

            h1 {

                margin:
                    0
                    0
                    12px;

                color: #1f2937;

                font-size: 24px;
            }

            .mensagem {

                margin-bottom: 8px;

                color: #6b7280;

                font-size: 15px;
            }

            .numero-pedido {

                margin:
                    18px
                    0
                    25px;

                color: #dc2626;

                font-size: 22px;
                font-weight: bold;
            }

            .btn-voltar {

                border: 0;

                background: #2563eb;
                color: #fff;

                padding:
                    11px
                    24px;

                border-radius: 7px;

                font-size: 14px;
                font-weight: bold;

                cursor: pointer;
            }

            .btn-voltar:hover {
                background: #1d4ed8;
            }

            .atalho {

                margin-top: 15px;

                color: #9ca3af;

                font-size: 12px;
            }

        </style>

    </head>

    <body>

        <div class="card-erro">

            <div class="icone">
                !
            </div>

            <h1>
                Pedido não encontrado
            </h1>

            <div class="mensagem">
                O número do pedido digitado não existe.
            </div>

            <div class="numero-pedido">
                Pedido Nº <?= h($pedidoDigitado) ?>
            </div>

            <button
                type="button"
                class="btn-voltar"
                onclick="voltar()"
            >
                ← Voltar
            </button>

            <div class="atalho">
                Pressione ESC para voltar
            </div>

        </div>


<script>

function voltar() {

    sessionStorage.setItem(
        'limparPedidoVer',
        '1'
    );

    history.back();

}


document.addEventListener(
    'keydown',
    function(event) {

        if (event.key === 'Escape') {

            voltar();

        }

    }
);

</script>

    </body>

    </html>

    <?php

    exit;
}


// ============================================================
// PRODUTOS
// ============================================================

$sqlProdutos = "
    SELECT
        PI.CODPROD,
        PD.CODFAMILIA AS DEPAR,
        PD.DESCRICAO,
        PI.QUANTIDADE

    FROM PEDIDOI PI

    LEFT JOIN PRODUTO PD
        ON PI.CODPROD = PD.CODPROD

    WHERE
        PI.TIPOPEDIDO = '55'
        AND PI.CODPEDIDO = LPAD(:PEDIDO, 8, '0')
        AND PI.CODEMPRESA = '00'

    ORDER BY
        PD.CODFAMILIA,
        PD.DESCRICAO
";

try {

    $stmt = $pdo->prepare($sqlProdutos);

    $stmt->execute([
        ':PEDIDO' => $codPedido
    ]);

    $produtos = $stmt->fetchAll();

} catch (PDOException $e) {

    die(
        '<h3>Erro ao consultar produtos</h3>' .
        '<pre>' .
        h($e->getMessage()) .
        '</pre>'
    );
}


// ============================================================
// STATUS
// ============================================================

$liberado = strtoupper(
    trim(
        (string)$cabecalho['LIBERADO_ENT']
    )
);

$numeroPedido = formatarPedido(
    $cabecalho['CODPEDIDO']
);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
    Pedido <?= h($numeroPedido) ?>
</title>
<script>

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        history.back();

    }

});
</script>



<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f6f9;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color: #1f2937;
}


/* ============================================================
   TOPO
   ============================================================ */

.topo {
    background: #0755b5;
    color: #fff;

    padding:
        16px
        25px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,0.15);
}


.titulo {
    font-size: 21px;
    font-weight: bold;
}


.btn-voltar {

    border: 0;

    background: #fff;
    color: #0755b5;

    padding:
        10px
        18px;

    border-radius: 7px;

    font-size: 14px;
    font-weight: bold;

    cursor: pointer;
}


.btn-voltar:hover {
    background: #edf3ff;
}


/* ============================================================
   CONTEÚDO
   ============================================================ */

.container {

    max-width: 1200px;

    margin:
        25px
        auto;

    padding:
        0
        20px;

}


/* ============================================================
   CABEÇALHO DO PEDIDO
   ============================================================ */

.card {

    background: #fff;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.08);

    overflow: hidden;

}


.card-header {

    position: relative;

    padding:
        22px
        190px
        22px
        25px;

    border-bottom:
        1px solid #e5e7eb;

}


.pedido-numero {

    color: #0755b5;

    font-size: 23px;
    font-weight: bold;

    margin-bottom: 18px;

}


.dados-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap:
        16px
        30px;

}


.campo-titulo {

    margin-bottom: 4px;

    color: #6b7280;

    font-size: 12px;
    font-weight: bold;

    text-transform: uppercase;

}


.campo-valor {

    font-size: 16px;
    font-weight: 600;

}


/* ============================================================
   STATUS LIBERAÇÃO
   ============================================================ */

.status {

    position: absolute;

    top: 20px;
    right: 22px;

    width: 140px;
    height: 80px;

    display: flex;

    align-items: center;
    justify-content: center;

    text-align: center;

    border-radius: 9px;

    color: #fff;

    font-size: 15px;
    font-weight: bold;

    letter-spacing: 0.3px;

}


.status-liberado {

    background: #16a34a;

    border:
        2px solid
        #15803d;

}


.status-nao-liberado {

    background: #dc2626;

    border:
        2px solid
        #b91c1c;

}


.status-indefinido {

    background: #6b7280;

    border:
        2px solid
        #4b5563;

}


/* ============================================================
   PRODUTOS
   ============================================================ */

.produtos-titulo {

    padding:
        18px
        25px;

    font-size: 18px;
    font-weight: bold;

    color: #0755b5;

}


.tabela-container {
    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

}


thead {

    background: #edf3fb;

}


th {

    padding:
        13px
        15px;

    text-align: left;

    color: #374151;

    font-size: 13px;

    border-bottom:
        2px solid
        #d1d5db;

}


td {

    padding:
        13px
        15px;

    border-bottom:
        1px solid
        #e5e7eb;

    font-size: 14px;

}


tbody tr:hover {
    background: #f8fafc;
}


.codproduto {
    width: 120px;
}


.departamento {

    width: 130px;

    text-align: center;

}


.quantidade {

    width: 130px;

    text-align: right;

    font-weight: bold;

}


.sem-produtos {

    padding: 30px;

    text-align: center;

    color: #777;

}


/* ============================================================
   RESPONSIVO
   ============================================================ */

@media (
    max-width: 700px
) {

    .card-header {

        padding:
            20px;

        padding-top:
            115px;

    }


    .status {

        top: 20px;
        left: 20px;
        right: auto;

    }


    .dados-grid {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<!-- ==========================================================
     TOPO
     ========================================================== -->

<div class="topo">

    <div class="titulo">
        Visualização do Pedido
    </div>

    <button
        class="btn-voltar"
        onclick="history.back()"
    >
        ← Voltar
    </button>

</div>


<div class="container">


    <div class="card">


        <!-- ==================================================
             CABEÇALHO
             ================================================== -->

        <div class="card-header">


            <div class="pedido-numero">

                Pedido Nº
                <?= h($numeroPedido) ?>

            </div>


            <!-- STATUS -->

            <?php if ($liberado === 'S'): ?>

                <div
                    class="
                        status
                        status-liberado
                    "
                >
                    ✓ LIBERADO
                </div>

            <?php elseif ($liberado === 'N'): ?>

                <div
                    class="
                        status
                        status-nao-liberado
                    "
                >
                    NÃO LIBERADO
                </div>

            <?php else: ?>

                <div
                    class="
                        status
                        status-indefinido
                    "
                >
                    SEM STATUS
                </div>

            <?php endif; ?>


            <div class="dados-grid">


                <div>

                    <div class="campo-titulo">
                        Cliente
                    </div>

                    <div class="campo-valor">
                        <?= h($cabecalho['NOME']) ?>
                    </div>

                </div>


                <div>

                    <div class="campo-titulo">
                        Cidade / UF
                    </div>

                    <div class="campo-valor">
                        <?= h($cabecalho['CID_UF']) ?>
                    </div>

                </div>


                <div>

                    <div class="campo-titulo">
                        Endereço
                    </div>

                    <div class="campo-valor">

                        <?= h($cabecalho['ENDERECO']) ?>

                        <?php
                        if (
                            trim(
                                (string)$cabecalho['NUMERO']
                            ) !== ''
                        ):
                        ?>

                            ,
                            <?= h($cabecalho['NUMERO']) ?>

                        <?php endif; ?>

                    </div>

                </div>


                <div>

                    <div class="campo-titulo">
                        Bairro
                    </div>

                    <div class="campo-valor">
                        <?= h($cabecalho['BAIRRO']) ?>
                    </div>

                </div>


            </div>


        </div>


        <!-- ==================================================
             PRODUTOS
             ================================================== -->

        <div class="produtos-titulo">

            Produtos do Pedido

        </div>


        <div class="tabela-container">


            <?php if (count($produtos) > 0): ?>


                <table>


                    <thead>

                        <tr>

                            <th class="codproduto">
                                Código
                            </th>

                            <th class="departamento">
                                Departamento
                            </th>

                            <th>
                                Descrição
                            </th>

                            <th class="quantidade">
                                Quantidade
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($produtos as $produto): ?>


                        <tr>


                            <td class="codproduto">

                                <?= h(
                                    $produto['CODPROD']
                                ) ?>

                            </td>


                            <td class="departamento">

                                <?= h(
                                    $produto['DEPAR']
                                ) ?>

                            </td>


                            <td>

                                <?= h(
                                    $produto['DESCRICAO']
                                ) ?>

                            </td>


                            <td class="quantidade">

                                <?= h(
                                    $produto['QUANTIDADE']
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            <?php else: ?>


                <div class="sem-produtos">

                    Nenhum produto encontrado
                    para este pedido.

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


</body>

</html>