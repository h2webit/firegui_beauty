<script>
    var distruggiAutocomplete = function(el) {
        if (el.data('ui-autocomplete') != undefined) {
            el.autocomplete('destroy').unbind('focus');
        }
    }
    $(() => {
        $('[name="customers_contropartita_codice"]').focusin(function() {
            var label = $('.control-label', $(this).parent());
            var labelText = label.html();



            $(this).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        method: 'post',
                        async: true,
                        url: base_url + "contabilita/primanota/autocompleteSottoconto/dare",
                        dataType: "json",
                        data: {
                            search: request.term,
                            [token_name]: token_hash
                        },
                        success: function(res) {
                            var collection = [];

                            $.each(res.data, function(index, item) {
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
                open: function(e, ui) {
                    var acData = $(this).data('ui-autocomplete');
                    if (typeof acData !== 'undefined') {
                        acData
                            .menu
                            .element
                            .find('li')
                            .each(function() {
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
                response: function(event, ui) {
                    if (ui.content.length == 1) {

                        //$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', { item: ui.content[0] });
                    }
                },
                select: function(event, ui) {
                    $('[name="customers_contropartita_sottoconto"]').val(ui.item.id);
                    $(this).val(ui.item.value.documenti_contabilita_sottoconti_codice_completo).trigger('change');
                    label.html(labelText + ' (' + ui.item.data.documenti_contabilita_sottoconti_descrizione + ')');
                }
            })



        }).focusout(function() {


            distruggiAutocomplete($(this));
        }).bind('focus', function() {

            $(this).autocomplete("search");
        });
    });
</script>
