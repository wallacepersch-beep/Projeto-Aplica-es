//Cliente http pro php
(function () {

    const pastaApi = new URL("../../api/", document.currentScript.src).href;

    function montarUrl(rota, parametros = {}) {
        const url = new URL(pastaApi + rota + ".php");

        Object.entries(parametros).forEach(([chave, valor]) => {
            if (valor !== undefined && valor !== null && valor !== "") {
                url.searchParams.set(chave, String(valor));
            }
        });

        return url.toString();
    }

    async function requisitar(rota, opcoes = {}) {
        const cabecalhos = {};
        if (opcoes.body !== undefined) {
            cabecalhos["Content-Type"] = "application/json";
        }
        if (opcoes.token) {
            cabecalhos["Authorization"] = `Bearer ${opcoes.token}`;
        }

        let resposta;
        try {
            resposta = await fetch(montarUrl(rota, opcoes.params || {}), {
                method: opcoes.method || "GET",
                headers: cabecalhos,
                body: opcoes.body === undefined ? undefined : JSON.stringify(opcoes.body)
            });
        } catch (erro) {
            return {
                ok: false,
                status: 0,
                data: { message: "Não foi possível acessar a API. Confira se o WampServer está ligado." }
            };
        }

        const dados = await resposta.json().catch(() => ({}));
        return { ok: resposta.ok, status: resposta.status, data: dados };
    }

    function mostrarMensagem(elemento, texto, tipo = "") {
        if (!elemento) return;
        elemento.textContent = texto;
        elemento.className = "mensagem" + (tipo ? ` ${tipo}` : "");
        elemento.hidden = !texto;
    }

    function escaparHtml(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, caractere => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[caractere]);
    }

    function dataLocal() {
        const agora = new Date();
        agora.setMinutes(agora.getMinutes() - agora.getTimezoneOffset());
        return agora.toISOString().slice(0, 10);
    }

    window.API = { requisitar, montarUrl, mostrarMensagem, escaparHtml, dataLocal };
})();
