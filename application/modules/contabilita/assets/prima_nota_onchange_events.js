function ___________onChangeRigaCausale($riga) {
    alert('What?');
    $('.js_causale', $riga).on('change', function () {

        $('input:not(\'.js_numero_riga\')', $riga).val('').trigger('change');
        var causale_id = $(this).val();
        console.log('Causale riga cambiata (' + causale_id + '). Imposto i vari mastri/conti/sottoconti');
        console.log(causali);
        var causale_data = causali[causale_id];
        if (causale_data.prime_note_causale_mastro_dare) {
            console.log('Imposto mastro dare');
            $('.js_riga_mastro_dare_codice', $riga).val(causale_data.prime_note_causale_mastro_dare_documenti_contabilita_mastri_codice).trigger('change');

            if (causale_data.prime_note_causale_conto_dare) {
                console.log($riga + ' Imposto conto dare');

                $('.js_riga_conto_dare_codice', $riga).val(causale_data.prime_note_causale_conto_dare_documenti_contabilita_conti_codice).trigger('change');
                if (causale_data.prime_note_causale_sottoconto_dare) {
                    console.log('Imposto sottoconto dare');

                    $('.js_riga_sottoconto_dare_codice', $riga).val(causale_data.prime_note_causale_sottoconto_dare_documenti_contabilita_sottoconti_codice).trigger('change');
                }
            }

            if (causale_data.prime_note_causale_mastro_avere) {
                console.log('Questa causale ha anche un conto avere, inserisco nuova riga in automatico');
                var nuova_riga_data = creaNuovaRiga(false); //Passo false per non inizializzare da subito i vari onchange, altrimenti entra in loop
                var $nuovariga = nuova_riga_data[0];

                console.log('Forzo causale uguale a quella prima');
                //$('.js_causale option').removeAttr('selected');

                $('.js_causale', $nuovariga).select2('val', causale_id);
                //A questo punto posso agganciare tutti gli eventi in quanto ho già fatto quello che dovevo fare
                agganciaEventiRiga($nuovariga);

                inizializzaComponentiRiga($nuovariga);

                console.log('Imposto mastro avere su nuova riga');
                $('.js_riga_mastro_avere_codice', $nuovariga).val(causale_data.prime_note_causale_mastro_avere_documenti_contabilita_mastri_codice).trigger('change');

                if (causale_data.prime_note_causale_conto_avere) {
                    console.log('Imposto conto avere su nuova riga');

                    $('.js_riga_conto_avere_codice', $nuovariga).val(causale_data.prime_note_causale_conto_avere_documenti_contabilita_conti_codice).trigger('change');
                    if (causale_data.prime_note_causale_sottoconto_avere) {
                        console.log('Imposto sottoconto avere su nuova riga');

                        $('.js_riga_sottoconto_avere_codice', $nuovariga).val(causale_data.prime_note_causale_sottoconto_avere_documenti_contabilita_sottoconti_codice).trigger('change');
                    }
                }
            }
        } else if (causale_data.prime_note_causale_mastro_avere) {
            console.log('Imposto mastro avere');
            $('.js_riga_mastro_avere_codice', $riga).val(causale_data.prime_note_causale_mastro_avere_documenti_contabilita_mastri_codice).trigger('change');

            if (causale_data.prime_note_causale_conto_avere) {
                console.log('Imposto conto avere');

                $('.js_riga_conto_avere_codice', $riga).val(causale_data.prime_note_causale_conto_avere_documenti_contabilita_conti_codice).trigger('change');
                if (causale_data.prime_note_causale_sottoconto_avere) {
                    console.log('Imposto sottoconto avere');

                    $('.js_riga_sottoconto_avere_codice', $riga).val(causale_data.prime_note_causale_sottoconto_avere_documenti_contabilita_sottoconti_codice).trigger('change');
                }
            }
        }
    });
    //console.log("TODO: al cambio causale vanno forzati i valori di dare e/o avere in base alla causale scelta. Ricordarsi che c'è la ricorsione da gestire sulle causali che scatenano altre causali...");
}


/*******************************
 *
 * ON CHANGE DARE
 *
 */


