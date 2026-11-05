set -u

API="${API_URL:-http://localhost/integracao_pizzaria/integracao_pizzaria/api}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-Admin@12345}"

RESERVAS="$API/reservas.php"
MESAS="$API/mesas.php"
AUTH="$API/auth.php"

TMP="${TMPDIR:-/tmp}/casa-amore-resposta.json"   #guarda a ult resposta
pass=0
fail=0


check() {
    if [[ "$3" == "$2" ]]; then
        echo "[OK] $1 ($3)"
        ((pass+=1))
    else
        echo "[FALHOU] $1: esperado $2, recebido $3"
        ((fail+=1))
    fi
}

#escreve o HTTP na tela
chamar() {
    local metodo="$1" url="$2" token="${3:-}" corpo="${4:-}"
    local args=(-sS -o "$TMP" -w '%{http_code}' -X "$metodo")
    if [[ -n "$token" ]]; then
        args+=(-H "Authorization: Bearer $token")
    fi
    if [[ -n "$corpo" ]]; then
        args+=(-H 'Content-Type: application/json' -d "$corpo")
    fi
    curl "${args[@]}" "$url"
}

#lê última resposta só do idreserva e token
campo() {
    case "$1" in
        idreserva) sed -nE 's/.*"idreserva"[[:space:]]*:[[:space:]]*([0-9]+).*/\1/p' "$TMP" | head -n 1 ;;
        token)     sed -nE 's/.*"token"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/p' "$TMP" | head -n 1 ;;
    esac
}

#quantas reservas vieram na última resposta
contar_reservas() {
    grep -o '"idreserva"' "$TMP" | wc -l | tr -d ' '
}

#dados pros testes
CPF_A="98765432100"; TEL_A="51987654321"    # cliente de teste A
CPF_B="12345678909"; TEL_B="51999990000"    # cliente de teste B
FUTURA="2099-12-30"

echo "Testando a API em: $API"
echo

#401 sem token
echo "--- Token ---"
code=$(chamar GET "$RESERVAS");                          check 'GET sem token' 401 "$code"
code=$(chamar GET "$RESERVAS?id=1");                     check 'GET por id sem token' 401 "$code"
code=$(chamar PUT "$RESERVAS?id=1" "" '{"nome":"X"}');   check 'PUT sem token' 401 "$code"
code=$(chamar DELETE "$RESERVAS?id=1");                  check 'DELETE sem token' 401 "$code"
code=$(chamar GET "$RESERVAS" "token-invalido");         check 'Token inválido' 401 "$code"
code=$(chamar GET "$RESERVAS" "$(printf 'a%.0s' {1..64})"); check 'Token com formato certo mas inexistente' 401 "$code"
code=$(curl -sS -o "$TMP" -w '%{http_code}' -H 'Authorization: Basic abc123' "$RESERVAS")
check 'Authorization que não é Bearer' 401 "$code"

#mesas
echo "--- Mesas ---"
code=$(chamar GET "$MESAS");   check 'Listar mesas sem login' 200 "$code"
tipo=$(curl -s -o /dev/null -w '%{content_type}' "$MESAS")
check 'Resposta é JSON' 'application/json' "${tipo%%;*}"
code=$(chamar POST "$MESAS" "" '{"espaco":4}');  check 'POST em mesas' 405 "$code"

# 400 reserva invalida
echo "--- POST com dados inválidos ---"
code=$(chamar POST "$RESERVAS" "" '{"nome":"","telefone":"","cpf":"","data":""}')
check 'Campos obrigatórios vazios' 400 "$code"
code=$(chamar POST "$RESERVAS" "" 'isto nao e json')
check 'Corpo que não é JSON' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Teste\",\"telefone\":\"123\",\"cpf\":\"$CPF_A\",\"data\":\"$FUTURA\"}")
check 'Telefone curto' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Teste\",\"telefone\":\"$TEL_A\",\"cpf\":\"123\",\"data\":\"$FUTURA\"}")
check 'CPF curto' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Teste\",\"telefone\":\"$TEL_A\",\"cpf\":\"$CPF_A\",\"data\":\"2099-13-45\"}")
check 'Data que não existe' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Teste\",\"telefone\":\"$TEL_A\",\"cpf\":\"$CPF_A\",\"data\":\"30/12/2099\"}")
check 'Data no formato errado' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Teste\",\"telefone\":\"$TEL_A\",\"cpf\":\"$CPF_A\",\"data\":\"2020-01-01\"}")
check 'Data no passado' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Teste\",\"telefone\":\"$TEL_A\",\"cpf\":\"$CPF_A\",\"data\":\"$FUTURA\",\"idmesa\":99999}")
check 'Mesa que não existe' 400 "$code"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Ana\",\"telefone\":\"51000000000\",\"cpf\":\"11144477735\",\"data\":\"$FUTURA\"}")
check 'CPF já cadastrado com outro telefone' 400 "$code"

