<?php $this->layout->addModuleJavascript('contabilita', 'toastr/toastr.min.js');?>
<?php $this->layout->addModuleStylesheet('contabilita', 'toastr/toastr.min.css');?>
<?php $this->layout->addModuleStylesheet('contabilita', 'css/prima_nota.css');?>

<?php
$this->load->model('contabilita/prima_nota');
$form_intestazione = $this->datab->get_form_id_by_identifier('intestazione_prima_nota');
$documenti_contabilita_settings = $this->apilib->search('documenti_contabilita_settings');

$full_enabled = $this->apilib->searchFirst('documenti_contabilita_general_settings', ['documenti_contabilita_general_settings_contabilita_full' => DB_BOOL_TRUE]);

if (!$full_enabled) {
    ?>
    <div class="error-page">
        <h2 class="headline text-yellow">Non attivo!</h2>

        <div class="error-content">
            <h3><i class="fa fa-warning text-yellow"></i>Attenzione</h3>

            <p>
                Le registrazioni prima nota sono disponibili solo con modulo contabilità full attivato. Contattare l'assistenza.
            </p>
        </div>
        <!-- /.error-content -->
    </div>
<?php
return;
}

$form = $this->datab->get_form($form_intestazione, null);

if (!$this->datab->can_write_entity($form['forms']['forms_entity_id'])) {
    return str_repeat('&nbsp;', 3) . 'Non disponi dei permessi sufficienti per modificare i dati.';
}
//TODO: una volta integrato il filtro, passare il where corretto (si può prendere con datab->buildWhere o qualcosa del genere...)

// Pagination
$offset = 0;
if (
    !empty($this->input->get('offset'))
    && ctype_digit($this->input->get('offset'))
) {
    $offset = $this->input->get('offset');
}

$grid_id = $this->datab->get_grid_id_by_identifier('prime_note_internal');

$native_where = $this->datab->generate_where("grids", $grid_id, null);

$native_where = [
    $native_where,
    "prime_note_modello <> 1",
];

// $count_prime_note = $this->apilib->count('prime_note', $native_where);
// $prime_note = $this->prima_nota->getPrimeNoteData($native_where, 50, 'prime_note_data_registrazione DESC, prime_note_id DESC', $offset, false, true);

$_modelli = $this->apilib->search('prime_note_modelli', [
    //'prime_note_modelli_nascosto <> ' => DB_BOOL_TRUE
], null, 'prime_note_modelli_ordine');
foreach ($_modelli as $key => $modello) {

    $prima_nota_modello = $this->prima_nota->getPrimeNoteData(['prime_note_id' => $modello['prime_note_modelli_prima_nota']], 10, 'prime_note_data_registrazione DESC, prime_note_id DESC', 0, false, true);
    if (empty($prima_nota_modello)) {
        debug("Prima nota non trovata per il modello '{$modello['prime_note_modelli_nome']}'");
    } else {

        $prima_nota_modello = $prima_nota_modello[$modello['prime_note_modelli_prima_nota']];
        $_modelli[$key]['prima_nota'] = $prima_nota_modello;
    }
}
$modelli = array_key_map_data($_modelli, 'prime_note_modelli_id');

$_causali = $this->apilib->search('prime_note_causali', ['prime_note_causali_cancellata <> 1'], null, 0, '', '', 3);
$causali = array_key_map_data($_causali, 'prime_note_causali_id');

$sezionali_iva = $this->apilib->search('sezionali_iva');

$mastri = $this->apilib->search('documenti_contabilita_mastri');
$mastri_autocomplete_js = [];
foreach ($mastri as $mastro) {
    $mastri_autocomplete_js[] = [
        'value' => $mastro['documenti_contabilita_mastri_codice'],
        'id' => $mastro['documenti_contabilita_mastri_codice'],
        'label' => $mastro['documenti_contabilita_mastri_codice'] . ' - ' . $mastro['documenti_contabilita_mastri_descrizione'],
    ];
}

