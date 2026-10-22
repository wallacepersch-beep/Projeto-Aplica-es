<?php
require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/ReservaDAO.php";

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$erro = "";
$reservas = [];

try {
    $reservas = (new ReservaDAO($pdo))->listar();
} catch (PDOException $e) {
    $erro = "Não foi possível carregar as reservas." . (DEBUG ? " Detalhe: " . $e->getMessage() : "");
}

$hoje = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Lista de reservas</title>
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

        <h3>Reservas cadastradas</h3>

        <?php if ($erro): ?>
            <p style="color: red;"><?= h($erro) ?></p>
        <?php elseif (!$reservas): ?>
            <p>Nenhuma reserva cadastrada ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Data da reserva</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                        <tr<?= $r["data"] < $hoje ? ' style="color: #999;"' : '' ?>>
                            <td><?= h($r["idreserva"]) ?></td>
                            <td><?= h($r["nome"]) ?></td>
                            <td><?= h($r["telefone"]) ?></td>
                            <td><?= h(date("d/m/Y", strtotime($r["data"]))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Total: <?= count($reservas) ?> reserva(s). Datas já passadas aparecem em cinza.</p>
        <?php endif; ?>
    </main>

    <footer>Av. bem te ver, 1969 - Cs. postal 1253 - Bairro tal do tal - Não-Me-Toque - RS - CEP 21553
    </footer>
</body>

</html>
