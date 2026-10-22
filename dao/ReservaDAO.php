<?php
// DAO da tabela `reservas` (tabela "filha": depende de clientes e mesas)
class ReservaDAO
{
    public function __construct(private PDO $pdo) {}

    private const SELECT = "SELECT r.idreserva, r.idpessoa, c.nome, c.telefone,
                                   r.idmesa, r.data, r.criada_em
                              FROM reservas r
                              JOIN clientes c ON c.idpessoa = r.idpessoa";

    /** Insere e devolve o id criado. $idmesa é opcional. */
    public function inserir(int $idpessoa, string $data, ?int $idmesa = null): int
    {
        $st = $this->pdo->prepare("INSERT INTO reservas (idpessoa, idmesa, data) VALUES (?, ?, ?)");
        $st->execute([$idpessoa, $idmesa, $data]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Lista todas as reservas já com nome e telefone do cliente. */
    public function listar(): array
    {
        $st = $this->pdo->prepare(self::SELECT . " ORDER BY r.data ASC, r.idreserva ASC");
        $st->execute();
        return $st->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare(self::SELECT . " WHERE r.idreserva = ?");
        $st->execute([$id]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    public function atualizar(int $id, int $idpessoa, string $data, ?int $idmesa = null): bool
    {
        $st = $this->pdo->prepare("UPDATE reservas SET idpessoa = ?, idmesa = ?, data = ? WHERE idreserva = ?");
        return $st->execute([$idpessoa, $idmesa, $data, $id]);
    }

    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare("DELETE FROM reservas WHERE idreserva = ?");
        return $st->execute([$id]);
    }
}