$conti = $this->apilib->search('documenti_contabilita_conti');
$conti_autocomplete_js = [];
foreach ($conti as $conto) {
    $conti_autocomplete_js[] = [
        'value' => $conto['documenti_contabilita_conti_codice'],
        'id' => $conto['documenti_contabilita_conti_codice'],
        'label' => $conto['documenti_contabilita_conti_codice'] . ' - ' . $conto['documenti_contabilita_conti_descrizione'],
        'mastro' => $conto['documenti_contabilita_mastri_codice'],
    ];
}

$elenco_iva = $this->apilib->search('iva', [], null, 0, 'iva_order');
$iva_autocomplete_js = [];
foreach ($elenco_iva as $iva) {
    //debug($iva);
    $iva_autocomplete_js[] = [
        'value' => $iva['iva_valore'],
        'id' => $iva['iva_id'],
        'label' => $iva['iva_label'],
        'description' => $iva['iva_descrizione'],
        'indetraibilita' => $iva['iva_percentuale_indetraibilita'],

    ];
}
$year = date('Y');

$prime_note_mappatura = $this->apilib->search('prime_note_mappature');
$fields_to_set = [];
if ($modello = $this->input->get('modello')) {
    $fields_to_set['.js_modello_select'] = $modello;
} else {
    foreach ($modelli as $key => $_modello) {
        if ($_modello['prime_note_modelli_nascosto']) {
            unset($modelli[$key]);
        }
    }
    foreach ($causali as $key => $_causale) {
        if ($_causale['prime_note_causali_nascosta']) {
            //unset($causali[$key]);
        }
    }
}
if ($documento_id = $this->input->get('documento_id')) {
    $modello_documento = [];

    $documento = $this->apilib->view('documenti_contabilita', $documento_id);
    debug($documento['documenti_contabilita_rif_spesa'], true);
    if ($documento['documenti_contabilita_rif_spesa'] && $spesa_associata = $this->apilib->view('spese', $documento['documenti_contabilita_rif_spesa'])) {
        if ($spesa_associata['spese_modello_prima_nota']) {
            $modello_documento = $modello_documento = $this->apilib->view('prime_note_modelli', $spesa_associata['spese_modello_prima_nota']);
        }
    }

    if (!$modello_documento) {
        if ($documento['documenti_contabilita_tipo'] == 1) { //Fattura
            //Verfico se il cliente è italiano o intra e in base a quello seleziono il modello corretto
            $prime_note_mappature_tipo_identifier = 'FOO';
            if (empty($documento['customers_country_id_countries_name']) || $documento['customers_country_id_countries_name'] == 'Italy') {
                $prime_note_mappature_tipo_identifier = 'modello_fattura_vendita_italia';
            } else {
                $prime_note_mappature_tipo_identifier = 'modello_fattura_vendita_intra';
            }
            $modello_documento = $this->apilib->searchFirst('prime_note_modelli', [
                "prime_note_modelli_tipo IN (SELECT prime_note_mappature_id FROM prime_note_mappature WHERE prime_note_mappature_tipo_identifier = '$prime_note_mappature_tipo_identifier')",
            ]);
        } elseif ($documento['documenti_contabilita_tipo'] == 4) { //E' una nota di credito
            $modello_documento = $this->apilib->searchFirst('prime_note_modelli', [
                "prime_note_modelli_tipo IN (SELECT prime_note_mappature_id FROM prime_note_mappature WHERE prime_note_mappature_tipo_identifier = 'modello_nota_di_credito_vendita_ita')",
            ]);
            //debug($modello_documento, true);
        }

    }

    if (!$modello_documento) {
        $fields_to_set = [

            'function_js_to_run' => [
                "alert('Nessun modello impostato per questo tipo di documenti... creare o modificare un modello per attivare gli automatismi oppure procedere alla registrazione manuale.');",
            ],
        ];
    } else {
        //debug($documento, true);
        //$serie = $documento['documenti_contabilita_serie'];
        $fields_to_set = [
            '.js_modello_select' => $modello_documento['prime_note_modelli_id'],
            //'#prime_note_data_registrazione' => dateFormat($documento['documenti_contabilita_data_emissione']),
            'function_js_to_run' => [
                'documento_selezionato(\'' . base64_encode(json_encode(['id' => $documento_id, 'documento' => $documento])) . '\');',
            ],
        ];
    }
}
if ($spesa_id = $this->input->get('spesa_id')) {

    $spesa = $this->apilib->view('spese', $spesa_id);

    if ($modello) { //Se ho forzato un modello
        $modello_spesa = $this->apilib->view('prime_note_modelli', $modello);
    } else {
        //TODO distinzione tra estero e italia
        $prime_note_mappature_tipo_identifier = 'FOO';
        if (in_array($spesa['spese_tipologia_fatturazione'], [4])) { //Nota di credito
            $prime_note_mappature_tipo_identifier = 'modello_nota_di_credito_acquisto_ita';
        } else {
            $prime_note_mappature_tipo_identifier = 'modello_fattura_acquisto_italia';
        }

        $modello_spesa = $this->apilib->searchFirst('prime_note_modelli', [
            "prime_note_modelli_tipo IN (SELECT prime_note_mappature_id FROM prime_note_mappature WHERE prime_note_mappature_tipo_identifier = '$prime_note_mappature_tipo_identifier')",
        ]);
        //debug($modello_spesa, true);
    }

    if (!$modello_spesa) {
        $fields_to_set = [

            'function_js_to_run' => [
                "alert('Nessun modello impostato per le fatture/note di credito acquisto italia... creare o modificare un modello per attivare gli automatismi.');",
            ],
        ];
    } else {
        $prima_nota_modello_spesa = $modelli[$modello_spesa['prime_note_modelli_id']];

        // $identifier_causale = 'modello_fattura_acquisto_italia';

        // $mappature_causali = array_key_map_data($prime_note_mappatura, 'prime_note_mappature_tipo_identifier');
        // debug($mappature_causali[$identifier_causale]);
        // $causale_id = $mappature_causali[$identifier_causale]['prime_note_mappature_causale'];
        // die('test'); //Decommentando va in memory limit. Assurdo.

        // $identifier_sezionale_spesa_ita = 'sezionale_spese_ita';
        // $sezionale_id = $mappature_causali[$identifier_sezionale_spesa_ita]['prime_note_mappature_sezionale'];

        $fields_to_set = [
            //'.js_prime_note_causale' => $causale_id,
            //'.js-prime_note_sezionale' => $sezionale_id,
            '.js_modello_select' => $modello_spesa['prime_note_modelli_id'],
            'function_js_to_run' => [
                //'initPrimanotaForm(JSON.parse(atob(\'' . base64_encode(json_encode($prima_nota_modello_spesa)) . '\')), true);',
                'spesa_selezionata(\'' . base64_encode(json_encode(['id' => $spesa_id, 'spesa' => $spesa])) . '\');',
            ],
        ];
    }
}

