# Casa Amore — API de Reservas (piloto)

**Tema:** sistema de reservas de mesas de um restaurante. O cliente reserva uma mesa para uma data; o administrador gerencia todas as reservas. Os pratos do cardápio serão apenas exibidos no site (não são reservados).

**Integrantes:** _(preencher: nome 1 e nome 2)_

Esta pasta guarda o **piloto da API em PHP** (Sprint 2). O site principal (`Projeto_WebSite_PIzzaria`) será ligado a esta API na Sprint 3. A pasta `frontend/` serve só para visualizar e testar a API no navegador.

## Estrutura

```text
integracao_pizzaria/
├── api/
│   ├── conexao.php        conexão PDO (único lugar com usuário/senha do banco)
│   ├── funcoes.php        funções comuns: responder, erro, ler o body, exigir token
│   ├── reservas.php       API das reservas  (GET, POST, PUT, DELETE)
│   ├── mesas.php          API das mesas     (GET)
│   ├── auth.php           login (gera o token)
│   └── dao/               uma classe por tabela, só com SQL (prepare + execute)
│       ├── ClienteDAO.php
│       ├── MesaDAO.php
│       ├── ReservaDAO.php
│       ├── TokenDAO.php
│       └── UsuarioDAO.php
├── frontend/              páginas de demonstração (HTML, CSS e JS)
├── banco.sql              cria o banco e as tabelas, com dados de exemplo
├── testes-api.sh          testes com curl
└── README.md
```

## Como rodar (WampServer)

1. Coloque a pasta em `C:\wamp64\www\Casa_Amore_API_Piloto\integracao_pizzaria`.
2. Ligue o WampServer (Apache e MySQL ficam verdes).
3. No phpMyAdmin, importe o `banco.sql`.
   **Atenção:** ele começa com `DROP DATABASE IF EXISTS restaurante`, então importar de novo apaga e recria o banco.
4. Confira o usuário e a senha em `api/conexao.php` (padrão: `root`, sem senha).
5. Teste no navegador: `http://localhost/Casa_Amore_API_Piloto/integracao_pizzaria/api/mesas.php` deve mostrar as mesas em JSON.
6. Páginas de demonstração: `http://localhost/Casa_Amore_API_Piloto/integracao_pizzaria/frontend/index.html`.
   (Abrir só `.../integracao_pizzaria/` mostra "Index of" porque não há `index.html` na raiz; é normal.)

## Rotas

| Método | URL | Quem pode | O que faz |
|---|---|---|---|
| GET | `api/mesas.php` | público | lista as mesas |
| POST | `api/auth.php` | público | login (cliente ou admin) e devolve o token |
| POST | `api/reservas.php` | público | cria uma reserva (e o cliente, se for novo) |
| GET | `api/reservas.php` | token | lista as reservas (admin: todas; cliente: só as suas) |
| GET | `api/reservas.php?id=1` | token | mostra uma reserva |
| PUT | `api/reservas.php?id=1` | token | atualiza nome, telefone, data e mesa |
| DELETE | `api/reservas.php?id=1` | token | cancela a reserva |

As respostas de erro têm o formato `{"message": "texto do erro"}`.

### Login e token

- **Cliente:** `POST api/auth.php` com `{"tipo":"cliente","cpf":"...","telefone":"..."}` (vale 2 horas).
- **Administrador:** `POST api/auth.php` com `{"tipo":"admin","usuario":"...","senha":"..."}` (vale 4 horas).

O token vai no header das próximas chamadas: `Authorization: Bearer <token>`. O cliente só enxerga e altera as próprias reservas (senão 403); o admin enxerga todas.

Conta de desenvolvimento: usuário `admin`, senha `Admin@12345`. **Troque antes de publicar.**

### Códigos HTTP usados

200 (OK) · 201 (criado) · 400 (dado inválido) · 401 (sem token / token inválido / login errado) · 403 (sem permissão nessa reserva) · 404 (não encontrado) · 405 (método não permitido) · 500 (erro no PHP ou no banco).

## Como rodar os testes

No Git Bash, dentro da pasta `integracao_pizzaria`:

```bash
bash testes-api.sh
```

Se a pasta for outra, informe a pasta da API:

```bash
API_URL="http://localhost/OUTRA_PASTA/api" bash testes-api.sh
```

O script testa sucessos e erros (401, 400, 403, 404, 405...) e mostra `[OK]` ou `[FALHOU]` em cada caso. Ele cria reservas e dois clientes de teste e apaga as reservas no final; não rode em banco de produção.

## Onde está cada requisito do projeto

| Req. | Onde |
|---|---|
| 1. `banco.sql` cria o banco e as tabelas | `banco.sql` |
| 2. Tabelas relacionadas 1:N com FOREIGN KEY | `reservas` → `clientes` e `reservas` → `mesas` |
| 3. 5 registros de exemplo | `clientes`, `mesas` e `reservas` têm 5 cada. `usuarios_admin` (1 admin) e `tokens_api` (preenchida no login) são tabelas de apoio |
| 4. `conexao.php` com PDO | `api/conexao.php` |
| 5. Uma classe DAO por tabela | `api/dao/` (`inserir`, `listar`, `buscarPorId`, `atualizar`, `excluir`) |
| 6. Só `prepare` + `execute` com `?` | todos os arquivos de `api/dao/` |
| 7. API REST em JSON | `api/reservas.php` (switch com GET, POST, PUT, DELETE) |
| 8. Token Bearer e 401 | `exigirToken()` em `api/funcoes.php`; login em `api/auth.php` |
| 9. Códigos HTTP | `api/reservas.php`, `api/mesas.php`, `api/auth.php` |
| 10. Validação no servidor | `lerDadosDaReserva()` em `api/reservas.php` |
| 19. `testes-api.sh` | `testes-api.sh` |

O POST de reservas é público de propósito: quem ainda não tem cadastro precisa conseguir reservar. Todos os outros métodos exigem token.

## Limitações conhecidas

- Não existe regra de conflito de mesas: duas reservas podem usar a mesma mesa na mesma data.
- O login do cliente (CPF + telefone) é simplificado, sem limite de tentativas, e o CPF só tem o tamanho conferido.
- Alterar o telefone numa reserva altera o telefone do cliente (que é parte do login dele).
- `DEBUG` está `true` em `api/conexao.php` (mostra detalhes de erros). Mude para `false` ao publicar.
- Tokens vencidos continuam na tabela `tokens_api` (não são apagados).
