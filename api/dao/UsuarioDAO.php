<?php
// DAO da tabela `usuarios_admin` (administradores do restaurante)
class UsuarioDAO
{
    public function __construct(private PDO $pdo) {}

    /** Devolve a linha (com o hash da senha) ou null se o usuário não existir. */
    public function buscarPorUsuario(string $usuario): ?array
    {
        $st = $this->pdo->prepare("SELECT idusuario, usuario, senha_hash FROM usuarios_admin WHERE usuario = ?");
        $st->execute([$usuario]);
        $linha = $st->fetch();
        return $linha ?: null;
    }
}
