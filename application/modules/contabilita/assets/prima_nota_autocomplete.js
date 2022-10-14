



var inizializzaAutocompleteDareSottoconto = function ($riga) {

    var numero_riga = $('.js_numero_riga', $riga).val();

    log(numero_riga + ' ' + "Inizializzo autocomplete sottoconto dare");


    if ($('.js_riga_sottoconto_dare_codice', $riga).attr('readonly') == 'readonly') {
        log(numero_riga + ' ' + 'Distruggo autocomplete sottoconto dare in quanto readonly');

        distruggiAutocomplete($('.js_riga_sottoconto_dare_codice', $riga));
    } else {
        log(numero_riga + ' ' + 'Inizializzo autocomplete sottoconto dare');

        $('.js_riga_sottoconto_dare_codice', $riga).focusin(function () {

            $('.js_conto_dare_descrizione', $riga).html('');
            $('.js_conto_avere_descrizione', $riga).html('');

            var current_input = $(this);

            var conti_selezionati = [];
            $('.js_riga_sottoconto_avere_codice').each(function () {
                if (!$(this).is(current_input)) {
                    var codice_conto = $(this).val();
                
                    if (codice_conto) {
                        conti_selezionati.push(codice_conto);
                    }
                }
            });

            $('.js_riga_sottoconto_dare_codice').each(function () {
                if (!$(this).is(current_input)) {
                    var codice_conto = $(this).val();
                    if (codice_conto) {
                        conti_selezionati.push(codice_conto);
                    }
                }
                
            });
            //console.log(conti_selezionati);
            //alert(1);

            $('.js_riga_sottoconto_dare_codice', $riga).autocomplete({
                source: function (request, response) {
                    $.ajax({
                        method: 'post',
                        async: true,
                        url: base_url + "contabilita/primanota/autocompleteSottoconto/dare",
                        dataType: "json",
                        data: {
                            search: request.term,
                            conti_selezionati: conti_selezionati,
                            [token_name]: token_hash
                        },
                        success: function (res) {
                            var collection = [];
                            //alert(2);
                            $.each(res.data, function (index, item) {
                                collection.push({
                                    "id": item.documenti_contabilita_sottoconti_id,
                                    "label": item.documenti_contabilita_sottoconti_codice_completo + ' - ' + item.documenti_contabilita_sottoconti_descrizione,
                                    "value": item.documenti_contabilita_sottoconti_codice_completo,
                                    'data': item
                                });

                            });

                            response(collection);
                        }
                    });
                },
                open: function (e, ui) {
                    var acData = $(this).data('ui-autocomplete');
                    if (typeof acData !== 'undefined') {
                        acData
                            .menu
                            .element
                            .find('li')
                            .each(function () {
                                var me = $(this);
                                var keywords = acData.term.split(' ').join('|');
                                //log(me.text(), true);
                                me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), '<strong>$1</strong>'));
                            });
                    }

                },
                delay: 0.5,
                minLength: 0,
                selectFirst: true,
                response: function (event, ui) {
                    if (ui.content.length == 1) {
                        log(numero_riga + ' Forzo selezione su unico sottoconto dare disponibile');
                        //$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: ui.content[0] });
                    }
                },
                select: function (event, ui) {
                    log(numero_riga + ' ' + "Cliccato il valore dell'autocomplete sottoconto dare");
                    $(this).val(ui.item.value.documenti_contabilita_sottoconti_codice_completo).trigger('change');
                    $('.js_conto_dare_descrizione', $riga).html(ui.item.data.documenti_contabilita_sottoconti_descrizione);
                }
            })



        }).focusout(function () {
            log("Triggerato il focusout, distruggo l'autocomplete sottoconto dare")

            distruggiAutocomplete($('.js_riga_sottoconto_dare_codice', $riga));
        }).bind('focus', function () {
            log(numero_riga + ' ' + 'Triggero focus autocomplete sottoconto dare');
            $(this).autocomplete("search");
        });
    }
}


/**
 * 
 * AVERE
 */



