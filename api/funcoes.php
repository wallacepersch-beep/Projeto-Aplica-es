<?php
//as funções que usam os arquivos da api
require_once __DIR__ . "/dao/TokenDAO.php";

//cabeçalho de todas respostas
function cabecalhos()
{
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
        http_response_code(204);
        exit;
    }
}

//define status, escreve o JSON e encerra o script
function responder($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

//atalho pra resposta de ereor
function erro($status, $mensagem)
{
    responder($status, ["message" => $mensagem]);
}

//resposta 500 quando o PHP ou o banco falham.
function erroDoServidor($e)
{
    error_log($e->getMessage());
    $mensagem = "Erro no servidor.";
    if (DEBUG) {
        $mensagem = $mensagem . " Detalhe: " . $e->getMessage();
    }
    erro(500, $mensagem);
}

//le o body do json enviado e devolve um array, e devolve vazio se não tiver json
function lerBody()
{
    $d = json_decode(file_get_contents("php://input"), true);
    if (!is_array($d)) {
        return [];
    }
    return $d;
}

//devolve (texto e numero) sem espacos nas pontas
function texto($valor)
{
    if (is_string($valor) || is_int($valor)) {
        return trim((string) $valor);
    }
    return "";
}


//confere o header "Authorization: Bearer <token>" e, sem token válido, só responde 401 e para. Com token válido,  devolve os dados dele

function exigirToken($pdo)
{
    $auth = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

    if ($auth == "" && function_exists("getallheaders")) {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $auth = $headers["authorization"] ?? "";
    }

    if (substr($auth, 0, 7) != "Bearer ") {
        erro(401, "Token ausente. Envie o header Authorization: Bearer <token>.");
    }

    $token = substr($auth, 7);
    $tokens = new TokenDAO($pdo);
    $dados = $tokens->buscarPorToken($token);

    if (!$dados) {
        erro(401, "Token inválido ou expirado. Faça login novamente.");
    }

    return $dados;
}