#201 cria reserva
echo "--- POST: criar ---"
code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Cliente Teste A\",\"telefone\":\"$TEL_A\",\"cpf\":\"$CPF_A\",\"data\":\"$FUTURA\",\"idmesa\":null}")
check 'Criar reserva do cliente A' 201 "$code"
ID_A=$(campo idreserva)
if [[ -z "$ID_A" ]]; then echo "[FALHOU] O id da reserva A não veio na resposta:"; cat "$TMP"; echo; ((fail+=1)); fi

code=$(chamar POST "$RESERVAS" "" "{\"nome\":\"Cliente Teste B\",\"telefone\":\"(51) 99999-0000\",\"cpf\":\"123.456.789-09\",\"data\":\"$FUTURA\",\"idmesa\":1}")
check 'Criar reserva do cliente B (com máscara e mesa)' 201 "$code"
ID_B=$(campo idreserva)
if [[ -z "$ID_B" ]]; then echo "[FALHOU] O id da reserva B não veio na resposta:"; cat "$TMP"; echo; ((fail+=1)); fi

#cliente
echo "--- Login do cliente ---"
code=$(chamar POST "$AUTH" "" '{"tipo":"cliente"}');                                   check 'Login sem CPF e telefone' 400 "$code"
code=$(chamar POST "$AUTH" "" '{"tipo":"outro"}');                                     check 'Tipo de login inválido' 400 "$code"
code=$(chamar POST "$AUTH" "" "{\"tipo\":\"cliente\",\"cpf\":\"$CPF_A\",\"telefone\":\"51900000000\"}")
check 'Login com telefone errado' 401 "$code"
code=$(chamar GET "$AUTH");                                                            check 'GET em auth' 405 "$code"
code=$(chamar POST "$AUTH" "" "{\"tipo\":\"cliente\",\"cpf\":\"$CPF_A\",\"telefone\":\"$TEL_A\"}")
check 'Login do cliente A' 200 "$code"
TOKEN_A=$(campo token)
if [[ -z "$TOKEN_A" ]]; then echo "[FALHOU] O token do cliente não veio na resposta:"; cat "$TMP"; echo; ((fail+=1)); fi

# ---------- 6. O que o cliente pode (e não pode) fazer ----------
echo "--- Cliente A com token ---"
code=$(chamar GET "$RESERVAS?id=$ID_A" "$TOKEN_A");  check 'Cliente vê a própria reserva' 200 "$code"
code=$(chamar GET "$RESERVAS" "$TOKEN_A");           check 'Cliente lista as reservas' 200 "$code"
check 'Cliente vê só 1 reserva na lista' 1 "$(contar_reservas)"
code=$(chamar GET "$RESERVAS?id=$ID_B" "$TOKEN_A");  check 'Cliente não vê reserva de outro' 403 "$code"
code=$(chamar PUT "$RESERVAS?id=$ID_B" "$TOKEN_A" "{\"nome\":\"Invasor\",\"telefone\":\"$TEL_B\",\"data\":\"$FUTURA\"}")
check 'Cliente não altera reserva de outro' 403 "$code"
code=$(chamar DELETE "$RESERVAS?id=$ID_B" "$TOKEN_A"); check 'Cliente não cancela reserva de outro' 403 "$code"
code=$(chamar GET "$RESERVAS?id=99999999" "$TOKEN_A"); check 'Id que não existe' 404 "$code"
code=$(chamar PUT "$RESERVAS?id=99999999" "$TOKEN_A" "{\"nome\":\"X\",\"telefone\":\"$TEL_A\",\"data\":\"$FUTURA\"}")
check 'PUT em id que não existe' 404 "$code"
code=$(chamar GET "$RESERVAS?id=abc" "$TOKEN_A");    check 'Id que não é número' 400 "$code"
code=$(chamar PUT "$RESERVAS" "$TOKEN_A" "{\"nome\":\"X\",\"telefone\":\"$TEL_A\",\"data\":\"$FUTURA\"}")
check 'PUT sem id' 400 "$code"
code=$(chamar DELETE "$RESERVAS" "$TOKEN_A");        check 'DELETE sem id' 400 "$code"
code=$(chamar PUT "$RESERVAS?id=$ID_A" "$TOKEN_A" "{\"nome\":\"\",\"telefone\":\"$TEL_A\",\"data\":\"$FUTURA\"}")
check 'PUT com nome vazio' 400 "$code"
code=$(chamar PUT "$RESERVAS?id=$ID_A" "$TOKEN_A" "{\"nome\":\"X\",\"telefone\":\"$TEL_A\",\"data\":\"2020-01-01\"}")
check 'PUT com data no passado' 400 "$code"
code=$(chamar PUT "$RESERVAS?id=$ID_A" "$TOKEN_A" "{\"nome\":\"Cliente Teste A Editado\",\"telefone\":\"$TEL_A\",\"data\":\"2099-12-31\",\"idmesa\":2}")
check 'Cliente altera a própria reserva' 200 "$code"

