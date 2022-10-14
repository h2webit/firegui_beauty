<?php
$this->load->model('magazzino/mappature');
$mappature = $this->mappature->getMappature();
extract($mappature);

$magazzini = $this->apilib->search('magazzini');

$settings = $this->apilib->searchFirst('magazzino_settings');

$prodotti_inventario_non_confermati = $this->apilib->count('prodotti_inventario', ['prodotti_inventario_confermato' => DB_BOOL_FALSE]);

$current_userdata = $this->auth->getSessionUserdata();
$magazzino_id = $current_userdata['users_magazzino'];

$form_filtro_inventario_azzeramento_quantita = $this->datab->get_form_id_by_identifier('filtro_inventario_azzeramento_quantita');
$form = $this->datab->get_form($form_filtro_inventario_azzeramento_quantita, null);
$formHtml = $this->load->view("pages/layouts/forms/form_{$form['forms']['forms_layout']}", [
    'form' => $form,
    'ref_id' => 'test',
    'value_id' => null,
    'layout_data_detail' => null,
], true);

$filters = $this->session->userdata(SESS_WHERE_DATA);

// Costruisco uno specchietto di filtri autogenerati leggibile
$filtri = array();

if (!empty($filters["filtro_inventario_azzeramento_quantita"])) {
    foreach ($filters["filtro_inventario_azzeramento_quantita"] as $field) {
        if ($field['value'] == '-1') {
            continue;
        }
        $filter_field = $this->datab->get_field($field["field_id"], true);
        $field_name = $filter_field['fields_name'];
        switch ($field_name) {
            // case 'movimenti_magazzino':
            //     $filtri[] = "";
            //     break;
            default:
                // debug("Filtro {$field_name} non riconosciuto!");
                break;
        }
    }
}

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

.action_container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.action_container h3 {
    margin: 0;
}
</style>
<div class="row">
    <?php if ($settings['magazzino_settings_inventario_in_corso']): ?>
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border  ">
                    <div class="box-title">
                        <i class=" fas fa-bars"></i>
                        <span data-layou_box_id="4339" class="js_layouts_boxes_title">Azzeramento quantità</span>
                    </div>
                    <div class="box-tools"></div>
                </div>
                <div class="box-body">
                <?php echo $formHtml; ?>
                </div>
            </div>
        </div>
    <?php endif;?>
    <div class="col-sm-6">
        <div class="btn-group">
            <?php if (!$settings['magazzino_settings_inventario_in_corso']): ?>
                <a href="<?php echo base_url('db_ajax/switch_bool/magazzino_settings_inventario_in_corso/' . $settings['magazzino_settings_id']); ?>" class="btn btn-lg btn-success mb-10">Avvia inventario</a>
            <?php elseif (!$prodotti_inventario_non_confermati): ?>
                <?php if (!empty($filters["filtro_inventario_azzeramento_quantita"])): ?>
                    <a href="<?php echo base_url('magazzino/movimenti/azzera_quantita'); ?>" class="btn btn-lg btn-success mb-10" type="button" onclick="return confirm('Sei sicuro di voler azzerare le quantità? Controlla bene i filtri a destra prima di confermare!')">Azzera quantità prodotti non sparati</a>
                    &nbsp;
                <?php endif;?>
                <a href="<?php echo base_url('db_ajax/switch_bool/magazzino_settings_inventario_in_corso/' . $settings['magazzino_settings_id']); ?>" type="button" class="btn btn-lg btn-danger mb-10" onclick="return confirm('Sei sicuro di voler chiudere inventario? Hai già azzerato le quantità?')">Fine inventario</a>
            <?php endif;?>
        </div>
    </div>
</div>
<?php if ($settings['magazzino_settings_inventario_in_corso']): ?>
<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-body">
                <div class="col-md-4">
                    <select name="magazzino_inventario" id="" class="select2 form-control input-lg js_set_magazzino">
                        <?php foreach ($magazzini as $magazzino): ?>
                        <option value="<?php echo $magazzino['magazzini_id']; ?>"> <?php echo $magazzino['magazzini_titolo']; ?></option>
                        <?php endforeach;?>
                    </select>
                </div>

                <div class="col-md-4">
                    <input type="text" class="form-control input-lg" id="inputBarcode" placeholder="Barcode" autofocus="autofocus" style="min-width:100% !important;">
                </div>
                <div class="col-md-4">
                    <div class="action_container">
                        <button class="btn btn-warning btn-lg js_btn_crea_movimento"><i class="fas fa-save"></i>
                            Conferma e procedi...
                        </button>
                        <h3 class="js_item_counter">0 / 50</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="clearfix visible-sm-block"></div>
</div>

<script src="<?php echo base_url('/template/adminlte/bower_components/jquery-ui/jquery-ui.min.js?v=1.9.4'); ?>"></script>

<script>
var magazzino = null;

/* var user_magazzino = <?php echo $magazzino_id; ?>;
console.log(user_magazzino, user_magazzino.length); */

