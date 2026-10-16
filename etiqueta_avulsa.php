<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiqueta Avulsa</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    background: #f4f6f8;
    font-family: Arial, Helvetica, sans-serif;
    color: #222;
}

.container {
    max-width: 760px;
    margin: 30px auto;
    padding: 0 20px;
}

.card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.10);
    overflow: hidden;
}

.header {
    padding: 22px 25px;
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

.conteudo { padding: 25px; }

.linha {
    display: grid;
    grid-template-columns: 1fr 160px;
    gap: 15px;
}

.campo { margin-bottom: 18px; }

.campo label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: bold;
}

.campo input {
    width: 100%;
    height: 43px;
    padding: 8px 11px;
    border: 1px solid #bbb;
    border-radius: 6px;
    font-size: 16px;
}

.campo input[type="text"] {
    text-transform: uppercase;
}

.campo input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,.12);
}

.quantidade {
    width: 120px !important;
    text-align: center;
}

.botoes {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 10px;
}

.btn {
    border: 0;
    border-radius: 7px;
    padding: 11px 20px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
}

.btn-voltar {
    background: #e5e7eb;
    color: #222;
}

.btn-voltar:hover { background: #d1d5db; }

.btn-imprimir {
    background: #198754;
    color: #fff;
}

.btn-imprimir:hover { background: #157347; }

@media(max-width:600px) {
    .linha { grid-template-columns: 1fr; }
}
</style>
</head>

<body>
<div class="container">
<div class="card">

    <div class="header">
        <h1>Etiqueta Avulsa</h1>
        <p>Digite os dados que serão impressos na etiqueta.</p>
    </div>

    <form id="formEtiqueta" method="post" action="imprimir_avulsa.php" target="_blank">
        <div class="conteudo">

            <div class="campo">
                <label>Nome / Cliente</label>
                <input
                    type="text"
                    name="nome"
                    id="nome"
                    autocomplete="off"
                    required
                    autofocus
                    placeholder="DIGITE O NOME"
                    oninput="this.value=this.value.toLocaleUpperCase('pt-BR')"
                >
            </div>

            <div class="linha">
                <div class="campo">
                    <label>Endereço</label>
                    <input
                        type="text"
                        name="endereco"
                        id="endereco"
                        autocomplete="off"
                        placeholder="RUA / AVENIDA"
                        oninput="this.value=this.value.toLocaleUpperCase('pt-BR')"
                    >
                </div>

                <div class="campo">
                    <label>Número</label>
                    <input
                        type="text"
                        name="numero"
                        id="numero"
                        autocomplete="off"
                        oninput="this.value=this.value.toLocaleUpperCase('pt-BR')"
                    >
                </div>
            </div>

            <div class="campo">
                <label>Bairro</label>
                <input
                    type="text"
                    name="bairro"
                    id="bairro"
                    autocomplete="off"
                    placeholder="BAIRRO"
                    oninput="this.value=this.value.toLocaleUpperCase('pt-BR')"
                >
            </div>

            <div class="campo">
                <label>Telefone</label>
                <input
                    type="text"
                    name="telefone"
                    id="telefone"
                    autocomplete="off"
                    placeholder="TELEFONE"
                    oninput="this.value=this.value.toLocaleUpperCase('pt-BR')"
                >
            </div>

            <div class="linha">
                <div class="campo">
                    <label>Observação</label>
                    <input
                        type="text"
                        name="observacao"
                        id="observacao"
                        autocomplete="off"
                        placeholder="OPCIONAL"
                        oninput="this.value=this.value.toLocaleUpperCase('pt-BR')"
                    >
                </div>

                <div class="campo">
                    <label>Quantidade</label>
                    <input
                        type="number"
                        name="qtd"
                        id="qtd"
                        class="quantidade"
                        value="1"
                        min="1"
                        max="100"
                    >
                </div>
            </div>

            <div class="botoes">
                <button type="button" class="btn btn-voltar" onclick="history.back()">
                    ← Voltar
                </button>

                <button type="submit" class="btn btn-imprimir">
                    Imprimir
                </button>
            </div>

        </div>
    </form>

</div>
</div>

<script>
// Garante que qualquer texto digitado, colado ou preenchido pelo navegador fique em maiúsculo.
document.addEventListener('input', function(event) {
    if (event.target.matches('input[type="text"]')) {
        const inicio = event.target.selectionStart;
        const fim = event.target.selectionEnd;
        event.target.value = event.target.value.toLocaleUpperCase('pt-BR');
        try { event.target.setSelectionRange(inicio, fim); } catch (e) {}
    }
});

document.getElementById('formEtiqueta').addEventListener('submit', function() {
    document.querySelectorAll('input[type="text"]').forEach(function(input) {
        input.value = input.value.toLocaleUpperCase('pt-BR');
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        history.back();
    }
});
</script>

</body>
</html>
