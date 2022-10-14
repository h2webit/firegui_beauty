$(() => {
    $('.js_registro_iva_vendite_definitivo').on('click', function () {
        if ($(this).hasClass('disabled')) {
            alert('Non è possibile generare il registro iva vendite nel periodo selezionato!');
        } else {
            x = confirm('Sei sicuro di voler generare il Registro IVA Vendite DEFINITIVO? Controlla con attenzione il periodo selezionato e i filtri qui sopra.');

            if (x) {
                var anno = $('.btn_year.btn_filter_custom_active').html();
                var mese = $('.btn_month.btn_filter_custom_active:visible').data('mese');
                if (typeof mese === 'undefined') {
                    mese = '';
                }
                var trimestre = $('.btn_quarter.btn_filter_custom_active:visible').data('trimestre');
                if (typeof trimestre === 'undefined') {
                    trimestre = '';
                }
                location.href=base_url+'contabilita/primanota/generaRegistroIvaVenditeDefinitivo/'+anno+'/'+mese+trimestre;
            } else {
                
            }
        }
    });
    $('.js_registro_iva_acquisti_definitivo').on('click', function () {
        if ($(this).hasClass('disabled')) {
            alert('Non è possibile generare il registro iva acquisti nel periodo selezionato!');
        } else {
            x = confirm('Sei sicuro di voler generare il Registro IVA Acquisti DEFINITIVO? Controlla con attenzione il periodo selezionato e i filtri qui sopra.');

            if (x) {
                var anno = $('.btn_year.btn_filter_custom_active').html();
                var mese = $('.btn_month.btn_filter_custom_active:visible').data('mese');
                if (typeof mese === 'undefined') {
                    mese = '';
                }
                var trimestre = $('.btn_quarter.btn_filter_custom_active:visible').data('trimestre');
                if (typeof trimestre === 'undefined') {
                    trimestre = '';
                }
                location.href=base_url+'contabilita/primanota/generaRegistroIvaAcquistiDefinitivo/'+anno+'/'+mese+trimestre;
            } else {
                
            }
        }
    });
    $('.js_liquidazione_iva_definitivo').on('click', function () {
        if ($(this).hasClass('disabled')) {
            alert('Non è possibile generare la liquidazione iva nel periodo selezionato!');
        } else {
            x = confirm('Sei sicuro di voler generare la liquidazione IVA DEFINITIVA? Controlla con attenzione il periodo selezionato e i filtri qui sopra.');

            if (x) {
                var anno = $('.btn_year.btn_filter_custom_active').html();
                var mese = $('.btn_month.btn_filter_custom_active:visible').data('mese');
                if (typeof mese === 'undefined') {
                    mese = '';
                }
                var trimestre = $('.btn_quarter.btn_filter_custom_active:visible').data('trimestre');
                if (typeof trimestre === 'undefined') {
                    trimestre = '';
                }
                location.href=base_url+'contabilita/primanota/generaLiquidazioneIvaDefinitiva/'+anno+'/'+mese+trimestre;
            } else {
                
            }
        }
    });


    $('.btn_year').on('click', function () {
        $('.btn_year').removeClass('btn_filter_custom_active');
        $(this).addClass('btn_filter_custom_active');
    });
    $('.btn_month').on('click', function () {
        $('.btn_month').removeClass('btn_filter_custom_active');
        $(this).addClass('btn_filter_custom_active');
    });
    $('.btn_quarter').on('click', function () {
        $('.btn_quarter').removeClass('btn_filter_custom_active');
        $(this).addClass('btn_filter_custom_active');
    });

    $('.btn_filter_custom').on('click',function () {
        var anno = $('.btn_year.btn_filter_custom_active').html();
        var mese = $('.btn_month.btn_filter_custom_active:visible').data('mese');
        if (typeof mese === 'undefined') {
            mese = '';
        }
        var trimestre = $('.btn_quarter.btn_filter_custom_active:visible').data('trimestre');
        if (typeof trimestre === 'undefined') {
            trimestre = '';
        }
        location.href=base_url+'main/layout/stampe-contabilita/?mese='+mese+'&trimestre='+trimestre+'&anno='+anno;
    });
});