var timeout = null;

function countTableRows() {
    var table = $('table').find('[data-entity="prodotti_inventario"]');
    var tr_rows = $('tbody tr', table[0]).length;



    if (tr_rows >= 50) {
        $('.js_item_counter').addClass('text-red');
        $('.js_item_counter').html(tr_rows + ' / 50' + '<br /><span style="font-size:0.5em;">Si consiglia di confermare i parziali e procedere...</span>');
    } else {
        $('.js_item_counter').text(tr_rows + ' / 50');
        $('.js_item_counter').removeClass('text-red');
    }



    //Se qtà attesa != sparata deve mettere riga in rosso, se uguali in verde
    var table_body_row = $('tbody tr', table[0]);
    $.each(table_body_row, function(i, item) {
        var tr = $(item).closest('tr');
        var qta_inserita = parseInt($('.qta_inserita', item).text());
        var qta_attesa = parseInt($('.qta_attesa', item).text());

        if (qta_inserita != qta_attesa) {
            tr.addClass('danger');
            tr.removeClass('success');
        } else if (qta_inserita == qta_attesa) {
            tr.addClass('success');
            tr.removeClass('danger');
        }
    });
}


function insertProdotto(prodotto, magazzino) {
    var data = [];

    $.ajax({
        url: base_url + 'magazzino/movimenti/insertProduct',
        type: 'POST',
        async: false,
        dataType: 'json',
        data: {
            [token_name]: token_hash,
            'product': JSON.stringify(prodotto),
            'magazzino': magazzino,
        },
        success: function(response) {
            //console.log('TODO: aggiornare conteggio');
    
            $('.js_fg_grid_prodotti_inventario').DataTable().ajax.reload();
            // refreshGridsByEntity('prodotti_inventario');
            //refreshAjaxLayoutBoxes();


            setTimeout(countTableRows, 2000);
            auto_focus_barcode();

        },
        error: function(jqXHR, exception, errorMessage) {
            //refreshAjaxLayoutBoxes();
            console.log(jqXHR);
            console.log(exception);
            console.log(errorMessage);
        },
    });

    return data;
}

function searchBarcode(barcode) {
    var data = [];

    $.ajax({
        url: base_url + 'magazzino/movimenti/searchBarcodeFiltered',
        type: 'POST',
        async: false,
        dataType: 'json',
        data: {
            [token_name]: token_hash,
            'barcode': barcode,
            'magazzino': magazzino,
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

function auto_focus_barcode() {
    $('#inputBarcode').val('');
    $('#inputBarcode').focus();
}

$(document).ready(function() {

    //Conta righe della tabella al load della pagina
    setTimeout(countTableRows, 2000);


    setTimeout(auto_focus_barcode, 1000);

    $('.js_btn_crea_movimento').on('click', function() {
        if ($('.js_fg_grid_prodotti_inventario tbody tr').length != 0 && magazzino) {
            location.href = base_url + 'magazzino/movimenti/salva_inventario/' + magazzino;
        } else {
            alert('Non puoi salvare senza aver inserito almeno un barcode o senza magazzino');
        }
    })
    
    $('table.js_fg_grid_prodotti_inventario').on('draw.dt', function () {
        $('.js_change_quantity').on('change', function() {
            var this_val = $(this).val();
            var this_id = $(this).data('id');
        
            if ($.isNumeric(this_val)) {
                $.ajax({
                    url: base_url + 'db_ajax/change_value/prodotti_inventario/'+this_id+'/prodotti_inventario_qta_sparata/'+this_val,
                    ajax:false,
                    dataType: 'json',
                    type: 'get',
                    success: function(res) {},
                    error: function(status, request, error) {
                        alert("Errore aggiornamento quantita: "+error);
                    }
                });
            }
        });
    });
    
    $('#inputBarcode').on('change', function() {
        clearTimeout(timeout);

        const barcode = $(this).val();

        if (barcode.length != 0) {
            timeout = setTimeout(function() {
                var dati_prodotto = searchBarcode(barcode);

                if (dati_prodotto['status'] == 1) {
                    insertProdotto(dati_prodotto['data'], magazzino);

                    $(this).val('');
                } else {
                    alert('Errore: ' + dati_prodotto['data']);
                }

                return false;
            }, 500);
        }
    })


    //Set magazzino value on change
    $('.js_set_magazzino').on("change", function() {
        magazzino = $(this).val();
    });

    //Set magazzino value on page load as user magazzino in session or as first option value
    <?php if (!empty($magazzino_id)): ?>
    $(".js_set_magazzino").val('<?php echo $magazzino_id; ?>').change();
    magazzino = $(".js_set_magazzino option:selected").val();
    <?php else: ?>
    $(".js_set_magazzino option:first").attr('selected', 'selected');
    magazzino = $(".js_set_magazzino option:selected").val();
    <?php endif;?>
});
</script>
<?php endif;?>