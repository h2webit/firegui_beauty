<?php
$buttons = [
    [
        'label' => 'Clienti',
        'icon' => 'users',
        'color' => 'green',
        'link' => 'main/layout/customers-list',
        'type' => 'link',
    ], [
        'label' => 'Fornitori',
        'icon' => 'industry',
        'color' => 'orange',
        'link' => 'main/layout/customers-list',
        'type' => 'link',
    ], [
        'label' => 'Catalogo Prodotti',
        'icon' => 'sitemap',
        'color' => 'red',
        'link' => 'main/layout/products-list',
        'type' => 'link',
    ], [
        'label' => 'Contabilità',
        'icon' => 'list-alt',
        'color' => 'blue',
        'link' => 'main/layout/elenco_documenti',
        'type' => 'link',
    ], [
        'label' => 'Movimenti Magazzino',
        'icon' => 'dolly',
        'color' => 'navy',
        'link' => 'main/layout/movements-list',
        'type' => 'link',
    ], [
        'label' => 'Nuova fattura',
        'icon' => 'file-invoice-dollar',
        'color' => 'purple',
        'link' => 'main/layout/nuovo_documento?doc_type=Fattura',
        'type' => 'link',
    ], /*[
        'label' => 'Nuovo DDT',
        'icon' => 'file-signature',
        'color' => 'teal',
        'link' => 'main/layout/nuovo_documento?doc_type=DDT',
        'type' => 'link',
    ], [
        'label' => 'Nuovo Ordine',
        'icon' => 'file-invoice',
        'color' => 'maroon',
        'link' => 'main/layout/nuovo_documento?doc_type=Ordine+Cliente',
        'type' => 'link',
    ],*/
];
$this->load->model('magazzino/mappature');
$mappature = $this->mappature->getMappature();
extract($mappature);
?>

<style>
    .big-button span {
        height: auto !important;
        width: 100%;
        padding: 10px;
        margin-top: 30px;
    }

    .text {
        margin-top: 35px;
        font-family: "Arial";
        font-size: 0.40em;
    }

    .big-button i {
        padding: 10px;
    }

    .info-box {
        font-size: 0.80em !important;
        background: transparent;
    }

    .ui-autocomplete {
        max-height: 250px;
        overflow-y: auto;
        /* prevent horizontal scrollbar */
        overflow-x: hidden;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-body">
                <form id="check_barcode" class="form-inline">
                    <div class="col-md-4">
                        <input type="text" class="form-control input-lg" id="inputBarcode" placeholder="Barcode" autofocus="autofocus" style="min-width:100% !important;">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success btn-lg js_btn_searchbarcode"><i class="fas fa-search"></i>
                            Barcode
                        </button>
                    </div>
                </form>

                <div class="col-md-6">
                    <input type="text" class="form-control input-lg" id="inputProductName" placeholder="Nome prodotto">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php foreach ($buttons as $button) : ?>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <a href="<?php echo base_url($button['link']); ?>" <?php echo ($button['type'] == 'modal') ? 'class="js_open_modal"' : null; ?>>
                <div class="info-box big-button">
                    <span class="info-box-icon bg-<?php echo $button['color'] ?>">
                        <i class="fas fa-<?php echo $button['icon'] ?>">
                            <div class="text"><?php echo $button['label'] ?></div>
                        </i>
                    </span>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

    <div class="clearfix visible-sm-block"></div>
</div>

<script src="<?php echo base_url('/template/adminlte/bower_components/jquery-ui/jquery-ui.min.js?v=1.9.4'); ?>"></script>

<script>
    function searchBarcode(barcode) {
        var data = [];

        $.ajax({
            url: base_url + 'magazzino/movimenti/searchBarcode',
            type: 'POST',
            async: false,
            dataType: 'json',
            data: {
                [token_name]: token_hash,
                'barcode': barcode
            },
            success: function(response) {
                data = response;
            },
            error: function(jqXHR, exception, errorMessage) {
                console.log(jqXHR);
                console.log(exception);
                console.log(errorMessage);
            },
        });

        return data;
    }

    function resetForm(form) {
        $(form).trigger('reset');
    }

    function auto_focus_barcode() {
        $('#inputBarcode').val('');
        $('#inputBarcode').focus();
    }

    $(document).ready(function() {
        setTimeout(auto_focus_barcode, 1000);

        $('#check_barcode').on('submit', function(e) {
            var barcode = $('#inputBarcode').val(); //.substr(0, 13);

            var dati_prodotto = searchBarcode(barcode);

            if (dati_prodotto['status'] == 1) {
                var sUrl = base_url + "/get_ajax/layout_modal/dettagli_prodotto_modale/" + dati_prodotto['data']['<?php echo $campo_id_prodotto; ?>'] + '?_size=large';

                var token = JSON.parse(atob($('body').data('csrf')));
                var token_name = token.name;
                var token_hash = token.hash;

                if (sUrl) {
                    var data_post = [];
                    data_post.push({
                        "name": token_name,
                        "value": token_hash
                    });

                    loadModal(sUrl, data_post);
                }
            } else {
                alert('Errore: ' + dati_prodotto['data']);
            }

            return false;
            e.preventDefault();
        });

        $("#inputProductName").autocomplete({
            source: function(request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + 'magazzino/movimenti/autocomplete/<?php echo $entita_prodotti; ?>',
                    dataType: "json",
                    data: {
                        [token_name]: token_hash,
                        search: request.term
                    },
                    minLength: 2,
                    success: function(data) {
                        var collection = [];

                        $.each(data.results.data, function(i, p) {
                            collection.push({
                                "id": p.<?php echo $campo_id_prodotto; ?>,
                                "label": p.<?php echo $campo_preview_prodotto; ?>,
                                "value": p
                            });
                        });

                        response(collection);
                    }
                });
            },
            minLength: 2,

            select: function(event, ui) {
                if (event.keyCode === 9)
                    return false;

                var sUrl = base_url + "/get_ajax/layout_modal/dettagli_prodotto_modale/" + ui.item.id + '?_size=large';

                var token = JSON.parse(atob($('body').data('csrf')));
                var token_name = token.name;
                var token_hash = token.hash;

                if (sUrl) {
                    var data_post = [];
                    data_post.push({
                        "name": token_name,
                        "value": token_hash
                    });

                    loadModal(sUrl, data_post);
                }

                return false;
            }
        });
    });
</script>