<?php

require __DIR__ . "/funcoes.php";
cabecalhos();

require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/MesaDAO.php";

if ($_SERVER["REQUEST_METHOD"] != "GET") {
    erro(405, "Método não permitido. As mesas aceitam apenas GET.");
}

try {
    $mesas = new MesaDAO($pdo);
    responder(200, ["mesas" => $mesas->listar()]);
} catch (Throwable $e) {
    erroDoServidor($e);
}