?>

<style>

</style>
<?php //debug($prime_note_mappatura_js, true);
?>
<script>
    var mastri = <?php echo json_encode($mastri_autocomplete_js); ?>;
    var conti = <?php echo json_encode($conti_autocomplete_js); ?>;


    var tabella_iva = <?php echo json_encode($iva_autocomplete_js); ?>;

    var mappature_causali = <?php echo json_encode($prime_note_mappatura); ?>;

    var fields_to_set = <?php echo (!empty($fields_to_set)) ? json_encode($fields_to_set) : '{}'; ?>;
</script>

<script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
<link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />

<script src="https://jsuites.net/v4/jsuites.js"></script>
<link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />

<?php $this->layout->addModuleJavascript('contabilita', 'prima_nota_functions.js');?>
<?php $this->layout->addModuleJavascript('contabilita', 'prima_nota_autocomplete.js');?>
<?php $this->layout->addModuleJavascript('contabilita', 'prima_nota_onchange_events.js');?>
<?php $this->layout->addModuleJavascript('contabilita', 'prima_nota.js');?>

<?php $this->layout->addModuleJavascript('contabilita', 'dettagli_iva_autocomplete.js');?>
<?php $this->layout->addModuleJavascript('contabilita', 'dettagli_iva_functions.js');?>
<?php $this->layout->addModuleJavascript('contabilita', 'dettagli_iva.js');?>

<?php $this->layout->addModuleJavascript('contabilita', 'prima_nota/onclick_events.js');?>

