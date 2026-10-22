<?php
// DAO da tabela `mesas`
class MesaDAO
{
    public function __construct(private PDO $pdo) {}

    public function inserir(int $espaco): int
    {
        $st = $this->pdo->prepare("INSERT INTO mesas (espaco) VALUES (?)");
        $st->execute([$espaco]);
        return (int)$this->pdo->lastInsertId();
    }

    public function listar(): array
    {
        $st = $this->pdo->prepare("SELECT idmesa, espaco FROM mesas ORDER BY idmesa");
        $st->execute();
        return $st->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT idmesa, espaco FROM mesas WHERE idmesa = ?");
        $st->execute([$id]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    public function atualizar(int $id, int $espaco): bool
    {
        $st = $this->pdo->prepare("UPDATE mesas SET espaco = ? WHERE idmesa = ?");
        return $st->execute([$espaco, $id]);
    }

    /** Falha (PDOException) se a mesa ainda estiver em alguma reserva. */
    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare("DELETE FROM mesas WHERE idmesa = ?");
        return $st->execute([$id]);
    }
}
