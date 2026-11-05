<?php

require __DIR__ . "/funcoes.php";
cabecalhos();

require __DIR__ . "/conexao.php";
require __DIR__ . "/dao/ClienteDAO.php";
require __DIR__ . "/dao/UsuarioDAO.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    erro(405, "Método não permitido. Use POST para fazer login.");
}

$d    = lerBody();
$tipo = texto($d["tipo"] ?? "cliente");

try {
    $tokens = new TokenDAO($pdo);

    if ($tipo == "cliente") {
        $cpf      = preg_replace("/[^0-9]/", "", texto($d["cpf"] ?? ""));
        $telefone = preg_replace("/[^0-9]/", "", texto($d["telefone"] ?? ""));

        if (strlen($cpf) != 11 || strlen($telefone) < 10 || strlen($telefone) > 11) {
            erro(400, "Informe o CPF com 11 números e o telefone com DDD.");
        }

        $clientes = new ClienteDAO($pdo);
        $cliente  = $clientes->buscarPorCpfTelefone($cpf, $telefone);
        if (!$cliente) {
            erro(401, "CPF e telefone não conferem com o cadastro.");
        }

        $token = $tokens->criar("cliente", $cliente["idpessoa"], null, 2); // vale 2 horas
        responder(200, ["message" => "Login realizado.", "token" => $token, "tipo" => "cliente"]);

    } elseif ($tipo == "admin") {
        $usuario = texto($d["usuario"] ?? "");
        $senha   = (string) ($d["senha"] ?? "");

        if ($usuario == "" || $senha == "") {
            erro(400, "Informe o usuário e a senha.");
        }

        $usuarios = new UsuarioDAO($pdo);
        $admin    = $usuarios->buscarPorUsuario($usuario);

        //password_verify compara oq bota de senha com o hash guardado no banco
        if (!$admin || !password_verify($senha, $admin["senha_hash"])) {
            erro(401, "Usuário ou senha inválidos.");
        }

        $token = $tokens->criar("admin", null, $admin["idusuario"], 4); //4 horas
        responder(200, ["message" => "Login realizado.", "token" => $token, "tipo" => "admin"]);

    } else {
        erro(400, "Tipo inválido. Use \"cliente\" ou \"admin\".");
    }
} catch (Throwable $e) {
    erroDoServidor($e);
}