<div class="container-fluid">

    <div class="row small">
        <div class="box box box-success ">

            <div class="box-header with-border js_title_collapse ">

                <div class="row">

                    <div class="col-md-3">



                        <select class="form-control select2_standard js_modello_select">
                            <option value="">Scegli modello</option>
                            <option value="">------</option>
                            <?php foreach ($modelli as $modello): ?>
                                <option value="<?php echo $modello['prime_note_modelli_id']; ?>" data-primanota="<?php echo base64_encode(json_encode($modello['prima_nota'])); ?>"><?php echo $modello['prime_note_modelli_nome']; ?></option>
                            <?php endforeach;?>
                        </select>



                    </div>
                    <div class="col-md-2">
                        <a data-layout-id="314" href="#" class="bg-purple btn btn-primary mr-10 br-4 js_crea_modello_prima_nota">
                            <i class="fas fa-plus"></i> Crea modello
                        </a>
                    </div>
                </div>




                <div class="box-tools">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>

            <div class="box-body form js_container_prima_nota">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box-title">INTESTAZIONE</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <form id="form_1822" role="form" method="post" action="<?php echo base_url("contabilita/primanota/salva"); ?>" class="formAjax js_form_prima_nota" enctype="multipart/form-data" data-edit-id="" autocomplete="off">
                            <?php add_csrf();?>
                            <div class="box-body">
                                <div class="row js_nome_modello_container">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Nome modello</label> <small class="text-danger fas fa-asterisk firegui_fontsize85"></small>
                                            <input type="hidden" name="prime_note_modello" value="0">
                                            <input type="text" class="form-control js_nome_modello" name="nome_modello">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-1">
                                        <div class="form-group"><label class="control-label">Azienda</label> <small class="text-danger fas fa-asterisk firegui_fontsize85"></small>

                                            <select class="form-control select2_standard js_prime_note_azienda field_1817" name="prime_note_azienda" data-source-field="" data-ref="documenti_contabilita_settings" data-val="">
                                                <?php foreach ($documenti_contabilita_settings as $azienda): ?>
                                                    <option value="<?php echo $azienda['documenti_contabilita_settings_id']; ?>"><?php echo $azienda['documenti_contabilita_settings_company_name']; ?></option>
                                                <?php endforeach;?>

                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label class="control-label">Progr. Anno</label> <small class="text-danger fas fa-asterisk firegui_fontsize85"></small>
                                            <input type="text" name="prime_note_progressivo_annuo" class="form-control" placeholder="" value="" autocomplete="off" _readonly="readonly" />
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label class="control-label">Progr. Giorno</label> <small class="text-danger fas fa-asterisk firegui_fontsize85"></small>
                                            <input type="text" name="prime_note_progressivo_giornaliero" class="form-control field_1818" placeholder="" value="" autocomplete="off" _readonly="readonly" />
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <div class="form-group"><label class="control-label">Data reg.</label> <small class="text-danger fas fa-asterisk firegui_fontsize85"></small>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="input-group js_form_datepicker date  field_1819">
                                                        <input name="prime_note_data_registrazione" style="width:100px;" id="prime_note_data_registrazione" type="text" class="form-control" value="<?php echo date('d/m/Y') ?>">
                                                        <span class="input-group-btn" style="opacity:0;">
                                                            <button class="btn btn-default" type="button"><i class="fas fa-calendar-alt"></i></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="clearfix"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group"><label class="control-label">Causale</label> <small class="text-danger fas fa-asterisk firegui_fontsize85"></small>
                                            <select class="form-control ___select2_standard field_1839 js_prime_note_causale" name="prime_note_causale" data-source-field="" data-ref="prime_note_causale" data-val="">
                                                <option value="">-</option>
                                                <?php foreach ($causali as $causale): ?>
                                                    <?php //debug($causali, true);
