function agganciaEventiRigaIva($riga) {
    onChangeAll($riga);
    onChangeIvaSelect($riga);
    onClickPulsanteConferma();
}

function inizializzaComponentiRigaIva($riga) {
    log('Inizializzo componenti riga iva');

    inizializzaAutocompleteIva($riga);
    //$('.js_iva_codice', $riga).select2();
}
var calcolaIndetraibilita = function () {
    $('.js_riga_dett_iva:visible').each (function () {
        $riga = $(this);
        calcolaIndetraibilitaRiga($riga);
    });
}
var calcolaIndetraibilitaRiga = function ($riga, lock_iva) {
    
        
        var valore_imponibile = ($('.js_iva_imponibile', $riga).val()) ?? 0;
        if (!valore_imponibile) {
            valore_imponibile = 0;
            $('.js_iva_imponibile', $riga).val(valore_imponibile);
        }
        valore_imponibile = parseFloat(valore_imponibile);

        var iva_valore_percentuale = $('.js_iva_valore', $riga).val();
        if (!iva_valore_percentuale) {
            return;
        }   
        
        iva_valore_percentuale = parseFloat(iva_valore_percentuale);

        var $campo_iva_importo = $('.js_iva_importo', $riga);
        var valore_iva = $campo_iva_importo.val();

        if (!valore_iva || !lock_iva) { // Se non ho messo l'iva, la calcolo
            
            var valore_iva = (valore_imponibile / 100) * iva_valore_percentuale;
            //alert(valore_iva);
        }
        valore_iva = parseFloat(valore_iva);
        // if (valore_imponibile < 0 && $campo_iva_importo.val() > 0) {
        //     var importo_iva_negativo = -($campo_iva_importo.val());
        //     $campo_iva_importo.val(importo_iva_negativo);
        // }

        var $campo_iva_importo_indetrabile = $('.js_iva_importo_indetraibile', $riga);
        var $campo_iva_imponibile_indetrabile = $('.js_iva_imponibile_indetraibile', $riga);
        
        $campo_iva_importo.val(valore_iva.toFixed(2));

        var iva_detr_perc = parseFloat($('.js_iva_perc_indet', $riga).val());
        var iva_indetraibile = valore_iva / 100 * (iva_detr_perc);
        var imponibile_indetraibile = valore_imponibile / 100 * (iva_detr_perc);
        $campo_iva_importo_indetrabile.val(iva_indetraibile.toFixed(2));

        $campo_iva_imponibile_indetrabile.val(imponibile_indetraibile.toFixed(2));

        
    
}

