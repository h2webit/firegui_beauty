var inizializzaAutocompleteIva = function ($riga) {
    // alert('@Michael: Cambiare qua, inizializzando con select2 e al change fare le cose che sono qua sotto...');

    var numero_riga = $('.js_numero_riga_iva', $riga).val();

    log(numero_riga + ' ' + "Inizializzo autocomplete iva");

    // if ($('.js_iva_codice', $riga).hasClass("select2-hidden-accessible")) {
    //     $('.js_iva_codice', $riga).select2('destroy');
    // }

    // $('.js_iva_codice', $riga).select2();



    $('.js_iva_codice', $riga).on('change', function (event) {
        event.stopPropagation();
        var this_select = $(this);
        var selected = this_select.find('option:selected').val();

        tabella_iva.find(function (item) {
            if (item.id == selected) {
                log('tendina iva cambiata');
                $('.iva_desc', this_select.closest('.js_riga_dett_iva')).val(item.description);
                $('.js_iva_valore', this_select.closest('.js_riga_dett_iva')).val(item.value);
                $('.js_iva_tipo_es', this_select.closest('.js_riga_dett_iva')).val(item.tipo_esigibilita);
                $('.js_iva_perc_indet', this_select.closest('.js_riga_dett_iva')).val(item.indetraibilita);
                $('.js_iva_omag', this_select.closest('.js_riga_dett_iva')).val(item.omaggio);
                $('.js_iva_corrisp', this_select.closest('.js_riga_dett_iva')).val(item.corrispettivi);
                $('.js_iva_rc', this_select.closest('.js_riga_dett_iva')).val(item.rc);
            }
        });
        ricalcoloIva($riga);
    });
}