?>
                                                    <option data-tipo="<?php echo $causale['prime_note_causali_tipo']; ?>" data-mappatura_identifier="<?php echo $causale['prime_note_mappature_tipo_identifier']; ?>" data-registro_iva="<?php echo $causale['prime_note_causali_registro_iva']; ?>" value="<?php echo $causale['prime_note_causali_id']; ?>"><?php echo $causale['prime_note_causali_codice']; ?> - <?php echo $causale['prime_note_causali_descrizione']; ?></option>
                                                <?php endforeach;?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Sezionale</label>

                                            <select class="form-control __select2_standard js-prime_note_sezionale field_1839" name="prime_note_sezionale" data-source-field="" data-ref="prime_note_sezionale" data-val="">
                                                <option value="">---</option>
                                                <?php foreach ($sezionali_iva as $sezionale): ?>
                                                    <?php //debug($sezionale, true);
?>
                                                    <option value="<?php echo $sezionale['sezionali_iva_id']; ?>" data-sezionale="<?php echo $sezionale['sezionali_iva_sezionale']; ?>" data-tipo="<?php echo $sezionale['sezionali_iva_tipo']; ?>"><?php echo ($sezionale['sezionali_iva_numero']) ? $sezionale['sezionali_iva_numero'] . '. ' : ''; ?> <?php echo $sezionale['sezionali_iva_sezionale']; ?></option>
                                                <?php endforeach;?>
                                            </select>


                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group"><label class="control-label">Prot. iva</label>
                                            <input type="text" name="prime_note_protocollo" class="form-control js-prime_note_protocollo field_1823" placeholder="" ___readonly="readonly" autocomplete="off" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group"><label class="control-label js_riferimento_documento">Rif. doc. (<a href="<?php echo base_url('get_ajax/modal_layout/modale_elenco_documenti_non_contabilizzati?_size=extra'); ?>" class="js_open_modal">lista</a>) / prot. interno</label>
                                            <input type="hidden" name="prime_note_documento" class="js_prima_nota_documento">
                                            <input type="hidden" name="prime_note_spesa" class="js_prima_nota_spesa">
                                            <input type="hidden" name="prime_note_numero_documento" class="js_numero_documento_orig">

                                            <input type="text" class="form-control js_prima_nota_numero_documento" name="_prime_note_numero_documento">
                                            <!-- <select class="js_select_ajax_new form-control  field_1824" name="prime_note_documento" value="" data-required="0" data-source-field="" data-ref="documenti_contabilita" data-val=""></select> -->
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <div class="form-group"><label class="control-label">Data doc. <small class="text-danger fas fa-asterisk firegui_fontsize85"></small></label>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="input-group js_form_datepicker date  field_1829">
                                                        <input name="prime_note_scadenza" style="width:100px;" type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>">
                                                        <span class="input-group-btn" style="opacity:0;">
                                                            <button class="btn btn-default" type="button"><i class="fas fa-calendar-alt"></i></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="clearfix"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr />
                                <div class="bg-info registrazioni_container">
                                    <div class="row js_riga_registrazione hidden ">

                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label class="control-label">N.</label>

                                                <input type="text" data-name="prime_note_registrazioni_numero_riga" data-id="1" class="form-control js_numero_riga" value="" autocomplete="off">
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" data-name="prime_note_registrazioni_codice_dare_testuale" data-id="1" class="form-control js_conto_dare_testuale" value="" autocomplete="off">
                                                <label class="control-label">Conto dare</label>
                                                <div>
                                                    <!--<input type="text" data-name="prime_note_registrazioni_mastro_dare_codice" size="2" data-id="1" class="form-control conto2 js_riga_mastro_dare_codice" value="" autocomplete="off">
                                                <input type="text" data-name="prime_note_registrazioni_conto_dare_codice" size="2" data-id="1" class="form-control conto2 js_riga_conto_dare_codice" value="" autocomplete="off">-->
                                                    <input type="text" data-name="prime_note_registrazioni_codice_dare_testuale" size="12" data-id="1" class="form-control conto4 js_riga_sottoconto_dare_codice" value="" autocomplete="off">
                                                    <span class="js_conto_dare_descrizione js_conto_descrizione"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Importo dare</label>
                                                <div class="input_container">
                                                    <div class="eur_sign">€</div>
                                                    <input type="text" data-name="prime_note_registrazioni_importo_dare" data-id="1" class="form-control js_decimal js_importo_dare" value="" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" data-name="prime_note_registrazioni_codice_avere_testuale" data-id="1" class="form-control js_conto_avere_testuale" value="" autocomplete="off">
                                                <label class="control-label">Conto avere</label>
                                                <div>
                                                    <!--<input type="text" data-name="prime_note_registrazioni_mastro_avere_codice" size="2" data-id="1" class="form-control conto2 js_riga_mastro_avere_codice" value="" autocomplete="off">
                                                <input type="text" data-name="prime_note_registrazioni_conto_avere_codice" size="2" data-id="1" class="form-control conto2 js_riga_conto_avere_codice" value="" autocomplete="off">-->
                                                    <input type="text" data-name="prime_note_registrazioni_codice_avere_testuale" size="12" data-id="1" class="form-control conto4 js_riga_sottoconto_avere_codice" value="" autocomplete="off">
                                                    <span class="js_conto_avere_descrizione js_conto_descrizione"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Importo avere</label>
                                                <div class="input_container">
                                                    <div class="eur_sign">€</div>
                                                    <input type="text" data-name="prime_note_registrazioni_importo_avere" data-id="1" class="form-control js_decimal js_importo_avere" value="" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Rif. doc. / descrizione</label>

                                                <input type="text" data-name="prime_note_registrazioni_rif_doc" class="form-control js_registrazione_rif_doc" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label class="control-label" style="display:block">Elimina</label>

                                                <a href="javascript:void(0);" class="btn btn-danger js_delete_riga_registrazione">
                                                    <span class="fas fa-trash"></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <button id="js_add_riga" type="button" class="btn btn-primary btn-sm">
                                                + Nuova riga
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php echo $this->load->view('contabilita/prima_nota/quadratura'); ?>

                                <?php echo $this->load->view('contabilita/prima_nota/dettaglio_iva', [
    'elenco_iva' => $elenco_iva,
]); ?>

                                <?php echo $this->load->view('contabilita/prima_nota/quadratura_iva'); ?>


                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="msg_form_1822" class="alert alert-danger hide"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions pull-right">
                                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="location.href='<?php echo base_url('main/layout/prima-nota'); ?>';">Annulla</button>
                                <button type="submit" class="btn btn-primary">Salva</button>
                                <button type="submit" style="display:none;" class="btn btn-primary js_salva_riproponi" onclick="$('#form_1822').attr('action', $('#form_1822').attr('action') + '?modello='+$('.js_modello_select').val());">Salva e riproponi modello</button>
                            </div>
                        </form>


                        <?php
