<?php

require __DIR__ . "/funcoes.php";
cabecalhos();

require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/ClienteDAO.php";
require __DIR__ . "/dao/MesaDAO.php";
require __DIR__ . "/dao/ReservaDAO.php";

//funcoes para esta api

//data em formato AAAA-MM-DD e valido
function dataValida($data)
{
    if (strlen($data) != 10 || $data[4] != "-" || $data[7] != "-") {
        return false;
    }
    return checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4));
}

//confere os dados do body e algo estiver errado, responde 400 e o script para e se estiver tudo certo, devolve os dados limpos num array
function lerDadosDaReserva($d, $mesas, $exigirCpf)
{
    $nome     = texto($d["nome"] ?? "");
    $telefone = preg_replace("/[^0-9]/", "", texto($d["telefone"] ?? "")); //fica só com os números
    $cpf      = preg_replace("/[^0-9]/", "", texto($d["cpf"] ?? ""));
    $data     = texto($d["data"] ?? "");

    if ($nome == "" || mb_strlen($nome) > 70) {
        erro(400, "Informe o nome do cliente (até 70 caracteres).");
    }
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        erro(400, "Informe o telefone com DDD (10 ou 11 números).");
    }
    if ($exigirCpf && strlen($cpf) != 11) {
        erro(400, "Informe o CPF com 11 números.");
    }
    if (!dataValida($data)) {
        erro(400, "Informe uma data válida no formato AAAA-MM-DD.");
    }

    //mesa opcional nesse caso
    $idmesa = null;
    if (isset($d["idmesa"]) && $d["idmesa"] !== "") {
        if (!is_numeric($d["idmesa"]) || (int) $d["idmesa"] < 1 || !$mesas->buscarPorId((int) $d["idmesa"])) {
            erro(400, "A mesa escolhida não existe.");
        }
        $idmesa = (int) $d["idmesa"];
    }

    return ["nome" => $nome, "telefone" => $telefone, "cpf" => $cpf, "data" => $data, "idmesa" => $idmesa];
}


function podeAcessar($usuario, $reserva)
{
    if ($usuario["tipo"] == "admin") {
        return true;
    }
    return $reserva["idpessoa"] == $usuario["idpessoa"];
}

//le a requisição no topo

$metodo = $_SERVER["REQUEST_METHOD"];
$d      = lerBody();

$id = null;
if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];
    if ($id < 1) {
        erro(400, "ID inválido.");
    }
}

try {
    $clientes = new ClienteDAO($pdo);
    $mesas    = new MesaDAO($pdo);
    $reservas = new ReservaDAO($pdo);

    $usuario = null;
    if ($metodo != "POST") {
        $usuario = exigirToken($pdo);
    }


    switch ($metodo) {

        case "GET":
            if ($id === null) {
                if ($usuario["tipo"] == "admin") {
                    $lista = $reservas->listar();
                } else {
                    $lista = $reservas->listarPorCliente($usuario["idpessoa"]);
                }
                responder(200, ["reservas" => $lista]);
            }

            $reserva = $reservas->buscarPorId($id);
            if (!$reserva) {
                erro(404, "Reserva não encontrada.");
            }
            if (!podeAcessar($usuario, $reserva)) {
                erro(403, "Você não tem permissão para ver esta reserva.");
            }
            responder(200, ["reserva" => $reserva]);
            break;

        case "POST":
            $r = lerDadosDaReserva($d, $mesas, true);

            if ($r["data"] < date("Y-m-d")) {
                erro(400, "A data da reserva não pode estar no passado.");
            }

            $cliente = $clientes->buscarPorCpf($r["cpf"]);
            if ($cliente) {
                if ($cliente["telefone"] != $r["telefone"]) {
                    erro(400, "Já existe um cadastro com esse CPF, mas o telefone é diferente.");
                }
                $idpessoa = $cliente["idpessoa"];
            } else {
                $idpessoa = $clientes->inserir($r["nome"], $r["telefone"], $r["cpf"]);
            }

            $idreserva = $reservas->inserir($idpessoa, $r["data"], $r["idmesa"]);
            responder(201, ["message" => "Reserva criada com sucesso.", "idreserva" => $idreserva]);
            break;

        case "PUT":
            if ($id === null) {
                erro(400, "Informe o id na URL (reservas.php?id=1).");
            }
            $reserva = $reservas->buscarPorId($id);
            if (!$reserva) {
                erro(404, "Reserva não encontrada.");
            }
            if (!podeAcessar($usuario, $reserva)) {
                erro(403, "Você não tem permissão para alterar esta reserva.");
            }

            $r = lerDadosDaReserva($d, $mesas, false);

            //só barra as datas no passado se foi trocado
            if ($r["data"] != $reserva["data"] && $r["data"] < date("Y-m-d")) {
                erro(400, "A data da reserva não pode estar no passado.");
            }

            $clientes->atualizar($reserva["idpessoa"], $r["nome"], $r["telefone"]);
            $reservas->atualizar($id, $reserva["idpessoa"], $r["data"], $r["idmesa"]);
            responder(200, ["message" => "Reserva atualizada com sucesso.", "idreserva" => $id]);
            break;

        case "DELETE":
            if ($id === null) {
                erro(400, "Informe o id na URL (reservas.php?id=1).");
            }
            $reserva = $reservas->buscarPorId($id);
            if (!$reserva) {
                erro(404, "Reserva não encontrada.");
            }
            if (!podeAcessar($usuario, $reserva)) {
                erro(403, "Você não tem permissão para cancelar esta reserva.");
            }

            $reservas->excluir($id);
            responder(200, ["message" => "Reserva cancelada com sucesso."]);
            break;

        default:
            erro(405, "Método não permitido.");
    }
} catch (Throwable $e) {
    erroDoServidor($e);
}