function onChangeSottocontoDare($riga) {

    $('.js_riga_sottoconto_dare_codice', $riga).on('focusout change', function () {
        var numero_riga = $('.js_numero_riga', $riga).val();
        log(numero_riga + ' ' + 'Sottoconto dare cambiato.');

        var val = $(this).val();

        if (val) {


            $('.js_riga_sottoconto_avere_codice', $riga).attr('readonly', true).val('');
            $('.js_riga_sottoconto_avere_codice', $riga).attr('tabIndex', '-1');

            $('.js_importo_avere', $riga).attr('readonly', true).val('');
            $('.js_importo_avere', $riga).attr('tabIndex', '-1');
        } else {

            $('.js_riga_sottoconto_avere_codice', $riga).removeAttr('readonly');
            $('.js_importo_avere', $riga).removeAttr('readonly');


            $('.js_riga_sottoconto_avere_codice', $riga).removeAttr('tabIndex');
            $('.js_importo_avere', $riga).removeAttr('tabIndex');
        }


    });
    console.log('Sotto conto dare cambiato');


}

function onChangeImportoDare($riga) {
    $('.js_form_prima_nota').on('change', '.js_importo_dare', function () {
        // var val = $(this).val();

        // if (val) {


        //     $('.js_riga_sottoconto_avere_codice', $riga).attr('readonly', true).val('');
        //     $('.js_riga_sottoconto_avere_codice', $riga).attr('tabIndex', '-1');

        //     $('.js_importo_avere', $riga).attr('readonly', true).val('');
        //     $('.js_importo_avere', $riga).attr('tabIndex', '-1');
        // } else {

        //     $('.js_riga_sottoconto_avere_codice', $riga).removeAttr('readonly');
        //     $('.js_importo_avere', $riga).removeAttr('readonly');


        //     $('.js_riga_sottoconto_avere_codice', $riga).removeAttr('tabIndex');
        //     $('.js_importo_avere', $riga).removeAttr('tabIndex');
        // }
        console.log('Cambiato importo dare. Richiamo funzione per controllo importi');
        checkSommaDareAvere();
    });

}






/*******************************
 * 
 * ON CHANGE AVERE
 * 
 */


function onChangeSottocontoAvere($riga) {
    $('.js_riga_sottoconto_avere_codice', $riga).on('focusout change', function () {
        var numero_riga = $('.js_numero_riga', $riga).val();
        log(numero_riga + ' ' + 'Sottoconto avere cambiato.');

        var val = $(this).val();

        if (val) {


            $('.js_riga_sottoconto_dare_codice', $riga).attr('readonly', true).val('');
            $('.js_riga_sottoconto_dare_codice', $riga).attr('tabIndex', '-1');

            $('.js_importo_dare', $riga).attr('readonly', true).val('');
            $('.js_importo_dare', $riga).attr('tabIndex', '-1');
        } else {

            $('.js_riga_sottoconto_dare_codice', $riga).removeAttr('readonly');
            $('.js_importo_dare', $riga).removeAttr('readonly');


            $('.js_riga_sottoconto_dare_codice', $riga).removeAttr('tabIndex');
            $('.js_importo_dare', $riga).removeAttr('tabIndex');
        }


    });
    console.log('Sotto conto dare cambiato');
}

function onChangeImportoAvere($riga) {
    $('.js_form_prima_nota').on('change', '.js_importo_avere', function () {
        // var val = $(this).val();

        // if (val) {


        //     $('.js_riga_sottoconto_avere_codice', $riga).attr('readonly', true).val('');
        //     $('.js_riga_sottoconto_avere_codice', $riga).attr('tabIndex', '-1');

        //     $('.js_importo_avere', $riga).attr('readonly', true).val('');
        //     $('.js_importo_avere', $riga).attr('tabIndex', '-1');
        // } else {

        //     $('.js_riga_sottoconto_avere_codice', $riga).removeAttr('readonly');
        //     $('.js_importo_avere', $riga).removeAttr('readonly');


        //     $('.js_riga_sottoconto_avere_codice', $riga).removeAttr('tabIndex');
        //     $('.js_importo_avere', $riga).removeAttr('tabIndex');
        // }
        console.log('Cambiato importo avere. Richiamo funzione per controllo importi');
        checkSommaDareAvere();
    });
}

