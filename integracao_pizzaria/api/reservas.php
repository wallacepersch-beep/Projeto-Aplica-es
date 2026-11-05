<?php
// API de RESERVAS  →  reservas.php
//
//   GET    reservas.php        lista as reservas (admin: todas; cliente: só as suas)  [token]
//   GET    reservas.php?id=1   mostra uma reserva                                     [token]
//   POST   reservas.php        cria uma reserva                                       [público]
//   PUT    reservas.php?id=1   atualiza uma reserva                                   [token]
//   DELETE reservas.php?id=1   cancela uma reserva                                    [token]
//
// Como funciona o token (resumo):
//   1. O front-end envia o header  Authorization: Bearer <token>.
//   2. exigirToken() (em funcoes.php) confere esse token no banco.
//      - Inválido/ausente  → responde 401 e o script PARA.
//      - Válido            → devolve os dados do usuário (tipo, idpessoa, idusuario).
//   3. Aqui usamos esses dados para decidir o que cada um pode ver/alterar (403 se não puder).

require __DIR__ . "/funcoes.php";
cabecalhos();

// Garante que "hoje" seja o dia do Brasil (senão a validação de data no passado pode errar à noite).
date_default_timezone_set("America/Sao_Paulo");

require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/ClienteDAO.php";
require __DIR__ . "/dao/MesaDAO.php";
require __DIR__ . "/dao/ReservaDAO.php";

// ---------- funções só desta API ----------

// A data está no formato AAAA-MM-DD e existe no calendário?
function dataValida($data)
{
    // A regex garante que só há números nos lugares certos (evita "2026-1a-10").
    if (!preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $data, $partes)) {
        return false;
    }
    return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
}

// Confere os dados do body. Se algo estiver errado, responde 400 e o script para.
// Se estiver tudo certo, devolve os dados limpos num array.
// $exigirCpf: no POST o CPF é obrigatório; no PUT ele não muda.
function lerDadosDaReserva($d, $mesas, $exigirCpf)
{
    $nome     = texto($d["nome"] ?? "");
    $telefone = preg_replace("/[^0-9]/", "", texto($d["telefone"] ?? "")); // fica só com os números
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

    // A mesa é opcional. Se vier, precisa ser um número inteiro e existir.
    $idmesa = null;
    if (isset($d["idmesa"]) && $d["idmesa"] !== "" && $d["idmesa"] !== null) {
        $valor = texto($d["idmesa"]);
        if (!ctype_digit($valor) || (int) $valor < 1 || !$mesas->buscarPorId((int) $valor)) {
            erro(400, "A mesa escolhida não existe.");
        }
        $idmesa = (int) $valor;
    }

    return ["nome" => $nome, "telefone" => $telefone, "cpf" => $cpf, "data" => $data, "idmesa" => $idmesa];
}

// O admin vê qualquer reserva; o cliente só as dele.
function podeAcessar($usuario, $reserva)
{
    if ($usuario["tipo"] == "admin") {
        return true;
    }
    return $reserva["idpessoa"] == $usuario["idpessoa"];
}

// ---------- topo: ler a requisição ----------

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

    // Só o POST (fazer uma reserva) é público. Todo o resto exige token (401).
    // Se o token for inválido, exigirToken() já responde 401 e encerra o script.
    $usuario = null;
    if ($metodo != "POST") {
        $usuario = exigirToken($pdo);
    }

    // ---------- o switch: um case por método ----------

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

            // Cliente novo ou que já existe (o CPF identifica a pessoa).
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

            // Só barra data no passado se a data foi trocada (assim dá para corrigir o nome de uma reserva antiga).
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