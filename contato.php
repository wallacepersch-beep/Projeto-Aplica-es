<?php
require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/ClienteDAO.php";
require __DIR__ . "/dao/ReservaDAO.php";

$clienteDAO = new ClienteDAO($pdo);
$reservaDAO = new ReservaDAO($pdo);

$erros = [];
$nome = $data = $telefone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome     = trim($_POST["nome"] ?? "");
    $data     = trim($_POST["data"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $digitos  = preg_replace('/\D/', '', $telefone);

    if ($nome === "" || mb_strlen($nome) > 70) {
        $erros[] = "Informe o nome da reserva (até 70 caracteres).";
    }

    $d = DateTime::createFromFormat("Y-m-d", $data);
    if (!$d || $d->format("Y-m-d") !== $data) {
        $erros[] = "Informe uma data válida.";
    } elseif ($data < date("Y-m-d")) {
        $erros[] = "A data da reserva não pode ser no passado.";
    }

    if (strlen($digitos) < 10 || strlen($digitos) > 11) {
        $erros[] = "Informe um telefone com DDD (10 ou 11 dígitos).";
    }

    if (!$erros) {
        try {
            $pdo->beginTransaction();

            // Reaproveita o cliente se já existir (mesmo nome e telefone)
            $cliente  = $clienteDAO->buscarPorNomeTelefone($nome, $telefone);
            $idpessoa = $cliente ? (int)$cliente["idpessoa"] : $clienteDAO->inserir($nome, $telefone);

            $reservaDAO->inserir($idpessoa, $data);

            $pdo->commit();
            header("Location: contato.php?ok=1");
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erros[] = "Erro ao salvar a reserva. Tente novamente." . (DEBUG ? " Detalhe: " . $e->getMessage() : "");
        }
    }
}

$sucesso = isset($_GET["ok"]);
function h($v) { return htmlspecialchars($v, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Reservas</title>
    <link rel="stylesheet" href="style.css">
    <style>
        nav a {
            margin-right: 15px;
        }
    </style>
</head>

<body>
    <header>
        <h1>Restaurante Salto Ventoso </h1>
    </header>

    <main>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="cardapio.php">Cardapio</a>
            <a href="descritivo.php">Sobre nós</a>
            <a href="contato.php">Reserva</a>
            <a href="lista_reservas.php">Lista de reservas</a>
        </nav>

        <?php if ($sucesso): ?>
            <p style="color: green;">Reserva cadastrada com sucesso!</p>
        <?php endif; ?>

        <?php foreach ($erros as $e): ?>
            <p style="color: red;"><?= h($e) ?></p>
        <?php endforeach; ?>

        <form method="post" action="contato.php">
            <label for="nome">Nome da reserva</label>
            <input type="text" id="nome" name="nome" required maxlength="70" placeholder="Nome do cliente" value="<?= h($nome) ?>">

            <label for="data">Data de reserva</label>
            <input type="date" id="data" name="data" required min="<?= date('Y-m-d') ?>" value="<?= h($data) ?>">

            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" required placeholder="(XX) XXXXX-XXXX" value="<?= h($telefone) ?>">

            <button type="submit" id="botao">Cadastrar</button>
        </form>


    </main>

    <script src="cadastro.js"></script>

    <footer>Av. bem te ver, 1969 - Cs. postal 1253 - Bairro tal do tal - Não-Me-Toque - RS - CEP 21553
    </footer>
</body>

</html>