<?php

class ClienteDAO
{
    public function __construct(private PDO $pdo) {}

    //insere e devolve o id criado
    public function inserir(string $nome, string $telefone, string $cpf): int
    {
        $st = $this->pdo->prepare("INSERT INTO clientes (nome, telefone, cpf) VALUES (?, ?, ?)");
        $st->execute([$nome, $telefone, $cpf]);
        return (int)$this->pdo->lastInsertId();
    }

    public function listar(): array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone, cpf FROM clientes ORDER BY nome");
        $st->execute();
        return $st->fetchAll();
    }

    //devolve a linha ou null se não existir
    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone, cpf FROM clientes WHERE idpessoa = ?");
        $st->execute([$id]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    //cadastro de reserva para não duplicar clientes
    public function buscarPorCpf(string $cpf): ?array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone, cpf FROM clientes WHERE cpf = ?");
        $st->execute([$cpf]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    //usado no login do cliente
    public function buscarPorCpfTelefone(string $cpf, string $telefone): ?array
    {
        $st = $this->pdo->prepare("SELECT idpessoa, nome, telefone, cpf FROM clientes WHERE cpf = ? AND telefone = ?");
        $st->execute([$cpf, $telefone]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    //apenas telefone e nome, cpf fica fixo
    public function atualizar(int $id, string $nome, string $telefone): bool
    {
        $st = $this->pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE idpessoa = ?");
        return $st->execute([$nome, $telefone, $id]);
    }

    //exclusão
    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare("DELETE FROM clientes WHERE idpessoa = ?");
        return $st->execute([$id]);
    }
}
