$(() => {
    var form_container = $('.js_form_prima_nota');

    var pulsante_nuova_riga_iva = $('#js_add_riga_iva', form_container);

    pulsante_nuova_riga_iva.keyup(function (e) {
        var code = e.keyCode || e.which;
        // ELENCO COMPLETO DEI KEYCODES https://css-tricks.com/snippets/javascript/javascript-keycodes/
        if (code === 9) { // KEYCODE 9 = TAB
            log('il tasto premuto è TAB, quindi blocco e triggero il clico sul bottone')
            e.preventDefault();
            $("#js_add_riga_iva").trigger("click");

        }
    });

    form_container.on('click', '.js_delete_dettaglio_iva', function () {
        $(this).closest('.js_riga_dett_iva').remove();
        reIndexRigheDettagliIva();
        onClickPulsanteConferma();
        $('#js_save_iva').trigger('click');
    });

    pulsante_nuova_riga_iva.on('click', function () {
        try {
            //$('.select2,.select2_standard').select2('destroy');
        } catch (e) { }

        var nuovaRiga = creaNuovaRigaDettagliIva(true);
    });

    pulsante_nuova_riga_iva.trigger('click')

    $('.iva_container').on('change', '.js_iva_imponibile,.js_iva_importo', function () {
        //alert(1);
        ricalcoloIva($(this).closest('.js_riga_dett_iva'), true);
        //alert(2);
        checkSommaDareAvere();

    });
});