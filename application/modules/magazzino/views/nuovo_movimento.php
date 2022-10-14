<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

<style>
#js_product_table>tbody>tr>td,
#js_product_table>tbody>tr>th,
#js_product_table>tfoot>tr>td,
#js_product_table>tfoot>tr>th,
#js_product_table>thead>tr>td {
    vertical-align: top;
}

.row {
    margin-left: 0px !important;
    margin-right: 0px !important;
}

button {
    outline: none;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

.button_selected {
    opacity: 0.6;
}

.table_prodotti td {
    vertical-align: top;
}

.totali label {
    display: block;
    font-weight: normal;
    text-align: left;
}

.totali label span {
    font-weight: bold;
    float: right;
}

label {
    font-size: 0.8em;
}

.rcr-adjust {
    width: 40%;
    display: inline;
}

.rcr_label label {
    width: 100%;
}

.margin-bottom-5 {
    margin-bottom: 5px;
}

.margin-left-20 {
    margin-left: 20px;
}

.mt-5 {
    margin-top: 5px;
}

.mb-5 {
    margin-bottom: 5px;
}

th,
td {
    border: none !important;
}

#js_product_table {
    margin-bottom: 0px;
}

.w-100 {
    width: 100% !important;
}

.product_icons {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.js_check_qty {
    margin-right: 20px;
}

.js_remove_product {
    padding: 1px 7px;
}

.info_product_icons {
    margin-top: 15px;
    margin-bottom: 15px;
}

.js_alert_missing_product {
    margin-right: 15px;
}

.info_movimento_field {
    display: flex;
    flex-direction: column;
}

hr {
    margin-top: 15px;
    margin-bottom: 15px;
}
</style>

<?php
$settings = $this->apilib->searchFirst('magazzino_settings');

$dati['id'] = null;
$dati['movimento'] = null;
$dati['movimenti_cliente'] = null;

$dati['prodotti'] = null;

/*
 * Install constants
 */

/** Entità **/
defined('ENTITY_SETTINGS') or define('ENTITY_SETTINGS', 'settings');

$this->load->model('magazzino/mappature');
$mappature = $this->mappature->getMappature();
extract($mappature);

/** Parametri **/
$movimenti_id = ($value_id) ?: $this->input->get('movimenti_id');
$documento_id = $this->input->get('documenti_id');
$ordine_produzione_id = $this->input->get('ordine_produzione_id');
$prodotti_id = $this->input->get('articoli_ids');
$movimenti_mittente = $this->apilib->search('movimenti_mittente');
$magazzini = $this->apilib->search('magazzini');
$causali_carico = $this->apilib->search('movimenti_causali', ['movimenti_causali_tipologia_movimento' => 1]);
$causali_scarico = $this->apilib->search('movimenti_causali', ['movimenti_causali_tipologia_movimento' => 2]);
$tipi_movimento = $this->apilib->search('movimenti_tipo_movimento');
$movimenti_documento_tipo = $this->apilib->search('movimenti_documento_tipo');
$unita_misura = $this->apilib->search('fw_products_unita_misura');
$tipo_mov = $this->input->get('tipo_mov');
$causale_movimento = $this->input->get('movimenti_causale');
try {
    $iva_exists = $this->db->table_exists('iva');
    if ($iva_exists) {
        $elenco_iva = $this->apilib->search('iva', [], null, 0, 'iva_order');
        // $elenco_iva = $this->db->order_by('iva_order', 'ASC')->get('iva')->results_array();
    } else {
        $elenco_iva = [];
    }
} catch (Exception $e) {
    log_message('error', $e->getMessage());
}

$clone = $this->input->get('clone');
if ($movimenti_id) {
    $movimento = $this->apilib->view('movimenti', $movimenti_id);

    if ($movimento['movimenti_documento_id']) {
        $articoli_origine = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $movimento['movimenti_documento_id']]);
        $movimento['ordine_articoli_origine'] = [];
        foreach ($articoli_origine as $art) {
            $movimento['ordine_articoli_origine'][$art['documenti_contabilita_articoli_prodotto_id']] = $art['documenti_contabilita_articoli_quantita'];
        }
    }

    $movimento['articoli'] = $this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $movimenti_id]);
    $movimento['movimenti_destinatario'] = json_decode($movimento['movimenti_destinatario'], true);
} elseif ($documento_id) {
    $documento = $this->apilib->view('documenti_contabilita', $documento_id);

    //debug($documento, true);

    $documento['documenti_contabilita_destinatario'] = json_decode($documento['documenti_contabilita_destinatario'], true);
    $clone = true;

    $movimento = [];
    $movimento['articoli'] = [];
    foreach ($documento as $field => $value) {
        $field = str_ireplace('documenti_contabilita_', 'movimenti_', $field);
        $movimento[$field] = $value;
    }

    //debug($movimento,true);

    $movimento['movimenti_numero_documento'] = $movimento['movimenti_numero'];

    $movimento['movimenti_documento_id'] = $documento_id;

    $movimento['movimenti_documento_tipo'] = $documento['documenti_contabilita_tipo'];

    //Carico se vengo da ordine fornitore o da ddt fornitore
    $movimento['movimenti_tipo_movimento'] = (in_array($documento['documenti_contabilita_tipo'], [6, 10])) ? 1 : 2;

    $documento['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ["documenti_contabilita_articoli_documento" => $documento_id]);
    foreach ($documento['articoli'] as $articolo) {

        $_articolo = [];
        foreach ($articolo as $field => $value) {
            $field = str_ireplace('documenti_contabilita_articoli_', 'movimenti_articoli_', $field);
            $_articolo[$field] = $value;
        }
        //Il barcode non esiste nella tabella documenti_contabilita_articoli, quindi lo prendo dal prodotto o lo setto a vuoto
        if (!empty($articolo['documenti_contabilita_articoli_prodotto_id']) && $prodotto = $this->apilib->view('fw_products', $articolo['documenti_contabilita_articoli_prodotto_id'])) {
            if (is_array(json_decode($prodotto['fw_products_barcode']))) {
                $_articolo['movimenti_articoli_barcode'] = json_decode($prodotto['fw_products_barcode'])[0];
            } else {
                $_articolo['movimenti_articoli_barcode'] = $prodotto['fw_products_barcode'];
            }
        } else {
            $_articolo['movimenti_articoli_barcode'] = '';
        }
        $_articolo['movimenti_articoli_lotto'] = '';
        $movimento['articoli'][] = $_articolo;
    }
} elseif ($prodotti_id) {

    $products = $this->apilib->search($entita_prodotti, [$campo_id_prodotto => $prodotti_id]);

    //debug($products);
} elseif ($ordine_produzione_id && !$prodotti_id) {
    $ordine_produzione = $this->apilib->view('ordini_produzione', $ordine_produzione_id);

    // $distinta_base = $this->apilib->view('distinte_base', $ordine_produzione['ordini_produzione_distinta_base']);

    $distinta_base_righe = $this->apilib->search('distinte_base_righe', ['distinte_base_righe_distinta_base_id' => $ordine_produzione['ordini_produzione_distinta_base']]);

    // debug($ordine_produzione);

    $movimento = [];
    $movimento['articoli'] = [];
    
    
    if ($tipo_mov == 1) { //Se è carico devo caricare il prodotto messo in produzione
        
        $prodotto = $this->apilib->view('fw_products', $ordine_produzione['distinte_base_prodotto']);
        $articolo = [];

            $articolo['movimenti_articoli_barcode'] = '';
            if (!empty($prodotto['fw_products_barcode'])) {
                if (is_array(json_decode($prodotto['fw_products_barcode']))) {
                    $articolo['movimenti_articoli_barcode'] = json_decode($prodotto['fw_products_barcode'])[0];
                } else {
                    $articolo['movimenti_articoli_barcode'] = $prodotto['fw_products_barcode'];
                }
}

            $articolo['movimenti_articoli_quantita'] = $ordine_produzione['ordini_produzione_quantita'];
            $articolo['movimenti_articoli_prezzo'] = $prodotto['fw_products_provider_price'];
            
            
            $articolo['movimenti_articoli_lotto'] = '';
            $articolo['movimenti_articoli_prodotto_id'] = $ordine_produzione['distinte_base_prodotto'];
            $articolo['movimenti_articoli_iva_id'] = '';
            
            

            
                    $articolo['movimenti_articoli_name'] = $prodotto['fw_products_name'];
                    $articolo['movimenti_articoli_codice'] = $prodotto['fw_products_sku'];
                    $articolo['movimenti_articoli_prodotto_id'] = $prodotto['fw_products_id'];
                    $articolo['movimenti_articoli_unita_misura'] = $prodotto['fw_products_unita_misura'];
                    $articolo['movimenti_articoli_descrizione'] = $prodotto['fw_products_description'];
            
            $movimento['articoli'][] = $articolo;
    } else { //Se è scarico, devo prendere gli articoli dalle righe della distinta
        
        //debug($ordine_produzione,true);
        if (!empty($distinta_base_righe)) {
        foreach ($distinta_base_righe as $riga) {
            $articolo = [];

            $articolo['movimenti_articoli_barcode'] = '';
            if (!empty($riga['fw_products_barcode'])) {
                if (is_array(json_decode($riga['fw_products_barcode']))) {
                    $articolo['movimenti_articoli_barcode'] = json_decode($riga['fw_products_barcode'])[0];
                } else {
                    $articolo['movimenti_articoli_barcode'] = $riga['fw_products_barcode'];
                }
}
            //debug($ordine_produzione,true);
            $articolo['movimenti_articoli_quantita'] = ($riga['distinte_base_righe_quantita'] * $ordine_produzione['ordini_produzione_quantita'] / ($ordine_produzione['distinte_base_scala']/100))/$ordine_produzione['distinte_base_quantita'];
            $articolo['movimenti_articoli_prezzo'] = $riga['distinte_base_righe_costo_unitario'];
            $articolo['movimenti_articoli_name'] = $riga['distinte_base_righe_descrizione'];
            $articolo['movimenti_articoli_codice'] = '';
            $articolo['movimenti_articoli_lotto'] = '';
            $articolo['movimenti_articoli_prodotto_id'] = null;
            $articolo['movimenti_articoli_iva_id'] = '';
            $articolo['movimenti_articoli_unita_misura'] = '';
            $articolo['movimenti_articoli_descrizione'] = $riga['distinte_base_righe_descrizione'];
        
            if (!empty($riga['distinte_base_righe_prodotto_id'])) {
                $fw_prod = $this->apilib->view('fw_products', $riga['distinte_base_righe_prodotto_id']);
                
                if (!empty($fw_prod)){
                    $articolo['movimenti_articoli_name'] = $fw_prod['fw_products_name'];
                    $articolo['movimenti_articoli_codice'] = $fw_prod['fw_products_sku'];
                    $articolo['movimenti_articoli_prodotto_id'] = $fw_prod['fw_products_id'];
                    $articolo['movimenti_articoli_unita_misura'] = $fw_prod['fw_products_unita_misura'];
                    $articolo['movimenti_articoli_descrizione'] = $fw_prod['fw_products_description'];
                } else {
                    continue;
                }

            } else {
                    continue;
                }

            $movimento['articoli'][] = $articolo;
        }
    }
    }
    
}

// debug($movimento);

//A cosa serviva questa mappatura?
// $doctype_map = [
//     '1' => '1', // FATTURA -> FATTURA
//     '2', // FATTURA ELETTRONICA
//     '8' => '3', // DDT -> FATTURA ACCOMPAGNATORIA (non ne sono sicuro)
//     '4' => '4', // NOTA DI CREDITO -> NOTA DI CREDITO
//     '3' => '6', // FATTURA PROFORMA -> FATTURA PROFORMA
//     '7' => '5', // PREVENTIVO -> PREVENTIVO
//     '5' => '7', // ORDINE CLIENTE -> ORDINE (non ne sono sicuro)
//     '6' => '8', // ORDINE FORNITORE -> ORDINE FORNITORE
//     '9',
//     '10',
//     '11',
//     '12' => 10
// ];

?>


<style>
.riga_anomala {
    background-color: #F99;
}
</style>

<?php if ($settings['magazzino_settings_inventario_in_corso']): ?>
    <section class="content-header">
        <div class="alert alert-danger mb-0">
            <h5>Inventario in corso!</h5>

            <div><?php e('Attenzione! E\' in corso l\'inventario. Si sconsiglia di movimentare la merce in questa fase.');?></div>
        </div>
    </section>
<?php endif;?>


<form class="formAjax" id="new_movimento" action="<?php echo base_url('magazzino/movimenti/nuovo_movimento'); ?>">
    <?php add_csrf(); ?>
    <?php if ($movimenti_id && !$clone) : ?>
    <input name="movimenti_id" type="hidden" value="<?php echo $movimenti_id; ?>" />
    <?php endif; ?>

    <?php if ($ordine_produzione_id) : ?>
    <input name="movimenti_ordine_produzione_id" type="hidden" value="<?php echo $ordine_produzione_id; ?>" />
    <?php endif; ?>

    <input type="hidden" name="movimenti_totale" value="<?php echo ($movimenti_id && $movimento['movimenti_totale']) ? $movimento['movimenti_totale'] : ''; ?>" />

    <div class="row">
        <div class="col-md-12 text-center mt-5 mb-5">

            <div class="btn-group">
                <?php foreach ($movimenti_mittente as $mittente) : ?>
                <button type="button" style="margin-left:10px;" class="btn btn-lg <?php if ($movimenti_id && ($movimenti_id && $movimento['movimenti_mittente'] == $mittente['movimenti_mittente_id'])) : ?>btn-primary<?php else : ?>btn-default<?php endif; ?> js_btn_mittente" data-tipo="<?php echo $mittente['movimenti_mittente_id']; ?>">Movimento <?php echo $mittente['movimenti_mittente_value']; ?></button>
                <?php endforeach; ?>
                <input type="hidden" name="movimenti_mittente" class="js_movimenti_mittente" value="<?php if ($movimenti_id && $movimento['movimenti_mittente']) : ?><?php echo $movimento['movimenti_mittente']; ?><?php endif; ?>" />
            </div>
        </div>
    </div>
    <hr />

    <div class="row">

        <div class="col-md-4">

            <div class="form-group">
                <label>Tipo: </label>

                <select name="movimenti_tipo_movimento" class="__select2 form-control js_movimenti_tipo input-lg">
                    <?php foreach ($tipi_movimento as $tipo_movimento) : ?>
                    <option value="<?php echo $tipo_movimento['movimenti_tipo_movimento_id']; ?>" <?php if ((!empty($movimento['movimenti_tipo_movimento']) && $movimento['movimenti_tipo_movimento'] == $tipo_movimento['movimenti_tipo_movimento_id']) || (!empty($tipo_mov) && $tipo_mov == $tipo_movimento['movimenti_tipo_movimento_id'])) : ?> selected="selected" <?php endif; ?>><?php echo $tipo_movimento['movimenti_tipo_movimento_value']; ?></option>
                    <?php endforeach; ?>

                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Causale: </label>
                <select name="movimenti_causale" class="__select2 form-control input-lg">
                    <optgroup label="Carico" class="js_movimenti_causali_carico">
                        <?php foreach ($causali_carico as $causale) : ?>
                        <option data-tipo="1" value="<?php echo $causale['movimenti_causali_id']; ?>" <?php if ((!empty($movimento['movimenti_causale']) && $movimento['movimenti_causale'] == $causale['movimenti_causali_id']) || (!empty($causale_movimento) && $causale_movimento == $causale['movimenti_causali_id'])) : ?> selected="selected" <?php endif; ?>><?php echo $causale['movimenti_causali_nome']; ?></option>
                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup label="Scarico" class="js_movimenti_causali_scarico">
                        <?php foreach ($causali_scarico as $causale) : ?>
                        <option data-tipo="2" value="<?php echo $causale['movimenti_causali_id']; ?>" <?php if ((!empty($movimento['movimenti_causale']) && $movimento['movimenti_causale'] == $causale['movimenti_causali_id']) || (!empty($causale_movimento) && $causale_movimento == $causale['movimenti_causali_id'])) : ?> selected="selected" <?php endif; ?>><?php echo $causale['movimenti_causali_nome']; ?></option>
                        <?php endforeach; ?>
                    </optgroup>

                </select>
                <?php //debug($causale, true); 
                ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label>Magazzino: </label>
                <select name="movimenti_magazzino" class="select2 form-control input-lg">
                    <?php foreach ($magazzini as $magazzino) : ?>
                    <option value="<?php echo $magazzino['magazzini_id']; ?>" <?php if (!empty($movimento['movimenti_magazzino']) && $movimento['movimenti_magazzino'] == $magazzino['magazzini_id']) : ?> selected="selected" <?php endif; ?>><?php echo $magazzino['magazzini_titolo']; ?></option>
                    <?php endforeach; ?>

                </select>
            </div>
        </div>


    </div>
    <hr />
    <div class="row">
        <div class="col-md-4 bg-gray js_dati_mittente">
            <div class="row">
                <div class="col-md-12">
                    <h4>Dati del <span class="js_dest_type"><?php if ($movimenti_id && $movimento['movimenti_fornitori_id']) : ?>fornitore<?php else : ?>cliente<?php endif; ?></span>
                    </h4>
                </div>
            </div>


            <input type="hidden" name="dest_entity_name" value="<?php if ($movimenti_id && $movimento['movimenti_fornitori_id']) : ?>suppliers<?php else : ?>customers<?php endif; ?>" />
            <input id="js_dest_id" type="hidden" name="dest_id" value="<?php if ($movimenti_id && $movimento['movimenti_clienti_id']) : ?><?php echo ($movimento['movimenti_clienti_id'] ?: $movimento['movimenti_fornitori_id']); ?><?php endif; ?>" />

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input id="search_cliente" type="text" name="ragione_sociale" class="form-control js_dest_ragione_sociale" placeholder="Ragione sociale" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["ragione_sociale"]; ?><?php endif; ?>" autocomplete="off" />
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="indirizzo" class="form-control js_dest_indirizzo" placeholder="Indirizzo" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["indirizzo"]; ?><?php endif; ?>" />
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="citta" class="form-control js_dest_citta" placeholder="Città" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["citta"]; ?><?php endif; ?>" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="nazione" class="form-control js_dest_nazione" placeholder="Nazione" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["nazione"]; ?><?php else : ?><?php echo "Italia"; ?><?php endif; ?>" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <input type="text" name="cap" class="form-control js_dest_cap" placeholder="CAP" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["cap"]; ?><?php endif; ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div clasS="form-group">
                        <input type="text" name="provincia" class="form-control js_dest_provincia" placeholder="Provincia" maxlength="2" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["provincia"]; ?><?php endif; ?>" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="partita_iva" class="form-control js_dest_partita_iva" placeholder="P.IVA" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["partita_iva"]; ?><?php endif; ?>" />
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="codice_fiscale" class="form-control js_dest_codice_fiscale" placeholder="Codice fiscale" value="<?php if (!empty($movimento['movimenti_destinatario'])) : ?><?php echo $movimento['movimenti_destinatario']["codice_fiscale"]; ?><?php endif; ?>" />
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label id="js_label_rubrica">Salva in rubrica</label>
                        <input type="checkbox" class="minimal" name="save_dest" value="true" />

                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-8 js_informazioni_movimento">
            <div class="row bg-light-blue" style="background-color: #6bbf81;">
                <div class="row">
                    <div class="col-md-12">
                        <h4>Informazioni movimento</h4>
                    </div>
                </div>
                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Data movimento: </label>

                            <div class="input-group js_form_datepicker date w-100">
                                <input type="text" name="movimenti_data_registrazione" class="form-control w-100" placeholder="Data registrazione" value="<?php if (!empty($movimento['movimenti_data_registrazione']) && !$clone) : ?><?php echo date('d/m/Y', strtotime($movimento['movimenti_data_registrazione'])); ?><?php else : ?><?php echo date('d/m/Y'); ?><?php endif; ?>" data-name="movimenti_data_registrazione" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group w-100">
                            <label>Tipo documento: </label>

                            <select name="movimenti_documento_tipo" class="select2 form-control w-100">
                                <option value=""></option>
                                <?php foreach ($movimenti_documento_tipo as $tipo_documento) : ?>
                                <option value="<?php echo $tipo_documento['movimenti_documento_tipo_id']; ?>" <?php if (!empty($movimento['movimenti_documento_tipo']) && $movimento['movimenti_documento_tipo'] == $tipo_documento['movimenti_documento_tipo_id']) : ?> selected="selected" <?php endif; ?>><?php echo $tipo_documento['movimenti_documento_tipo_value']; ?></option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group w-100">
                            <label>Numero documento: </label>

                            <input type="hidden" name="movimenti_numero_documento" class="form-control movimenti_numero_documento w-100" placeholder="Numero documento" value="<?php if (!empty($movimento['movimenti_numero_documento']) && ($clone || $movimenti_id)) : ?><?php echo $movimento['movimenti_numero_documento']; ?><?php endif; ?>" />


                            <select class=" form-control select2_standard" name="movimenti_documento_id" value="<?php if (!empty($movimento['movimenti_documento_id']) && ($clone || $movimenti_id)) : ?><?php echo $movimento['movimenti_documento_id']; ?><?php endif; ?>" data-required="0" data-source-field="" data-ref="documenti_contabilita" data-val="<?php if (!empty($movimento['movimenti_documento_id']) && ($clone || $movimenti_id)) : ?><?php echo $movimento['movimenti_documento_id']; ?><?php endif; ?>">
                                <option></option>
                                <?php foreach ($this->apilib->search('documenti_contabilita', [], 100, null, 'documenti_contabilita_data_emissione DESC') as $_documento) : ?>
                                <option data-tipo_documento="<?php echo $_documento['documenti_contabilita_tipo']; ?>" data-data_documento="<?php echo dateFormat($_documento['documenti_contabilita_data_emissione'], 'd/m/Y'); ?>" data-rif="<?php echo $_documento['documenti_contabilita_numero']; ?><?php if ($_documento['documenti_contabilita_serie']) : ?>/<?php echo $_documento['documenti_contabilita_serie']; ?><?php endif; ?>" value="<?php echo $_documento['documenti_contabilita_id']; ?>" <?php if (!empty($movimento['movimenti_documento_id']) && $movimento['movimenti_documento_id'] == $_documento['documenti_contabilita_id']) : ?> selected="selected" <?php endif; ?>>
                                    <?php echo $_documento['documenti_contabilita_numero']; ?><?php if ($_documento['documenti_contabilita_serie']) : ?>/<?php echo $_documento['documenti_contabilita_serie']; ?><?php endif; ?> - <?php echo json_decode($_documento['documenti_contabilita_destinatario'], true)['ragione_sociale']; ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="-1">Altro...</option>



                            </select>

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group w-100">
                            <label>Data documento: </label>
                            <?php //debug($movimento); 
                            ?>
                            <div class="input-group js_form_datepicker date w-100">
                                <input type="text" name="movimenti_data_documento" class="form-control w-100" placeholder="Data emissione" value="<?php if (!empty($movimento['movimenti_data_documento']) && !$clone) : ?><?php echo date('d/m/Y', strtotime($movimento['movimenti_data_documento'])); ?><?php else : ?><?php endif; ?>" data-name="movimenti_data_documento" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>



                </div>


            </div>

            <!--<div class="row" style="background-color:#e0eaf0;" id="carica_csv">
                <div class="col-md-12">
                    <h4>Caricamento lotto</h4>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Carica file di lotto: </label>

                            <input type="file" ___name="lotto_file" id="lotto_file" />
                            <button type="button" class="btn btn-sm btn-primary" id="submit_lotto_file">CARICA</button>
                        </div>
                    </div>

                </div>

            </div>-->

        </div>

    </div>

    <!-- Modal per lotti -->
    <div id="lotti_modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Selezionare il lotto da cui prelevare i prodotti</h4>
                </div>
                <!--<div class="modal-body">-->
                <div class="modal-body">
                    <div class="callout callout-danger" style="display: none;" id="lotti_modal_error"></div>
                    <table class="table table-responsive table-condensed table-striped table-bordered" id="lotti_table">
                        <thead>
                            <tr>
                                <th>Lotto</th>
                                <th>Data Scadenza</th>
                                <th>Quantità</th>

                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <!--</div>-->
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-12">
            <hr />
            <div class="row">
                <div class="col-md-3 col-md-offset-9 text-right">
                    <input type="checkbox" value="1" class="uso_pistola" checked="checked" /> Attiva pistola barcode
                </div>
                <div class="col-md-12">
                    <table id="js_product_table" class="table table-condensed table-striped table_prodotti">
                        <thead>
                            <tr>
                                <th width="200">Barcode</th>
                                <th width="200">Code (SKU)</th>
                                <th width="300">Nome prodotto <em style="color:#FF0000;">*</em></th>
                                <th width="100">Lotto</th>
                                <th width="150">Data Scadenza</th>
                                <th width="100">U.M.</th>
                                <th width="80">Qty</th>
                                <th width="150">Prezzo <span class="js_prezzo_label">acquisto</span></th>
                                <th width="150">Iva</th>

                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hidden">
                                <td style="position:relative;">
                                    <input type="text" class="form-control text-right input-sm js_movimenti_articoli_barcode js_autocomplete_prodotto" data-id="1" data-name="movimenti_articoli_barcode" />
                                <td>
                                    <input type="text" class="form-control text-right input-sm js_movimenti_articoli_codice js_autocomplete_prodotto" data-id="1" data-name="movimenti_articoli_codice" />
                                </td>
                                <td>
                                    <input type="text" class="form-control input-sm js_movimenti_articoli_name js_autocomplete_prodotto" data-id="1" data-name="movimenti_articoli_name" />
                                    <small>Descrizione aggiuntiva:</small>
                                    <textarea class="form-control input-sm js_movimenti_articoli_descrizione" data-name="movimenti_articoli_descrizione" style="width:100%;" row="2"></textarea>
                                </td>

                                <td>
                                    <input type="text" class="form-control input-sm text-right js_movimenti_articoli_lotto" data-name="movimenti_articoli_lotto" />
                                    <!--<input type="hidden" class="js_movimenti_articoli_lotto_id" data-name="movimenti_articoli_lotto_id" />-->
                                </td>
                                <td>
                                    <div class="input-group js_form_datepicker date">
                                        <input type="text" class="form-control input-sm text-right js_movimenti_articoli_data_scadenza" data-name="movimenti_articoli_data_scadenza" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display: none;">
                                                <i class="fa fa-calendar"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>

                                <td>

                                    <select class="form-control input-sm text-right js_movimenti_articoli_unita_misura" data-name="movimenti_articoli_unita_misura">
                                        <?php foreach ($unita_misura as $um) : ?>
                                        <option value="<?php echo $um['fw_products_unita_misura_id']; ?>"><?php echo $um['fw_products_unita_misura_value']; ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                </td>
                                <td><input type="text" class="form-control text-right input-sm js_movimenti_articoli_quantita" data-name="movimenti_articoli_quantita" value="1" />
                                    <!--<a href="#" data-toggle="tooltip" title="Questa spunta verrà automaticamente abilitata nella selezione di un lotto. Se tutti gli articoli avranno la spunta, l'ordine verrà considerato come chiuso!">Conferma</a>&nbsp;
                                    <input type="checkbox" class="js_movimenti_articoli_genera_movimenti" data-name="movimenti_articoli_genera_movimenti" value="<?php echo DB_BOOL_TRUE; ?>">-->
                                </td>
                                <td>
                                    <input type="text" class="form-control text-right input-sm js_movimenti_articoli_prezzo" data-name="movimenti_articoli_prezzo" value="" />

                                </td>


                                <td>

                                    <select class="form-control input-sm text-right js_movimenti_articoli_iva_id" data-name="movimenti_articoli_iva_id">
                                        <?php foreach ($elenco_iva as $iva) : ?>
                                        <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo $iva['iva_valore']; ?>"><?php echo $iva['iva_label']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" class="form-control input-sm text-right js_movimenti_articoli_iva" data-name="movimenti_articoli_iva" value="0" />
                                </td>




                                <td class="text-right">
                                    <input type="hidden" class="js_movimenti_articoli_prodotto_id" data-name="movimenti_articoli_prodotto_id" />
                                    <div class="product_icons">
                                        <button type="button" class="btn  btn-danger btn-xs js_remove_product">
                                            <span class="fas fa-trash"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- DA RIVEDEER POTREBBERO MANCARE DEI CAMPI QUANDO SI FARA L EDIT -->
                            <?php if (isset($movimento['articoli']) && $movimento['articoli']) : ?>
                            <?php foreach ($movimento['articoli'] as $k => $prodotto) : ?>

                            <?php

                                    if (!empty($movimento['ordine_articoli_origine'])) {
                                        if (empty($movimento['ordine_articoli_origine'][$prodotto['movimenti_articoli_prodotto_id']]) || $movimento['ordine_articoli_origine'][$prodotto['movimenti_articoli_prodotto_id']] != $prodotto['movimenti_articoli_quantita']) {
                                            $class = 'riga_anomala';
                                        } else {
                                            $class = '';
                                        }
                                    } else {
                                        $class = '';
                                    }
                                    //debug($prodotto, true);
                                    ?>

                            <tr class="<?php echo $class; ?>">
                                <td><input data-id="<?php echo $k + 1; ?>" type="text" class="form-control input-sm js_movimenti_articoli_barcode js_autocomplete_prodotto" name="products[<?php echo $k + 1; ?>][movimenti_articoli_barcode]" value="<?php echo $prodotto['movimenti_articoli_barcode']; ?>" />
                                <td><input data-id="<?php echo $k + 1; ?>" type="text" class="form-control input-sm text-right js_movimenti_articoli_codice js_autocomplete_prodotto" name="products[<?php echo $k + 1; ?>][movimenti_articoli_codice]" value="<?php echo $prodotto['movimenti_articoli_codice']; ?>" />
                                </td>
                                <td>
                                    <input data-id="<?php echo $k + 1; ?>" type="text" class="form-control input-sm js_movimenti_articoli_name js_autocomplete_prodotto" name="products[<?php echo $k + 1; ?>][movimenti_articoli_name]" value="<?php echo $prodotto['movimenti_articoli_name']; ?>" />
                                    <small>Descrizione aggiuntiva:</small>
                                    <textarea class="form-control input-sm js_movimenti_articoli_descrizione" name="products[<?php echo $k + 1; ?>][movimenti_articoli_descrizione]" style="width:100%;" row="2"><?php echo $prodotto['movimenti_articoli_descrizione']; ?></textarea>
                                </td>

                                <td>
                                    <input type="text" class="form-control input-sm text-right js_movimenti_articoli_lotto" name="products[<?php echo $k + 1; ?>][movimenti_articoli_lotto]" value="<?php echo $prodotto['movimenti_articoli_lotto']; ?>" />
                                    <!--<input type="hidden" class="js_movimenti_articoli_lotto_id" name="products[<?php echo $k + 1; ?>][movimenti_articoli_lotto_id]" value="<?php echo (!empty($prodotto['movimenti_articoli_lotto_id'])) ? $prodotto['movimenti_articoli_lotto_id'] : ''; ?>" />-->
                                </td>
                                <td>
                                    <div class="input-group js_form_datepicker date">
                                        <input type="text" class="form-control input-sm text-right js_movimenti_articoli_data_scadenza" name="products[<?php echo $k + 1; ?>][movimenti_articoli_data_scadenza]" value="<?php echo (!empty($prodotto['movimenti_articoli_data_scadenza'])) ? date('d/m/Y', strtotime($prodotto['movimenti_articoli_data_scadenza'])) : ''; ?>" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display: none;">
                                                <i class="fa fa-calendar"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <select class="form-control input-sm text-right js_movimenti_articoli_unita_misura" name="products[<?php echo $k + 1; ?>][movimenti_articoli_unita_misura]">
                                        <?php foreach ($unita_misura as $um) : ?>
                                        <option value="<?php echo $um['fw_products_unita_misura_id']; ?>" <?php if ($prodotto['movimenti_articoli_unita_misura'] == $um['fw_products_unita_misura_id']) : ?> selected="selected" <?php endif; ?>><?php echo $um['fw_products_unita_misura_value']; ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                </td>
                                <td><input type="text" class="form-control input-sm text-right js_movimenti_articoli_quantita" name="products[<?php echo $k + 1; ?>][movimenti_articoli_quantita]" value="<?php echo (int) $prodotto['movimenti_articoli_quantita']; ?>" placeholder="1" /></td>
                                <td>
                                    <input type="text" class="form-control text-right input-sm js_movimenti_articoli_prezzo" name="products[<?php echo $k + 1; ?>][movimenti_articoli_prezzo]" value="<?php echo $prodotto['movimenti_articoli_prezzo']; ?>" />

                                </td>


                                <td>

                                    <select class="form-control input-sm text-right js_movimenti_articoli_iva_id" name="products[<?php echo $k + 1; ?>][movimenti_articoli_iva_id]">
                                        <?php foreach ($elenco_iva as $iva) : ?>
                                        <option <?php if ($prodotto['movimenti_articoli_iva_id'] == $iva['iva_id']) : ?> selected="selected" <?php endif; ?>value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo $iva['iva_valore']; ?>"><?php echo $iva['iva_label']; ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                </td>



                                <td class="text-right">
                                    <input type="hidden" class="js_movimenti_articoli_prodotto_id" name="products[<?php echo $k + 1; ?>][movimenti_articoli_prodotto_id]" value="<?php echo $prodotto['movimenti_articoli_prodotto_id']; ?>" />
                                    <div class="product_icons">
                                        <button type="button" class="btn btn-danger btn-xs js_remove_product">
                                            <span class="fas fa-trash"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>
                                    <button id="js_add_product" type="button" class="btn btn-primary btn-sm"><span class="fas fa-plus"></span> Aggiungi prodotto
                                    </button>
                                </td>
                                <td colspan="3"></td>
                                <td class="totali hidden" colspan="3" style="background: #faf6ea">

                                    <label>Totale: <span class="js_competenze">€ 0</span></label>


                                </td>
                            </tr>
                            <tr class="_hidden js_missing_products_block">
                                <td colspan="5">
                                    <div class="info_product_icons">
                                        <span style="color:#F00;"><span class="fas fa-exclamation-triangle"></span></span> Prodotto non riconosciuto in catalogo.<br />
                                        <span style="color:#FFFF71;"><span class="fas fa-square"></span></span> Prodotto non presente nel magazzino selezionato.<br />
                                        <span style="color:#FF8C00;"><span class="fas fa-square"></span></span> Quantità selezionata non disponibile.<br />
                                    </div>

                                    <input type="checkbox" name="missing_products_insert" value="1" /> Spuntare qui se si vuole inserire questi prodotti in catalogo
                                </td>
                            </tr>
                        </tfoot>
                    </table>


                </div>
            </div>

            <hr />


        </div>


        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div id="msg_new_movimento" class="alert alert-danger hide"></div>
                </div>
            </div>
        </div>
    </div>


    <div class="form-actions fluid">
        <div class="col-md-offset-8 col-md-4">
            <div class="pull-right">
                <a href="<?php echo base_url('main/layout/movements-list'); ?>" class="btn btn-danger">Annulla</a>
                <button type="submit" class="btn btn-success">Salva</button>
            </div>
        </div>

    </div>
    </div>
</form>

<script>
$(".js_fattura_accompagnatoria_checkbox").change(function() {

    if ($(this).is(':checked')) {
        $(".js_fattura_accompagnatoria_row").removeClass('hide');
    } else {
        $(".js_fattura_accompagnatoria_row").addClass('hide');
    }


    //        if (!$( ".js_fattura_accompagnatoria_row" ).hasClass('hide')) {
    //            $( ".js_fattura_accompagnatoria_row" ).addClass('hide');
    //        } else {
    //            $( ".js_fattura_accompagnatoria_row" ).removeClass('hide');
    //        }
});

$(".js_fattura_accompagnatoria_checkbox").trigger('change');

/*
$('#new_movimento').on('submit',function(){
    
    $('.js_movimenti_articoli_lotto_id').each(function(i, obj) {
        var attr = $(this).attr('name');

        if (typeof attr !== typeof undefined && attr !== false) {
            if($(this).val().length == 0){
                var myConfirm = confirm('Uno o più prodotti non hanno un lotto associato. Considerare comunque chiuso l\'ordine?');
                
                console.log( myConfirm );
                
                if(myConfirm == false){
                    return false;
                    e.preventDefault(e);
                }
            }
        }
    });
   
    return false;
    e.preventDefault(e);
});
*/
</script>

<script>
var token = JSON.parse(atob('<?php echo base64_encode(json_encode(get_csrf())); ?>'));
var token_name = token.name;
var token_hash = token.hash;

var mittente_movimento = 1;

/****************** AUTOCOMPLETE Destinatario *************************/
function initAutocomplete(autocomplete_selector) {
    //console.log(autocomplete_selector);

    autocomplete_selector.autocomplete({
        source: function(request, response) {

            $.ajax({
                method: 'post',
                url: base_url + "magazzino/movimenti/autocomplete/<?php echo $entita_prodotti; ?>",
                dataType: "json",
                data: {
                    search: request.term,
                    [token_name]: token_hash
                },
                /*search: function( event, ui ) {
                    loading(true);
                },*/
                success: function(data) {
                    var collection = [];
                    loading(false);

                    //console.log(autocomplete_selector.data("id"));
                    //TODO: aggiunto questo false per permettere di inserire un nuovo prodotto con nome simile a uno già presente... Da capire come gestire la pistola, che invece deve popolare il prodotto in automatico
                    if ($('.uso_pistola').is(':checked') && data.count_total == 1 && ($('.js_movimenti_articoli_barcode').is(':focus') || $('.js_movimenti_articoli_codice').is(':focus'))) {

                        popolaProdotto(data.results.data[0], autocomplete_selector.data("id"));
                    } else {

                        $.each(data.results.data, function(i, p) {
                            <?php if ($campo_codice_prodotto) : ?>
                            collection.push({
                                "id": p.<?php echo $campo_id_prodotto; ?>,
                                "label": <?php if ($campo_preview_prodotto) : ?>p.<?php echo $campo_codice_prodotto; ?> + ' - ' + p.
                                <?php echo $campo_preview_prodotto; ?><?php else : ?> '*impostare campo preview*'
                                <?php endif; ?>,
                                "value": p
                            });
                            <?php else : ?>
                            collection.push({
                                "id": p.<?php echo $campo_id_prodotto; ?>,
                                "label": <?php if ($campo_preview_prodotto) : ?>p.
                                <?php echo $campo_preview_prodotto; ?><?php else : ?> '*impostare campo preview*'
                                <?php endif; ?>,
                                "value": p
                            });
                            <?php endif; ?>

                        });
                        response(collection);
                    }



                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            // fix per disabilitare la ricerca con il tab
            if (event.keyCode === 9)
                return false;

            if (mittente_movimento == 2) {
                getLotti(ui.item.value.prodotti_id, $(this).parent().parent());
                //getLotti(ui.item.value.prodotti_id, autocomplete_selector.data("id"));
            }

            popolaProdotto(ui.item.value, autocomplete_selector.data("id"));
            //Se non esiste già una riga vuota (senza nome articolo, visibile)
            //console.log($('.js_movimenti_articoli_name[value=""]'));



            return false;
        }
    });
}

<?php if ($movimenti_id) : ?>

setTimeout(function() {
    //$('button[data-tipo="<?php echo $movimento['movimenti_mittente']; ?>"]').trigger('click');
}, 1000);

<?php endif; ?>
var current_row_lotto;
$(document).ready(function() {


    $('.js_btn_mittente').click(function(e) {
        var old_tipo = $('.js_movimenti_tipo option[selected="selected"]').val();
        //alert(old_tipo);
        var tipo_mittente = $(this).data('tipo');
        //Cambio eventuali label


        //$('[name="movimenti_causale"]').val($('[name="movimenti_causale"] option[data-tipo="' + tipo_mittente + '"]').first().val()).trigger('change');
        $('.js_informazioni_movimento').removeClass('col-md-12').removeClass('col-md-8').addClass('col-md-8');
        $('.js_dati_mittente').show();
        //alert(tipo_mittente);
        switch (tipo_mittente) {
            case 1:
                $('.js_dest_type').html('fornitore');
                $('[name="dest_entity_name"]').val('suppliers');
                mittente_movimento = 1;
                $('#carica_csv').show();
                //console.log(mittente_movimento);
                $('[name="movimenti_causale"]').val('1').trigger('change');

                if (old_tipo != 1) {

                    $('.js_movimenti_tipo').val('1').trigger('change');
                }

                break;
            case 2:
                $('.js_dest_type').html('cliente');
                $('[name="dest_entity_name"]').val('customers');
                mittente_movimento = 2;
                $('#carica_csv').hide();
                //console.log(mittente_movimento);
                $('[name="movimenti_causale"]').val('18').trigger('change');
                if (old_tipo != 2) {
                    $('.js_movimenti_tipo').val('2').trigger('change');
                }
                break;
            case 3:
                //$('.js_dest_type').html('cliente');
                $('[name="dest_entity_name"]').val('');
                mittente_movimento = 3;
                $('#carica_csv').hide();
                //console.log(mittente_movimento);
                //$('[name="movimenti_causale"]').val('18').trigger('change');
                $('.js_dati_mittente').hide();
                $('.js_informazioni_movimento').removeClass('col-md-8').addClass('col-md-12');
                // alert(old_tipo);
                // if (old_tipo != 2) {
                //     $('.js_movimenti_tipo').val('2').trigger('change');
                // }
                break;
            default:
                break;
        }

        $('.js_btn_mittente').removeClass('btn-primary');
        $('.js_btn_mittente').addClass('btn-default');
        $(this).addClass('btn-primary');
        $(this).removeClass('btn-default');
        $('.js_movimenti_mittente').val(tipo_mittente);

    });

    $('.table_prodotti').on('change', '.js_movimenti_articoli_quantita', function() {
        var tr = $(this).closest('tr');
        if (tr.data('available-quantity') < $(this).val()) {
            tr.css('background-color', '#FF8C00');
        }
    });

    $('.table_prodotti').on('click', '.js_movimenti_articoli_lotto', function() {

        if ($('.js_movimenti_tipo').val() == 2) {
            var prodotto_id = $('.js_movimenti_articoli_prodotto_id', $(this).closest('tr')).val();
            console.log(prodotto_id);
            if (prodotto_id) {
                getLotti(prodotto_id, $(this).closest('tr'));
            }

        }

    });

    $('#lotti_table').on('click', '.btn_lotto', function() {

        var riga = current_row_lotto; //$(this).data('row');
        var lotto = $(this).data('lotto_codice');
        var scadenza = $(this).data('lotto_scadenza');
        var quantita = $(this).data('lotto_quantita');

        $('.js_movimenti_articoli_lotto', riga).val(lotto);
        $('.js_movimenti_articoli_data_scadenza', riga).val(scadenza);
        if (quantita >= $('.js_movimenti_articoli_quantita', riga).val()) { //Se ci sono abbastanza articoli siamo a posto con questa riga

        } else { //Altrimenti devo scorporare perchè non ho abbastanza articoli in questo lotto
            var differenza = $('.js_movimenti_articoli_quantita', riga).val() - quantita;
            $('.js_movimenti_articoli_quantita', riga).val(quantita);
            $('#js_product_table tbody tr')
            $('#js_add_product').trigger('click');
            var codice_articolo = $('.js_movimenti_articoli_codice', riga).val();
            var nuova_riga = riga.next();
            $('.js_movimenti_articoli_quantita', nuova_riga).val(differenza).trigger('change');
            $('.js_movimenti_articoli_codice', nuova_riga).val(codice_articolo).trigger('keydown');



        }

        $('#lotti_modal').modal('hide');
        $('.modal-backdrop').remove();

    });
});

function getLotti(prodotto_id, row_lotto = null) {
    //console.log(prodotto);
    //console.log(row_lotto);

    current_row_lotto = row_lotto;

    $.ajax({
        url: base_url + "magazzino/movimenti/getlotti/" + prodotto_id + '/' + $('[name="movimenti_magazzino"]').val(),
        method: "get",

        success: function(data) {
            var my = JSON.parse(data);
            //Sottraggo le quantità già selezionate:

            if (my.status == 1) {
                my.data.forEach((item, index) => {
                    var lotto_codice = item.movimenti_articoli_lotto;
                    var riga_lotto = $('.js_movimenti_articoli_lotto').filter(function() {
                        return this.value == lotto_codice
                    }).parents('tr');

                    var quantita_gia_scalate = $('.js_movimenti_articoli_quantita', riga_lotto).val();
                    if (typeof quantita_gia_scalate !== 'undefined') {
                        my.data[index].movimenti_articoli_quantita = item.movimenti_articoli_quantita - quantita_gia_scalate;
                    }

                });
                if (my.data.length == 1) {
                    movimento = my.data[0];
                    quantita = movimento.movimenti_articoli_quantita;
                    $('.js_movimenti_articoli_lotto', row_lotto).val(movimento.movimenti_articoli_lotto);
                    $('.js_movimenti_articoli_data_scadenza', row_lotto).val(movimento.movimenti_articoli_data_scadenza);
                    if (quantita >= $('.js_movimenti_articoli_quantita', row_lotto).val()) { //Se ci sono abbastanza articoli siamo a posto con questa riga

                    } else { //Altrimenti devo scorporare perchè non ho abbastanza articoli in questo lotto
                        var differenza = $('.js_movimenti_articoli_quantita', row_lotto).val() - quantita;
                        $('.js_movimenti_articoli_quantita', row_lotto).val(quantita);
                        console.log('TODO: duplicare la riga con la differenza ' + differenza);
                    }
                } else {
                    $('#lotti_modal').modal('show');
                    //console.log(my.data);
                    $("#lotti_table tbody").html('');

                    $.each(my.data, function(i, item) {
                        var _data_scadenza = item.movimenti_articoli_data_scadenza;

                        if (_data_scadenza != null) {
                            var data_scadenza = _data_scadenza.substr(0, 10);
                        } else {
                            var data_scadenza = '';
                        }
                        if (item.movimenti_articoli_quantita > 0) {
                            var button = "<button type='button' class='btn btn-success btn-sm btn_lotto' data-row='" + JSON.stringify(row_lotto) + "' data-lotto_codice='" + item.movimenti_articoli_lotto + "' data-lotto_scadenza='" + data_scadenza + "' data-lotto_quantita='" + item.movimenti_articoli_quantita + "'><i class='fa fa-plus'></i> Seleziona</button>";
                        } else {
                            var button = '';
                        }

                        $("#lotti_table tbody").append("<tr><td>" + item.movimenti_articoli_lotto + "</td><td>" + data_scadenza + "</td><td>" + item.movimenti_articoli_quantita + "</td><td>" + button + "</td></tr>");

                    });
                }

            } else {
                //alert(my.error);
            }
        }
    });
}

function popolaProdottoContabilita(prodotto, rowid) {

    $("input[name='products[" + rowid + "][movimenti_articoli_prodotto_id]']").val(prodotto.documenti_contabilita_articoli_prodotto_id);
    $("input[name='products[" + rowid + "][movimenti_articoli_barcode]']").val(prodotto.documenti_contabilita_articoli_codice_ean);
    $("input[name='products[" + rowid + "][movimenti_articoli_codice]']").val(prodotto.documenti_contabilita_articoli_codice);
    $("input[name='products[" + rowid + "][movimenti_articoli_name]']").val(prodotto.documenti_contabilita_articoli_name);
    $("textarea[name='products[" + rowid + "][movimenti_articoli_descrizione]']").html(prodotto.documenti_contabilita_articoli_descrizione);
    $("[name='products[" + rowid + "][movimenti_articoli_unita_misura]']").val(prodotto.documenti_contabilita_articoli_unita_misura);
    $("input[name='products[" + rowid + "][movimenti_articoli_prezzo]']").val(parseFloat(prodotto.documenti_contabilita_articoli_prezzo)).trigger('change');
    if (isNaN(parseInt(prodotto.documenti_contabilita_articoli_iva_perc))) {
        $("input[name='products[" + rowid + "][movimenti_articoli_iva]']").val('0');
    } else {
        $("input[name='products[" + rowid + "][movimenti_articoli_iva]']").val(parseInt(prodotto.documenti_contabilita_articoli_iva_perc));
    }

    $("input[name='products[" + rowid + "][movimenti_articoli_quantita]']").val(parseInt(prodotto.documenti_contabilita_articoli_quantita)).trigger('change');

    calculateTotals();

    if (!$('input.js_movimenti_articoli_name').filter(function() {
            return this.value == '';
        }).is(':visible')) {
        $('#js_add_product').trigger('click');
    } else {
        $('.js_movimenti_articoli_barcode:last').focus();
    }


    //stampo la riga su cui sto modificando i campi
    const closest_tr = $("input[name='products[" + rowid + "][movimenti_articoli_prodotto_id]']").closest('tr');
    checkProducts(closest_tr);
}

function popolaProdotto(prodotto, rowid) {

    var focused = $(':focus');
    //var rowid = focused.data('id');

    <?php if ($campo_id_prodotto) : ?>
    //console.log('Popolo id');
    $("input[name='products[" + rowid + "][movimenti_articoli_prodotto_id]']").val(prodotto['<?php echo $campo_id_prodotto; ?>']);

    <?php endif; ?>
    <?php if ($campo_barcode_prodotto) : ?>
    try {
        var json_parse = JSON.parse(prodotto['<?php echo $campo_barcode_prodotto; ?>']);
        if (Array.isArray(json_parse)) {
            //Se è un array di barcodes, prendo il primo
            prodotto['<?php echo $campo_barcode_prodotto; ?>'] = json_parse[0];
        }
    } catch (e) {
        //return false;
    }

    $("input[name='products[" + rowid + "][movimenti_articoli_barcode]']").val(prodotto['<?php echo $campo_barcode_prodotto; ?>']);
    <?php endif; ?>
    <?php if ($campo_codice_prodotto) : ?>
    try {
        var json_parse = JSON.parse(prodotto['<?php echo $campo_codice_prodotto; ?>']);
        if (Array.isArray(json_parse)) {
            //Se è un array di barcodes, prendo il primo
            prodotto['<?php echo $campo_codice_prodotto; ?>'] = json_parse[0];
        }
    } catch (e) {
        //return false;
    }

    $("input[name='products[" + rowid + "][movimenti_articoli_codice]']").val(prodotto['<?php echo $campo_codice_prodotto; ?>']);
    <?php endif; ?>
    <?php if ($campo_preview_prodotto) : ?>
    // console.log(prodotto['<?php echo $campo_preview_prodotto; ?>']);
    // console.log(rowid);
    $("input[name='products[" + rowid + "][movimenti_articoli_name]']").val(prodotto['<?php echo $campo_preview_prodotto; ?>']).trigger('change');
    <?php endif; ?>
    <?php if ($campo_descrizione_prodotto) : ?>
    $("textarea[name='products[" + rowid + "][movimenti_articoli_descrizione]']").html(prodotto['<?php echo $campo_descrizione_prodotto; ?>']);
    <?php endif; ?>

    <?php if ($campo_unita_misura_prodotto) : ?>
    console.log(prodotto);
    $("[name='products[" + rowid + "][movimenti_articoli_unita_misura]']").val(prodotto['<?php echo $campo_unita_misura_prodotto; ?>']);
    <?php endif; ?>

    <?php /*if ($campo_lotto) : ?>
    $("textarea[name='products[" + rowid + "][movimenti_articoli_lotto]']").html(prodotto['<?php echo $campo_lotto; ?>']);
    <?php endif; ?>
    <?php if ($campo_data_scadenza) : ?>
    $("textarea[name='products[" + rowid + "][movimenti_articoli_data_scadenza]']").html(prodotto['<?php echo $campo_data_scadenza; ?>']);
    <?php endif;*/ ?>
    <?php if ($campo_prezzo_prodotto && $campo_prezzo_fornitore_prodotto) : ?>

    if ($('.js_movimenti_tipo').val() == 1) { //Se siamo in movimento di carico il prezzo è da intendersi in acquisto
        if (!prodotto['<?php echo $campo_prezzo_fornitore_prodotto; ?>']) {
            prodotto['<?php echo $campo_prezzo_fornitore_prodotto; ?>'] = '0.00';
        } else {
            prodotto['<?php echo $campo_prezzo_fornitore_prodotto; ?>'] = prodotto['<?php echo $campo_prezzo_fornitore_prodotto; ?>'].replace(',', '.');
        }

        $("input[name='products[" + rowid + "][movimenti_articoli_prezzo]']").val(parseFloat(prodotto['<?php echo $campo_prezzo_fornitore_prodotto; ?>'])).trigger('change');
    } else { //Altrimenti siamo in vendita e quindi metto il prezzo di vendita
        prodotto['<?php echo $campo_prezzo_prodotto; ?>'] = prodotto['<?php echo $campo_prezzo_prodotto; ?>'].replace(',', '.');
        $("input[name='products[" + rowid + "][movimenti_articoli_prezzo]']").val(parseFloat(prodotto['<?php echo $campo_prezzo_prodotto; ?>'])).trigger('change');

    }


    <?php endif; ?>

    <?php if ($campo_iva_prodotto) : ?>

    if (isNaN(parseInt(prodotto['<?php echo $campo_iva_prodotto; ?>']))) {
        $("input[name='products[" + rowid + "][movimenti_articoli_iva]']").val('0');
    } else {
        $("input[name='products[" + rowid + "][movimenti_articoli_iva]']").val(parseInt(prodotto['<?php echo $campo_iva_prodotto; ?>']));
    }
    <?php endif; ?>


    //$("input[name='products[" + rowid + "][movimenti_articoli_quantita]']").val(1).trigger('change');

    calculateTotals();

    if (!$('input.js_movimenti_articoli_name').filter(function() {
            return this.value == '';
        }).is(':visible')) {
        $('#js_add_product').trigger('click');
    } else {
        $('.js_movimenti_articoli_barcode:last').focus();
    }

    //stampo la riga su cui sto modificando i campi
    const closest_tr = $("input[name='products[" + rowid + "][movimenti_articoli_prodotto_id]']").closest('tr');
    checkProducts(closest_tr);

    if ($('.js_movimenti_tipo').val() == 2) {

        $("input[name='products[" + rowid + "][movimenti_articoli_lotto]']").trigger('click');
    }
}

$(document).ready(function() {

    $('[name="movimenti_causale"]').change(function() {
        var tipo_movimento = $('option:selected', $(this)).data('tipo');

        $('[name="movimenti_tipo_movimento"]').val(tipo_movimento);
    });

    $('.js_movimenti_tipo').on('change', function() {

        var tipo_movimento = $(this).val();

        if (1 == tipo_movimento) { //Carico
            $('[name="movimenti_causale"]').val(1);

            //Nascondo le causali di scarico
            $('.js_movimenti_causali_scarico').hide();
            $('.js_movimenti_causali_carico').show();
            $('.js_prezzo_label').html('acquisto');
        } else if (2 == tipo_movimento) { //Scarico
            $('[name="movimenti_causale"]').val(18);

            //Nascondo le causali di carico
            $('.js_movimenti_causali_carico').hide();
            $('.js_movimenti_causali_scarico').show();

            $('.js_prezzo_label').html('vendita');
        }

        //Mi ripasso tutti i prodotti per ripopolare il prezzo (d'acquisto o di vendita in base al tipo movimento)
        $('.js_movimenti_articoli_prodotto_id').each(function(index) {
            var mythis = $(this);
            if ($(this).val()) {
                var rowid = index + 1;
                $.ajax({
                    method: 'get',
                    url: base_url + "magazzino/movimenti/getProdotto/" + $(this).val(),
                    dataType: "json",

                    success: function(data) {
                        if (tipo_movimento == 2) {
                            $('.js_movimenti_articoli_prezzo', mythis.closest('tr')).val(data.<?php echo $campo_prezzo_prodotto; ?>);
                        } else {
                            $('.js_movimenti_articoli_prezzo', mythis.closest('tr')).val(data.<?php echo $campo_prezzo_fornitore_prodotto; ?>);
                        }


                    }
                });
            }

        });

    });

    /****************** AUTOCOMPLETE Destinatario *************************/
    $("#search_cliente").autocomplete({
        source: function(request, response) {
            $.ajax({
                method: 'post',
                url: base_url + "contabilita/documenti/autocomplete/" + $('[name="dest_entity_name"]').val(),
                dataType: "json",
                data: {
                    search: request.term,
                    [token_name]: token_hash
                },
                minLength: 0,
                /*search: function( event, ui ) {
                    loading(true);
                },*/
                success: function(data) {
                    var collection = [];
                    loading(false);

                    //                        if (data.count_total == 1) {
                    //
                    //                            popolaCliente(data.results.data[0]);
                    //                        } else {

                    $.each(data.results.data, function(i, p) {
                        //console.log(p);
                        collection.push({
                            "id": p.customers_id,
                            "label": p.customers_company,
                            "value": p
                        });

                    });
                    //                        }

                    //console.log(collection);
                    response(collection);
                }
            });
        },
        minLength: 3,
        //            focus: function (event, ui) {
        //                return false;
        //            },
        select: function(event, ui) {
            // fix per disabilitare la ricerca con il tab
            if (event.keyCode === 9)
                return false;

            //console.log(ui.item.value);
            //if ($('[name="dest_entity_name"]').val() == 'customers') {
            popolaCliente(ui.item.value);
            //} else {
            //     popolaFornitore(ui.item.value);
            // }

            //drawProdotto(ui.item.value, true);
            return false;
        }
    });


    function popolaCliente(cliente) {
        console.log(cliente),
            //Cambio la label
            $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

        $('.js_dest_ragione_sociale').val(cliente['customers_company']);
        $('.js_dest_indirizzo').val(cliente['customers_address']);
        $('.js_dest_citta').val(cliente['customers_city']);
        $('.js_dest_nazione').val(cliente['customers_country']);
        $('.js_dest_cap').val(cliente['customers_zip_code']);
        $('.js_dest_provincia').val(cliente['customers_province']);
        $('.js_dest_partita_iva').val(cliente['customers_vat_number']);
        $('.js_dest_codice_fiscale').val(cliente['customers_cf']);
        $('.js_dest_codice_sdi').val(cliente['customers_sdi']);
        $('.js_dest_pec').val(cliente['customers_pec']);
        $('#js_dest_id').val(cliente['customers_id']);
    }

    function popolaFornitore(fornitore) {
        //Cambio la label
        $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

        $('.js_dest_ragione_sociale').val(fornitore['suppliers_business_name']);
        $('.js_dest_indirizzo').val(fornitore['suppliers_address']);
        $('.js_dest_citta').val(fornitore['suppliers_city']);
        $('.js_dest_nazione').val(fornitore['suppliers_country']);
        $('.js_dest_cap').val(fornitore['suppliers_zip_code']);
        $('.js_dest_provincia').val(fornitore['suppliers_province']);
        $('.js_dest_partita_iva').val(fornitore['suppliers_vat_number']);
        $('.js_dest_codice_fiscale').val(fornitore['suppliers_cf']);
        $('#js_dest_id').val(fornitore['suppliers_id']);
    }

    initAutocomplete($('.js_autocomplete_prodotto'));

    $('.js_select2').each(function() {
        var select = $(this);
        var placeholder = select.attr('data-placeholder');
        select.select2({
            placeholder: placeholder ? placeholder : '',
            allowClear: true
        });
    });

    <?php if ($movimenti_id || $documento_id) : ?> calculateTotals(<?php echo (!$clone) ? $movimenti_id : ''; ?>);
    <?php endif; ?>

    <?php if ($documento_id && $documento['documenti_contabilita_tipo'] == 5) : //Se è un ordine cliente 
        ?> $('.js_btn_mittente[data-tipo=2]').trigger('click');
    <?php elseif ($documento_id && $movimento['movimenti_tipo_movimento'] == 1) : ?>
    $('.js_btn_mittente[data-tipo=1]').trigger('click');
    <?php elseif (!$movimenti_id || $ordine_produzione_id) : ?> $('.js_btn_mittente[data-tipo=3]').trigger('click');
    <?php endif; ?>
});
</script>


<script>
$('.js_btn_serie').click(function(e) {
    if ($(this).hasClass('button_selected')) {
        $('.js_btn_serie').removeClass('button_selected');
        $('.js_movimenti_serie').val('');
    } else {
        $('.js_btn_serie').removeClass('button_selected');
        $(this).addClass('button_selected');

        $('.js_movimenti_serie').val($(this).data('serie'));
    }
    //getNumeroDocumento();
});
$('.js_btn_mittente').click(function(e) {
    //$('.js_btn_serie').first().trigger('click');
    //getNumeroDocumento();

});

<?php if ((empty($movimenti_id) && empty($ordine_produzione_id)) || $clone) : ?>

$('.js_btn_mittente').last().trigger('click');
//$('.js_btn_serie').first().trigger('click');
<?php endif; ?>


var totale = 0;
var totale_iva = 0;
var competenze = 0;
var competenze_no_ritenute = 0;
var iva_perc_max = 0;
var rivalsa_inps_percentuale = 0;
var rivalsa_inps_valore = 0;

var competenze_con_rivalsa = 0;

var cassa_professionisti_perc = 0;
var cassa_professionisti_valore = 0;

var imponibile = 0;

var ritenuta_acconto_perc = 0;
var ritenuta_acconto_perc_sull_imponibile = 0;

function reverseRowCalculate(tr) {
    //Calcolo gli importi basandomi sul totale...
    var qty = parseFloat($('.js_movimenti_articoli_quantita', tr).val());
    var sconto = 0; //parseFloat($('.js_movimenti_articoli_sconto', tr).val());
    var iva = parseFloat($('.js_movimenti_articoli_iva_id option:selected', tr).data('perc'));

    if (isNaN(qty)) {
        qty = 0;
    }
    if (isNaN(sconto)) {
        sconto = 0;
    }
    if (isNaN(iva)) {
        iva = 0;
    }

    var importo_ivato = parseFloat($('.js-importo', tr).val());

    //Applico lo sconto al rovescio
    var importo = parseFloat(importo_ivato / ((100 + iva) / 100));
    var importo_ricalcolato = parseFloat(importo_ivato - ((importo_ivato / 100) * sconto));


    //console.log(importo);

    $('.js-importo', tr).val(importo_ricalcolato.toFixed(2));
    $('.js_movimenti_articoli_prezzo', tr).val(importo.toFixed(2));
    //
    calculateTotals();
}

function calculateTotals(movimenti_id) {
    totale = 0;
    totale_iva = 0;
    totale_iva_divisa = {};
    totale_imponibile_divisa = {};
    competenze = 0;
    competenze_no_ritenute = 0;

    $('#js_product_table tbody tr:not(.hidden)').each(function() {
        var qty = parseFloat($('.js_movimenti_articoli_quantita', $(this)).val());
        var prezzo = parseFloat($('.js_movimenti_articoli_prezzo', $(this)).val());
        var sconto = parseFloat($('.js_movimenti_articoli_sconto', $(this)).val());
        var iva = parseFloat($('.js_movimenti_articoli_iva_id option:selected', $(this)).data('perc'));
        var appl_ritenute = $('.js-applica_ritenute', $(this)).is(':checked');

        //console.log(appl_ritenute);

        iva_perc_max = Math.max(iva_perc_max, iva);

        if (isNaN(qty)) {
            qty = 0;
        }
        if (isNaN(prezzo)) {
            prezzo = 0;
        }
        if (isNaN(sconto)) {
            sconto = 0;
        }
        if (isNaN(iva)) {
            iva = 0;
        }
        //            console.log(qty);
        //            console.log(prezzo);
        //            console.log(sconto);
        //            console.log(iva);
        var totale_riga = prezzo * qty;
        var totale_riga_scontato = (totale_riga / 100) * (100 - sconto);
        var totale_riga_scontato_ivato = parseFloat((totale_riga_scontato / 100) * (100 + iva));
        competenze += totale_riga_scontato;

        if (!appl_ritenute) {
            competenze_no_ritenute += totale_riga_scontato;
        }

        if (isNaN(totale_iva_divisa[iva])) {
            totale_iva_divisa[iva] = parseFloat((totale_riga_scontato / 100) * iva);
            //console.log(totale_iva_divisa);
            totale_imponibile_divisa[iva] = totale_riga_scontato;
        } else {
            totale_iva_divisa[iva] += parseFloat((totale_riga_scontato / 100) * iva);
            totale_imponibile_divisa[iva] += totale_riga_scontato;
        }

        totale_iva += parseFloat((totale_riga_scontato / 100) * iva);
        totale += totale_riga_scontato_ivato;

        $('.js-importo', $(this)).val(totale_riga_scontato_ivato.toFixed(2));
        $('.js_movimenti_articoli_iva', $(this)).val(parseFloat((totale_riga_scontato / 100) * iva).toFixed(2));

    });

    competenze_con_rivalsa = competenze;

    imponibile = competenze_con_rivalsa;

    var totale_imponibili_iva_diverse_da_max = 0;
    var totale_iva_diverse_da_max = 0;
    for (var _iva in totale_iva_divisa) {
        if (_iva != iva_perc_max) {
            if (_iva != 0) {
                totale_imponibili_iva_diverse_da_max += parseFloat((totale_iva_divisa[_iva] / _iva) * 100);
            } else {
                totale_imponibili_iva_diverse_da_max += totale_imponibile_divisa[_iva]; //L'errore è qua. Devo aggiungere tutto l'imponibile in quanto l'iva è 0. Però nn ce l'ho in nessun array

            }
            totale_iva_diverse_da_max += parseFloat(totale_iva_divisa[_iva]);
        }
    }
    //Aggiungo alla iva massima, ciò che manca tenendo conto delle modifiche ai totali dovute a rivalsa e cassa
    //        console.log(imponibile);
    //        console.log(totale_imponibili_iva_diverse_da_max);
    //        console.log(iva_perc_max);
    totale_iva_divisa[iva_perc_max] = parseFloat(((imponibile - totale_imponibili_iva_diverse_da_max) / 100) * iva_perc_max);

    //Valuto le ritenute
    ritenuta_acconto_perc = parseFloat($('[name="movimenti_ritenuta_acconto_perc"]').val());
    ritenuta_acconto_perc_sull_imponibile = parseFloat($('[name="movimenti_ritenuta_acconto_perc_imponibile"]').val());
    ritenuta_acconto_valore_sull_imponibile = ((competenze_con_rivalsa - competenze_no_ritenute) / 100) * ritenuta_acconto_perc_sull_imponibile;
    totale_ritenuta = (ritenuta_acconto_valore_sull_imponibile / 100) * ritenuta_acconto_perc;

    totale = imponibile + totale_iva_diverse_da_max + totale_iva_divisa[iva_perc_max];

    $('[name="movimenti_rivalsa_inps_valore"]').val(rivalsa_inps_valore);
    $('[name="movimenti_competenze_lordo_rivalsa"]').val(competenze_con_rivalsa);
    if (rivalsa_inps_percentuale && rivalsa_inps_valore > 0) {
        $('.js_rivalsa').html('Rivalsa INPS ' + rivalsa_inps_percentuale + '% <span>€ ' + rivalsa_inps_valore.toFixed(2) + '</span>').show();
        $('.js_competenze_rivalsa').html('Competenze (al lordo della rivalsa)<span>€ ' + competenze_con_rivalsa.toFixed(2) + '</span>').show();
    } else {
        $('.js_rivalsa').hide();
        $('.js_competenze_rivalsa').hide();
    }

    $('[name="movimenti_cassa_professionisti_valore"]').val(cassa_professionisti_valore);
    $('[name="movimenti_imponibile"]').val(imponibile.toFixed(2));

    if (cassa_professionisti_perc && cassa_professionisti_valore > 0) {
        $('.js_cassa_professionisti').html('Cassa professionisti ' + cassa_professionisti_perc + '% <span>€ ' + cassa_professionisti_valore.toFixed(2) + '</span>').show();
        $('.js_imponibile').html('Imponibile <span>€ ' + imponibile.toFixed(2) + '</span>').show();
    } else {
        $('.js_cassa_professionisti').hide();
        $('.js_imponibile').hide();
    }


    $('[name="movimenti_ritenuta_acconto_valore"]').val(totale_ritenuta);
    $('[name="movimenti_ritenuta_acconto_imponibile_valore"]').val(ritenuta_acconto_valore_sull_imponibile);
    if (ritenuta_acconto_perc > 0 && ritenuta_acconto_perc_sull_imponibile > 0 && totale_ritenuta > 0) {
        $('.js_ritenuta_acconto').html('Ritenuta d\'acconto -' + ritenuta_acconto_perc + '% di &euro; ' + ritenuta_acconto_valore_sull_imponibile.toFixed(2) + '<span>€ ' + totale_ritenuta.toFixed(2) + '</span>').show();
    } else {
        $('.js_ritenuta_acconto').hide();
    }

    $('[name="movimenti_competenze"]').val(competenze);
    $('.js_competenze').html('€ ' + totale.toFixed(2));

    $(".js_tot_iva:not(:first)").remove();
    $(".js_tot_iva:first").hide();


    $('[name="movimenti_iva_json"]').val(JSON.stringify(totale_iva_divisa));


    $('.js_tot_da_saldare').html('€ ' + totale.toFixed(2));

    $('[name="movimenti_totale"]').val(totale.toFixed(2));
    //$('[name="movimenti_totale"]').val(competenze.toFixed(2));
    $('[name="movimenti_iva"]').val(totale_iva.toFixed(2));

    if (isNaN(movimenti_id)) {
        $('.movimenti_scadenze_ammontare').val(totale.toFixed(2));
        $('.movimenti_scadenze_ammontare:first').trigger('change');
    } else {
        //$('.movimenti_scadenze_ammontare:last').closest('.row_scadenza').remove();
        $('.movimenti_scadenze_ammontare:last').trigger('change');
    }

}

function increment_scadenza() {
    var counter_scad = $('.row_scadenza').length;
    var rows_scadenze = $('.js_rows_scadenze');
    // Fix per clonare select inizializzata
    $('.js_table_select2').filter(':first').select2('destroy');

    var newScadRow = $('.row_scadenza').filter(':first').clone();
    $('.movimenti_scadenze_data_saldo', newScadRow).val('');
    // Fix per clonare select inizializzata
    $('.js_table_select2').filter(':first').select2();

    /* Line manipulation begin */
    //newScadRow.removeClass('hidden');
    $('input, select, textarea', newScadRow).each(function() {
        var control = $(this);
        var name = control.attr('data-name');
        control.attr('name', 'scadenze[' + counter_scad + '][' + name + ']').removeAttr('data-name');
    });

    $('.js_table_select2', newScadRow).select2({
        //placeholder: "Seleziona prodotto",
        allowClear: true
    });

    $('.js_form_datepicker input', newScadRow).datepicker({
        todayBtn: 'linked',
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        weekStart: 1,
        language: 'it'
    });

    /* Line manipulation end */
    counter_scad++;
    newScadRow.appendTo(rows_scadenze);
}

function checkProducts(closest_tr) {

    var magazzino_id = $('[name="movimenti_magazzino"]').val();
    var append_movimento = '<?php if ($movimenti_id) : ?>/<?php echo $movimenti_id; ?><?php endif; ?>';
    //$('.js_missing_products_block').addClass('hidden');
    $('.js_alert_missing_product').remove();
    //TODO: colorare di rosso le righe con name ma senza product_id

    //se passo la riga esegue solo su di lei
    if (closest_tr) {
        console.log('ho passato riga: ', closest_tr);
        if ($('.js_movimenti_articoli_name', closest_tr).val() && (!$('.js_movimenti_articoli_prodotto_id', closest_tr).val() || $('.js_movimenti_articoli_prodotto_id', closest_tr).val() == 0)) { //} && ($('.js_movimenti_articoli_name', closest_tr).val() != '' || $('.js_movimenti_articoli_codice', closest_tr).val() != '' || $('.js_movimenti_articoli_barcode', closest_tr).val() != '')) {
            closest_tr.css('background-color', '#FAA');
            $('.product_icons', closest_tr).prepend('<span class="js_alert_missing_product" data-toggle="tooltip" title="" style="color:#F00;" data-original-title="Prodotto non trovato"><span class="fas fa-exclamation-triangle"></span></span>');
            //$('.js_missing_products_block').removeClass('hidden');
        } else {
            closest_tr.css('background-color', '');
            //Add link for check quantity
            if ($('.js_movimenti_articoli_prodotto_id', closest_tr).val()) {
                //28/07/2022 - Rimosso controllo che stampa icon per qta solo se movimento è di scarico
                var my_this = closest_tr;
                //Con un ajax verifico se stampare l'icona per il check quantità sui magazzini
                $.ajax({
                    url: '<?php echo base_url('magazzino/movimenti/check_quantity_icon_show/'); ?>' + $('.js_movimenti_articoli_prodotto_id', closest_tr).val() + append_movimento, // point to server - side PHP script
                    type: 'get',
                    success: function(response) {
                        $('.js_check_qty', my_this).remove();
                        my_this.data('available-quantity', parseInt(response));
                        if (response > 0) {
                            $('.product_icons', my_this).prepend('<button type="button" data-product_id="' + $('.js_movimenti_articoli_prodotto_id', my_this).val() + '" class="btn  btn-primary btn-xs js_check_qty" data-toggle="tooltip" title="" data-original-title="Check quantity"><span class="fas fa-warehouse"></span> ' + response + '</button>');
                        }
                        $('.js_movimenti_articoli_quantita', my_this).trigger('change');
                    }
                });
                //con un ajax, verificare se il prodotto esiste nel magazzino selezionato. Se non esiste, evidenziare in giallo
                $.ajax({
                    url: '<?php echo base_url('magazzino/movimenti/check_quantity_available/'); ?>' + $('.js_movimenti_articoli_prodotto_id', closest_tr).val() + '/' + magazzino_id, // point to server-side PHP script
                    type: 'get',
                    success: function(response) {
                        if (response > 0) {

                        } else {
                            //$('.js_missing_products_block').removeClass('hidden');
                            my_this.css('background-color', '#FFFF71');
                        }
                    }
                });
            }
        }
    } else {
        console.log('richiesta senza riga')
        //eseguo su tutta la tabella
        $('#js_product_table tbody tr:visible').each(function() {
            // console.log($('.js_movimenti_articoli_prodotto_id', $(this)));
            console.log($(this));

            if ($('.js_movimenti_articoli_name', $(this)).val() && (!$('.js_movimenti_articoli_prodotto_id', $(this)).val() || $('.js_movimenti_articoli_prodotto_id', $(this)).val() == 0)) { //} && ($('.js_movimenti_articoli_name', $(this)).val() != '' || $('.js_movimenti_articoli_codice', $(this)).val() != '' || $('.js_movimenti_articoli_barcode', $(this)).val() != '')) {
                $(this).css('background-color', '#FAA');
                $('.product_icons', $(this)).prepend('<span class="js_alert_missing_product" data-toggle="tooltip" title="" style="color:#F00;" data-original-title="Prodotto non trovato"><span class="fas fa-exclamation-triangle"></span></span>');
                //$('.js_missing_products_block').removeClass('hidden');
            } else {
                $(this).css('background-color', '');

                //Add link for check quantity
                if ($('.js_movimenti_articoli_prodotto_id', $(this)).val()) {
                    //28/07/2022 - Rimosso controllo che stampa icon per qta solo se movimento è di scarico
                    var my_this = $(this);
                    //Con un ajax verifico se stampare l'icona per il check quantità sui magazzini
                    $.ajax({
                        url: '<?php echo base_url('magazzino/movimenti/check_quantity_icon_show/'); ?>' + $('.js_movimenti_articoli_prodotto_id', $(this)).val() + append_movimento, // point to server - side PHP script
                        type: 'get',
                        success: function(response) {
                            $('.js_check_qty', my_this).remove();
                            my_this.data('available-quantity', parseInt(response));
                            if (response > 0) {
                                $('.product_icons', my_this).prepend('<button type="button" data-product_id="' + $('.js_movimenti_articoli_prodotto_id', my_this).val() + '" class="btn  btn-primary btn-xs js_check_qty" data-toggle="tooltip" title="" data-original-title="Check quantity"><span class="fas fa-warehouse"></span> ' + response + '</button>');
                            }
                            $('.js_movimenti_articoli_quantita', my_this).trigger('change');
                        }
                    });

                    //con un ajax, verificare se il prodotto esiste nel magazzino selezionato. Se non esiste, evidenziare in giallo
                    $.ajax({
                        url: '<?php echo base_url('magazzino/movimenti/check_quantity_available/'); ?>' + $('.js_movimenti_articoli_prodotto_id', $(this)).val() + '/' + magazzino_id, // point to server-side PHP script
                        type: 'get',
                        success: function(response) {
                            if (response > 0) {} else {
                                //$('.js_missing_products_block').removeClass('hidden');
                                my_this.css('background-color', '#FFFF71');
                            }
                        }
                    });
                }
            }
        });
    }
}
$(document).ready(function() {
    checkProducts();
    $('[name="movimenti_magazzino"],[name="movimenti_tipo_movimento"]').on('change', function() {
        checkProducts();
    });

    $('#js_product_table').on('change', 'input.js_movimenti_articoli_name,input.js_movimenti_articoli_codice,input.js_movimenti_articoli_barcode', function(e) {
        //stampo la riga su cui sto modificando i campi
        const closest_tr = $(this).closest('tr');
        checkProducts(closest_tr);
    });
    $('#js_product_table').on('keypressed keydown', '.js_autocomplete_prodotto', function(e) {
        var tr = $(this).closest('tr');
        //$('tbody tr').css('background-color', '#00F');;
        //tr.css('background-color', '#0F0');
        console.log('Svuoto prodotto id');
        $('.js_movimenti_articoli_prodotto_id', $(tr)).val('');

        //Blocco l'invio/submit
        var keyCode = e.keyCode || e.which;

        if (keyCode === 13) {
            e.preventDefault();

            return false;
        }

    });

    $('#js_product_table').on('click', '.js_check_qty', function() {
        var product_id = $(this).data('product_id');
        loadModal(base_url + 'get_ajax/modal_layout/movements-quantity-check/' + product_id + '?_size=large');
    });

    var table = $('#js_product_table');
    var body = $('tbody', table);
    var rows = $('tr', body);
    var increment = $('#js_add_product', table);

    var rows_scadenze = $('.js_rows_scadenze');
    //var increment_scadenza = $('#js_add_scadenza');


    var firstRow = rows.filter(':first');
    var counter = rows.length;

    table.on('change', '.js_movimenti_articoli_quantita, .js_movimenti_articoli_prezzo, .js_movimenti_articoli_sconto, .js_movimenti_articoli_iva_id',
        function() {
            calculateTotals();
        });

    table.on('change', '.js-importo', function() {

        reverseRowCalculate($(this).closest('tr'));
    });

    // Aggiungi prodotto
    increment.on('click', function() {
        var newRow = firstRow.clone();

        /* Line manipulation begin */
        newRow.removeClass('hidden');
        $('input, select, textarea', newRow).each(function() {
            var control = $(this);
            var name = control.attr('data-name');
            if (name) {
                control.attr('name', 'products[' + counter + '][' + name + ']').removeAttr('data-name');
            }
            //control.val("");
        });

        $('.js_table_select2', newRow).select2({
            placeholder: "Seleziona prodotto",
            allowClear: true
        });
        $('.js_autocomplete_prodotto', newRow).data('id', counter);
        initAutocomplete($('.js_autocomplete_prodotto', newRow));

        /* Line manipulation end */

        counter++;
        newRow.appendTo(body);
        $('.js_form_datepicker input', newRow).datepicker({
            todayBtn: 'linked',
            format: 'dd/mm/yyyy',
            todayHighlight: true,
            weekStart: 1,
            language: 'it'
        });

        $('.js_movimenti_articoli_barcode:last').focus();
        //checkProducts();
    });


    table.on('click', '.js_remove_product', function() {
        $(this).parents('tr').remove();
        calculateTotals();
    });
    $('#offerproducttable .js_remove_product').on('click', function() {
        $(this).parents('tr').remove();
    });


    //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
    rows_scadenze.on('change', '.movimenti_scadenze_ammontare', function() {
        //Se la somma degli ammontare è minore del totale procedo
        var totale_scadenze = 0;
        $('.movimenti_scadenze_ammontare').each(function() {
            totale_scadenze += parseFloat($(this).val());
        });

        /*
         * La logica è questa:
         * 1. se le scadenza superano l'importo totale, metto a posto togliendo ricorsivamente la riga sucessiva finchè non entro nel caso 2
         * 2. se le scadenza non superano l'importo totale, tolgo tutte le righe sucessiva all'ultima modificata, ne creo una nuova e forzo importo corretto sull'ultima
         */
        next_row_exists = $(this).closest('.row_scadenza').next('.row_scadenza').length != 0;

        if (totale_scadenze < totale) {
            if (next_row_exists) {
                //console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                $(this).trigger('change');
            } else {
                //console.log('Non esiste scadenza successiva. Creo...');
                //$('#js_add_scadenza').trigger('click');
                increment_scadenza();
                next_row = $(this).closest('.row_scadenza').next('.row_scadenza');
                $('.movimenti_scadenze_ammontare', next_row).val((totale - totale_scadenze).toFixed(2));
            }
        } else {
            if (next_row_exists) {
                //console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                $(this).trigger('change');
            } else {
                //console.log('Non esiste scadenza successiva. Tutto a posto ma nel dubbio forzo questa = alla differenza tra totale e totale scadenze');
                $(this).val((totale - (totale_scadenze - $(this).val())).toFixed(2));

            }
        }

    });

    if (rows.length < 2) {
        increment.click();

    }
});
</script>


<script>
function loadProductsFromDocumento(doc_id) {
    $.ajax({
        url: '<?php echo base_url('contabilita/documenti/getProducts/'); ?>' + doc_id, // point to server-side PHP script
        dataType: 'json', // what to expect back from the PHP script, if anything
        cache: false,
        contentType: false,
        processData: false,
        type: 'get',
        async: false,
        success: function(response) {
            $('.js_remove_product').trigger('click');

            $.each(response, function(index, item) {
                $('#js_add_product').trigger('click');

                var name = $('.js_movimenti_articoli_name:visible').filter(function() {
                    return this.value == "";
                }).attr('name');
                //recupero il numero riga XX prendendolo dal name che è di tipo products[XX][nome_campo].
                var i = name.substring(9, 1000).split(']')[0];

                popolaProdottoContabilita(item, i);
            });
        }
    });
}

function reloadDocumenti() {
    var data = {
        tipo: $('[name="movimenti_documento_tipo"]').val(),
        [token_name]: token_hash
    };
    $.ajax({
        url: '<?php echo base_url('contabilita/documenti/listDocumenti/1'); ?>', // point to server-side PHP script
        dataType: 'html', // what to expect back from the PHP script, if anything
        type: 'post',
        data: data,
        success: function(response) {
            $('[name="movimenti_documento_id"]').html('<option></option>');
            $('[name="movimenti_documento_id"]').append(response);
            $('[name="movimenti_documento_id"]').append('<option value="-1">Altro...</option>');

        }
    });
}


$(document).ready(function() {
    $('#js_add_product').on('keyup', function(e) {
        $(this).trigger('click');
        //TODO: focus su barcode?
        $('.js_movimenti_articoli_codice:last').focus();
    });

    //se il selettore è su "Non ancora saldato", il campo "Data saldo" viene svuotata
    $(".select2").on("change", function() {
        //console.log('entrato');
        if ($('#empty_select').val() == "") {
            //console.log('entrato if');
            $("#empty_date").val("");
        }
    });

    $('#js_dtable').dataTable({
        aoColumns: [null, null, null, null, null, null, null, {
            bSortable: false
        }],
        aaSorting: [
            [0, 'desc']
        ]
    });
    $('#js_dtable_wrapper .dataTables_filter input').addClass("form-control input-small"); // modify table search input
    $('#js_dtable_wrapper .dataTables_length select').addClass("form-control input-xsmall"); // modify table per page dropdown

    $('[name="movimenti_documento_id"]').on('change', function() {
        //Se rif è vuoto, ho selezionato "altro". Nascondere questo field e mostrare il campo testuale per scrivere il numero di documento
        var rif = $('option:selected', $(this)).data('rif');
        if (rif) {
            var data_emissione = $('option:selected', $(this)).data('data_documento');
            var tipo_documento = $('option:selected', $(this)).data('tipo_documento');

            //scrivere il rif sul campo testuale del documento //TODO: scrivere il rif sul campo testuale del documento 
            $('[name="movimenti_numero_documento"]').val(rif);


            $('[name="movimenti_data_documento"]').val(data_emissione);
            $('[name="movimenti_documento_tipo"]').val(tipo_documento);

            //In base al tipo di documento, imposto il tipo movimento e la causale
            switch (tipo_documento) {
                case 1: //Fattura
                case 3: //Proforma
                case 5: //Ordine cliente
                case 7: //Preventivo
                case 8: //DDT Cliente
                    $('.js_movimenti_tipo').val(2);
                    $('[name="movimenti_causale"]').val(18);
                    break;
                case 4: //Nota di credito
                    $('.js_movimenti_tipo').val(1);
                    $('[name="movimenti_causale"]').val(14);
                    break;
                case 6: //Ordine fornitore
                case 10: //DDT Fornitore
                    $('.js_movimenti_tipo').val(1);
                    $('[name="movimenti_causale"]').val(1);
                    break;
                default:
                    console.log(tipo_documento);
                    alert('tipo ' + tipo_documento + ' non gestito.');
                    break;
            }

            //TODO: fare ajax per popolare i prodotti di quell'ordine
            loadProductsFromDocumento($(this).val());

        } else if ($(this).val() == -1) {
            $('[name="movimenti_numero_documento"]').prop('type', 'text');
            $(this).hide();
            $('.select2', $(this).parent()).hide();

        }



    });

    $('[name="movimenti_documento_tipo"]').on('change', function() {
        reloadDocumenti();
    });
});
</script>
<!-- END Module Related Javascript -->

<script>
$(document).ready(function() {
    //TODO: rimuovere questo blocco?
    $('#submit_lotto_file').on('click', function() {

        var file_data = $('#lotto_file').prop('files')[0];
        var form_data = new FormData();
        form_data.append('file', file_data);
        //alert(form_data);
        $.ajax({
            url: '<?php echo base_url('magazzino/movimenti/parseLottoFile'); ?>', // point to server-side PHP script
            dataType: 'json', // what to expect back from the PHP script, if anything
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            type: 'post',
            success: function(response) {
                $.each(response, function() {
                    var codice_prodotto = $(this)[0];
                    var lotto = $(this)[4];
                    var scadenza = $(this)[6];
                    var quantita = $(this)[3];
                    var nome = $(this)[2];

                    var riga = $('input[name*=\'movimenti_articoli_codice\'][value="' + codice_prodotto + '"]').parent().parent();
                    if (riga.length) {
                        //console.log('RIGA TROVATA! TODO...');
                        $('.js_movimenti_articoli_lotto', riga).val($(this)[4]);
                        $('.js_movimenti_articoli_data_scadenza', riga).val($(this)[6]);
                        $('.js_movimenti_articoli_quantita', riga).val($(this)[3]);
                    } else {
                        //Purtroppo se non ho la riga di prodotto devo necessariamente fare un'ajax per capire se questo prodotto ce l'ho a listino, ed eventualmente prendere il nome dal db (e non dalla packing list)
                        $.ajax({
                            url: '<?php echo base_url('magazzino/movimenti/getProdottoByCode/'); ?>' + codice_prodotto, // point to server-side PHP script
                            dataType: 'json', // what to expect back from the PHP script, if anything
                            cache: false,
                            contentType: false,
                            processData: false,
                            //data: form_data,
                            type: 'post',
                            success: function(data) {
                                if (data.prodotto.length > 0) {

                                    popolaProdotto(data.prodotto, riga.index());
                                } else {

                                    console.log(data.prodotto);

                                    $('#js_add_product').trigger('click');
                                    riga = $('.js_movimenti_articoli_codice:visible').filter(function() {
                                        return $(this).val() == "";
                                    }).parent().parent();
                                    //console.log(riga);
                                    $('.js_movimenti_articoli_codice', riga).val(codice_prodotto);
                                    console.log('Errore qui! Non devo prendere il name dal csv ma direttamente dal listino, basandomi sul codice fornitore...');
                                    $('.js_movimenti_articoli_name', riga).val(nome).trigger('change');
                                }
                                $('.js_movimenti_articoli_lotto', riga).val(lotto);
                                $('.js_movimenti_articoli_data_scadenza', riga).val(scadenza);
                                //console.log($(this)[3]);
                                $('.js_movimenti_articoli_quantita', riga).val(quantita);
                            }
                        });
                    }

                });
            }
        });
    });

    <?php if (!empty($products)) : ?>
    var products = <?php echo json_encode($products); ?>;
    $.each(products, function(i) {
        //console.log(products[i]);
        popolaProdotto(products[i]);
    });
    <?php endif; ?>


});
</script>