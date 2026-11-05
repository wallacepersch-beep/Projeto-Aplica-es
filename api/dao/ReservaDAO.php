<?php
class ReservaDAO
{
    public function __construct(private PDO $pdo) {}

    private const SELECT = "SELECT r.idreserva, r.idpessoa, c.nome, c.telefone, c.cpf,
                                   r.idmesa, m.espaco AS lugares_mesa, r.data, r.criada_em
                              FROM reservas r
                              JOIN clientes c ON c.idpessoa = r.idpessoa
                              LEFT JOIN mesas m ON m.idmesa = r.idmesa";

    //insere e devolve id
    public function inserir(int $idpessoa, string $data, ?int $idmesa = null): int
    {
        $st = $this->pdo->prepare("INSERT INTO reservas (idpessoa, idmesa, data) VALUES (?, ?, ?)");
        $st->execute([$idpessoa, $idmesa, $data]);
        return (int)$this->pdo->lastInsertId();
    }

    //lista todas as reservas(adm)
    public function listar(): array
    {
        $st = $this->pdo->prepare(self::SELECT . " ORDER BY r.data ASC, r.idreserva ASC");
        $st->execute();
        return $st->fetchAll();
    }


    public function listarPorCliente(int $idpessoa): array
    {
        $st = $this->pdo->prepare(self::SELECT . " WHERE r.idpessoa = ? ORDER BY r.data ASC, r.idreserva ASC");
        $st->execute([$idpessoa]);
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
