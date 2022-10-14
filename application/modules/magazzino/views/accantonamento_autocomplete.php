<style>
    .ui-autocomplete {
        max-height: 200px;
        overflow-y: auto;
        /* prevent horizontal scrollbar */
        overflow-x: hidden;
        /* add padding to account for vertical scrollbar */
        padding-right: 20px;
        z-index: 9999;
    }
</style>

<input type="text" class="form-control input-lg js_accantonamento_autocomplete" placeholder="Incolla il barcode" width="100%">

<script>
    var token = JSON.parse(atob($('body').data('csrf')));
    var token_name = token.name;
    var token_hash = token.hash;

    $(function() {
        $(".js_accantonamento_autocomplete").autocomplete({
            source: function(request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "magazzino/movimenti/autocompleteBarcodeProduct/<?php echo $value_id; ?>",
                    dataType: "json",
                    data: {
                        [token_name]: token_hash,
                        query: request.term
                    },
                    //minLength: 2,
                    success: function(ajax_response) {
                        var collection = [];

                        if (ajax_response.status == '1' && ajax_response.data) {
                            if (ajax_response.data.length == 1) {
                                //Il controller avrà già inserito la riga di accantonamento, quindi refresho solo la grid
                                $('.js_ajax_datatable').DataTable().ajax.reload();

                                $('.js_accantonamento_autocomplete').val('');

                            } else if (ajax_response.data.length >= 1) {
                                //Faccio selezionare l'ordine su cui accantonare
                                $.each(ajax_response.data, function(index, articolo) {
                                    var cliente = JSON.parse(articolo.documenti_contabilita_destinatario);
                                    var data_ordine = articolo.documenti_contabilita_data_emissione.substr(0, 10);
                                    var numero_ordine = articolo.documenti_contabilita_numero;
                                    var qty = parseInt(articolo.documenti_contabilita_articoli_quantita);
                                    var label = 'Ordine: ' + numero_ordine + ' del ' + data_ordine + ' di ' + cliente.ragione_sociale + ' (qty: ' + qty + ')';
                                    collection.push({
                                        "id": articolo.documenti_contabilita_articoli_id,
                                        "label": label,
                                        "value": articolo,
                                    });
                                });
                            } else {
                                alert('Articolo non trovato tra gli ordini cliente');
                            }

                        } else {
                            alert(ajax_response.data);
                        }

                        response(collection);
                    }
                });
            },
            minLength: 2,

            select: function(event, ui) {
                if (event.keyCode === 9) {
                    return false;
                }

                var articolo = ui.item.value;
                //TODO: ajax per inserire in accantonamento
                console.log(articolo);

                $.ajax({
                    method: 'post',
                    url: base_url + "magazzino/movimenti/accantona/<?php echo $value_id; ?>",
                    dataType: "json",
                    data: {
                        [token_name]: token_hash,
                        "riga_ordine": JSON.stringify(articolo)
                    },
                    //minLength: 2,
                    success: function(ajax_response) {
                        $('.js_ajax_datatable').DataTable().ajax.reload();
                        $('.js_accantonamento_autocomplete').val('');
                    }
                });



                return false;
            }
        });
    });

    $(() => {
        function checkChangeQty(accantonamento_id) {
            var qty = $('.js_qty_accantonamento[data-id="' + accantonamento_id + '"]').val();
            var bo = $('.js_bo_accantonamento[data-id="' + accantonamento_id + '"]').val();
            var stk = $('.js_stk_accantonamento[data-id="' + accantonamento_id + '"]').val();
            var del = $('.js_del_accantonamento[data-id="' + accantonamento_id + '"]').val();
            var shp = $('.js_shp_accantonamento[data-id="' + accantonamento_id + '"]').val();

            if (qty == bo + stk + del + shp) {
                return true;
            } else {
                return true;
            }
        }
        $('.js_ajax_datatable').on('keyup', '.js_qty_accantonamento', function(event) {
            var accantonamento_id = $(this).data('id');
            var value = $(this).val();
            $.ajax({
                url: base_url + "magazzino/movimenti/change_qty_accantonamento/" + accantonamento_id + '/' + value,
                success: function(ajax_response) {
                    //$('.js_ajax_datatable').DataTable().ajax.reload();
                }
            });
        });
    });
</script>