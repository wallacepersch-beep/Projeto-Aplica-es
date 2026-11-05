<?php
date_default_timezone_set("America/Sao_Paulo");


define("DEBUG", true); //mudar pra false depois que estiver pronto, é só pra se der o erro 500 esse

$host  = "127.0.0.1";
$porta = 3306;
$banco = "restaurante";
$user  = "root";
$senha = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $user,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    //sem banco responde 500 em JSON e para
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    $mensagem = "Não foi possível conectar ao banco de dados.";
    if (DEBUG) {
        $mensagem = $mensagem . " Detalhe: " . $e->getMessage();
    }
    echo json_encode(["message" => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}
