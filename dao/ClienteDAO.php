<?php
// DAO da tabela `clientes` (tabela "pai")
class ClienteDAO
{
    public function __construct(private PDO $pdo) {}

    /** Insere e devolve o id criado. */
    public function inserir(string $nome, string $telefone): int
    {
        $st = $this->pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
        $st->execute([$nome, $telefone]);
        return (int)$this->pdo->lastInsertId();
    }

    public function listar(): array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone FROM clientes ORDER BY nome");
        $st->execute();
        return $st->fetchAll();
    }

    /** Devolve a linha ou null se não existir. */
    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone FROM clientes WHERE idpessoa = ?");
        $st->execute([$id]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    /** Usado pelo formulário de reserva para não duplicar clientes. */
    public function buscarPorNomeTelefone(string $nome, string $telefone): ?array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone FROM clientes WHERE nome = ? AND telefone = ?");
        $st->execute([$nome, $telefone]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    public function atualizar(int $id, string $nome, string $telefone): bool
    {
        $st = $this->pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE idpessoa = ?");
        return $st->execute([$nome, $telefone, $id]);
    }

    /** Falha (PDOException) se o cliente ainda tiver reservas. */
    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare("DELETE FROM clientes WHERE idpessoa = ?");
        return $st->execute([$id]);
    }
}