var ricalcoloIva = function ($riga, iva_lock) {
    return;

    //TODO: Ricalcolare l'iva non facendo /100x22 ma prendnendo il totale documento e sottraendo l'imponibile...
    console.trace();
    //setTimeout(function () { //Ho dovuto mettere un timeout perchè le select2 triggerano il change prima di aver valorizzato la select vera e propria, oppure, abbiamo messo da qualche parte un trigger change prima che sia effettivamente valorizzata la select2...non so bene, ma così funziona, anche se con un piccolo delay
    //Calcolo valore iva
    var valore_imponibile = $('.js_iva_imponibile', $riga).val();


    var iva_valore = $('.js_iva_valore', $riga).val();
    //alert(iva_valore);
    var valore_iva = parseFloat((valore_imponibile / 100) * iva_valore);
    var $campo_iva_importo = $('.js_iva_importo', $riga);
    // if (valore_imponibile < 0 && $campo_iva_importo.val() > 0) {
    //     var importo_iva_negativo = -($campo_iva_importo.val());
    //     $campo_iva_importo.val(importo_iva_negativo);
    // }




    var $campo_iva_importo_indetrabile = $('.js_iva_importo_indetraibile', $riga);
    var $campo_iva_imponibile_indetrabile = $('.js_iva_imponibile_indetraibile', $riga);

    if (iva_lock) {
        valore_iva = $campo_iva_importo.val();

    } else {
        $campo_iva_importo.val(valore_iva.toFixed(2));
    }



    var iva_detr_perc = parseFloat($('.js_iva_perc_indet', $riga).val());
    var iva_indetraibile = valore_iva / 100 * (iva_detr_perc);
    var imponibile_indetraibile = valore_imponibile / 100 * (iva_detr_perc);
    $campo_iva_importo_indetrabile.val(iva_indetraibile.toFixed(2));


    $campo_iva_imponibile_indetrabile.val(imponibile_indetraibile.toFixed(2));

    //Lo imposto sul campo iva

    //Abilito pulsante per portare sopra tutti i dati o procedere con nuovi inserimenti iva
    //checkSommaDareAvere();
    //Tento di ricalcolare l'iva per far battere i conti, forzando l'ultima riga al js_totale_iva_atteso - js_totale_iva_attuale
    var iva_attesa = parseFloat($('.js_totale_iva_atteso').html());
    var iva_attuale = parseFloat($('.js_totale_iva_attuale').html());

    var totale = parseFloat($('.js_totale_dare').html());



    var differenza = Math.abs(iva_attesa) - Math.abs(iva_attuale);
    log('differenza iva: ' + differenza);
    if (differenza > 0) {
        var somma_imponibili = 0;
        $('.js_iva_imponibile:visible').not(':last').each(function () {
            somma_imponibili += parseFloat($(this).val());
        });
        var somma_iva = 0;
        $('.js_iva_importo:visible').not(':last').each(function () {
            somma_iva += parseFloat($(this).val());
        });


        var totale_mancante = totale - (somma_iva + somma_imponibili);

        if ($('.js_riga_dett_iva .js_numero_riga_iva').last().val() == $('.js_riga_dett_iva .js_numero_riga_iva:visible').first().val()) {
            //$('#js_add_riga_iva').trigger('click');
        }

        var $ultima_riga_iva = $('.js_riga_dett_iva').last();
        var perc_iva = parseInt($('.js_iva_valore', $ultima_riga_iva).val());

        var iva_mancante = totale_mancante / (100 + perc_iva) * perc_iva;

        var causale = $('.js_prime_note_causale option:selected');
        if (causale.data('mappatura_identifier') == 'modello_nota_di_credito_vendita_ita' || causale.data('mappatura_identifier') == 'modello_nota_di_credito_acquisto_ita') {
            $('.js_iva_importo', $ultima_riga_iva).val(-(iva_mancante.toFixed(2)));
            $('.js_iva_imponibile', $ultima_riga_iva).val(-((totale_mancante - iva_mancante).toFixed(2)));
        } else {
            $('.js_iva_importo', $ultima_riga_iva).val(iva_mancante.toFixed(2));
            $('.js_iva_imponibile', $ultima_riga_iva).val((totale_mancante - iva_mancante).toFixed(2));
        }




        $('#js_save_iva').trigger('click');

        checkSommaDareAvere();

        popupSave();
    } else if (differenza < 0) {
        popupSave();
    } else {
        $('#js_save_iva').trigger('click');
    }

    //}, 300);

}

function onChangeAll($riga) {
    // var campo_iva = $('.js_iva_importo', $riga);

    // campo_iva.on('change', function () {
    //     ricalcoloIva($riga);
    // });
    $(':input', $riga).on('change', function () {

        //ricalcoloIva($riga);
        if ($(this).hasClass('js_iva_importo')) {//Ho cambiato l'iva quindi non devo forzare il ricalcolo
            calcolaIndetraibilitaRiga($riga, true);
        } else { //Ho cambiato aliquota o imponibile
            calcolaIndetraibilitaRiga($riga, false);
        }
        
    });
}
function onChangeIvaSelect($riga) {
    // var campo_imponibile = $('.js_iva_imponibile', $riga);

    // campo_imponibile.on('change', function () {
    //     ricalcoloIva($riga);
    // });

    $('.js_iva_codice', $riga).on('change', function () {
        if (!lock_automations) {

            ricalcoloIva($riga);
        }



    });

    $(':input,.js_iva_codice', $riga).on('change', function () {

        checkSommaDareAvere();
        checkSave();

    });
}
var creaNuovaRigaDettagliIva = function (autoHandlersAndEvents) {
    log('Creo nuova riga iva');
    var newRow = $('.js_riga_dett_iva.hidden').clone();

    log('Conteggio quante righe dettaglio iva ci sono');
    var counter = $('.js_riga_dett_iva').not('.hidden').length;
    log('trovate ' + counter + ' righe iva')

    log('Rimuovo la classe hidden alla nuova riga dettaglio iva');
    newRow.removeClass('hidden');

    log('sistemo gli attributi name agli elementi della nuova riga dettaglio iva')
    $('input, select, textarea', newRow).each(function () {
        var control = $(this);
        var name = control.attr('data-name');
        if (name) {
            control.attr('name', 'dettaglio_iva[' + counter + '][' + name + ']').removeAttr('data-name');
        }
        //control.val("");
    });

    log('Inserisco la nuova riga sotto l\'ultima dettaglio iva')
    $('.js_riga_dett_iva:last').after(newRow);

    if (autoHandlersAndEvents) {
        inizializzaComponentiRigaIva(newRow);
        agganciaEventiRigaIva(newRow);
        
    }

    log('Incremento di 1 l\'attuale riga dettaglio iva');
    $('.js_numero_riga_iva', newRow).val(counter + 1);

    log('faccio il focus sulla riga dettaglio iva appena creata')
    $('.js_numero_riga_iva', newRow).focus();
    //alert('TODO: ripristino?');
    $('.js_iva_codice', newRow).trigger('change');

    log('=============================');

    return [newRow, counter];
}
var checkSave = function () {
    var show = true;
    $('.js_riga_dett_iva:visible').each(function () {
        var $riga = $(this);
        if (!$('.js_iva_codice', $riga).val() || !$('.js_iva_importo', $riga).val()) {
            show = false;

        }
    });
    if (show) {
        log('faccio apparire pulsante conferma');
        $('#js_save_iva').show();
    } else {
        log('nascondo pulsante conferma');
        $('#js_save_iva').hide();
    }
    return show;
}
var popupSave = function () {
    if (checkSave()) {
        log('TODO: popup per conferma inserimento');
        // x = confirm('Vuoi riportare i dati in alto, nelle registrazioni?');

        // if (x) {
        $('#js_save_iva').trigger('click');
        // } else {
        //     return false;
        // }
    }
}