var inizializzaAutocompleteAvereSottoconto = function ($riga) {


    var numero_riga = $('.js_numero_riga', $riga).val();

    log(numero_riga + ' ' + "Inizializzo autocomplete sottoconto avere");
    var conto_codice = $('.js_riga_conto_avere_codice', $riga).val();
    var mastro_codice = $('.js_riga_mastro_avere_codice', $riga).val();


    // michael e. - Non riesco a capire per quale motivo continua ad entrare nell'else, nonostante i campi siano readonly
    if ($('.js_riga_sottoconto_avere_codice', $riga).attr('readonly') == 'readonly') {
        log(numero_riga + ' ' + 'Distruggo autocomplete sottoconto avere in quanto readonly');

        distruggiAutocomplete($('.js_riga_sottoconto_avere_codice', $riga));
    } else {
        log(numero_riga + ' ' + 'Inizializzo autocomplete sottoconto avere');

        $('.js_riga_sottoconto_avere_codice', $riga).focusin(function () {
            $('.js_conto_dare_descrizione', $riga).html('');
            $('.js_conto_avere_descrizione', $riga).html('');

            var current_input = $(this);

            var conti_selezionati = [];
            $('.js_riga_sottoconto_avere_codice').each(function () {
                if (!$(this).is(current_input)) {
var codice_conto = $(this).val();
                    //alert(codice_conto);
                if (codice_conto) {
                    conti_selezionati.push(codice_conto);
                }
                }
                
                
            });

            $('.js_riga_sottoconto_dare_codice').each(function () {
                if (!$(this).is(current_input)) {
                    var codice_conto = $(this).val();
                    if (codice_conto) {
                        conti_selezionati.push(codice_conto);
                    }
                }
                
            });
            $('.js_riga_sottoconto_avere_codice', $riga).autocomplete({
                source: function (request, response) {
                    $.ajax({
                        method: 'post',
                        async: true,
                        url: base_url + "contabilita/primanota/autocompleteSottoconto/avere",
                        dataType: "json",
                        data: {
                            search: request.term,
                            conti_selezionati: conti_selezionati,
                            [token_name]: token_hash
                        },
                        success: function (res) {
                            var collection = [];

                            $.each(res.data, function (index, item) {
                                collection.push({
                                    "id": item.documenti_contabilita_sottoconti_id,
                                    "label": item.documenti_contabilita_sottoconti_codice_completo + ' - ' + item.documenti_contabilita_sottoconti_descrizione,
                                    "value": item.documenti_contabilita_sottoconti_codice_completo,
                                    'data': item
                                });

                            });

                            response(collection);
                        }
                    });
                },
                delay: 0.5,
                minLength: 0,
                selectFirst: true,
                response: function (event, ui) {
                    if (ui.content.length == 1) {
                        log(numero_riga + ' Forzo selezione su unico sottoconto avere disponibile');
                        //$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: ui.content[0] });
                    }
                },
                select: function (event, ui) {
                    log(numero_riga + ' ' + "Cliccato il valore dell'autocomplete sottoconto avere");
                    $(this).val(ui.item.value.documenti_contabilita_sottoconti_codice_completo).trigger('change');
                    $('.js_conto_avere_descrizione', $riga).html(ui.item.data.documenti_contabilita_sottoconti_descrizione);
                }
            }).bind('focus', function () {
                log(numero_riga + ' ' + 'Triggero focus autocomplete sottoconto avere');
                $(this).autocomplete("search");
            });
        }).focusout(function () {
            log("Triggerato il focusout, distruggo l'autocomplete sottoconto avere")

            distruggiAutocomplete($('.js_riga_sottoconto_avere_codice', $riga));
        });
    }
}

