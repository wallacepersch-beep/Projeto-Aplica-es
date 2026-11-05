<?php
// Funções usadas pelos três arquivos da API (reservas.php, mesas.php e auth.php).
require_once __DIR__ . "/dao/TokenDAO.php";

// Cabeçalhos que toda resposta leva: formato JSON e CORS (libera chamadas de outro site).
// O navegador manda um OPTIONS antes de enviar o token; respondemos 204 e paramos.
function cabecalhos()
{
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *"); // em produção, troque * pelo domínio do seu front-end
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
        http_response_code(204);
        exit;
    }
}

// Define o status, escreve o JSON e encerra o script.
function responder($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Atalho para resposta de erro: {"message": "..."}
function erro($status, $mensagem)
{
    responder($status, ["message" => $mensagem]);
}

// Resposta 500 quando o PHP ou o banco falham.
// Só mostra o detalhe do erro se a constante DEBUG existir e for true.
function erroDoServidor($e)
{
    error_log($e->getMessage());
    $mensagem = "Erro no servidor.";
    if (defined("DEBUG") && DEBUG) {
        $mensagem = $mensagem . " Detalhe: " . $e->getMessage();
    }
    erro(500, $mensagem);
}

// Lê o body JSON enviado e devolve um array (vazio se não tiver JSON válido).
function lerBody()
{
    $d = json_decode(file_get_contents("php://input"), true);
    if (!is_array($d)) {
        return [];
    }
    return $d;
}

// Devolve texto ou número como string, sem espaços nas pontas.
function texto($valor)
{
    if (is_string($valor) || is_int($valor)) {
        return trim((string) $valor);
    }
    return "";
}

// Confere o header "Authorization: Bearer <token>".
// Sem token válido: responde 401 e para. Com token válido: devolve os dados dele
// (tipo = 'cliente' ou 'admin', idpessoa, idusuario).
function exigirToken($pdo)
{
    // 1. Pega o header. Em alguns servidores ele não aparece em $_SERVER,
    //    então a segunda tentativa usa getallheaders().
    $auth = $_SERVER["HTTP_AUTHORIZATION"] ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? "";

    if ($auth == "" && function_exists("getallheaders")) {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $auth = $headers["authorization"] ?? "";
    }

    // 2. Confere o formato "Bearer <token>" (sem diferenciar maiúscula/minúscula).
    if (stripos($auth, "Bearer ") !== 0) {
        erro(401, "Token ausente. Envie o header Authorization: Bearer <token>.");
    }

    $token = trim(substr($auth, 7));
    if ($token == "") {
        erro(401, "Token ausente. Envie o header Authorization: Bearer <token>.");
    }

    // 3. Procura o token no banco (o TokenDAO confere se existe e se não expirou).
    $tokens = new TokenDAO($pdo);
    $dados = $tokens->buscarPorToken($token);

    // 4. Não achou: 401. Achou: devolve os dados do usuário.
    if (!$dados) {
        erro(401, "Token inválido ou expirado. Faça login novamente.");
    }

    return $dados;
}