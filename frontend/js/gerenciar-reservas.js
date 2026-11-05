(function () {
    let reservasAtuais = [];
    let idEmEdicao = null;
    let perfil = "cliente";
    let token = "";

    function elementos() {
        return {
            tabela: document.querySelector("#corpoReservas"),
            mensagem: document.querySelector("#mensagemReservas"),
            formularioEdicao: document.querySelector("#formEditarReserva"),
            painelEdicao: document.querySelector("#painelEdicao"),
            selectMesa: document.querySelector("#mesaEdicao"),
            campoData: document.querySelector("#dataEdicao"),
            botaoSair: document.querySelector("#sairAdmin")
        };
    }

    async function carregarMesas() {
        const el = elementos();
        const resultado = await API.requisitar("mesas");
        if (!resultado.ok || !el.selectMesa) return;

        el.selectMesa.innerHTML = '<option value="">Sem preferência de mesa</option>';
        (resultado.data.mesas || []).forEach(mesa => {
            const opcao = document.createElement("option");
            opcao.value = mesa.idmesa;
            opcao.textContent = `Mesa ${mesa.idmesa} — ${mesa.espaco} lugar(es)`;
            el.selectMesa.appendChild(opcao);
        });
    }

    function renderizar() {
        const el = elementos();
        if (!el.tabela) return;

        if (!reservasAtuais.length) {
            el.tabela.innerHTML = '<tr><td colspan="6">Nenhuma reserva encontrada.</td></tr>';
            return;
        }

        el.tabela.innerHTML = reservasAtuais.map(reserva => `
            <tr>
                <td>${API.escaparHtml(reserva.idreserva)}</td>
                <td>${API.escaparHtml(reserva.nome)}</td>
                <td>${API.escaparHtml(reserva.telefone)}</td>
                <td>${API.escaparHtml(reserva.data)}</td>
                <td>${reserva.idmesa ? `Mesa ${API.escaparHtml(reserva.idmesa)} (${API.escaparHtml(reserva.lugares_mesa || "?")} lugares)` : "Sem mesa definida"}</td>
                <td class="acoes">
                    <button type="button" class="pequeno" data-acao="editar" data-id="${API.escaparHtml(reserva.idreserva)}">Editar</button>
                    <button type="button" class="pequeno perigo" data-acao="cancelar" data-id="${API.escaparHtml(reserva.idreserva)}">Cancelar</button>
                </td>
            </tr>
        `).join("");
    }

    async function carregarReservas() {
        const el = elementos();
        const resultado = await API.requisitar("reservas", { token });

        if (resultado.status === 401) {
            sessionStorage.removeItem(perfil === "admin" ? "tokenAdmin" : "tokenCliente");
            API.mostrarMensagem(el.mensagem, "Sua sessão expirou. Entre novamente.", "erro");
            if (perfil === "admin") {
                window.location.href = "login-admin.html";
            } else {
                document.querySelector("#painelCliente").hidden = true;
                document.querySelector("#painelListaCliente").hidden = true;
                document.querySelector("#formLoginCliente").hidden = false;
                API.mostrarMensagem(
                    document.querySelector("#mensagemLoginCliente"),
                    "Sua sessão expirou. Entre novamente.",
                    "erro"
                );
            }
            return;
        }

        if (!resultado.ok) {
            API.mostrarMensagem(el.mensagem, resultado.data.message || "Não foi possível carregar as reservas.", "erro");
            return;
        }

        reservasAtuais = resultado.data.reservas || [];
        renderizar();
        API.mostrarMensagem(el.mensagem, "", "");
    }

    async function iniciar() {
        const corpo = document.body;
        perfil = corpo.dataset.profile || "cliente";
        const chaveToken = perfil === "admin" ? "tokenAdmin" : "tokenCliente";
        token = sessionStorage.getItem(chaveToken) || "";

        if (!token) {
            if (perfil === "admin") window.location.href = "login-admin.html";
            return;
        }

        const el = elementos();
        if (el.campoData) el.campoData.min = API.dataLocal();
        if (el.botaoSair) {
            el.botaoSair.addEventListener("click", () => {
                sessionStorage.removeItem(chaveToken);
                window.location.href = perfil === "admin" ? "login-admin.html" : "consultar.html";
            });
        }

        await carregarMesas();
        await carregarReservas();
    }

    document.addEventListener("DOMContentLoaded", () => {
        const el = elementos();
        if (!el.tabela) return;

        if (document.body.dataset.profile === "cliente") {
            window.iniciarGerenciadorReservas = iniciar;
            if (sessionStorage.getItem("tokenCliente")) iniciar();
        } else {
            iniciar();
        }

        el.tabela.addEventListener("click", async evento => {
            const botao = evento.target.closest("button[data-acao]");
            if (!botao) return;

            const id = Number(botao.dataset.id);
            const reserva = reservasAtuais.find(item => Number(item.idreserva) === id);
            if (!reserva) return;

            if (botao.dataset.acao === "editar") {
                idEmEdicao = id;
                el.painelEdicao.hidden = false;
                document.querySelector("#nomeEdicao").value = reserva.nome || "";
                document.querySelector("#telefoneEdicao").value = reserva.telefone || "";
                el.campoData.value = reserva.data || "";
                el.selectMesa.value = reserva.idmesa || "";
                el.painelEdicao.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            if (botao.dataset.acao === "cancelar") {
                if (!window.confirm(`Deseja realmente cancelar a reserva ${id}?`)) return;

                const resposta = await API.requisitar("reservas", {
                    method: "DELETE",
                    params: { id },
                    token
                });
                if (resposta.ok) {
                    API.mostrarMensagem(el.mensagem, resposta.data.message, "sucesso");
                    await carregarReservas();
                } else {
                    API.mostrarMensagem(el.mensagem, resposta.data.message || "Não foi possível cancelar.", "erro");
                }
            }
        });

        if (el.formularioEdicao) {
            el.formularioEdicao.addEventListener("submit", async evento => {
                evento.preventDefault();
                if (!idEmEdicao) return;

                const dados = {
                    nome: document.querySelector("#nomeEdicao").value.trim(),
                    telefone: document.querySelector("#telefoneEdicao").value.trim(),
                    data: el.campoData.value,
                    idmesa: el.selectMesa.value || null
                };

                const resposta = await API.requisitar("reservas", {
                    method: "PUT",
                    params: { id: idEmEdicao },
                    token,
                    body: dados
                });

                if (resposta.ok) {
                    API.mostrarMensagem(el.mensagem, resposta.data.message, "sucesso");
                    el.painelEdicao.hidden = true;
                    el.formularioEdicao.reset();
                    idEmEdicao = null;
                    await carregarReservas();
                } else {
                    API.mostrarMensagem(el.mensagem, resposta.data.message || "Não foi possível atualizar.", "erro");
                }
            });

            document.querySelector("#cancelarEdicao").addEventListener("click", () => {
                idEmEdicao = null;
                el.painelEdicao.hidden = true;
                el.formularioEdicao.reset();
            });
        }
    });
})();
