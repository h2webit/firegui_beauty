var data = [
    ['Jazz', 'Honda', '2019-02-12', '', true, '$ 2.000,00', '#777700'],
    ['Civic', 'Honda', '2018-07-11', '', true, '$ 4.000,01', '#007777'],
];
jspreadsheet(document.getElementById('spreadsheet'), {
    data: data,
    columns: [{
        type: 'text',
        title: 'Car',
        width: 120
    },
    {
        type: 'dropdown',
        title: 'Make',
        width: 200,
        source: ["Alfa Romeo", "Audi", "Bmw"]
    },
    {
        type: 'calendar',
        title: 'Available',
        width: 200
    },
    {
        type: 'image',
        title: 'Photo',
        width: 120
    },
    {
        type: 'checkbox',
        title: 'Stock',
        width: 80
    },
    {
        type: 'numeric',
        title: 'Price',
        width: 100,
        mask: '$ #.##,00',
        decimal: ','
    },
    {
        type: 'color',
        width: 100,
        render: 'square',
    }
    ]
});

function getProgressivoPrimanota() {
    var data_registrazione = $('[name="prime_note_data_registrazione"]').val();

    $.ajax({
        url: base_url + 'contabilita/primanota/getprogressivo',
        async: false,
        type: 'post',
        dataType: 'json',
        data: {
            date: data_registrazione,
            [token_name]: token_hash
        },
        success: function (res) {
            if (res.status) {
                $('[name="prime_note_numero"]').val(res.txt);
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

    console.log('Verifica dare/avere');

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
    console.log('Somma dare: ' + somma_dare);
    console.log('Somma avere: ' + somma_avere);
    if (somma_dare != 0 && somma_avere != 0 && somma_dare == somma_avere) {
        $('#form_1822 .form-actions .btn-primary').show();
    } else {
        $('#form_1822 .form-actions .btn-primary').hide();
    }
}

function contiFiltrati(ui) {
    var conti_filtrati = [];
    var codice_mastro = ui.item.value;

    $.each(conti, function (index, conto) {
        if (conto.mastro == codice_mastro) {
            conti_filtrati.push({ "id": conto.id, "label": conto.label, "value": conto.value, "conto": conto.value });
        }
    });

    return conti_filtrati;
}

function sottocontiFiltrati(ui) {
    var sottoconti_filtrati = [];
    var codice_conto = ui.item.value;

    $.each(sottoconti, function (index, sottoconto) {
        if (sottoconto.conto == codice_conto) {
            sottoconti_filtrati.push({ "id": sottoconto.id, "label": sottoconto.label, "value": sottoconto.value });
        }
    });

    return sottoconti_filtrati;
}

$(() => {
    //Rimozione pulsante cancella
    $('#form_1822 .form-actions .btn-default').remove();
    $('#form_1822 .form-actions .btn-primary').hide();
    //click TAB button on last element will trigger a new row
    //console.log($('[name="prime_note_scadenza"]'))

    //Se clicco tab su scadenza, scateno creazione riga automatico
    $('[name="prime_note_scadenza"]').keydown(function (e) {
        var code = e.keyCode || e.which;
        if (code === 9) {
            e.preventDefault();
            $("#js_add_riga").trigger("click");
        }
    });

    var form_container = $('#form_1822');

    $('#prime_note_data_registrazione', form_container).on('change', function (i, el) {
        getProgressivoPrimanota();
    })

    $('#prime_note_data_registrazione', form_container).trigger('change');

    var pulsante_nuova_riga = $('#js_add_riga');

    pulsante_nuova_riga.keyup(function (e) {
        var code = e.keyCode || e.which;
        if (code === 9) {
            e.preventDefault();
            $("#js_add_riga").trigger("click");
        }
    });

    function creaNuovaRiga() {
        var newRow = $('.js_riga_registrazione.hidden').clone();

        var counter = $('.js_riga_registrazione').not('.hidden').length;

        $('.js_numero_riga', newRow).val(counter + 1);

        newRow.removeClass('hidden');
        $('input, select, textarea', newRow).each(function () {
            var control = $(this);
            var name = control.attr('data-name');
            if (name) {
                control.attr('name', 'registrazioni[' + counter + '][' + name + ']').removeAttr('data-name');
            }
            //control.val("");
        });


        $('.js_riga_registrazione:last').after(newRow);
        $('.js_causale', newRow).select2();

        return [newRow, counter];
    }

    /*******************************************************************************************************************/
    //Portare qui tutti gli onchange con la logica del tipo $('body').on('change', '.conto2', function () {FUNZIONE DI MICHAEL});

    // 1) INIZIALIZZO AUTOCOMPLETE MASTRO DARE CODICE
    $('body').on('change', '.js_riga_mastro_dare_codice', function () {
        var riga = $(this).closest('.js_riga_registrazione');

        if ($(this).data('ui-autocomplete') != undefined) {
            console.log('autocomplete inizializata')
        } else {
            $(this).autocomplete({
                source: mastri,
                delay: 0,
                minLength: 0,
                selectFirst: true,
                response: function (event, ui) {
                    console.log(ui);
                    if (ui.content.length == 1) {
                        //$(this).val(ui.content[0].value).trigger('change');
                        //$(this).autocomplete("close");
                        $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: { value: ui.content[0].value } });
                    }
                },
                //autoFocus: true,
                //focus: function (event, ui) { $(this).val(ui.item.value).trigger('change'); },
                select: function (event, ui) {

                    $(this).trigger('change');
                    var conti_filtrati = contiFiltrati(ui);
                    //campo_conto_dare_codice.add(campo_sottoconto_dare_codice).val('');
                    $('.js_riga_conto_dare_codice', riga).add($('.js_riga_sottoconto_dare_codice', riga)).val('');

                    try {
                        //campo_conto_dare_codice.add(campo_sottoconto_dare_codice).autocomplete("destroy");
                        $('.js_riga_conto_dare_codice', riga).add($('.js_riga_sottoconto_dare_codice', riga)).autocomplete("destroy");
                    } catch (e) { }

                    $('.js_riga_conto_dare_codice', riga).autocomplete({
                        source: conti_filtrati,
                        delay: 0,
                        minLength: 0,
                        select: function (event, ui) {
                            var sottoconti_filtrati = sottocontiFiltrati(ui);
                            //campo_sottoconto_dare_codice.val('');
                            $('.js_riga_sottoconto_dare_codice', riga).val('');

                            try {
                                //campo_sottoconto_dare_codice.autocomplete("destroy");
                                $('.js_riga_sottoconto_dare_codice', riga).autocomplete("destroy");
                            } catch (e) { }

                            $('.js_riga_sottoconto_dare_codice', riga).autocomplete({
                                source: sottoconti_filtrati,
                                delay: 0,
                                minLength: 0,
                            }).bind('focus', function () { $(this).autocomplete("search"); });
                        }
                    }).bind('focus', function () { $(this).autocomplete("search"); });
                }
            }).bind('focus', function () { $(this).autocomplete("search"); });
        }
    });

    // 2) AL CAMBIO DEL MASTRO DARE/CONTO/SOTTOCONTO VADO A RISCRIVERE IL CAMPO HIDDEN COL CODICE COMPLETO TESTUALE
    $('body').on('change', '.js_riga_mastro_dare_codice, .js_riga_conto_dare_codice, .js_riga_sottoconto_dare_codice', function (event) {
        var data = {};
        data.field_testuale = $('.js_conto_dare_testuale', $(this).closest('.js_riga_registrazione'));
        data.field_mastro = $('.js_riga_mastro_dare_codice', $(this).closest('.js_riga_registrazione'));
        data.field_conto = $('.js_riga_conto_dare_codice', $(this).closest('.js_riga_registrazione'));
        data.field_sottoconto = $('.js_riga_sottoconto_dare_codice', $(this).closest('.js_riga_registrazione'));

        var codice = popolaContoTestuale(data);

        // if (codice != '') {
        //     bloccaAvere();
        // } else {
        //     bloccaDare();
        // }
    });

    // 3) AL CAMBIO DEL MASTRO DARE BLOCCO IL CONTO AVERE
    $('body').on('change', '.js_riga_mastro_dare_codice', function () {
        console.log('cambio mastro dare codice');
        var riga = $(this).closest('.js_riga_registrazione');

        if ($(this).val() != '') {
            $('.js_riga_mastro_avere_codice', riga).attr('readonly', true);
            $('.js_riga_mastro_avere_codice', riga).attr('tabIndex', '-1');

            $('.js_riga_conto_avere_codice', riga).attr('readonly', true);
            $('.js_riga_conto_avere_codice', riga).attr('tabIndex', '-1');

            $('.js_riga_sottoconto_avere_codice', riga).attr('readonly', true);
            $('.js_riga_sottoconto_avere_codice', riga).attr('tabIndex', '-1');

            $('.js_importo_avere', riga).attr('readonly', true);
            $('.js_importo_avere', riga).attr('tabIndex', '-1');
        } else {
            $('.js_riga_mastro_avere_codice', riga).removeAttr('readonly');
            $('.js_riga_conto_avere_codice', riga).removeAttr('readonly');
            $('.js_riga_sottoconto_avere_codice', riga).removeAttr('readonly');
            $('.js_importo_avere', riga).removeAttr('readonly');

            $('.js_riga_mastro_avere_codice', riga).removeAttr('tabIndex');
            $('.js_riga_conto_avere_codice', riga).removeAttr('tabIndex');
            $('.js_riga_sottoconto_avere_codice', riga).removeAttr('tabIndex');
            $('.js_importo_avere', riga).removeAttr('tabIndex');
        }
    });






    // 4) AL CAMBIO DEL MASTRO AVERE/CONTO/SOTTOCONTO VADO A RISCRIVERE IL CAMPO HIDDEN COL CODICE COMPLETO TESTUALE
    $('body').on('change', '.js_riga_mastro_avere_codice, .js_riga_conto_avere_codice, .js_riga_sottoconto_avere_codice', function (event) {
        var data = {};
        data.field_testuale = $('.js_conto_avere_testuale', $(this).closest('.js_riga_registrazione'));
        data.field_mastro = $('.js_riga_mastro_avere_codice', $(this).closest('.js_riga_registrazione'));
        data.field_conto = $('.js_riga_conto_avere_codice', $(this).closest('.js_riga_registrazione'));
        data.field_sottoconto = $('.js_riga_sottoconto_avere_codice', $(this).closest('.js_riga_registrazione'));
        var codice = popolaContoTestuale(data);

        // if (codice != '') {
        //     bloccaAvere();
        // } else {
        //     bloccaDare();
        // }
    });

    // 5) INIZIALIZZO AUTOCOMPLETE MASTRO AVERE CODICE
    $('body').on('change', '.js_riga_mastro_avere_codice', function () {
        var riga = $(this).closest('.js_riga_registrazione');

        if ($(this).data('ui-autocomplete') != undefined) {
            console.log('autocomplete inizializata')
        } else {
            $(this).autocomplete({
                source: mastri,
                delay: 0,
                minLength: 0,
                selectFirst: true,
                response: function (event, ui) {
                    console.log(ui);
                    if (ui.content.length == 1) {
                        //$(this).val(ui.content[0].value).trigger('change');
                        //$(this).autocomplete("close");
                        $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: { value: ui.content[0].value } });
                    }
                },
                //autoFocus: true,
                //focus: function (event, ui) { $(this).val(ui.item.value).trigger('change'); },
                select: function (event, ui) {

                    $(this).trigger('change');
                    var conti_filtrati = contiFiltrati(ui);
                    //campo_conto_avere_codice.add(campo_sottoconto_avere_codice).val('');
                    $('.js_riga_conto_avere_codice', riga).add($('.js_riga_sottoconto_avere_codice', riga)).val('');

                    try {
                        //campo_conto_avere_codice.add(campo_sottoconto_avere_codice).autocomplete("destroy");
                        $('.js_riga_conto_avere_codice', riga).add($('.js_riga_sottoconto_avere_codice', riga)).autocomplete("destroy");
                    } catch (e) { }

                    $('.js_riga_conto_avere_codice', riga).autocomplete({
                        source: conti_filtrati,
                        delay: 0,
                        minLength: 0,
                        select: function (event, ui) {
                            var sottoconti_filtrati = sottocontiFiltrati(ui);
                            //campo_sottoconto_avere_codice.val('');
                            $('.js_riga_sottoconto_avere_codice', riga).val('');

                            try {
                                //campo_sottoconto_avere_codice.autocomplete("destroy");
                                $('.js_riga_sottoconto_avere_codice', riga).autocomplete("destroy");
                            } catch (e) { }

                            $('.js_riga_sottoconto_avere_codice', riga).autocomplete({
                                source: sottoconti_filtrati,
                                delay: 0,
                                minLength: 0,
                            }).bind('focus', function () { $(this).autocomplete("search"); });
                        }
                    }).bind('focus', function () { $(this).autocomplete("search"); });
                }
            }).bind('focus', function () { $(this).autocomplete("search"); });
        }
    });

    // 6) AL CAMBIO DEL MASTRO AVERE BLOCCO IL CONTO AVERE
    $('body').on('change', '.js_riga_mastro_avere_codice', function () {
        console.log('cambio mastro avere codice');
        var riga = $(this).closest('.js_riga_registrazione');

        if ($(this).val() != '') {
            $('.js_riga_mastro_dare_codice', riga).attr('readonly', true);
            $('.js_riga_mastro_dare_codice', riga).attr('tabIndex', '-1');

            $('.js_riga_conto_dare_codice', riga).attr('readonly', true);
            $('.js_riga_conto_dare_codice', riga).attr('tabIndex', '-1');

            $('.js_riga_sottoconto_dare_codice', riga).attr('readonly', true);
            $('.js_riga_sottoconto_dare_codice', riga).attr('tabIndex', '-1');

            $('.js_importo_dare', riga).attr('readonly', true);
            $('.js_importo_dare', riga).attr('tabIndex', '-1');
        } else {
            $('.js_riga_mastro_dare_codice', riga).removeAttr('readonly');
            $('.js_riga_conto_dare_codice', riga).removeAttr('readonly');
            $('.js_riga_sottoconto_dare_codice', riga).removeAttr('readonly');
            $('.js_importo_dare', riga).removeAttr('readonly');

            $('.js_riga_mastro_dare_codice', riga).removeAttr('tabIndex');
            $('.js_riga_conto_dare_codice', riga).removeAttr('tabIndex');
            $('.js_riga_sottoconto_dare_codice', riga).removeAttr('tabIndex');
            $('.js_importo_dare', riga).removeAttr('tabIndex');
        }
    });







    pulsante_nuova_riga.on('click', function () {
        try {
            //$('.select2,.select2_standard').select2('destroy');
        } catch (e) { }
        var datiNuovaRiga = creaNuovaRiga();
        var newRow = datiNuovaRiga[0];
        var counter = datiNuovaRiga[1];

        //CAMPI DARE - TODO: cambiare usanto il selettore newRow invece del counter
        var campo_mastro_dare_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_mastro_dare_codice]"]');
        var campo_conto_dare_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_dare_codice]"]');
        var campo_sottoconto_dare_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_sottoconto_dare_codice]"]');

        var campo_conto_dare_testuale = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_dare_testuale]"]');
        var campo_importo_dare = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_importo_dare]"]');

        //CAMPI AVERE
        var campo_mastro_avere_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_mastro_avere_codice]"]');
        var campo_conto_avere_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_avere_codice]"]');
        var campo_sottoconto_avere_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_sottoconto_avere_codice]"]');

        var campo_conto_avere_testuale = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_avere_testuale]"]');
        var campo_importo_avere = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_importo_avere]"]');

        //console.log($('.js_numero_riga', newRow).val());
        $('.js_numero_riga', newRow).focus();
        //Confronto importo dare con importo avere, se uguali abilito tasto salva


        //Show button if somma_dare = somma_avere
        /*if (somma_dare != 0 && somma_avere != 0 && somma_dare == somma_avere) {
            $('#form_1822 .form-actions .btn-primary').show();
        } else {
            $('#form_1822 .form-actions .btn-primary').hide();
        }*/
        //Tab sull'ultimo input crea nuova riga, sposto il focus della tab sul primo elemento della riga appena creata (ultima riga)



        //TODO: TuTTI QUESTI listeners/autocomplete ecc vanno creati "globali", non alla creazione nuova riga. Altrimenti se forzo manualmente la creazione di una nuova riga non ho gli eventi agganciati. Usare quindi un selettore del tipo $(contenitore).on('change', 'campo', function) e non $(campo).on(change...)



        //Inizializzo gli autocomplete mastri/conti/sottoconti
        //DARE
        // campo_mastro_dare_codice.on('keydown', function (event) {
        //     var newEvent = $.Event('keydown', {
        //         keyCode: event.keyCode
        //     });

        //     if (newEvent.keyCode !== $.ui.keyCode.TAB) {
        //         return;
        //     }

        //     if (newEvent.keyCode == $.ui.keyCode.TAB) {
        //         $(this).trigger('change');
        //     }
        // });

        /*campo_mastro_dare_codice.autocomplete({
            source: mastri,
            delay: 0,
            minLength: 0,
            selectFirst: true,
            response: function (event, ui) {
                console.log(ui);
                //alert(2);
                if (ui.content.length == 1) {
                    //$(this).val(ui.content[0].value).trigger('change');
                    //$(this).autocomplete("close");
                    $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: { value: ui.content[0].value } });
                }


            },
            //autoFocus: true,
            //focus: function (event, ui) { $(this).val(ui.item.value).trigger('change'); },
            select: function (event, ui) {

                $(this).trigger('change');
                var conti_filtrati = contiFiltrati(ui);

                campo_conto_dare_codice.add(campo_sottoconto_dare_codice).val('');

                try {
                    campo_conto_dare_codice.add(campo_sottoconto_dare_codice).autocomplete("destroy");
                } catch (e) { }

                campo_conto_dare_codice.autocomplete({
                    source: conti_filtrati,
                    delay: 0,
                    minLength: 0,
                    select: function (event, ui) {

                        var sottoconti_filtrati = sottocontiFiltrati(ui);

                        campo_sottoconto_dare_codice.val('');

                        try {
                            campo_sottoconto_dare_codice.autocomplete("destroy");
                        } catch (e) { }

                        campo_sottoconto_dare_codice.autocomplete({
                            source: sottoconti_filtrati,
                            delay: 0,
                            minLength: 0,
                        }).bind('focus', function () { $(this).autocomplete("search"); });
                    }
                }).bind('focus', function () { $(this).autocomplete("search"); });
            }
        }).bind('focus', function () { $(this).autocomplete("search"); });*/

        //Al cambio di uno dei campi sopra, vado a riscrivere il campo hidden col codice completo testuale
        /*campo_mastro_dare_codice.add(campo_conto_dare_codice).add(campo_sottoconto_dare_codice).on('change', {
            field_testuale: campo_conto_dare_testuale,
            field_mastro: campo_mastro_dare_codice,
            field_conto: campo_conto_dare_codice,
            field_sottoconto: campo_sottoconto_dare_codice
        }, function (event) {
            var codice = popolaContoTestuale(event.data);

            // if (codice != '') {
            //     bloccaAvere();
            // } else {
            //     bloccaDare();
            // }

        });*/

        // Al cambio del conto dare testuale o mastro dare blocco il conto avere
        /*campo_conto_dare_testuale.add(campo_mastro_dare_codice).on('change', function () {
            if ($(this).val() != '') {

                campo_mastro_avere_codice.attr('readonly', true);
                campo_mastro_avere_codice.attr('tabIndex', '-1');

                campo_conto_avere_codice.attr('readonly', true);
                campo_conto_avere_codice.attr('tabIndex', '-1');

                campo_sottoconto_avere_codice.attr('readonly', true);
                campo_sottoconto_avere_codice.attr('tabIndex', '-1');

                campo_importo_avere.attr('readonly', true);
                campo_importo_avere.attr('tabIndex', '-1');
            } else {
                campo_mastro_avere_codice.removeAttr('readonly');
                campo_conto_avere_codice.removeAttr('readonly');
                campo_sottoconto_avere_codice.removeAttr('readonly');
                campo_importo_avere.removeAttr('readonly');

                campo_mastro_avere_codice.removeAttr('tabIndex');
                campo_conto_avere_codice.removeAttr('tabIndex');
                campo_sottoconto_avere_codice.removeAttr('tabIndex');
                campo_importo_avere.removeAttr('tabIndex');
            }
        });*/


        //AVERE
        // Michael E. - Commento questa parte di codice perchè a Matteo non piace vedere eliminato codice
        // campo_mastro_avere_codice.autocomplete({
        //     source: mastri,
        //     delay: 0
        // });
        // campo_conto_avere_codice.autocomplete({
        //     source: conti,
        //     delay: 0
        // });
        // campo_sottoconto_avere_codice.autocomplete({
        //     source: sottoconti,
        //     delay: 0
        // });


        /*campo_mastro_avere_codice.autocomplete({
            source: mastri,
            delay: 0,
            minLength: 0,
            select: function (event, ui) {
                var conti_filtrati_avere = contiFiltrati(ui);

                campo_conto_avere_codice.add(campo_sottoconto_avere_codice).val('');

                try {
                    campo_conto_avere_codice.add(campo_sottoconto_avere_codice).autocomplete("destroy");
                } catch (e) { }

                campo_conto_avere_codice.autocomplete({
                    source: conti_filtrati_avere,
                    delay: 0,
                    minLength: 0,
                    select: function (event, ui) {
                        var sottoconti_filtrati_avere = sottocontiFiltrati(ui);

                        campo_sottoconto_dare_codice.val('');

                        try {
                            campo_sottoconto_avere_codice.autocomplete("destroy");
                        } catch (e) { }

                        campo_sottoconto_avere_codice.autocomplete({
                            source: sottoconti_filtrati_avere,
                            delay: 0,
                            minLength: 0,
                        }).bind('focus', function () { $(this).autocomplete("search"); });
                    }
                }).bind('focus', function () { $(this).autocomplete("search"); });
            }
        }).bind('focus', function () { $(this).autocomplete("search"); });*/

        //Al cambio di uno dei campi sopra, vado a riscrivere il campo hidden col codice completo testuale
        /*campo_mastro_avere_codice.add(campo_conto_avere_codice).add(campo_sottoconto_avere_codice).on('change', {
            field_testuale: campo_conto_avere_testuale,
            field_mastro: campo_mastro_avere_codice,
            field_conto: campo_conto_avere_codice,
            field_sottoconto: campo_sottoconto_avere_codice
        }, function (event) {
            var codice = popolaContoTestuale(event.data);

            // if (codice != '') {
            //     bloccaAvere();
            // } else {
            //     bloccaDare();
            // }

        });*/

        // Al cambio del conto avere testuale o mastro avere blocco il conto dare
        /*campo_conto_avere_testuale.add(campo_mastro_avere_codice).on('change', function () {
            if ($(this).val() != '') {

                campo_mastro_dare_codice.attr('readonly', true);
                campo_mastro_dare_codice.attr('tabIndex', '-1');

                campo_conto_dare_codice.attr('readonly', true);
                campo_conto_dare_codice.attr('tabIndex', '-1');

                campo_sottoconto_dare_codice.attr('readonly', true);
                campo_sottoconto_dare_codice.attr('tabIndex', '-1');

                campo_importo_dare.attr('readonly', true);
                campo_importo_dare.attr('tabIndex', '-1');
            } else {
                campo_mastro_dare_codice.removeAttr('readonly');
                campo_conto_dare_codice.removeAttr('readonly');
                campo_sottoconto_dare_codice.removeAttr('readonly');
                campo_importo_dare.removeAttr('readonly');

                campo_mastro_dare_codice.removeAttr('tabIndex');
                campo_conto_dare_codice.removeAttr('tabIndex');
                campo_sottoconto_dare_codice.removeAttr('tabIndex');
                campo_importo_dare.removeAttr('tabIndex');
            }
        });*/


        // gestione cambio causale
        //$('body').on('change', '.js_causale', function () {});

        //$('.js_causale').on('change', function () {});

        var causale_select = $('.js_causale', newRow);

        causale_select.on('change', function () {

            var riga_registrazione = $(this).closest('.js_riga_registrazione');
            var valore_select = $(this, riga_registrazione).find('option:selected').val();


            console.log('Causale cambiata. Recupero dati causale...');
            $.ajax({
                url: base_url + 'contabilita/primanota/getCausale/' + valore_select,
                async: false,
                data: {
                    [token_name]: token_hash
                },
                dataType: 'json',
                type: 'post',
                success: function (response) {
                    if (response.status == '1') {
                        var causale = response.txt;
                        console.log('Dati causale recuperati...');
                        console.log(causale);

                        if (causale) {
                            campo_mastro_dare_codice
                                .add(campo_conto_dare_codice)
                                .add(campo_sottoconto_dare_codice)
                                .add(campo_mastro_avere_codice)
                                .add(campo_conto_avere_codice)
                                .add(campo_sottoconto_avere_codice)
                                .val('').trigger('change');

                            // parte "dare"
                            if (causale.prime_note_causale_mastro_dare) {
                                campo_mastro_dare_codice.val(causale.mastro.documenti_contabilita_mastri_codice).trigger('change');
                                if (causale.prime_note_causale_conto_dare) {
                                    campo_conto_dare_codice.val(causale.conto.documenti_contabilita_conti_codice).trigger('change');
                                    if (causale.prime_note_causale_sottoconto_dare) {
                                        campo_sottoconto_dare_codice.val(causale.sottoconto.documenti_contabilita_sottoconti_codice).trigger('change');
                                    }
                                }
                                //Verifico, se c'è anche un conto avere, creo la seconda riga in automatico e popolo
                                if (causale.prime_note_causale_mastro_avere) {
                                    var datiNuovaRiga = creaNuovaRiga();
                                    var riga_registrazione = datiNuovaRiga[0];
                                    var counter = datiNuovaRiga[1];
                                    //Punto i nuovi campi alla nuova riga
                                    var campo_mastro_avere_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_mastro_avere_codice]"]');
                                    var campo_conto_avere_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_avere_codice]"]');
                                    var campo_sottoconto_avere_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_sottoconto_avere_codice]"]');

                                    var campo_conto_avere_testuale = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_avere_testuale]"]');
                                    var campo_importo_avere = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_importo_avere]"]');

                                    //Metto la stessa causale
                                    var causale_select = $('.js_causale', newRow);

                                    //TODO: arrivati qua si rischia il loop perchè il trigger change scatenerà nuovamente l'ajax e tutto il resto. A monte va messo un controllo su un attributo (data-qualcosa) del contenitore della riga o sui singoli input
                                    //in modo che non entri in loop infinito. Questo attributo va quindi settato qui sopra (tipo newRow.data('qualcosa', true/false)) e dovrebbe funzionare tutto. Ovviamente a monte va controllato questo attributo (if...)
                                    causale_select.val(causale.prime_note_registrazioni_causale_id).trigger('change');
                                }
                            }


                            // parte "avere"
                            /*if (causale.prime_note_causale_mastro_avere) {
                                campo_mastro_avere_codice.val(causale.mastro.documenti_contabilita_mastri_codice).trigger('change');
                                if (causale.prime_note_causale_conto_avere) {
                                    campo_conto_avere_codice.val(causale.conto.documenti_contabilita_conti_codice).trigger('change');
                                    if (causale.prime_note_causale_sottoconto_avere) {
                                        campo_sottoconto_avere_codice.val(causale.sottoconto.documenti_contabilita_sottoconti_codice).trigger('change');
                                    }
                                }
                                //Verifico, se c'è anche un conto dare, creo la seconda riga in automatico e popolo
                                // if (causale.prime_note_causale_mastro_dare) {
                                //     var datiNuovaRiga = creaNuovaRiga();
                                //     var newRow = datiNuovaRiga[0];
                                //     var counter = datiNuovaRiga[1];
                                //     //Punto i nuovi campi alla nuova riga
                                //     var campo_mastro_dare_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_mastro_dare_codice]"]');
                                //     var campo_conto_dare_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_dare_codice]"]');
                                //     var campo_sottoconto_dare_codice = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_sottoconto_dare_codice]"]');

                                //     var campo_conto_dare_testuale = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_conto_dare_testuale]"]');
                                //     var campo_importo_dare = $('[name="registrazioni[' + counter + '][prime_note_registrazioni_importo_dare]"]');

                                //     //Metto la stessa causale
                                //     var causale_select = $('.js_causale', newRow);

                                //     //TODO: arrivati qua si rischia il loop perchè il trigger change scatenerà nuovamente l'ajax e tutto il resto. A monte va messo un controllo su un attributo (data-qualcosa) del contenitore della riga o sui singoli input
                                //     //in modo che non entri in loop infinito. Questo attributo va quindi settato qui sopra (tipo newRow.data('qualcosa', true/false)) e dovrebbe funzionare tutto. Ovviamente a monte va controllato questo attributo (if...)
                                //     causale_select.val(causale.prime_note_registrazioni_causale_id).trigger('change');


                                // }
                            }*/



                            // parte "avere"
                            if (causale.prime_note_causale_mastro_avere) {
                                //campo_mastro_avere_codice.val(causale.mastro.documenti_contabilita_mastri_codice).trigger('change');
                                $('.js_riga_mastro_avere_codice', riga_registrazione).val(causale.mastro.documenti_contabilita_mastri_codice).trigger('change');
                            }

                            if (causale.prime_note_causale_conto_avere) {
                                //campo_conto_avere_codice.val(causale.conto.documenti_contabilita_conti_codice).trigger('change');
                                $('.js_riga_conto_avere_codice', riga_registrazione).val(causale.conto.documenti_contabilita_conti_codice).trigger('change');
                            }

                            if (causale.prime_note_causale_sottoconto_avere) {
                                //campo_sottoconto_avere_codice.val(causale.sottoconto.documenti_contabilita_sottoconti_codice).trigger('change');
                                $('.js_riga_sottoconto_avere_codice', riga_registrazione).val(causale.sottoconto.documenti_contabilita_sottoconti_codice).trigger('change');
                            }
                        }
                    }
                }
            })
        })
        console.log('Imposto causale automatica su riga ' + counter);
        $('.js_causale', newRow).val($('[name="prime_note_causale"]').val()).trigger('change');
    });

    $('.js_form_prima_nota').on('change', '.js_importo_dare, .js_importo_avere', function () {
        console.log('Importi cambiati!');
        checkSommaDareAvere();
    });

    $(document).on('focus', '.select2-selection.select2-selection--single', function (e) {
        $(this).closest(".select2-container").siblings('select:enabled').select2('open');
    });


    //Disattivo submit all'invio
    $(window).keydown(function (event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });
});