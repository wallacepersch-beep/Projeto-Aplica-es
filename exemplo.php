<?php
// Script de teste dos DAOs. Rode no terminal:  php exemplo.php
// ou abra no navegador:  http://localhost/restaurante/exemplo.php
// Cria dados de teste e remove tudo no final (o banco volta como estava).
if (PHP_SAPI !== "cli") {
    header("Content-Type: text/plain; charset=utf-8");
}

require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/ClienteDAO.php";
require __DIR__ . "/dao/MesaDAO.php";
require __DIR__ . "/dao/ReservaDAO.php";

function linha(string $txt): void { echo $txt . PHP_EOL; }

$clientes = new ClienteDAO($pdo);
$mesas    = new MesaDAO($pdo);
$reservas = new ReservaDAO($pdo);

linha("=== CLIENTES: listar (exemplos do banco.sql) ===");
foreach ($clientes->listar() as $c) {
    linha("{$c['idpessoa']} - {$c['nome']} - {$c['telefone']}");
}

linha("\n=== CLIENTES: inserir / buscarPorId / atualizar ===");
$idCliente = $clientes->inserir("Teste DAO", "(54) 90000-0000");
linha("Inserido com id $idCliente");
$clientes->atualizar($idCliente, "Teste DAO Atualizado", "(54) 91111-1111");
linha("Depois de atualizar: " . json_encode($clientes->buscarPorId($idCliente), JSON_UNESCAPED_UNICODE));

linha("\n=== MESAS: inserir / buscarPorId / atualizar ===");
$idMesa = $mesas->inserir(10);
linha("Mesa inserida com id $idMesa");
$mesas->atualizar($idMesa, 12);
linha("Depois de atualizar: " . json_encode($mesas->buscarPorId($idMesa)));

linha("\n=== RESERVAS: inserir / listar / buscarPorId / atualizar ===");
$idReserva = $reservas->inserir($idCliente, "2026-12-31", $idMesa);
linha("Reserva inserida com id $idReserva");
$reservas->atualizar($idReserva, $idCliente, "2027-01-01", $idMesa);
linha("Depois de atualizar: " . json_encode($reservas->buscarPorId($idReserva), JSON_UNESCAPED_UNICODE));
linha("Total de reservas na listagem: " . count($reservas->listar()));

linha("\n=== buscarPorId com id inexistente ===");
var_dump($clientes->buscarPorId(999999));

linha("\n=== excluir (limpando os dados de teste) ===");
$reservas->excluir($idReserva);
$mesas->excluir($idMesa);
$clientes->excluir($idCliente);
linha("Reserva buscada após excluir: " . var_export($reservas->buscarPorId($idReserva), true));

linha("\nOK: todos os métodos executaram sem erro.");