#adm
echo "--- Administrador ---"
code=$(chamar POST "$AUTH" "" '{"tipo":"admin"}');   check 'Login admin sem usuário e senha' 400 "$code"
code=$(chamar POST "$AUTH" "" "{\"tipo\":\"admin\",\"usuario\":\"$ADMIN_USER\",\"senha\":\"senha-errada\"}")
check 'Login admin com senha errada' 401 "$code"
code=$(chamar POST "$AUTH" "" "{\"tipo\":\"admin\",\"usuario\":\"$ADMIN_USER\",\"senha\":\"$ADMIN_PASSWORD\"}")
check 'Login admin' 200 "$code"
TOKEN_ADMIN=$(campo token)
if [[ -z "$TOKEN_ADMIN" ]]; then echo "[FALHOU] O token do admin não veio na resposta:"; cat "$TMP"; echo; ((fail+=1)); fi

code=$(chamar GET "$RESERVAS" "$TOKEN_ADMIN");                check 'Admin lista todas as reservas' 200 "$code"
if [[ "$(contar_reservas)" -ge 2 ]]; then check 'Admin vê as reservas de vários clientes' ok ok; else check 'Admin vê as reservas de vários clientes' ok "só $(contar_reservas)"; fi
code=$(chamar GET "$RESERVAS?id=$ID_B" "$TOKEN_ADMIN");       check 'Admin vê reserva de qualquer cliente' 200 "$code"
code=$(chamar PUT "$RESERVAS?id=$ID_B" "$TOKEN_ADMIN" "{\"nome\":\"Cliente Teste B Editado\",\"telefone\":\"$TEL_B\",\"data\":\"2099-12-31\",\"idmesa\":null}")
check 'Admin altera reserva de qualquer cliente' 200 "$code"
code=$(chamar PUT "$RESERVAS?id=$ID_B" "$TOKEN_ADMIN" "{\"nome\":\"X\",\"telefone\":\"$TEL_B\",\"data\":\"2020-01-01\"}")
check 'Admin: PUT com data nova no passado' 400 "$code"

#cancelar
echo "--- DELETE ---"
code=$(chamar DELETE "$RESERVAS?id=$ID_A" "$TOKEN_A");        check 'Cliente cancela a própria reserva' 200 "$code"
code=$(chamar GET "$RESERVAS?id=$ID_A" "$TOKEN_ADMIN");       check 'Reserva cancelada não existe mais' 404 "$code"
code=$(chamar DELETE "$RESERVAS?id=$ID_B" "$TOKEN_ADMIN");    check 'Admin cancela reserva' 200 "$code"
code=$(chamar DELETE "$RESERVAS?id=$ID_B" "$TOKEN_ADMIN");    check 'Cancelar de novo' 404 "$code"

#não permitido
echo "--- Métodos ---"
code=$(chamar PATCH "$RESERVAS" "$TOKEN_ADMIN");              check 'PATCH em reservas' 405 "$code"

echo
echo "Resultado: $pass aprovado(s), $fail falha(s)."
[[ "$fail" -eq 0 ]]
