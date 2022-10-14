function getProgressivoPrimanotaGiorno() {
    var data_registrazione = $('[name="prime_note_data_registrazione"]').val();
    var azienda_id = $('[name="prime_note_azienda"]').val();
    $.ajax({
        url: base_url + 'contabilita/primanota/getProgressivoGiorno',
        async: false,
        type: 'post',
        dataType: 'json',
        data: {
            date: data_registrazione,
            azienda: azienda_id,
            [token_name]: token_hash
        },
        success: function (res) {
            if (res.status) {
                $('[name="prime_note_progressivo_giornaliero"]').val(res.txt);
            }
        }
    });
}
function getProgressivoPrimanotaAnno() {

    var data_registrazione = $('[name="prime_note_data_registrazione"]').val();
    var azienda_id = $('[name="prime_note_azienda"]').val();
    $.ajax({
        url: base_url + 'contabilita/primanota/getProgressivoAnno',
        async: false,
        type: 'post',
        dataType: 'json',
        data: {
            date: data_registrazione,
            azienda: azienda_id,
            [token_name]: token_hash
        },
        success: function (res) {
            if (res.status) {
                $('[name="prime_note_progressivo_annuo"]').val(res.txt);
            }
        }
    });
}

function popolaContoTestuale(data) {

    var codice = data.field_mastro.val();
    if (codice && data.field_conto.val()) {
        codice += '-';
        codice += data.field_conto.val();
        if (data.field_sottoconto.val()) {
            codice += '-';
            codice += data.field_sottoconto.val();
            data.field_testuale.val(codice).trigger('change');

            return codice;
        }
    }
    data.field_testuale.val('').trigger('change');

    return '';
}

function checkSommaDareAvere() {
    var somma_dare = 0;
    var somma_avere = 0;
    var righe = $('.js_riga_registrazione').not('.hidden');

    log('Verifica dare/avere');

    righe.each(function () {
        var val = $('.js_importo_dare', $(this)).val();
        if (val > 0) {
            somma_dare += parseFloat(val);
        }

        var val = $('.js_importo_avere', $(this)).val();
        if (val > 0) {
            somma_avere += parseFloat(val);
        }

    });
    somma_dare = parseFloat(somma_dare.toFixed(2));
    somma_avere = parseFloat(somma_avere.toFixed(2));
    log('Somma dare: ' + somma_dare);
    log('Somma avere: ' + somma_avere);

    $('.js_totale_dare').html(parseFloat(somma_dare).toFixed(2));
    $('.js_totale_avere').html(parseFloat(somma_avere).toFixed(2));

    $('.js_squadratura').html(parseFloat(somma_dare - somma_avere).toFixed(2));

    if (parseFloat(somma_dare - somma_avere) != 0) {
        $('.js_squadratura').addClass('warning');
    } else {
        $('.js_squadratura').removeClass('warning');
    }


    //Verifica totali righe iva
    var totale_righe_iva = 0;
    $('.js_riga_dett_iva:visible').each(function () {
        totale_righe_iva += parseFloat($('.js_iva_imponibile', $(this)).val() * 100) + parseFloat($('.js_iva_importo', $(this)).val() * 100);

        //totale_righe_iva += parseFloat($('.js_iva_importo', $(this)).val() * 100);
    });

    if (isNaN(totale_righe_iva)) {
        return;
    }



    totale_righe_iva /= 100;

    totale_righe_iva = Math.abs(totale_righe_iva);

    log('tot righe iva: ' + totale_righe_iva);

    $('.js_totale_iva_attuale').html(totale_righe_iva.toFixed(2));
    $('.js_totale_iva_atteso').html(somma_dare.toFixed(2));
    $('.js_squadratura_iva').html(parseFloat(somma_dare - totale_righe_iva).toFixed(2));

    if (parseFloat(somma_dare - totale_righe_iva) != 0) {
        $('.js_squadratura_iva').addClass('warning');
    } else {
        $('.js_squadratura_iva').removeClass('warning');
    }

    if (crea_modello_enabled ||
        (
            (somma_dare != 0 || somma_avere != 0) && somma_dare == somma_avere && (totale_righe_iva == 0 || totale_righe_iva == somma_dare)
        )
    ) {
        //$('.form-actions .btn-primary', $('#form_1822').parent()).show();
    } else {
        log('Nascondo pulsante salva');
        //$('.form-actions .btn-primary', $('#form_1822').parent()).hide();
    }


}

