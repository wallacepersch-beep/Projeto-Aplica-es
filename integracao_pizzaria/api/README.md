# API PHP de Reservas

Backend separado do visual: qualquer site consegue usá-lo enviando requisições HTTP com JSON.

## Arquivos

- `conexao.php`: conexão PDO com o MySQL (usuário e senha ficam só aqui).
- `funcoes.php`: `cabecalhos()` (JSON e CORS), `responder()`, `erro()`, `lerBody()`, `texto()` e `exigirToken()`.
- `reservas.php`, `mesas.php`, `auth.php`: os endpoints. Cada um lê o método HTTP e usa um `switch` (ou um `if`) para decidir o que fazer.
- `dao/`: uma classe por tabela; é onde ficam os comandos SQL.

## Dependências

PHP 8.1 ou superior com PDO MySQL, MySQL/MariaDB com o `../banco.sql` importado e Apache (WampServer).

## Endpoints

- `GET mesas.php`: lista as mesas (público).
- `POST auth.php`: `{"tipo":"cliente","cpf":"...","telefone":"..."}` ou `{"tipo":"admin","usuario":"...","senha":"..."}` → devolve `token`.
- `POST reservas.php`: cria reserva (público). Campos: `nome`, `telefone`, `cpf`, `data` (AAAA-MM-DD) e, opcionalmente, `idmesa`.
- `GET reservas.php` e `GET reservas.php?id=ID`: consulta (token).
- `PUT reservas.php?id=ID`: atualiza `nome`, `telefone`, `data` e `idmesa` (token).
- `DELETE reservas.php?id=ID`: cancela (token).

Rotas protegidas usam `Authorization: Bearer <token>`. Cliente só acessa as próprias reservas; administrador acessa todas.

Para usar o mesmo banco em outro domínio será preciso trocar os DAOs e os endpoints; a estrutura (conexão, funções, DAOs, um arquivo por recurso) pode ser reaproveitada.
