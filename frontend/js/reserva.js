document.addEventListener("DOMContentLoaded", async () => {
    const formulario = document.querySelector("#formReserva");
    const mensagem = document.querySelector("#mensagemReserva");
    const campoData = document.querySelector("#dataReserva");
    const campoMesa = document.querySelector("#idmesa");

    campoData.min = API.dataLocal();

    const respostaMesas = await API.requisitar("mesas");
    if (respostaMesas.ok) {
        (respostaMesas.data.mesas || []).forEach(mesa => {
            const opcao = document.createElement("option");
            opcao.value = mesa.idmesa;
            opcao.textContent = `Mesa ${mesa.idmesa} — ${mesa.espaco} lugar(es)`;
            campoMesa.appendChild(opcao);
        });
    }

    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();
        const dados = Object.fromEntries(new FormData(formulario));
        dados.idmesa = dados.idmesa || null;

        const resultado = await API.requisitar("reservas", {
            method: "POST",
            body: dados
        });

        if (resultado.ok) {
            API.mostrarMensagem(
                mensagem,
                `Reserva solicitada! Número da reserva: ${resultado.data.idreserva}.`,
                "sucesso"
            );
            formulario.reset();
            campoData.min = API.dataLocal();
        } else {
            API.mostrarMensagem(mensagem, resultado.data.message || "Não foi possível cadastrar a reserva.", "erro");
        }
    });
});
