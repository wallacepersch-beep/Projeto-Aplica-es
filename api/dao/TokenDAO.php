<?php
class TokenDAO
{
    public function __construct(private PDO $pdo) {}


    //token é salvo por hash dentro da tabela,  sendo vinculado ao id e com um número fixo de horas
    public function criar(string $tipo, ?int $idpessoa, ?int $idusuario, int $horas): string
    {
        $token = bin2hex(random_bytes(32));
        $hash  = hash("sha256", $token);

        $st = $this->pdo->prepare(
            "INSERT INTO tokens_api (token_hash, tipo, idpessoa, idusuario, expira_em)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))"
        );
        $st->execute([$hash, $tipo, $idpessoa, $idusuario, $horas]);
        return $token;
    }

    //devolve informações se o token for valido
    public function buscarPorToken(string $token): ?array
    {
        $hash = hash("sha256", $token);
        $st = $this->pdo->prepare(
            "SELECT tipo, idpessoa, idusuario FROM tokens_api WHERE token_hash = ? AND expira_em > NOW()"
        );
        $st->execute([$hash]);
        $linha = $st->fetch();
        return $linha ?: null;
    }
}
