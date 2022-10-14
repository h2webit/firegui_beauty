<style>
    .mt-15 {
        margin-top: 15PX;
    }

    .box-tools {
        top: 10px !important;
    }

    .text-left {
        text-align: left !important;
    }

    td:not(:first-child) {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
        padding-right: 15px !important;
    }

    .container-custom {
        padding-left: 0;
        padding-right: 0;
    }

    .custom-box-header {

        /* color: #000; */
    }

    .box-custom {
        border-top: 3px solid transparent;
        border-top-color: transparent;
    }

    .confirm_row {
        background-color: #fff;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .title-container {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        height: 34px;
    }

    .actions-container {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .button_print,
    .courier {
        margin-right: 15px;
    }

    .w-100 {
        width: 100% !important;
    }

    /*Checkbox style*/
    .container_check {
        display: block;
        position: relative;
        padding-left: 35px;
        margin-bottom: 20px;
        cursor: pointer;
        font-size: 22px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    /* Hide the browser's default checkbox */
    .container_check input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    /* Create a custom checkbox */
    .checkmark {
        position: absolute;
        top: 0;
        right: 0;
        height: 20px;
        width: 20px;
        /*background-color: #eee;*/
        border: 1px solid #b0b0b0;
    }

    /* On mouse-over, add a grey background color */
    .container_check:hover input~.checkmark {
        background-color: #ccc;
    }

    /* When the checkbox is checked, add a green background */
    .container_check input:checked~.checkmark {
        background-color: #00a65a;
    }

    /* Create the checkmark/indicator (hidden when not checked) */
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    /* Show the checkmark when checked */
    .container_check input:checked~.checkmark:after {
        display: block;
    }

    /* Style the checkmark/indicator */
    .container_check .checkmark:after {
        left: 7px;
        top: 4px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
    }

    tr.bg-grey {
        background-color: #e1e3e9
    }

    tr.js_movimentato {
        background-color: #79B574;
        /*display: none;*/
    }
</style>

<?php
$settings = $this->apilib->searchFirst('magazzino_settings');
$this->apilib->clearCache();

$picking_filter = $this->session->userdata('picking_filter');
$fornitori = $this->apilib->search('customers', [
    'customers_type' => 2,
    'customers_id IN (SELECT documenti_contabilita_supplier_id FROM documenti_contabilita WHERE documenti_contabilita_supplier_id IS NOT NULL)'
], null, 0, "customers_name,customers_last_name,customers_company", 'ASC');
$clienti = $this->apilib->search('customers', [
    'customers_type' => 1,
    'customers_id IN (SELECT documenti_contabilita_customer_id FROM documenti_contabilita WHERE documenti_contabilita_customer_id IS NOT NULL AND documenti_contabilita_stato IN (1,2) AND documenti_contabilita_tipo = 5)'
], null, 0, "customers_name,customers_last_name,customers_company", 'ASC');
$magazzini = $this->apilib->search('magazzini');

if (empty($picking_filter['magazzino'])) {
    $picking_filter['magazzino'] = ($this->auth->get('magazzino')) ? $this->auth->get('magazzino') : $magazzini[0]['magazzini_id'];
    $this->session->set_userdata('picking_filter', $picking_filter);
}

// dd($picking_filter);

/*$magazzini = $this->db->query("
SELECT magazzini_id, magazzini_titolo
FROM movimenti 
LEFT JOIN magazzini ON movimenti_magazzino = magazzini_id 
WHERE movimenti_tipo_movimento = 1
AND movimenti_id IN (
    SELECT movimenti_articoli_movimento 
    FROM movimenti_articoli
    WHERE movimenti_articoli_prodotto_id = '{$articolo['documenti_contabilita_articoli_prodotto_id']}'
) 
GROUP BY movimenti_magazzino
")->result_array();*/



?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" />

<?php if ($settings['magazzino_settings_inventario_in_corso']): ?>
    <section class="content-header">
        <div class="alert alert-danger mb-0">
            <h5>Inventario in corso!</h5>

            <div><?php e('Attenzione! E\' in corso l\'inventario. Si sconsiglia di movimentare la merce in questa fase.');?></div>
        </div>
    </section>
<?php endif;?>


<div class="row">
    <div class="col-xs-12">
        <h4>Riepilogo ordini da evadere</h4>
    </div>
</div>

<!-- FILTRI -->
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border js_title_collapse">
                <div class="box-title">
                    <i class="fas fa-edit"></i>
                    <span class="">Filtri</span>
                </div>
                <div class="box-tools">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <form method="post" action="<?php echo base_url('magazzino/picking/savefilters'); ?>" class="formAjax">
                <?php add_csrf(); ?>
                <div class="box-body">
                    <div class="form-group row">
                        <div class="col-lg-4">
                            <label>Fornitore</label>
                            <select class="form-control select2_standard" name="picking_filter[fornitore]">
                                <option value=""></option>
                                <?php foreach ($fornitori as $fornitore) : ?>
                                    <option value="<?php echo $fornitore['customers_id'] ?>" <?php echo (!empty($picking_filter['fornitore']) && $picking_filter['fornitore'] == $fornitore['customers_id']) ? 'selected="selected"' : null; ?>><?php echo $fornitore['customers_company'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label>Clienti</label>
                            <select class="form-control select2_standard" name="picking_filter[cliente]">
                                <option value=""></option>
                                <?php foreach ($clienti as $cliente) : $nome = (!empty($cliente['customers_company'])) ? $cliente['customers_company'] : $cliente['customers_name'] . ' ' . $cliente['customers_last_name']; ?>
                                    <option value="<?php echo $cliente['customers_id'] ?>" <?php echo (!empty($picking_filter['cliente']) && $picking_filter['cliente'] == $cliente['customers_id']) ? 'selected="selected"' : null; ?>><?php echo $nome ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label>Magazzino</label>
                            <select class="form-control select2_standard" name="picking_filter[magazzino]">
                                <option value=""></option>
                                <?php foreach ($magazzini as $magazzino) : ?>
                                    <option value="<?php echo $magazzino['magazzini_id'] ?>" <?php echo (!empty($picking_filter['magazzino']) && $picking_filter['magazzino'] == $magazzino['magazzini_id']) ? 'selected="selected"' : null; ?>><?php echo $magazzino['magazzini_titolo'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <?php if (!empty($picking_filter)) : ?>
                                <a href="<?php echo base_url('magazzino/picking/clearfilters'); ?>" class="btn btn-default">Pulisci filtri</a>
                            <?php endif; ?>
                            <div class="pull-right">
                                <button type="submit" class="btn btn-success">Aggiorna filtri</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="box box-danger">
            <div class="box-header with-border">
                <div class="box-title">
                    <i class="fas fa-barcode"></i>
                    <span class="">Ricerca tramite barcode</span>
                </div>
            </div>

            <div class="box-body">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <label>Codice EAN</label>
                        <input type="text" class="form-control js_ean" placeholder="1234567890" />
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <!-- <button type="button" class="btn btn-primary btn-search">Ricerca</button> -->
            </div>
        </div>
    </div>
</div>

<?php
$where = ['documenti_contabilita_stato IN (1,2)', 'documenti_contabilita_tipo' => 5];

//TODO: prendere dalle mappature di contabilità e/o magazzino
if (!empty($picking_filter)) {
    if (!empty($picking_filter['cliente'])) {
        $where['documenti_contabilita_customer_id'] = $picking_filter['cliente'];
    }

    if (!empty($picking_filter['fornitore'])) {
        $where['documenti_contabilita_supplier_id'] = $picking_filter['fornitore'];
    }
}

$ordini = $this->apilib->search('documenti_contabilita', $where, 200, 0, 'documenti_contabilita_data_emissione', 'desc');


$documenti_ids = [-1];
foreach ($ordini as $ordine) {
    $documenti_ids[] = $ordine['documenti_contabilita_id'];
}
$_accantonamenti = $this->apilib->search('accantonamenti', [
    'accantonamenti_riga_ordine IN (SELECT documenti_contabilita_articoli_id FROM documenti_contabilita_articoli WHERE documenti_contabilita_articoli_documento IN (' . implode(',', $documenti_ids) . '))'
]);

$accantonamenti_map_ean = [];
foreach ($_accantonamenti as $acc) {
    $accantonamenti_map_ean[$acc['documenti_contabilita_articoli_codice']][] = $acc['documenti_contabilita_articoli_documento'];
}

$vettori = $this->apilib->search('vettori', [], null, 0, 'vettori_ragione_sociale', 'ASC');

if (empty($ordini)) :
    echo '<div class="row"><div class="col-sm-6 col-sm-offset-3"><div class="callout callout-info">Non ci sono ordini da evadere</div></div></div>';
else :
    foreach ($ordini as $ordine) :
        unset($ordine['documenti_contabilita_template_pdf_html']);

        extract($ordine);

        $cliente = json_decode($documenti_contabilita_destinatario);

        $articoli = $this->apilib->search('documenti_contabilita_articoli', [
            'documenti_contabilita_articoli_documento' => $ordine['documenti_contabilita_id'],
            '(documenti_contabilita_articoli_prodotto_id IN (SELECT fw_products_id FROM fw_products) OR documenti_contabilita_articoli_prodotto_id IS NULL)'
            //'documenti_contabilita_articoli_prodotto_id IN (SELECT fw_products_id FROM fw_products)'
        ]);

        //debug($articoli);

?>


        <!-- RIEPILOGO ORDINI -->
        <div class="row mt-15 row-ordine" id="order_<?php echo $documenti_contabilita_numero ?>" data-n_ordine="<?php echo $documenti_contabilita_numero ?>" data-id_ordine="<?php echo $documenti_contabilita_id ?>">
            <div class="col-xs-12">
                <div class="box <?php echo (strtolower($documenti_contabilita_stato_value) == 'aperto') ? 'box-danger' : 'box-warning'; ?>">
                    <div class="box-header custom-box-header" style="background-color: <?php echo (strtolower($documenti_contabilita_stato_value) == 'aperto') ? '#f2dede' : '#fcf8e3'; ?>;">
                        <div class="container-fluid container-custom">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="title-container clearfix">
                                        <h3 class="box-title text-uppercase"><a href="<?php echo base_url('main/layout/contabilita_dettaglio_documento/' . $documenti_contabilita_id); ?>" target="_blank">ORDINE #<?php echo $documenti_contabilita_numero ?> - <?php echo $cliente->ragione_sociale ?></a></h3>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="actions-container">
                                        <div class="button_print">
                                            <a target="_blank" class="btn js-action_button btn-grid-action-s btn-primary" href="<?php echo base_url('magazzino/picking/stampaNumeroOrdine/' . $documenti_contabilita_numero); ?>" data-toggle="tooltip" title="Stampa numero ordine">
                                                <span class="fas fa-print"></span>
                                            </a>
                                        </div>
                                        <div class="order_info">
                                            <h3 class="box-title text-uppercase"><span><?php echo dateFormat($documenti_contabilita_data_emissione); ?></span> - stato: <span><?php echo $documenti_contabilita_stato_value ?></span></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.header-container -->
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body table-responsive no-padding">
                        <table class="table js_table_order" data-documento_id="<?php echo $documenti_contabilita_id; ?>">
                            <thead>
                                <tr>
                                    <th>Immagine</th>
                                    <th>Articolo</th>
                                    <!--<th>Quantità</th>-->
                                    <th>Magazzini</th>
                                    <th>Posizione</th>
                                    <th class="text-right"></th>
                                </tr>
                            </thead>

                            <tbody data-magazzino="<?php echo $picking_filter['magazzino']; ?>">
                                <?php

                                $movimenti_articoli = $this->db->query("
                                SELECT * 
                                FROM movimenti_articoli 
                                WHERE movimenti_articoli_movimento IN (
                                    SELECT movimenti_id 
                                    FROM movimenti 
                                    WHERE movimenti_documento_id = '{$documenti_contabilita_id}'
                                )
                                ")->result_array();

                                $mov_art_ids = [];
                                if (!empty($movimenti_articoli)) {
                                    foreach ($movimenti_articoli as $mov_art) {
                                        $mov_art_ids[] = $mov_art['movimenti_articoli_prodotto_id'];
                                    }
                                }

                                foreach ($articoli as $articolo) :

                                    $image = null;
                                    // $fw_product = $this->apilib->searchFirst('fw_products', [
                                    //     'fw_products_id' => $articolo['documenti_contabilita_articoli_prodotto_id']
                                    // ]);
                                    $fw_product = $this->db->get_where('fw_products', [
                                        'fw_products_id' => $articolo['documenti_contabilita_articoli_prodotto_id']
                                    ])->row_array();
                                    // debug($articolo['documenti_contabilita_articoli_prodotto_id']);
                                    // debug($fw_product);
                                    // if (!empty($mov_art_ids) && in_array($articolo['documenti_contabilita_articoli_prodotto_id'], array_values($mov_art_ids))) {
                                    //     continue;
                                    // }

                                    if (!empty($fw_product['fw_products_images'])) {
                                        $images = json_decode($fw_product['fw_products_images'], true);
                                        //debug($images, true);
                                        $image = array_shift($images)['path_local'];
                                    }

                                    $quantity = 0;

                                    if ($this->datab->module_installed('magazzino') && !empty($fw_product)) :
                                        $quantity_carico = $this->db->query("
                                        SELECT SUM(movimenti_articoli_quantita) as qty 
                                        FROM movimenti_articoli 
                                        LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) 
                                        WHERE movimenti_tipo_movimento = 1 
                                        AND movimenti_articoli_prodotto_id = '{$fw_product['fw_products_id']}' 
                                        AND movimenti_magazzino = '{$picking_filter['magazzino']}'
                                        ")->row()->qty;

                                        $quantity_scarico = $this->db->query("
                                        SELECT SUM(movimenti_articoli_quantita) as qty 
                                        FROM movimenti_articoli 
                                        LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) 
                                        WHERE movimenti_tipo_movimento = 2 
                                        AND movimenti_articoli_prodotto_id = '{$fw_product['fw_products_id']}' 
                                        AND movimenti_magazzino = '{$picking_filter['magazzino']}'
                                        ")->row()->qty;

                                        $quantity = $quantity_carico - $quantity_scarico;
                                    endif;

                                    //debug($articolo);

                                ?>

                                    <?php for ($i = 0; $i < $articolo['documenti_contabilita_articoli_quantita']; $i++) :
                                    ?>
                                        <tr class="text-uppercase <?php echo ($quantity > 0) ? null : 'bg-grey'; ?><?php if ($articolo['documenti_contabilita_articoli_qty_movimentate'] > $i) : ?> js_movimentato <?php endif; ?>">
                                            <td width="100"><?php if (!empty($image)) : ?><a href="<?php echo base_url("uploads/{$image}"); ?>" class="fancybox"><img src="<?php echo base_url("imgn/1/50/50/uploads/{$image}"); ?>" class="img-thumbnail" alt=""></a><?php endif; ?></td>
                                            <td width="500"><?php echo $articolo['documenti_contabilita_articoli_name'] ?>
                                                <?php if (!empty($fw_product['fw_products_barcode'])) : ?>
                                                    <?php
                                                    //debug($fw_product);
                                                    $ean = json_decode($fw_product['fw_products_barcode'], true);
                                                    if (is_array($ean)) {
                                                        $ean = $ean[0];
                                                    }

                                                    ?>
                                                    <br />
                                                    <small style="font-weight: bold">EAN: </small>
                                                    <small class="ean" data-ean="<?php echo $ean; ?>"><?php echo $ean; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <!--<td>
                                                <?php echo $articolo['documenti_contabilita_articoli_quantita'], ' ', $articolo['documenti_contabilita_articoli_unita_misura'] ?>
                                            </td>-->
                                            <td>
                                                <ul class="list-unstyled">
                                                    <?php if (!empty($fw_product)) : foreach ($magazzini as $magazzino) : ?>
                                                            <?php
                                                            $qty_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$fw_product['fw_products_id']}' AND movimenti_magazzino = '{$magazzino['magazzini_id']}'")->row()->qty;
                                                            $qty_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$fw_product['fw_products_id']}' AND movimenti_magazzino = '{$magazzino['magazzini_id']}'")->row()->qty;
                                                            $qty = $qty_carico - $qty_scarico;
                                                            ?>
                                                            <li><?php echo $magazzino['magazzini_titolo']; ?>: <?php echo $qty; ?></li>
                                                    <?php endforeach;
                                                    endif; ?>
                                                </ul>
                                            </td>
                                            <td><?php echo (!empty($fw_product['fw_products_warehouse_location'])) ? $fw_product['fw_products_warehouse_location'] : null ?></td>
                                            <td class="text-right">
                                                <?php if ($articolo['documenti_contabilita_articoli_qty_movimentate'] <= $i) : ?>
                                                    <label class="container_check <?php echo ($quantity > 0 || empty($fw_product)) ? null : 'disabled' ?>">
                                                        <input type="checkbox" <?php echo ($quantity > 0 || empty($fw_product)) ? null : 'disabled' ?> data-articolo_id="<?php echo $articolo['documenti_contabilita_articoli_id'] ?>" class="js_checkbox_riga">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endfor;
                                    ?>
                                <?php unset($fw_product);
                                endforeach; ?>

                                <tr class="text-right">
                                    <td colspan="6" class="confirm_row">
                                        <button type="button" class="btn btn-sm btn-success btn-partial">Conferma ordine parziale</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
        </div>





<?php endforeach;
endif; ?>
<div class="modal fade" id="modal_order">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title">Conferma articoli</h4>
            </div>

            <div class="modal-body">
                <p>Sei sicuro di aver inserito nel pacco questi articoli?</p>
                <table class="table table-articoli table-striped">
                    <thead>
                        <tr>
                            <th style="width: 50px">Immagine</th>
                            <th>Articolo</th>
                            <th>Quantità</th>
                            <th>Posizione</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <form class="form form-horizontal formAjax mt-15" action="<?php echo base_url('magazzino/picking/save'); ?>" method="post" id="modal_picking_form">
                <div class="modal-body">

                    <input type="hidden" name="documento_id">
                    <input type="hidden" name="stato">
                    <input type="hidden" name="articoli_id">
                    <input type="hidden" name="quantities">
                    <input type="hidden" name="magazzino_id">
                    <?php add_csrf(); ?>

                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-5 control-label text-left">Seleziona il corriere</label>
                        <div class="col-sm-7">
                            <select name="vettore" class="form-control js-select2">
                                <option value=""></option>
                                <?php
                                foreach ($vettori as $vettore) {
                                    echo '<option class="' . $vettore['vettori_ragione_sociale'] . '">', $vettore['vettori_ragione_sociale'], '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-5 control-label text-left">Hai il tracking code dell'ordine? Inseriscilo qui</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control" name="tracking_code" placeholder="Tracking code">
                        </div>
                    </div>

                    <div class="form-group">
                        <div id="msg_modal_picking_form" class="alert alert-danger hide"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal" onclick="this.form.reset();">Chiudi</button>
                    <button type="submit" class="btn btn-success js_order_confirm">Conferma</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function manageChecked($order_container, open_modal) {
        var documento_id = $order_container.data('documento_id');

        var $articoli = $('.js_checkbox_riga:checked', $order_container);

        var articoli_id = [];
        var quantities = {};
        $articoli.each(function() {
            articoli_id.push($(this).data('articolo_id'));
            if (!quantities.hasOwnProperty('art_' + $(this).data('articolo_id'))) {
                quantities["art_" + $(this).data('articolo_id')] = 0;
            }
            quantities["art_" + $(this).data('articolo_id')]++;
        });
        console.log(quantities);
        var articoli_id = articoli_id.join(',');
        var all_checked = $articoli.length == $('.js_checkbox_riga', $order_container).length;
        $('#modal_order').find('[name="articoli_id"]').val(articoli_id);
        $('#modal_order').find('[name="quantities"]').val(JSON.stringify(quantities));
        $('#modal_order').find('[name="documento_id"]').val(documento_id);
        $('#modal_order').find('[name="stato"]').val(all_checked ? 'closed' : 'partial');
        $('#modal_order').find('[name="magazzino_id"]').val($order_container.find('tbody').data('magazzino'));
        if (open_modal || all_checked) {
            $.ajax({
                url: base_url + 'magazzino/picking/getarticoli',
                type: 'post',
                data: {
                    [token_name]: token_hash,
                    articoli_id: articoli_id,
                    quantities: quantities,
                },
                dataType: 'html',
                async: false,
                success: function(response) {
                    $('#modal_order').find('tbody').html(response);



                    $('#modal_order').modal('toggle', {
                        backdrop: false,
                        keyboard: false
                    });

                },
                error: function() {

                }
            });

        }
    }
    $(document).ready(function() {

        $('.js-select2').select2({
            width: 'resolve'
        });
        $(".btn-partial").on('click', function() {
            var $order_container = $(this).closest('.js_table_order');

            manageChecked($order_container, true);

        });
        $('.js_checkbox_riga').on('change', function() {
            var $order_container = $(this).closest('.js_table_order');

            manageChecked($order_container, false);

        });

    });




    var accantonamenti = <?php echo json_encode($accantonamenti_map_ean); ?>;

    $(function() {
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('tbody').html('');
            $(this).find('.js-select2').select2('destroy').val('');
        });

        $('.js_ean').on('keyup change', function() {
            var ean = $(this).val().trim();
            if (ean == '') {
                return;
            }
            var orders = $('.row-ordine');

            if ((ean in accantonamenti)) {
                var ordine_id = accantonamenti[ean].pop();
                $('small.ean[data-ean="' + ean + '"]', $('[data-id_ordine="' + ordine_id + '"]')).closest('tr').find('input[type="checkbox"]:not(:checked)').trigger('click');
            } else {
                var found = false;
                $.each(orders, function() {
                    var order_container = $(this);
                    var order_id = order_container.data('id_ordine');
                    if (found) return;

                    var checkbox = $('small.ean[data-ean="' + ean + '"]', $('[data-id_ordine="' + order_id + '"]')).closest('tr').find('input[type="checkbox"]:not(:checked)').first();
                    if (checkbox.length > 0) {
                        found = true;
                        checkbox.trigger('click');
                        return;
                    }



                    // $.each($('small.ean', order_container), function() {
                    //     var ean_code = $(this).text();

                    //     if (ean_code.length > 0 && ean_code === ean) {
                    //         found = true;

                    //         $(this).closest('tr').find('input[type="checkbox"]:not(:checked)').trigger('click');

                    //         return;
                    //     }
                    // });
                });

            }
            if (found) {
                toastr.success('Articoli selezionati');
            } else {
                toastr.warning('Ean non trovato!');
            }

            $(this).val('')
        });
    })
</script>