var onClickPulsanteConferma = function () {
    return;
    $('#js_save_iva').on('click', function () {
        //In base alla causale, metto iva e imponibile nel posto giusto. Mi baso semplicemente sui campi dare o avere abilitati
        var prima_nota_causale_intestazione = $('.js_prime_note_causale');
        var causale_tipo_iva = $('option:selected', prima_nota_causale_intestazione).data('iva');
        var imponibile_totale = 0;
        var iva_totale = 0;
        var somma = 0;

        //eventuali indetraibilità devono andare nell'imponibile e non nell'iva...

        $('.js_riga_dett_iva:visible').each(function () {
            iva_perc = $('.js_iva_valore', $(this));
            imponibile = parseFloat($('.js_iva_imponibile', $(this)).val() * 100);
            iva_importo = parseFloat($('.js_iva_importo', $(this)).val() * 100);
            iva_detr_perc = parseFloat($('.js_iva_perc_indet', $(this)).val());
            iva_detraibile = iva_importo / 100 * (100 - iva_detr_perc);
            iva_totale += iva_detraibile;
            //log('iva detraibile: ' + iva_detraibile);
            imponibile_totale += ((iva_importo - iva_detraibile) + imponibile);
            somma += imponibile + iva_importo;
        });
        imponibile_totale /= 100;
        iva_totale /= 100;
        somma /= 100;

        //alert(causale_tipo_iva);
        switch (causale_tipo_iva) {
            case 1: //DARE
                //Prendo l'avere con la stessa causale e metto la somma di imponibile e iva
                var causale_uguale = $('.js_causale option[value="' + prima_nota_causale_intestazione.val() + '"]:selected');
                log('Imposto l\'importo avere totale');
                log(causale_uguale);
                $('.js_importo_avere:not(readonly)', causale_uguale.closest('.js_riga_registrazione')).val(somma.toFixed(2)).trigger('change');

                //Cerco riga iva DARE
                $('.js_importo_dare:not([readonly])').each(function () {
                    var riga = $(this).closest('.js_riga_registrazione');

                    var causale_iva = $('.js_causale option[data-iva="1"]:selected', riga);
                    if (causale_iva.length > 0) {
                        //alert('imposto iva');
                        //Sono nella riga giusta per impostare l'iva
                        $(this).val(iva_totale.toFixed(2)).trigger('change');
                    } else {
                        //alert('imposto imponibile');
                        //Cerco la riga imponibile DARE
                        var causale_imponibile = $('.js_causale option[data-iva="4"]:selected', riga);
                        if (causale_imponibile.length > 0) {
                            //Sono nella riga giusta per impostare l'imponibile
                            $(this).val(imponibile_totale.toFixed(2)).trigger('change');
                        }
                    }

                });

                break;
            case 2: //AVERE
                var causale_uguale = $('.js_causale option[value="' + prima_nota_causale_intestazione.val() + '"]:selected');
                log('Imposto l\'importo dare totale');
                log(causale_uguale);
                $('.js_importo_dare:not(readonly)', causale_uguale.closest('.js_riga_registrazione')).val(somma.toFixed(2)).trigger('change');

                //Cerco riga iva DARE
                $('.js_importo_avere:not([readonly])').each(function () {
                    var riga = $(this).closest('.js_riga_registrazione');

                    var causale_iva = $('.js_causale option[data-iva="2"]:selected', riga);
                    if (causale_iva.length > 0) {
                        //alert('imposto iva');
                        //Sono nella riga giusta per impostare l'iva
                        $(this).val(iva_totale.toFixed(2)).trigger('change');
                    } else {
                        //alert('imposto imponibile');
                        //Cerco la riga imponibile DARE
                        var causale_imponibile = $('.js_causale option[data-iva="4"]:selected', riga);
                        if (causale_imponibile.length > 0) {
                            //Sono nella riga giusta per impostare l'imponibile
                            $(this).val(imponibile_totale.toFixed(2)).trigger('change');
                        }
                    }

                });
                break;
            case 3: //CORRISPETTIVI
                alert('Corrispettivi non ancora gestiti...');
                break;

            default:
                //alert('What...');
                log('Causale tipo iva "' + causale_tipo_iva + '" non gestito.');
                break;
        }
    });
}

