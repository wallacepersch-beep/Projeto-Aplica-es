<?php
// Conexão com o MySQL (padrão do XAMPP/WAMP: usuário root, sem senha)
date_default_timezone_set("America/Sao_Paulo");

// Mostra o erro detalhado do banco na tela. Coloque false quando tudo estiver funcionando.
define("DEBUG", true);

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
    http_response_code(500);
    exit("Não foi possível conectar ao banco de dados." . (DEBUG ? " Detalhe: " . $e->getMessage() : ""));
}