var causaleIntestazioneChanged = function () {
    var prima_nota_causale_intestazione = $('[name="prime_note_causale"]');
    var causale_tipo_iva = $('option:selected', prima_nota_causale_intestazione).data('iva');
    var causale_tipo = $('option:selected', prima_nota_causale_intestazione).data('tipo');



    $('.js_prima_nota_documento,.js_prima_nota_spesa').val('');

    getPrimaNotaRighe();
    // if (causale_tipo_iva != '' && causale_tipo_iva != 4) { //NO, tutte le altre mando al form sotto per il dettaglio iva
    //     console.log('TODO: decommentare qui quando si vuole ripristinare il focus automatico allo specchietto iva');
    //     //$('.js_iva_codice').focus();


    $('.js-prime_note_sezionale option:not([value=""])').removeAttr('selected').attr('disabled', 'disabled');
    $('.js-prime_note_sezionale option').first().attr('selected', 'selected').trigger('change');
    if (causale_tipo) {
        $('.js-prime_note_sezionale option').first().removeAttr('selected');
        $('.js-prime_note_sezionale option[data-tipo="' + causale_tipo + '"]').removeAttr('disabled');
        $('.js-prime_note_sezionale option[data-tipo="' + causale_tipo + '"]').first().attr('selected', 'selected').trigger('change');
    } else {
        //alert(1);
        //$('.js-prime_note_sezionale option[value=""]').removeAttr('disabled').attr('selected', 'selected').select2().trigger('change');
    }


    //$('.js-prime_note_sezionale').select2();

    switch (causale_tipo) {
        case 1: //Vendite
        case 3: //Entrate

            //Attivo autocomplete su spese
            log('Attivo autocomplete su documenti_contabilita');
            inizializzaAutocompleteDocumento();
            break;
        case 2: //Acquisti
        case 5: //Uscita
            //Attivo autocomplete su spese
            log('Attivo autocomplete su spese');
            inizializzaAutocompleteSpesa();
            break;

        default:
            //alert('TODO: causale non ancora gestita');
            break;
    }
    // }

    switch (causale_tipo_iva) {
        case 1: //IVA DARE
        case 2: //IVA AVERE
            $('.js_iva_dependent').show();
            break;
        case 3: //CORRISPETTIVI
            alert('Corrispettivi non ancora gestiti');
            break;
        case 4: //NO
            $('.js_iva_dependent').hide();
            break;

    }

    //Imposto il sezionale
    var map = false;
    mappature_causali.find(function (item) {
        if (item.prime_note_mappature_causale == prima_nota_causale_intestazione.val()) {
            map = item;
        }
    });
    if (map) {
        log('Trovata mappatura default per la causale ' + prima_nota_causale_intestazione.val());
        $('.js-prime_note_sezionale').val(map.prime_note_mappature_sezionale).trigger('change');

    }


}
var onChangeSezionaleIva = function () {

    var sezionale_id = $('[name="prime_note_sezionale"]').val();
    var data_registrazione = $('[name="prime_note_data_registrazione"]').val();
    var documento_id = $('.js_prima_nota_documento').val();

    if (!lock_automations) {
        if (sezionale_id) {
            $.ajax({
                url: base_url + 'contabilita/primanota/getProtocolloIva',
                async: false,
                type: 'post',
                dataType: 'json',
                data: {
                    date: data_registrazione,
                    sezionale: sezionale_id,
                    azienda: $('.js_prime_note_azienda').val(),
                    documento: documento_id,
                    [token_name]: token_hash
                },
                success: function (res) {
                    if (res.status) {

                        $('[name="prime_note_protocollo"]').val(res.txt);
                    }
                }
            });
        } else {
            $('[name="prime_note_protocollo"]').val('');
        }
    }
}

var initOnChangeModelloSelect = function () {
    $('.js_modello_select').on('change', function () {
        var $option_selected = $('option:selected', $(this));
        if ($option_selected.val()) {
            initPrimanotaForm(JSON.parse(atob($option_selected.data('primanota'))), true, false);
            $('.js_salva_riproponi').show();
        } else {
            $('.js_salva_riproponi').hide();
        }

    });
}