var reIndexRigheDettagliIva = function () {
    i = 1;
    $('.js_riga_dett_iva:visible').each(function () {
        var container = $(this);

        $('.js_numero_riga_iva', container).val(i)

        i++;
    });
}

var checkDettaglioIvaInsert = function () {
    //TODO
}
var popolaRigheIva = function (righe, lock_values) {
    // log(righe);
    //alert(1);
    $('.js_riga_dett_iva:not(.hidden)').remove();
    //alert(2);
    // console.log(righe);
    // alert(1);
    $.each(righe, function (i, riga) {

        if (Math.abs(riga.prime_note_righe_iva_imponibile) > 0) {

        } else {
            riga.prime_note_righe_iva_imponibile = riga.prime_note_righe_iva_totale - riga.prime_note_righe_iva_importo_iva;
        }

        log(riga);

        var datiNuovaRigaIva = creaNuovaRigaDettagliIva(true);

        var $nuovaRiga = datiNuovaRigaIva[0];
        var numero_riga = riga.prime_note_righe_iva_riga;

        $('.js_numero_riga_iva', $nuovaRiga).val(numero_riga);
        if (riga.iva_id) {

        } else {
            console.log('chiave: ' + i);
            //Trovo la prima iva con quella percentuale
            log(riga);
            var natura = riga.prime_note_righe_iva_natura;
            var iva_id = $('.js_iva_codice option[data-perc="' + i + '"][data-natura="' + natura + '"]', $nuovaRiga).val();
            log('iva: ' + i + ', iva_id: ' + iva_id);
            riga.iva_id = iva_id;


        }


        // console.log(riga.prime_note_righe_iva_importo_iva);
        // console.log(parseFloat(riga.prime_note_righe_iva_importo_iva));
        // console.log(parseFloat(riga.prime_note_righe_iva_importo_iva).toFixed(2));
        //alert(riga.prime_note_righe_iva_imponibile);
        $('.js_iva_codice', $nuovaRiga).val(riga.iva_id).trigger('change');
        $('.js_iva_imponibile', $nuovaRiga).val(parseFloat(riga.prime_note_righe_iva_imponibile).toFixed(2));
        $('.js_iva_importo', $nuovaRiga).val(parseFloat(riga.prime_note_righe_iva_importo_iva).toFixed(2));

        console.log(riga.prime_note_righe_iva_imponibile_indet);
        console.log(parseFloat(riga.prime_note_righe_iva_imponibile_indet));
        console.log(parseFloat(riga.prime_note_righe_iva_imponibile_indet).toFixed(2));


        $('.js_iva_imponibile_indetraibile', $nuovaRiga).val(parseFloat(riga.prime_note_righe_iva_imponibile_indet).toFixed(2));
        $('.js_iva_importo_indetraibile', $nuovaRiga).val(parseFloat(riga.prime_note_righe_iva_iva_valore_indet).toFixed(2));


        if (riga.prime_note_righe_iva_indetraibilie_perc) {

        } else {
            riga.prime_note_righe_iva_indetraibilie_perc = 0;
        }
        $('.js_iva_perc_indet', $nuovaRiga).val(parseFloat(riga.prime_note_righe_iva_indetraibilie_perc).toFixed(2));

        //Triggero il change solo se lock_values non è definito o false (es.: è true qquando mi arrivano i dati da un pulsante modifica e quindi non devo cambiare nulla di quello che era stato salvato!)
        if (!lock_values) {

            $('.js_iva_codice', $nuovaRiga).trigger('change');
            $('.js_iva_importo', $nuovaRiga).trigger('change');
        }

    });



}