$(() => {

    $('.js_crea_modello_prima_nota').on('click', function () {
        if (crea_modello_enabled) {
            crea_modello_enabled = false;
            $('.js_container_prima_nota').removeClass('js_modello');
            $('.js_nome_modello_container').hide();
            $('[name="prime_note_modello"]').val(0);

        } else {
            crea_modello_enabled = true;
            $('.js_container_prima_nota').addClass('js_modello');
            $('.js_nome_modello_container').show();

            $('[name="prime_note_modello"]').val(1);

        }
    });

});