var reIndexRighe = function () {
    var righe = $('.js_riga_registrazione').not('.hidden');

    log('Reindicizzo le righe');
    righe.each(function (index, item) {
        var numero_riga = index + 1;
        $(this).find('.js_numero_riga').val(numero_riga);

        //Rinumero anche la chiave dell'array name="registrazioni[NUMERO]xxx" per evitare che una chiave vada poi a sovrascrivere l'altra
        $(':input', $(this)).each(function () {
            var old_name = $(this).attr('name');
            var old_name_exp = old_name.split('[')
            var new_num = '[' + numero_riga + ']';
            var new_name = old_name_exp[0] + new_num + '[' + old_name_exp[2];
            $(this).attr('name', new_name);

        }

        );

    });
}

var contiFiltrati = function (conti, codice_mastro) {
    var conti_filtrati = [];
    $.each(conti, function (index, conto) {
        if (conto.mastro == codice_mastro) {
            conti_filtrati.push({ "id": conto.id, "label": conto.label, "value": conto.value, "conto": conto.value });
        }
    });
    return conti_filtrati;
}

var sottocontiFiltrati = function (sottoconti, codice_conto, codice_mastro) {
    var sottoconti_filtrati = [];

    $.each(sottoconti, function (index, sottoconto) {
        if ((sottoconto.conto == codice_conto || sottoconto.conto == '') && sottoconto.mastro == codice_mastro) {
            sottoconti_filtrati.push({ "id": sottoconto.id, "label": sottoconto.label, "value": sottoconto.value, "sottoconto": sottoconto.value });
        }
    });
    return sottoconti_filtrati;
}


function creaNuovaRiga(autoHandlersAndEvents) {
    log("Creo nuova riga");
    var newRow = $('.js_riga_registrazione.hidden').clone();
    // var counter = (data.counter) ? data.counter : length; // Michael E. - Commento questa parte in quanto ha una logica sbagliata
    var counter = $('.js_riga_registrazione').not('.hidden').length;

    newRow.removeClass('hidden');
    if (counter) {
        $('label', newRow).remove();
    }


    $('input, select, textarea', newRow).each(function () {
        var control = $(this);
        var name = control.attr('data-name');
        if (name) {
            control.attr('name', 'registrazioni[' + counter + '][' + name + ']').removeAttr('data-name');
        }
        //control.val("");
    });
    $(':input', newRow).val('');
    $('.js_riga_registrazione:last').after(newRow);
    $('.js_causale', newRow).select2();

    if (autoHandlersAndEvents) {

        agganciaEventiRiga(newRow);

        inizializzaComponentiRiga(newRow);
    }
    console.log('Incremento il numero sul campo "numero" della riga');
    //$('.js_numero_riga', newRow).val(counter + 1);
    console.log('Riga creata. Faccio focus sulla causale.');
    //$('.js_numero_riga', newRow).focus();
    $('.js_numero_riga', newRow).focus();
    return [newRow, counter];
}

function agganciaEventiRiga($riga) {
    //On change causale
    //onChangeRigaCausale($riga); //Una volta la causale era su ogni riga, ora non più

    //On change mastro dare
    //onChangeMastroDare($riga);
    //On change conto dare
    //onChangeContoDare($riga);
    //On change sottoconto dare
    onChangeSottocontoDare($riga);
    //On change importo dare
    onChangeImportoDare($riga);

    //On change mastro avere
    //onChangeMastroAvere($riga);
    //On change conto avere
    //onChangeContoAvere($riga);
    //On change sottoconto avere
    onChangeSottocontoAvere($riga);
    //On change importo avere
    onChangeImportoAvere($riga);
}

