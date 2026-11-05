document.addEventListener("DOMContentLoaded", () => {
    const formulario = document.querySelector("#formLoginCliente");
    const mensagem = document.querySelector("#mensagemLoginCliente");
    const painel = document.querySelector("#painelCliente");
    const painelLista = document.querySelector("#painelListaCliente");
    const botaoSair = document.querySelector("#sairCliente");

    const tokenGuardado = sessionStorage.getItem("tokenCliente");
    if (tokenGuardado) {
        formulario.hidden = true;
        painel.hidden = false;
        painelLista.hidden = false;
        window.iniciarGerenciadorReservas?.();
    }

    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();
        const dados = Object.fromEntries(new FormData(formulario));
        dados.tipo = "cliente";

        const resultado = await API.requisitar("auth", {
            method: "POST",
            body: dados
        });

        if (!resultado.ok) {
            API.mostrarMensagem(mensagem, resultado.data.message || "Não foi possível entrar.", "erro");
            return;
        }

        sessionStorage.setItem("tokenCliente", resultado.data.token);
        API.mostrarMensagem(mensagem, "Acesso confirmado.", "sucesso");
        formulario.hidden = true;
        painel.hidden = false;
        painelLista.hidden = false;
        window.iniciarGerenciadorReservas?.();
    });

    botaoSair.addEventListener("click", () => {
        sessionStorage.removeItem("tokenCliente");
        painel.hidden = true;
        painelLista.hidden = true;
        formulario.hidden = false;
        formulario.reset();
        API.mostrarMensagem(mensagem, "Você saiu da consulta.", "");
    });
});
