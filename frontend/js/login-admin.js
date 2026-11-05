document.addEventListener("DOMContentLoaded", () => {
    const formulario = document.querySelector("#formLoginAdmin");
    const mensagem = document.querySelector("#mensagemLoginAdmin");

    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();
        const dados = Object.fromEntries(new FormData(formulario));
        dados.tipo = "admin";

        const resultado = await API.requisitar("auth", {
            method: "POST",
            body: dados
        });

        if (!resultado.ok) {
            API.mostrarMensagem(mensagem, resultado.data.message || "Não foi possível entrar.", "erro");
            return;
        }

        sessionStorage.setItem("tokenAdmin", resultado.data.token);
        window.location.href = "admin.html";
    });
});