function inizializzaComponentiRiga($riga) {
    log('TODO: qui vanno messi gli eventi autocomplete, eventuali datepicker, select2 o altro. Solo sugli elementi di questa riga.');

    // inizializzaAutocompleteDareMastro($riga);
    // inizializzaAutocompleteDareConto($riga);
    inizializzaAutocompleteDareSottoconto($riga);

    // inizializzaAutocompleteAvereMastro($riga);
    // inizializzaAutocompleteAvereConto($riga);
    inizializzaAutocompleteAvereSottoconto($riga);

    //inizializzaAutocompleteDareConto($riga);


}
var precompilaRifDoc = function () {

    var rif = ($('.js_numero_documento_orig').val()) ? $('.js_numero_documento_orig').val() : $('.js_prima_nota_numero_documento').val();
    //var expl = rif.split(' ');
    //$('.js_registrazione_rif_doc').val(expl[0]);

    $('.js_registrazione_rif_doc').val(rif);

    //debugger
}
function impostaValoriDefault($riga, data) {
    var length = $('.js_riga_registrazione').not('.hidden').length;
    var counter = (data.counter) ? data.counter : length;

    log('Imposto numero riga a ' + counter);
    //$('.js_numero_riga', $riga).val(counter);

    if (data.causale) {
        log('Imposto causale riga');
        $('.js_causale', $riga).select2('val', data.causale);
    }

    log('TODO: Una volta agganciati i vari eventi onchange, on click, on key down ecc e inzializzate le componenti, valorizzare con i data che arrivano in ingresso');
}

log = function (variable) {
    switch (typeof variable) {
        case 'string':
        case 'integer':
            //alert(variable);
            console.log(variable);
            break;
        default:
            console.log(variable);
            break;
    }

}

var distruggiAutocomplete = function (el) {
    if (el.data('ui-autocomplete') != undefined) {
        el.autocomplete('destroy').unbind('focus');
    }
}

var lock_automations = false;

var creaLabelAutocomplete = function (p) {
    // console.log(p);
    if (p.documenti_contabilita_id || p.prime_note_documento) {
        p.documenti_contabilita_data_emissione = p.documenti_contabilita_data_emissione.substr(0, 10);
        var destinatario = JSON.parse(p.documenti_contabilita_destinatario);
        if (p.documenti_contabilita_serie != null) {
            p.documenti_contabilita_numero = p.documenti_contabilita_numero + '/' + p.documenti_contabilita_serie;
        }
        log(p.documenti_contabilita_data_emissione);
        var $momentDate = moment(p.documenti_contabilita_data_emissione, 'YYYY-MM-DD');

        if (destinatario.ragione_sociale.length > 10) {
            //destinatario.ragione_sociale = destinatario.ragione_sociale.substr(0, 20) + '...'
        }

        var label = p.documenti_contabilita_numero + ' ' + $momentDate.format('DD/MM/YYYY') + ' - ' + destinatario.ragione_sociale
    } else if (p.spese_id || p.prime_note_spesa) {
        var data_scadenza = moment(p.spese_data_emissione, 'YYYY-MM-DD').format('DD/MM/YYYY');
        var mittente = JSON.parse(p.spese_fornitore);
        if (mittente.ragione_sociale.length > 10) {
            //mittente.ragione_sociale = mittente.ragione_sociale.substr(0, 10) + '...'
        }
        var label = p.spese_numero + ' del ' + data_scadenza + ' - ' + mittente.ragione_sociale
    } else if (p.prime_note_numero_documento) {
        var label = p.prime_note_numero_documento;

    } else {
        var label = '';
    }

    return label;
}

