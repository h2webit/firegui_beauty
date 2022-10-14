
var crea_modello_enabled = false; //Mi serve globale e accessibile un po' da ovunque

$(() => {
    var form_container = $('.js_form_prima_nota');

    //Rimozione pulsante cancella
    //$('.form-actions .btn-default', form_container.parent()).remove();
    //$('.form-actions .btn-primary', form_container.parent()).hide();

    log("Rimossi pulsanti dal form");

    //click TAB button on last element will trigger a new row
    //console.log($('[name="prime_note_scadenza"]'))

    //Se clicco tab su scadenza, scateno creazione riga automatico
    $('[name="prime_note_scadenza"]').keydown(function (e) {

        var code = e.keyCode || e.which;
        if (code === 9) {
            log("Intercettato tab su scadenza. Creo nuova riga");
            e.preventDefault();
            $("#js_add_riga").trigger("click");
            //creaNuovaRiga();
        }
    });

    $('[name="prime_note_scadenza"]').on('change', function () {
        log("Intercettato un change sulla scadenza. Verifico che sia giusta con la data registrazione");

        var this_date = moment($(this).val(), "DD/MM/YYYY");
        var data_reg = moment($('#prime_note_data_registrazione').val(), "DD/MM/YYYY");
        var mese_liquidazione = this_date.format('MM');
        $('.js_iva_ml').val(mese_liquidazione);

        if (this_date.isAfter(data_reg.format("YYYY-MM-DD"))) {
            //alert('Attenzione: la data registrazione è dopo la data scadenza documento');
            toastr.error('La data registrazione è antecedente alla data doc!', 'Attenzione!', { showMethod: 'fadeIn', timeOut: 5000, progressBar: true, positionClass: 'toast-top-center' });
        }


    });

    $('#prime_note_data_registrazione', form_container).on('change', function (i, el) {
        if (!lock_automations) {
            log("Cambiata data registrazione. Cerco progressivo corretto");
            getProgressivoPrimanotaAnno();
            getProgressivoPrimanotaGiorno();
        }

    });
    $('#prime_note_data_registrazione', form_container).trigger('change');
    //getProgressivoPrimanotaAnno();

    var pulsante_nuova_riga = $('#js_add_riga');

    pulsante_nuova_riga.keyup(function (e) {
        var code = e.keyCode || e.which;
        if (code === 9) {
            log("Intercettato tab su pulsante nuova riga. Creo nuova riga");
            e.preventDefault();
            $("#js_add_riga").trigger("click");
        }
    });

    pulsante_nuova_riga.on('click', function () {
        try {
            //$('.select2,.select2_standard').select2('destroy');
        } catch (e) { }
        var datiNuovaRiga = creaNuovaRiga(true);
        var $riga = datiNuovaRiga[0];
        var valori_default = {};
        var prima_nota_causale_intestazione = $('[name="prime_note_causale"]');

        checkDettaglioIvaInsert();

        var intestazione_causale_valore = prima_nota_causale_intestazione.val();

        //Se non c'è già una riga con questa causale, precompilo con quella dell'intestazione
        if ($('.js_causale option[value="' + intestazione_causale_valore + '"]:selected').length == 0) {
            valori_default.causale = intestazione_causale_valore;

        }
        impostaValoriDefault($riga, valori_default);
        reIndexRighe();
    });

    form_container.on('click', '.js_delete_riga_registrazione', function () {
        $(this).closest('.js_riga_registrazione').remove();
        reIndexRighe();

        checkSommaDareAvere();
    });

    /*******************************************************************************************************************/
    //Portare qui tutti gli onchange con la logica del tipo $('body').on('change', '.conto2', function () {FUNZIONE DI MICHAEL});

    form_container.on('change', '.js_importo_dare, .js_importo_avere', function () {
        log('Importi cambiati!');
        checkSommaDareAvere();
    });

    form_container.on('change', '.js_prime_note_causale', function () {
        log('Causale intestazione cambiata');
        //Se la causale non prevede l'iva, nascondo lo specchietto "giallo"
        var registro_iva = $('option:selected', $(this)).data('registro_iva');
        if (registro_iva) {
            $('.js_iva_dependent').show();
        } else {
            $('.js_riga_dett_iva').not('.hidden').remove();
            $('.js_iva_dependent').hide();
        }
        causaleIntestazioneChanged();
    });
    form_container.on('change', '[name="prime_note_sezionale"]', function () {
        log('Sezionale iva cambiato');
        console.log(lock_automations);

        if (!lock_automations) {

            onChangeSezionaleIva();
        }
    });




    $(document).on('focus', '.select2-selection.select2-selection--single', function (e) {
        $(this).closest(".select2-container").siblings('select:enabled').select2('open');
    });

    //Disattivo submit all'invio
    $(window).keydown(function (event) {
        if (event.keyCode == 13) {
            log('Disattivo submit all\'invio');
            event.preventDefault();
            return false;
        }
    });

    initOnChangeModelloSelect();
    if (fields_to_set) {

        $.each(fields_to_set, function (field, value) {
            if (field == 'function_js_to_run') {
                $.each(value, function (key, funzione) {
                    setTimeout(function () { eval(funzione) }, 100);
                });
            } else {
                $(field).val(value).trigger('change');
            }

        });
    }



});