var inizializzaAutocompleteDocumento = function () {

    $('.js_prima_nota_numero_documento').add('[name="prime_note_scadenza"]').add('[name="prime_note_documento"]').val('').trigger('change');

    var autocomplete_running = false;
    $('.js_prima_nota_numero_documento').on('change', function () {
        if (autocomplete_running != true) {//Trick per fare in modo che se non clicco sull'autocomplete, prenda quello che scrivo. Se clicco sull'autocomplete ci mette invece il numero del documento
            $('.js_numero_documento_orig').val($(this).val());
        }

    });
    $('.js_prima_nota_numero_documento').focusin(function () {
        log('Inizializzo autocomplete documento');
        $(this).autocomplete({
            source: function (request, response) {
                log('Faccio chiamata AJAX per ottenere i documenti tramite ricerca');
                $.ajax({
                    url: base_url + 'contabilita/primanota/autocompleteDocumento',
                    async: true,
                    dataType: 'json',
                    data: {
                        q: request.term,
                        [token_name]: token_hash
                    },
                    type: 'post',
                    success: function (data) {

                        var collection = [];

                        log('Verifico se ci sono documenti nel response');
                        if (data.status == 1 && data.txt.length > 0) {
                            log('Ci sono, quindi li ciclo e creo l\'oggetto per l\'autocomplete');
                            $.each(data.txt, function (i, p) {
                                var label = creaLabelAutocomplete(p);

                                collection.push({
                                    "id": p.documenti_contabilita_id,
                                    "label": label,
                                    "value": label,
                                    "documento": p
                                });
                            });
                        } else {
                            log('Non ce ne sono, perciò creo un "finto" oggetto per l\'autocomplete');
                            collection.push({
                                "id": null,
                                "label": request.term,
                                "value": null
                            });
                        }

                        response(collection);
                    }
                });
            },
            delay: 300,
            minLength: 2,
            // selectFirst: true,
            response: function (event, ui) {
                // if (ui.content.length == 1) {
                //     log('C\'è solo un elemento nell\'oggetto autocomplete, perciò lo triggero direttamente');
                //     $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: { value: ui.content[0].value } });
                // }
            },
            select: function (event, ui) {

                autocomplete_running = true;
                if (ui.item.id) {
                    $('.prime_note_documento').val(ui.item.id);
                    $('.js_numero_documento_orig').val(ui.item.documento.documenti_contabilita_numero);
                    if (ui.item.documento.prime_note_id) {
                        //alert('Per questo documento esiste già una prima nota registrata!');
                        toastr.warning('Per questo documento esiste già una prima nota registrata!', 'Attenzione!', { showMethod: 'fadeIn', timeOut: 5000, progressBar: true, positionClass: 'toast-top-center' });
                    }

                    log('Se c\'è un documento valido selezionato, inserisco l\'id nel campo hidden');
                    $('.js_prima_nota_documento').val(ui.item.id).trigger('change');

                    var data_scadenza = moment(ui.item.documento.documenti_contabilita_data_emissione, 'YYYY-MM-DD').format('DD/MM/YYYY');

                    $('[name="prime_note_scadenza"]').val(data_scadenza).attr('readonly', 'readonly').trigger('change');
                    $('[name="prime_note_data_registrazione"]').val(data_scadenza).trigger('change');
                    $('.js_form_prima_nota').data('spesa', false);
                    $('.js_form_prima_nota').data('documento', ui.item.documento);
                    $('.js_modale_spesa').remove();
                    $('.js_modale_documento').remove();
                    $('.js_riferimento_documento').append('<a style="margin: 0 5px;font-size: 12px;line-height: 0.5;" class="js_modale_documento js-action_button js_open_modal btn btn-info btn-grid-action-s" href="' + base_url + '/get_ajax/layout_modal/contabilita_dettaglio_documento/' + ui.item.id + '?_size=extra"><span class= "fas fa-eye fa-fw" ></span></a>');

                    if (!lock_automations) {
                        onChangeSezionaleIva();
                        if ($('[name="prime_note_id"]').val()) {
                            //In modifica non ricarico le righe, ma tengo quelle precedentemente inserite
                        } else {
                            getPrimaNotaRighe();
                        }

                    }
                    //precompilaRifDoc();
                }

                $(this).val(ui.item.value).trigger('change');
                autocomplete_running = false;


            }
        }).bind('focus', function () {
            $(this).autocomplete("search");
        });
    }).focusout(function () {
        distruggiAutocomplete($('.js_prima_nota_numero_documento'));
    });
}
var spesa_selezionata = function (item) {
    lock_automations = false;

    if (typeof item === 'string') {
        item = JSON.parse(atob(item));
    }
    log('Se c\'è una spesa valida selezionata, inserisco l\'id nel campo hidden');
    $('.js_prima_nota_spesa').val(item.id).trigger('change');

    var data_scadenza = moment(item.spesa.spese_data_emissione).format('DD/MM/YYYY');

    $('[name="prime_note_scadenza"]').val(data_scadenza).attr('readonly', 'readonly').trigger('change');
    $('[name="prime_note_data_registrazione"]').val(data_scadenza).trigger('change');

    //Mi salvo i dati del documento così da poterli ripescare dopo
    $('.js_form_prima_nota').data('documento', false);
    $('.js_form_prima_nota').data('spesa', item.spesa);
    $('.js_modale_documento').remove();
    $('.js_modale_spesa').remove();
    $('.js_riferimento_documento').append('<a style="margin: 0 5px;font-size: 12px;line-height: 0.5;" class="js_modale_spesa js-action_button js_open_modal btn btn-info btn-grid-action-s" href="' + base_url + '/get_ajax/layout_modal/dettaglio-spesa-modale/' + item.id + '?_size=extra"><span class= "fas fa-eye fa-fw" ></span></a>');

    getPrimaNotaRighe();

    var label = creaLabelAutocomplete(item.spesa);
    $('.js_prima_nota_numero_documento').val(label).trigger('change');


}