var getPrimaNotaRighe = function () {
    if (!lock_automations) {
        var prima_nota_causale_intestazione = $('.js_prime_note_causale');
        var causale_id = prima_nota_causale_intestazione.val();
        var is_fattura = ($('.js_form_prima_nota').data('documento')) ? 1 : 0;
        var modello_selezionato = $('.js_modello_select').val();

        if (is_fattura) {
            var doc_id = $('.js_prima_nota_documento').val();
        } else {
            var doc_id = $('.js_prima_nota_spesa').val();
        }
        if (!doc_id) {
            doc_id = 0;
        }
        // if (!$('option:selected', prima_nota_causale_intestazione).data('registro_iva')) {

        //     //TODO: potenzialmente getPrimaNotaRighe si potrebbe gestire anche per gli incassi con le 2 sole righe di registrazione
        //     return;
        // }

        $.ajax({
            url: base_url + 'contabilita/primanota/getPrimaNotaRighe/' + causale_id + '/' + is_fattura + '/' + doc_id + '/' + modello_selezionato,
            async: true,
            type: 'post',
            dataType: 'json',
            data: {
                [token_name]: token_hash
            },
            success: function (data) {
                console.log(data);

                if (data.warning_message) {
                    toastr.warning(data.warning_message, 'Attenzione!', { showMethod: 'fadeIn', timeOut: 5000, progressBar: true, positionClass: 'toast-top-center' });
                }

                //$('.js_riga_registrazione:visible').remove();
                $.each(data.righe, function (i, riga) {


                    var numero_riga = riga.prime_note_registrazioni_numero_riga;
                    var $nuovaRiga = $('.js_numero_riga').filter(function () {
                        return $(this).val() == numero_riga;
                    }).closest('.js_riga_registrazione');
                    if ($nuovaRiga.length == 0) {

                        var datiNuovaRiga = creaNuovaRiga(true);
                        $nuovaRiga = datiNuovaRiga[0];
                        $('.js_numero_riga', $nuovaRiga).val(numero_riga);
                    }


                    var sottoconto_avere = riga.prime_note_registrazioni_codice_avere_testuale;
                    if (!sottoconto_avere) { //Se ho il sottoconto va bene così, altrimenti cerco il conto
                        sottoconto_avere = riga.prime_note_registrazioni_conto_avere_codice;
                        if (!sottoconto_avere) { //altrimenti ancora cerco il mastro
                            sottoconto_avere = riga.prime_note_registrazioni_mastro_avere_codice;
                            if (sottoconto_avere) {
                                sottoconto_avere = sottoconto_avere + '.';
                            }
                        } else {
                            sottoconto_avere = sottoconto_avere + '.';
                        }
                    }

                    var importo_avere = (riga.prime_note_registrazioni_importo_avere) ? parseFloat(riga.prime_note_registrazioni_importo_avere).toFixed(2) : '';

                    var sottoconto_dare = riga.prime_note_registrazioni_codice_dare_testuale;
                    if (!sottoconto_dare) { //Se ho il sottoconto va bene così, altrimenti cerco il conto
                        sottoconto_dare = riga.prime_note_registrazioni_conto_dare_codice;
                        if (!sottoconto_dare) { //altrimenti ancora cerco il mastro
                            sottoconto_dare = riga.prime_note_registrazioni_mastro_dare_codice;
                            if (sottoconto_dare) {
                                sottoconto_dare = sottoconto_dare + '.';
                            }
                        } else {
                            sottoconto_dare = sottoconto_dare + '.';
                        }
                    }

                    var importo_dare = (riga.prime_note_registrazioni_importo_dare) ? parseFloat(riga.prime_note_registrazioni_importo_dare).toFixed(2) : '';

                    if (sottoconto_avere) {
                        $('.js_riga_sottoconto_avere_codice', $nuovaRiga).val(sottoconto_avere).trigger('change');
                    }
                    if (riga.prime_note_registrazioni_sottoconto_avere_descrizione) {
                        $('.js_conto_avere_descrizione', $nuovaRiga).html(riga.prime_note_registrazioni_sottoconto_avere_descrizione);
                    }
                    if (importo_avere) {
                        $('.js_importo_avere', $nuovaRiga).val(importo_avere).trigger('change');
                    }

                    if (sottoconto_dare) {
                        $('.js_riga_sottoconto_dare_codice', $nuovaRiga).val(sottoconto_dare).trigger('change');
                    }
                    if (riga.prime_note_registrazioni_sottoconto_dare_descrizione) {
                        $('.js_conto_dare_descrizione', $nuovaRiga).html(riga.prime_note_registrazioni_sottoconto_dare_descrizione);
                    }
                    if (importo_dare) {
                        $('.js_importo_dare', $nuovaRiga).val(importo_dare).trigger('change');
                    }








                });

                //log(data.righe_iva);

                precompilaRifDoc();

                popolaRigheIva(data.righe_iva, true);

                checkSommaDareAvere();

                popolaDescrizioniConti();


            }
        });
    }
}