/*$this->load->view(
"pages/layouts/forms/form_{$form['forms']['forms_layout']}",
array(
'form' => $form,
//'ref_id' => $grid['grids']['grids_inline_form'],
'value_id' => null,
),
false
);*/
?>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <?php if (false && $prime_note): ?>
        <div class="row">
            <div class="">
                <div class="box box-primary">
                    <div class="box-header with-border js_title_collapse ">

                        <div class="box-title">
                            <i class=" fas fa-bars"></i>
                            <span class=" ">

                                Filtri </span>
                        </div>
                    </div>

                    <div class="box-body layout_box form display-hide" data-layout-box="" data-value_id="">
                        <?php
$form_filtro_prime_note = $this->datab->get_form_id_by_identifier('filtro_prime_note');
$form = $this->datab->get_form($form_filtro_prime_note, null);
$formHtml = $this->load->view("pages/layouts/forms/form_{$form['forms']['forms_layout']}", [
    'form' => $form,
    'ref_id' => 'test',
    'value_id' => $value_id,
    'layout_data_detail' => null,
], true);
echo $formHtml;
?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="box box box-success">
                <?php
//$prima_prima_nota = array_pop($prime_note);
$this->load->view('contabilita/prima_nota/excel_prime_note', [
    'prime_note' => $prime_note,
    'count_prime_note' => $count_prime_note,
    // 'primanota' => $prima_prima_nota,
    // 'primanota_registrazioni' => $prima_prima_nota['registrazioni']
]);
?>
            </div>
        </div>
    <?php endif;?>


</div>

<script>
    $(() => {
        setTimeout(function() {
            //$('#DataTables_Table_0_filter input[type="search"]').addClass('js_decimal');
        }, 2000);
    });
</script>