var documento_selezionato = function (item) {

    if (typeof item === 'string') {
        item = JSON.parse(atob(item));
    }
    log('Se c\'è un documento valido selezionato, inserisco l\'id nel campo hidden');
    $('.js_prima_nota_documento').val(item.id).trigger('change');

    var data_scadenza = moment(item.documento.documenti_contabilita_data_emissione).format('DD/MM/YYYY');

    $('[name="prime_note_scadenza"]').val(data_scadenza).attr('readonly', 'readonly').trigger('change');
    $('[name="prime_note_data_registrazione"]').val(data_scadenza).trigger('change');

    //Mi salvo i dati del documento così da poterli ripescare dopo
    $('.js_form_prima_nota').data('spesa', false);
    $('.js_form_prima_nota').data('documento', item.documento);
    $('.js_modale_documento').remove();
    $('.js_modale_spesa').remove();
    $('.js_riferimento_documento').append('<a style="margin: 0 5px;font-size: 12px;line-height: 0.5;" class="js_modale_spesa js-action_button js_open_modal btn btn-info btn-grid-action-s" href="' + base_url + '/get_ajax/layout_modal/contabilita_dettaglio_documento/' + item.id + '?_size=extra"><span class= "fas fa-eye fa-fw" ></span></a>');

    var serie = item.documento.documenti_contabilita_serie;
    onChangeSezionaleIva();
    $('.js-prime_note_sezionale').val($('.js-prime_note_sezionale option[data-sezionale="' + serie + '"]').val()).select2();
    getPrimaNotaRighe();

    var label = creaLabelAutocomplete(item.documento);
    $('.js_prima_nota_numero_documento').val(label).trigger('change');
}

var inizializzaAutocompleteSpesa = function () {
    $('.js_prima_nota_numero_documento').add('[name="prime_note_scadenza"]').add('[name="prime_note_documento"]').val('').trigger('change');

    var autocomplete_running = false;
    $('.js_prima_nota_numero_documento').on('change', function () {

        if (autocomplete_running != true) {//Trick per fare in modo che se non clicco sull'autocomplete, prenda quello che scrivo. Se clicco sull'autocomplete ci mette invece il numero del documento

            $('.js_numero_documento_orig').val($(this).val());
        }

    });

    $('.js_prima_nota_numero_documento').focusin(function () {
        log('Inizializzo autocomplete documento');

        $(this).autocomplete({
            source: function (request, response) {
                log('Faccio chiamata AJAX per ottenere le spese tramite ricerca');
                $.ajax({
                    url: base_url + 'contabilita/primanota/autocompleteSpesa',
                    async: true,
                    dataType: 'json',
                    data: {
                        q: request.term,
                        [token_name]: token_hash
                    },
                    type: 'post',
                    success: function (data) {

                        var collection = [];

                        log('Verifico se ci sono spese nel response');
                        if (data.status == 1 && data.txt.length > 0) {
                            log('Ci sono, quindi li ciclo e creo l\'oggetto per l\'autocomplete');



                            $.each(data.txt, function (i, p) {

                                var label = creaLabelAutocomplete(p);

                                collection.push({
                                    "id": p.spese_id,
                                    "label": label,
                                    "value": label,
                                    "spesa": p
                                });
                            });
                        } else {
                            log('Non ce ne sono, perciò creo un "finto" oggetto per l\'autocomplete');
                            collection.push({
                                "id": null,
                                "label": request.term,
                                "value": null
                            });
                        }

                        response(collection);
                    }
                });
            },
            delay: 300,
            minLength: 1,
            // selectFirst: true,
            response: function (event, ui) {
                // if (ui.content.length == 1) {
                //     log('C\'è solo un elemento nell\'oggetto autocomplete, perciò lo triggero direttamente');



                //     $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: { value: ui.content[0].value } });
                // }
            },
            select: function (event, ui) {
                autocomplete_running = true;
                if (ui.item.id) {
                    $('.prime_note_spesa').val(ui.item.id);
                    $('.js_numero_documento_orig').val(ui.item.spesa.spese_numero);
                    if (ui.item.spesa.prime_note_id) {
                        //alert('Per questo documento esiste già una prima nota registrata!');
                        toastr.warning('Per questo documento esiste già una prima nota registrata!', 'Attenzione!', { showMethod: 'fadeIn', timeOut: 5000, progressBar: true, positionClass: 'toast-top-center' });
                    }
                    spesa_selezionata(ui.item);

                }

                $(this).val(ui.item.value).trigger('change');
                autocomplete_running = false;
            }
        }).bind('focus', function () {
            $(this).autocomplete("search");
        });
    }).focusout(function () {
        distruggiAutocomplete($('.js_prima_nota_numero_documento'));
    });
}