var initPrimanotaForm = function (prima_nota, is_modello, lock) {
    $('.js_riga_registrazione:visible').remove();

    log('Edit prima nota');
    if (!is_modello) {
        log('Aggiungo hidden prima nota (verificando se esiste già un campo hidden, altrimenti lo crea)');
        if ($('#prima_nota_hidden').length > 0) {
            $('#prima_nota_hidden', $('.js_form_prima_nota')).val(prima_nota['prime_note_id']);
        } else {
            $('<input type="hidden" id="prima_nota_hidden" name="prime_note_id" value="' + prima_nota['prime_note_id'] + '" />').appendTo('.js_form_prima_nota');
        }
    }

    log('Blocco le automazioni')
    lock_automations = lock;
    // log(lock_automations);
    // alert(1);


    log('Ciclo i campi della prima nota e li setto');
    log(prima_nota);
    $.each(prima_nota, function (field_name, field_value) {
        if (field_name.startsWith('prime_note') && field_name != 'prime_note_modello') {

            if (field_name == 'prime_note_data_registrazione' && is_modello) {
                return;
            }

            // ho duplicato questa riga, togliendo il readonly sennò il numero incrementa anche durante la modifica ed è un problema
            // $('[name="' + field_name + '"]:not([readonly]):not(".select2_standard")').val(field_value);
            // $('[name="' + field_name + '"]:not(".select2_standard")').val(field_value);
            // $('[name="' + field_name + '"].select2_standard').select2('val', field_value);
            // $('[name="' + field_name + '"] option[value="' + field_value + '"]').attr('selected');
            // $('.select[name="' + field_name + '"]:not([readonly])').val(field_value); //.trigger('change');



            if ($('[name="' + field_name + '"]').parent().hasClass('date') && field_value != null) {

                var $momentDate = moment(field_value, 'YYYY-MM-DD');
                field_value = $momentDate.format('DD/MM/YYYY');
            }

            if ($('[name="' + field_name + '"]').val() != field_value) {
                /*$('[name="' + field_name + '"]:not([readonly]):not(".select2_standard")').val(field_value);
                try {

                    $('[name="' + field_name + '"].select2_standard').select2('val', field_value);
                } catch (error) {

                    $('[name="' + field_name + '"].select2_standard').val(field_value).trigger('change');
                }*/
                $('[name="' + field_name + '"]').val(field_value).trigger('change');


                if (field_name == 'prime_note_protocollo') {
                    //alert(field_value);
                }

                //$('[name="' + field_name + '"].select2_standard').val(field_value).trigger('change')
            }






        }

    });




    //if (!lock_automations) {
    onChangeSezionaleIva();
    //}

    log('Popolo il campo "Riferimento documento"');

    var labelDocumento = creaLabelAutocomplete(prima_nota);
    if (labelDocumento.length > 0) {
        $('.js_prima_nota_numero_documento').val(labelDocumento)
    }

    log('Pulisco i dettagli iva (tranne l\'elemento nascosto')


    log('Ciclo le registrazioni della prima nota');
    var registrazioni = prima_nota.registrazioni;
    $.each(registrazioni, function (index, registrazione) {
        log('Ciclo i campi della registrazione e li setto scatenando la creazione della riga dinamicamente');
        row_data = creaNuovaRiga(true);

        $.each(registrazione, function (field_name, field_value) {
            if (field_name.indexOf('prime_note_registrazioni') === 0) {
                var selector = $('[name="registrazioni[' + index + '][' + field_name + ']"]:not([readonly])');
                if (selector.hasClass('js_decimal') || selector.hasClass('js_money')) {
                    field_value = parseFloat(field_value).toFixed(2);
                }
                $('[name="registrazioni[' + index + '][' + field_name + ']"]:not([readonly])').val(field_value).trigger('change');

            }

        });

        $.each(registrazione, function (field_name, field_value) {
            var selector = $('[name="registrazioni[' + index + '][' + field_name + ']"]:not([readonly])');
            if (selector.hasClass('js_decimal') || selector.hasClass('js_money')) {
                field_value = parseFloat(field_value).toFixed(2);
            }
            selector.val(field_value);

        });
    });
    popolaDescrizioniConti();
    log('Ciclo i dettagli iva della prima nota');
    var registrazioni_iva = prima_nota.registrazioni_iva;
    var righePopolateIva = popolaRigheIva(registrazioni_iva, true);



    // $('.js_riga_dett_iva:not(.hidden)').remove();

    // $.each(registrazioni_iva, function(index, registrazione) {
    //     log('Ciclo i campi della registrazione iva e li setto scatenando la creazione della riga dinamicamente');
    //     row_data = creaNuovaRigaDettagliIva(true);

    //     $.each(registrazione, function(field_name, field_value) {
    //         // if (field_name.indexOf('prime_note_righe_iva') === 0) {
    //         var selector = $('[name="dettaglio_iva[' + index + '][' + field_name + ']"]:not([readonly])');
    //         if (selector.hasClass('js_decimal') || selector.hasClass('js_money')) {
    //             field_value = parseFloat(field_value).toFixed(2);
    //         }
    //         $('[name="dettaglio_iva[' + index + '][' + field_name + ']"]:not([readonly])').val(field_value).trigger('change');

    //         // }

    //     });

    //     $.each(registrazione, function(field_name, field_value) {
    //         var selector = $('[name="dettaglio_iva[' + index + '][' + field_name + ']"]:not([readonly])');
    //         if (selector.hasClass('js_decimal') || selector.hasClass('js_money')) {
    //             field_value = parseFloat(field_value).toFixed(2);
    //         }
    //         selector.val(field_value);

    //     });
    // });

    log('faccio un trigger change all\'ultimo campo importo avere così da scatenare l\'apparizione del bottone salva');
    $('.js_importo_avere').last().trigger('change');

    log('Sblocco le automazioni');
    lock_automations = false;

    if (is_modello) {

        $('#prime_note_data_registrazione').trigger('change');
    }
};
function makeid(length) {
    var result = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() *
            charactersLength));
    }
    return result;
}
var popolaDescrizioniConti = function () {
    $('.conto4').each(function () {
        var conto_testuale = $(this).val();
        if (conto_testuale) {
            var $input_conto = $(this);

            var id = makeid(20);
            $input_conto.attr('data-identifier', id);
            $.ajax({
                url: base_url + 'contabilita/primanota/getContoFromTestuale/' + id,
                async: true,
                type: 'post',
                dataType: 'json',
                data: {
                    conto: conto_testuale,
                    [token_name]: token_hash
                },
                success: function (res) {
                    if (res.status) {
                        $('.js_conto_descrizione', $('.conto4[data-identifier="' + res.id + '"]').parent()).html(res.conto.documenti_contabilita_sottoconti_descrizione);
                    }
                }
            });
        }

    });
}
