<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

<style>
    #js_product_table>tbody>tr>td,
    #js_product_table>tbody>tr>th,
    #js_product_table>tfoot>tr>td,
    #js_product_table>tfoot>tr>th,
    #js_product_table>thead>tr>td {
        vertical-align: top !important;
    }

    /* New sub-table */
    #js_product_table tr>td>table>tbody>tr>td,
    #js_product_table tr>td>table>tbody>tr>th,
    #js_product_table tr>td>table>tfoot>tr>td,
    #js_product_table tr>td>table>tfoot>tr>th,
    #js_product_table tr>td>table>thead>tr>td {
        vertical-align: top !important;
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
        /*width: 40%;*/
        width: 90%;
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

    small,
    .small {
        font-size: 75%;
    }

    .js_form_datepicker {
        width: 100% !important;
    }

    .causale-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-causale {
        font-size: 0.8em;
        margin-bottom: 3px
    }

    @media only screen and (max-width: 992px) {
        .mb-15 {
            margin-bottom: 15px;
        }

        .rcr-adjust {
            /*width: 90%;*/
        }

        .table-responsive {
            border: none;
        }

        /*Product table input field*/
        .js_documenti_contabilita_articoli_descrizione {
            min-width: 130px;
        }

        .js_documenti_contabilita_articoli_unita_misura,
        .js_documenti_contabilita_articoli_iva_id,
        .js_documenti_contabilita_articoli_prezzo,
        .js-importo {
            min-width: 85px;
        }

        .js_documenti_contabilita_articoli_sconto {
            min-width: 70px;
        }
    }

    @media only screen and (min-width:992px) {
        .rcr-adjust {
            /*width: 60%;*/
            display: inline;
        }
    }

    /* New rules */
    td.totali {
        font-size: 16px;
    }

    td.totali .sconto_totale {
        max-width: 50px;
        border: 1px solid #ccc;
    }

    /* Reset rules row, col and input-sm elements inside td */
    table.table_prodotti tr td div.row,
    table.table_prodotti tr td [class*="col-"],
    table.table_prodotti tr td div.input-sm {
        margin: 0;
        padding: 0;
    }
</style>

<?php

$this->load->model('contabilita/docs');

$dati['id'] = null;
$dati['fattura'] = null;
$dati['fatture_cliente'] = null;
$dati['serie'] = null;
$dati['fatture_numero'] = null;
$dati['fatture_serie'] = null;
$dati['fatture_scadenza_pagamento'] = null;
$dati['fatture_pagato'] = null;
$dati['prodotti'] = null;
$dati['fatture_note'] = null;

/*
 * Install constants
 */
define('MODULE_NAME', 'fatture');

/** Entità **/
defined('ENTITY_SETTINGS') or define('ENTITY_SETTINGS', 'settings');
//defined('FATTURE_E_CUSTOMERS') or define('FATTURE_E_CUSTOMERS', 'clienti');

/** Parametri **/
defined('FATTURAZIONE_METODI_PAGAMENTO') or define('FATTURAZIONE_METODI_PAGAMENTO', serialize(array('Bonifico', 'Paypal', 'Contanti', 'Sepa RID', 'RIBA')));

defined('FATTURAZIONE_URI_STAMPA') or define('FATTURAZIONE_URI_STAMPA', null);

$elenco_iva = $this->apilib->search('iva', [], null, 0, 'iva_order');
$serie_documento = $this->apilib->search('documenti_contabilita_serie');
$conti_correnti = $this->apilib->search('conti_correnti');
$documento_id = ($value_id) ?: $this->input->get('documenti_contabilita_id');
$spesa_id = $this->input->get('spesa_id'); //Serve per l'autofattura reverse
$serie_get = $this->input->get('serie'); //Serve per l'autofattura reverse

$documenti_tipo = $this->apilib->search('documenti_contabilita_tipo');
$centri_di_costo = $this->apilib->search('centri_di_costo_ricavo');
$templates = $this->apilib->search('documenti_contabilita_template_pdf');
$tipi_ritenuta = $this->apilib->search('documenti_contabilita_tipo_ritenuta');
$valute = $this->apilib->search('valute', [], null, 0, 'valute_codice');
$clone = $this->input->get('clone');
$tipo_destinatario = $this->apilib->search('documenti_contabilita_tipo_destinatario');
$rifDocId = '';
$show_iva_advisor = false;

$_campi_personalizzati = $this->apilib->search('campi_righe_articoli', [], null, 0, 'campi_righe_articoli_pos');
$campi_personalizzati = [1 => [], 2 => []];
foreach ($_campi_personalizzati as $key => $campo) {
    if (!$campo['campi_righe_articoli_riga'] || $campo['campi_righe_articoli_riga'] > 2) {
        $campo['campi_righe_articoli_riga'] = 1;
    }
    $field = $this->datab->get_field_by_name($campo['campi_righe_articoli_campo'], true);
    if ($field['fields_ref']) {
        $field['support_data'] = $this->crmentity->getEntityPreview($field['fields_ref'], $field['fields_select_where'], null);
    }
    $campi_personalizzati[$campo['campi_righe_articoli_riga']][$campo['campi_righe_articoli_pos']] = array_merge($campo, $field);
    $field['data_name'] = $field['fields_name'];
    $field['forms_fields_dependent_on'] = '';

    $data = [
        'lang' => '',
        'field' => $field,
        'value' => '',
        'label' => '', // '<label class="control-label">' . $field['fields_draw_label'] . '</label>',
        'placeholder' => '',
        'help' => '',
        'class' => 'input-sm',
        'attr' => 'data-name="' . $field['data_name'] . '"',
        'onclick' => '',
        'subform' => '',
    ];
    $campi_personalizzati[$campo['campi_righe_articoli_riga']][$campo['campi_righe_articoli_pos']]['html'] = str_ireplace('select2_standard', '', sprintf('<div class="form-group">%s</div>', $this->load->view("box/form_fields/{$field['fields_draw_html_type']}", $data, true)));
}

if ($documento_id) {
    $documento = $this->apilib->view('documenti_contabilita', $documento_id);

    if ($documento['documenti_contabilita_data_emissione'] <= '2020-12-31 23:59:59') {
        $show_iva_advisor = true;
    }

    //debug($documento,true);
    $documento['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);
    $documento['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
    $documento['documenti_contabilita_destinatario'] = json_decode($documento['documenti_contabilita_destinatario'], true);
    $documento['entity_destinatario'] = ($documento['documenti_contabilita_supplier_id']) ? 'suppliers' : 'clienti';

    $documento['documenti_contabilita_fe_dati_contratto'] = json_decode($documento['documenti_contabilita_fe_dati_contratto'], true);

    //debug(array_filter($documento['documenti_contabilita_fe_dati_contratto']));

    $rifDoc = $documento['documenti_contabilita_rif_documento_id'];

    if ($clone) {
        foreach ($documento['articoli'] as $key => $p) {
            $documento['articoli'][$key]['documenti_contabilita_articoli_rif_riga_articolo'] = $documento['articoli'][$key]['documenti_contabilita_articoli_id'];
            $documento['articoli'][$key]['documenti_contabilita_articoli_id'] = null;
        }
        if (!empty($rifDoc)) {
            if ($documento_id == $rifDoc) {
                $rifDocId = $rifDoc;
            } else {
                $rifDocId = $documento_id;
            }
        } else {
            $rifDocId = $documento_id;
        }
    } else {
        if (!empty($rifDoc)) {
            $rifDocId = $rifDoc;
        }
    }
} elseif ($this->input->post('ddt_ids') || $this->input->get('ddt_id')) {
    $ids = json_decode($this->input->post('ddt_ids'), true);
    //debug($ids, true);
    if ($this->input->post('bulk_action') == 'Genera fattura distinta') {
        $tipo = 'DDT';
        //Apro una tab per ogni ddt selezionato e gli passo il ddt
        foreach ($ids as $key => $id) {
            if ($key == 0) {
                //Il primo lo skippo perchè lo processerò qua...
                continue;
            }?>
            <script>
                window.open('<?php echo base_url(); ?>main/layout/nuovo_documento/?ddt_id=<?php echo $id; ?>', '_blank');
            </script>
<?php
}
        //Una volta aperte le tab (una per ddt) continuo con questo, quindi tolgo gli altri da ids...
        $ids = [$ids[0]];
    } else {
        if (!$ids) { //Se non arrivano in post, sono delle tab, una per ddocumento... quindi lo prendo da get
            $ids = [$this->input->get('ddt_id')];
        } else {
            //Mi sono arrivati in post da una bulk action. Devo però capire se mi arrivano da un elenco ddt o da degli ordini
            if ($this->input->post('tipo_doc')) {
                $tipo = $this->input->post('tipo_doc');
            } else {
                $tipo = 'DDT';
            }
        }
    }

    $clone = true;
    $documento_id = $ids[0];

    $documento = $this->apilib->view('documenti_contabilita', $documento_id);
    $documento['documenti_contabilita_tipo'] = 1;
    //debug($documento,true);
    foreach ($templates as $template) {
        //Cerco di trovare un template di tipo fattura...
        //debug($template,true);
        if (stripos($template['documenti_contabilita_template_pdf_nome'], 'attur')) {
            //debug($template['documenti_contabilita_template_pdf_id'],true);
            $documento['documenti_contabilita_template_pdf'] = $template['documenti_contabilita_template_pdf_id'];
        }
    }

    //debug($documento,true);
    $documento['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ["documenti_contabilita_articoli_documento IN (" . implode(',', $ids) . ")"], null, 0, 'documenti_contabilita_numero');
    foreach ($documento['articoli'] as $key => $articolo) {
        //debug($articolo,true);
        $data = substr($articolo['documenti_contabilita_data_emissione'], 0, 10);
        $data = date('d/m/Y', strtotime($data));
        $documento['articoli'][$key]['documenti_contabilita_articoli_rif_riga_articolo'] = $articolo['documenti_contabilita_articoli_id'];
        $documento['articoli'][$key]['documenti_contabilita_articoli_descrizione'] = "$tipo N. {$articolo['documenti_contabilita_numero']} del {$data}";
        $documento['articoli'][$key]['documenti_contabilita_articoli_id'] = null;
    }
    $documento['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
    $documento['documenti_contabilita_destinatario'] = json_decode($documento['documenti_contabilita_destinatario'], true);
    $documento['entity_destinatario'] = ($documento['documenti_contabilita_supplier_id']) ? 'suppliers' : 'clienti';
//debug($this->input->post('ddt_ids'));
} //Aggiungere qua il controllo se mi arrivano dei prodotti generici in post...
elseif ($this->input->post('articoli') && is_array($this->input->post('articoli'))) {
    $articoli_post = $this->input->post('articoli');

    // debug($articoli_post);
    foreach ($articoli_post as $index => $articolo) {
        if (empty(trim($articolo['nome'])) && empty($articolo['prezzo'])) {
            continue;
        }

        $prezzo = 0;
        if (!ctype_digit($articolo['prezzo'])) {
            $prezzo = $articolo['prezzo'];
        }

        $nome_articolo = trim($articolo['nome']);

        $desc = '';
        if (!empty(trim($articolo['descrizione']))) {
            $desc = trim($articolo['descrizione']);
        }

        $codice = '';
        if (!empty($articolo['codice'])) {
            $codice = trim($articolo['codice']);
        }

        $quantita = 1;
        if (!empty($articolo['quantita']) && ctype_digit($articolo['quantita'])) {
            $quantita = $articolo['quantita'];
        }

        $importo = 0;
        if (!empty($articolo['importo']) && ctype_digit($articolo['importo'])) {
            $importo = $prezzo * $quantita;
        }

        $documento['articoli'][$index] = [
            'documenti_contabilita_articoli_id' => null,
            'documenti_contabilita_articoli_rif_riga_articolo' => null,
            'documenti_contabilita_articoli_riga_desc' => null,
            'documenti_contabilita_articoli_codice' => $codice,
            'documenti_contabilita_articoli_name' => $nome_articolo,
            'documenti_contabilita_articoli_descrizione' => $desc,
            'documenti_contabilita_articoli_prezzo' => $prezzo,
            'documenti_contabilita_articoli_iva' => '',
            'documenti_contabilita_articoli_prodotto_id' => '',
            'documenti_contabilita_articoli_codice_asin' => '',
            'documenti_contabilita_articoli_codice_ean' => '',
            'documenti_contabilita_articoli_unita_misura' => '',
            'documenti_contabilita_articoli_quantita' => $quantita,
            'documenti_contabilita_articoli_sconto' => '',
            'documenti_contabilita_articoli_applica_ritenute' => '',
            'documenti_contabilita_articoli_applica_sconto' => '',
            'documenti_contabilita_articoli_importo_totale' => '',
            'documenti_contabilita_articoli_iva_id' => 1,
        ];

        // aggiungo campi personalizzati all'array $documento['articoli']
        if (!empty($campi_personalizzati[1])) {
            foreach ($campi_personalizzati[1] as $campo) {
                if (array_key_exists($campo['campi_righe_articoli_map_to'], $articolo) && !empty($articolo[$campo['campi_righe_articoli_map_to']])) {
                    $documento['articoli'][$index][$campo['campi_righe_articoli_map_to']] = $articolo[$campo['campi_righe_articoli_map_to']];
                }
            }
        }
    }
} elseif ($spesa_id) {
    //Sto generando un'autofattura partendo dalla spesa...
    $spesa = $this->apilib->view('spese', $spesa_id);
    $spesa_articoli = $this->apilib->search('spese_articoli', ['spese_articoli_spesa' => $spesa_id]);

    $clone = true;
    $documento['documenti_contabilita_tipo'] = 11;
    $documento['documenti_contabilita_tipologia_fatturazione'] = $spesa['spese_tipologia_autofattura'];

    //debug($documento,true);
    foreach ($templates as $template) {
        //Cerco di trovare un template di tipo reverse...
        //debug($template,true);
        if (stripos($template['documenti_contabilita_template_pdf_nome'], 'everse')) {
            //debug($template['documenti_contabilita_template_pdf_id'],true);
            $documento['documenti_contabilita_template_pdf'] = $template['documenti_contabilita_template_pdf_id'];
        }
    }

    //debug($documento,true);
    $documento['articoli'] = [];
    if ($spesa_articoli) {
        debug('TODO: Registrazioni singole in spesa non gestite!', true);
    } else {

        if ($this->input->get('doc_type') == 'Nota di credito Reverse') {
            $sign = -1;
        } else {
            $sign = 1;

        }
        //debug($spesa, true);

        $documento['articoli'][] = [
            'documenti_contabilita_articoli_name' => "Beni e servizi",
            'documenti_contabilita_articoli_prezzo' => number_format($spesa['spese_imponibile'] * $sign, 2, '.', ''),
            'documenti_contabilita_articoli_iva' => $spesa['spese_iva'],
            'documenti_contabilita_articoli_iva_id' => '',
            'documenti_contabilita_articoli_iva_perc' => 22,
            'documenti_contabilita_articoli_importo_totale' => $spesa['spese_totale'] * $sign,
            'documenti_contabilita_articoli_imponibile' => $spesa['spese_imponibile'] * $sign,
            'documenti_contabilita_articoli_quantita' => 1,
            'documenti_contabilita_articoli_codice' => '',
            'documenti_contabilita_articoli_unita_misura' => '',
            'documenti_contabilita_articoli_sconto' => '0',
            'documenti_contabilita_articoli_prodotto_id' => null,
            'documenti_contabilita_articoli_applica_ritenute' => DB_BOOL_FALSE,
            'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_FALSE,
            'documenti_contabilita_articoli_id' => null,
            'documenti_contabilita_articoli_descrizione' => '',
            'documenti_contabilita_articoli_codice_asin' => '',
            'documenti_contabilita_articoli_codice_ean' => '',
            'documenti_contabilita_articoli_rif_riga_articolo' => '',
            'documenti_contabilita_articoli_riga_desc' => DB_BOOL_FALSE,
        ];
        //debug($documento['articoli'],true);
    }
    $documento['documenti_contabilita_destinatario'] = json_decode($spesa['spese_fornitore'], true);
    // $documento['documenti_contabilita_destinatario']['nazione'] = '';
    $documento['entity_destinatario'] = 'customers';

}

$settings = $this->apilib->search('documenti_contabilita_settings');

$impostazioni = $settings[0];

//Rimosse le mappature. Ora punta tutto a customers
$mappature = $this->docs->getMappature();
$mappature_autocomplete = $this->docs->getMappatureAutocomplete();

extract($mappature);

//$entita_prodotti = 'listino_prezzi'; // commentato in quanto $entita_prodotti è preso dalla variabile $mappature (quindi mappato in db)
$entita = $entita_prodotti;
$campo_codice = $campo_codice_prodotto;
$campo_unita_misura = (!empty($campo_unita_misura_prodotto)) ? $campo_unita_misura_prodotto : '';
$campo_preview = $campo_preview_prodotto;
$campo_prezzo = $campo_prezzo_prodotto;
$campo_prezzo_fornitore = (!empty($campo_prezzo_fornitore)) ? $campo_prezzo_fornitore : '';
$campo_quantita = (!empty($campo_quantita_prodotto)) ? $campo_quantita_prodotto : '';
$campo_iva = @$campo_iva_prodotto;
$campo_provvigione = @$campo_provvigione_prodotto;
$campo_ricarico = @$campo_ricarico_prodotto;
$campo_descrizione = $campo_descrizione_prodotto;
$campo_sconto = (!empty($campo_sconto_prodotto)) ? $campo_sconto_prodotto : '';
$campo_sconto2 = (!empty($campo_sconto2_prodotto)) ? $campo_sconto2_prodotto : '';
$campo_sconto3 = (!empty($campo_sconto3_prodotto)) ? $campo_sconto3_prodotto : '';
$campo_centro_costo = (!empty($campo_centro_costo_prodotto)) ? $campo_centro_costo_prodotto : '';

//debug($campo_centro_costo, true);

$campo_id = (empty($campo_id_prodotto)) ? $entita . '_id' : $campo_id_prodotto;

$articoli_ids = $this->input->get_post('articoli_ids');

if (!empty($articoli_ids)) {
    $articoli_ids = implode(',', $articoli_ids);
    $articoli = $this->apilib->search($entita, [$entita . '_id IN (' . $articoli_ids . ')']);

    if (!empty($articoli)) {
        foreach ($articoli as $key => $value) {
            $documento['articoli'][$key] = [
                'documenti_contabilita_articoli_codice' => $value[$campo_codice],
                'documenti_contabilita_articoli_name' => $value[$campo_preview],
                'documenti_contabilita_articoli_descrizione' => $value[$campo_descrizione],
                'documenti_contabilita_articoli_prezzo' => $value[$campo_prezzo],
                'documenti_contabilita_articoli_iva' => $value[$campo_iva],
                'documenti_contabilita_articoli_prodotto_id' => $value[$campo_id],
                'documenti_contabilita_articoli_codice_asin' => '',
                'documenti_contabilita_articoli_codice_ean' => '',
                'documenti_contabilita_articoli_unita_misura' => '',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_sconto' => '',
                'documenti_contabilita_articoli_applica_ritenute' => '',
                'documenti_contabilita_articoli_applica_sconto' => '',
                'documenti_contabilita_articoli_importo_totale' => '',
                'documenti_contabilita_articoli_iva_id' => 1,
            ];
        }
    }
}

if ($this->input->get('documenti_contabilita_clienti_id')) {
    $customer = $this->apilib->view($entita_clienti, $this->input->get('documenti_contabilita_clienti_id'));
    $documento['documenti_contabilita_customer_id'] = $this->input->get('documenti_contabilita_clienti_id');

    $nazione_cliente = $customer[$clienti_nazione];
    if (!empty($mappature_autocomplete['clienti_nazione']) && $mappature_autocomplete['clienti_nazione'] != $clienti_nazione) {
        $nazione_cliente = $customer[$mappature_autocomplete['clienti_nazione']];
    }

    $cliente = [
        'codice' => $customer[$clienti_codice],
        'ragione_sociale' => $customer[$clienti_ragione_sociale] ?? $customer[$clienti_nome] . ' ' . $customer[$clienti_cognome],
        'indirizzo' => $customer[$clienti_indirizzo],
        'citta' => $customer[$clienti_citta],
        'nazione' => $nazione_cliente,
        'cap' => $customer[$clienti_cap],
        'provincia' => $customer[$clienti_provincia],
        'partita_iva' => $customer[$clienti_partita_iva],
        'codice_fiscale' => $customer[$clienti_codice_fiscale],
        'codice_sdi' => $customer[$clienti_codice_sdi],
        'pec' => $customer[$clienti_pec],
    ];

    $documento['documenti_contabilita_destinatario'] = $cliente;
    $documento['entity_destinatario'] = $entita_clienti;
}

$metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');

$tipologie_fatturazione = $this->apilib->search('documenti_contabilita_tipologie_fatturazione');
$ddts = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_tipo' => '8']); // @todo - 20190704 - Michael E. - Aggiungere poi il filtro documenti_contabilita_utente_id per filtrare solo i ddt dell'utente loggato

//Mi costruisco un oggetto da riutilizzare per le scadenze automatiche
$template_scadenze = $this->apilib->search('documenti_contabilita_template_pagamenti');
foreach ($template_scadenze as $key => $tpl_scad) {
    //Riordino le sotto scadenze sul campo "giorni"
    usort($tpl_scad['documenti_contabilita_tpl_pag_scadenze'], function ($a, $b) {
        return ($a['documenti_contabilita_tpl_pag_scadenze_giorni'] < $b['documenti_contabilita_tpl_pag_scadenze_giorni']) ? -1 : 1;
    });
    $template_scadenze[$key] = $tpl_scad;
}

//Mi costruisco un oggetto da riutilizzare per i listini automatici
$listini = $this->apilib->search('listini');
//debug($campi_personalizzati,true);
//La prima riga detta legge... tutti gli altri campi vengono mostrati nella riga 2 e devono essere <= ai campi di riga 1
$colonne_count = 9 + count($campi_personalizzati[1]) + ($impostazioni['documenti_contabilita_settings_sconto2'] ? 1 : 0) + ($impostazioni['documenti_contabilita_settings_sconto3'] ? 1 : 0) + ($campo_centro_costo ? 1 : 0);

for ($i = 1; $i <= $colonne_count; $i++) {
    if (!array_key_exists($i, $campi_personalizzati[2])) {
        //debug($campi_personalizzati[2]);
        $campi_personalizzati[2][$i] = false;
    }
}
ksort($campi_personalizzati[2]);

$nazioni = $this->apilib->search('countries', [], null, 0, 'countries_name', 'ASC');

?>

<?php if ($show_iva_advisor): ?>
    <section class="content-header">
        <div class="callout callout-warning">
            <h4>Nuove specifiche in vigore dal 01/01/2021</h4>

            <p>Il documento che stai modificando o duplicando è stato creato prima del 01/01/2021, data in cui sono entrate in vigore le nuove regole SDI per le classi iva. Verificare attentamente che le righe articolo riportino l'indicazione di iva corretta.</p>
        </div>
    </section>
<?php endif;?>

<form class="formAjax" id="new_fattura" action="<?php echo base_url('contabilita/documenti/create_document'); ?>">
    <?php add_csrf();?>
    <?php if ($documento_id && !$clone): ?>
        <input name="documento_id" type="hidden" value="<?php echo $documento_id; ?>" />
    <?php endif;?>

    <?php if ($spesa_id): ?>
        <input name="spesa_id" type="hidden" value="<?php echo $spesa_id; ?>" />
        <?php endif;?>

    <input type="hidden" name="documenti_contabilita_totale" value="<?php echo ($documento_id && $documento['documenti_contabilita_totale']) ? number_format((float) $documento['documenti_contabilita_totale'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_iva" value="<?php echo ($documento_id && $documento['documenti_contabilita_iva']) ? number_format((float) $documento['documenti_contabilita_iva'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_competenze" value="<?php echo ($documento_id && $documento['documenti_contabilita_competenze']) ? number_format((float) $documento['documenti_contabilita_competenze'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_rivalsa_inps_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_rivalsa_inps_valore']) ? number_format((float) $documento['documenti_contabilita_rivalsa_inps_valore'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_competenze_lordo_rivalsa" value="<?php echo ($documento_id && $documento['documenti_contabilita_competenze_lordo_rivalsa']) ? number_format((float) $documento['documenti_contabilita_competenze_lordo_rivalsa'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_cassa_professionisti_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_cassa_professionisti_valore']) ? number_format((float) $documento['documenti_contabilita_cassa_professionisti_valore'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile']) ? number_format((float) $documento['documenti_contabilita_imponibile'], 3, ',', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile_scontato" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile_scontato']) ? number_format((float) $documento['documenti_contabilita_imponibile_scontato'], 3, ',', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_ritenuta_acconto_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_ritenuta_acconto_valore']) ? number_format((float) $documento['documenti_contabilita_ritenuta_acconto_valore'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_ritenuta_acconto_imponibile_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_ritenuta_acconto_imponibile_valore']) ? number_format((float) $documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_iva_json" value="<?php echo ($documento_id && $documento['documenti_contabilita_iva_json']) ? $documento['documenti_contabilita_iva_json'] : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile_iva_json" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile_iva_json']) ? $documento['documenti_contabilita_imponibile_iva_json'] : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_extra_param" value="<?php echo ($documento_id && $documento['documenti_contabilita_extra_param']) ? $documento['documenti_contabilita_extra_param'] : (!empty($this->input->get('extra_param')) ? $this->input->get('extra_param') : ''); ?>" />
    <input type="hidden" name="documenti_contabilita_luogo_destinazione_id" value="<?php echo ($documento_id && $documento['documenti_contabilita_luogo_destinazione_id']) ? $documento['documenti_contabilita_luogo_destinazione_id'] : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_utente_id" value="<?php echo ($documento_id && $documento['documenti_contabilita_utente_id']) ? $documento['documenti_contabilita_utente_id'] : $this->session->userdata('session_login')[LOGIN_ENTITY . '_id']; ?>" />

    <div class="row mb-15">
        <div class="col-md-10 col-sm-12" style="margin-bottom:20px;">
            <label>Tipo di documento:</label>
            <div class="btn-group">
                <?php foreach ($documenti_tipo as $tipo): ?>
                    <button type="button" class="btn <?php if ($documento_id && ($documento_id && $documento['documenti_contabilita_tipo'] == $tipo['documenti_contabilita_tipo_id'])): ?>btn-primary<?php else: ?>btn-default<?php endif;?> js_btn_tipo" data-tipo="<?php echo $tipo['documenti_contabilita_tipo_id']; ?>"><?php echo $tipo['documenti_contabilita_tipo_value']; ?></button>
                <?php endforeach;?>

                <input type="hidden" name="documenti_contabilita_tipo" class="js_documenti_contabilita_tipo" value="<?php if (($documento_id || $spesa_id) && $documento['documenti_contabilita_tipo']): ?><?php echo $documento['documenti_contabilita_tipo']; ?><?php else: ?><?php echo 1; ?><?php endif;?>" />
            </div>
        </div>
        <div class="col-md-2 col-sm-12">
            <div class="pull-right">
                <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-success js_elenco_documenti"><i class="fa fa-arrow-left"></i> Elenco Documenti</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="row bg-gray mb-15 js_cliente_container">
                <div class="row">
                    <div class="col-md-12">
                        <h4>Dati del <span class="js_dest_type"><?php if ($documento_id && $documento['documenti_contabilita_supplier_id']): ?>cliente<?php else: ?>fornitore<?php endif;?></span></h4>
                    </div>
                </div>

                <input type="hidden" name="dest_entity_name" value="<?php if ($documento_id && $documento['documenti_contabilita_supplier_id']): ?>fornitori<?php else: ?><?php echo $entita_clienti; ?><?php endif;?>" />
                <input id="js_dest_id" type="hidden" name="dest_id" value="<?php if ((($this->input->get('documenti_contabilita_clienti_id') || $this->input->get('documenti_contabilita_customer_id')) || $documento_id) && $documento['documenti_contabilita_customer_id']): ?><?php echo ($documento['documenti_contabilita_customer_id'] ?: $documento['documenti_contabilita_supplier_id']); ?><?php endif;?>" />

                <div class="row">
                    <div class="form-group">
                        <?php foreach ($tipo_destinatario as $tipo_dest): ?>
                            <div class="col-sm-4">
                                <label>
                                    <input type="radio" name="documenti_contabilita_tipo_destinatario" class="js_tipo_destinatario" <?php if (!empty($documento['documenti_contabilita_tipo_destinatario']) && $documento['documenti_contabilita_tipo_destinatario'] == $tipo_dest['documenti_contabilita_tipo_destinatario_id']): ?> checked="checked" <?php endif;?> value="<?php echo $tipo_dest['documenti_contabilita_tipo_destinatario_id']; ?>"> <?php echo $tipo_dest['documenti_contabilita_tipo_destinatario_value']; ?>
                                </label>
                            </div>
                        <?php endforeach;?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class=" form-group">
                            <input type="text" name="codice" class="form-control js_dest_codice search_cliente" placeholder="Codice" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo @$documento['documenti_contabilita_destinatario']["codice"]; ?><?php endif;?>" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <input type="text" name="ragione_sociale" class="form-control js_dest_ragione_sociale search_cliente" placeholder="Ragione sociale" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["ragione_sociale"]; ?><?php endif;?>" autocomplete="off" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="indirizzo" class="form-control js_dest_indirizzo" placeholder="Indirizzo" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["indirizzo"]; ?><?php endif;?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="citta" class="form-control js_dest_citta" placeholder="Città" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["citta"]; ?><?php endif;?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <?php if (!empty($nazioni)): ?>
                                <select name="nazione" id="nazione" class="form-control select2_standard js_dest_nazione">
                                    <?php foreach ($nazioni as $nazione): ?>
                                        <?php
$selected = '';

if (
    (!empty($documento['documenti_contabilita_destinatario']) && $documento['documenti_contabilita_destinatario']["nazione"] === $nazione['countries_iso'])
    || empty($documento['documenti_contabilita_destinatario']) && $nazione['countries_iso'] == 'IT'
) {
    $selected = 'selected';
}
?>

                                        <option value="<?php echo $nazione['countries_iso']; ?>" <?php echo $selected ?>><?php echo $nazione['countries_name']; ?></option>
                                    <?php endforeach;?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="nazione" maxlength="2" minlength="2" class="form-control js_dest_nazione" placeholder="Nazione" value="<?php if (!empty($documento['documenti_contabilita_destinatario']) && (strlen($documento['documenti_contabilita_destinatario']["nazione"]) < 3)): ?><?php echo $documento['documenti_contabilita_destinatario']["nazione"]; ?><?php else: ?><?php echo "IT"; ?><?php endif;?>" />
                            <?php endif;?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="text" name="cap" class="form-control js_dest_cap" placeholder="CAP" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["cap"]; ?><?php endif;?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div clasS="form-group">
                            <input type="text" name="provincia" class="form-control js_dest_provincia" placeholder="Provincia" maxlength="2" minlength="2" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["provincia"]; ?><?php endif;?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="partita_iva" class="form-control js_dest_partita_iva" placeholder="P.IVA" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["partita_iva"]; ?><?php endif;?>" />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="codice_fiscale" class="form-control js_dest_codice_fiscale" placeholder="Codice fiscale" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["codice_fiscale"]; ?><?php endif;?>" />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="codice_sdi" class="form-control js_dest_codice_sdi" placeholder="Codice destinatario (per privati 0000000)" value="<?php if (!empty($documento['documenti_contabilita_destinatario']['codice_sdi'])): ?><?php echo $documento['documenti_contabilita_destinatario']['codice_sdi']; ?><?php endif;?>" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="pec" class="form-control js_dest_pec" placeholder="Indirizzo pec" value="<?php if (!empty($documento['documenti_contabilita_destinatario']['pec'])): ?><?php echo $documento['documenti_contabilita_destinatario']['pec']; ?><?php endif;?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label id="js_label_rubrica">Salva in rubrica</label> <input type="checkbox" class="minimal" name="save_dest" value="true" />

                        </div>

                    </div>
                    <div class="col-md-6">

                        <div id="js_listino_applicato"></div>



                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="row mb-15" style="padding-bottom:10px;">
                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-12">
                        <h4>Dati <span class="js_doc_type">documento</span></h4>
                    </div>
                </div>

                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Azienda: </label>
                            <select name="documenti_contabilita_azienda" class="form-control documenti_contabilita_azienda">
                                <?php foreach ($settings as $setting): ?>
                                    <option value="<?php echo $setting['documenti_contabilita_settings_id']; ?>" <?php if ((!empty($documento_id)) && (!empty($documento['documenti_contabilita_azienda']) && $documento['documenti_contabilita_azienda'] == $setting['documenti_contabilita_settings_id'])): ?> selected="selected" <?php endif;?>><?php echo $setting['documenti_contabilita_settings_company_name']; ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Numero: </label> <input type="text" name="documenti_contabilita_numero" class="form-control documenti_contabilita_numero" placeholder="Numero documento" value="<?php if (!empty($documento['documenti_contabilita_numero']) && !$clone): ?><?php echo $documento['documenti_contabilita_numero']; ?><?php endif;?>" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Data emissione: </label>
                            <?php //debug($documento);
?>
                            <div class="input-group js_form_datepicker date ">
                                <input type="text" name="documenti_contabilita_data_emissione" class="form-control" placeholder="Data emissione" value="<?php if (!empty($documento['documenti_contabilita_data_emissione']) && !$clone): ?><?php echo date('d/m/Y', strtotime($documento['documenti_contabilita_data_emissione'])); ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif;?>" data-name="documenti_contabilita_data_emissione" /> <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <?php //debug($documento['documenti_contabilita_valuta']);
?>
                            <label style="min-width:80px">Valuta: </label> <select name="documenti_contabilita_valuta" class="select2 form-control documenti_contabilita_valuta">
                                <?php foreach ($valute as $key => $valuta): ?>
                                    <option data-id="<?php echo $valuta['valute_id']; ?>" value="<?php echo $valuta['valute_codice']; ?>" <?php if (($valuta['valute_id'] == $impostazioni['documenti_contabilita_settings_valuta_base'] && empty($documento_id)) || (!empty($documento['documenti_contabilita_valuta']) && strtoupper($documento['documenti_contabilita_valuta']) == strtoupper($valuta['valute_codice']))): ?> selected="selected" <?php endif;?>><?php echo $valuta['valute_nome']; ?> - <?php echo $valuta['valute_simbolo']; ?></option>
                                <?php endforeach;?>

                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-3">
                        <label style="min-width:80px">Tasso di cambio (<?php echo $impostazioni['valute_simbolo']; ?>): </label>
                        <input type="text" class="form-control documenti_contabilita_tasso_di_cambio" name="documenti_contabilita_tasso_di_cambio" value="<?php if (empty($documento_id) || empty($documento['documenti_contabilita_tasso_di_cambio'])): ?>1<?php else: ?><?php echo $documento['documenti_contabilita_tasso_di_cambio']; ?><?php endif;?>">
                    </div>
                </div>

                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Serie: </label><br />
                            <div class="btn-group">
                                <?php foreach ($serie_documento as $serie): ?>
                                    <button type="button" class="btn js_btn_serie btn-info <?php if (($serie_get && $serie_get == $serie['documenti_contabilita_serie_value']) || (!$serie_get && !empty($documento['documenti_contabilita_serie']) && $documento['documenti_contabilita_serie'] == $serie['documenti_contabilita_serie_value']) || (!$serie_get && empty($documento['documenti_contabilita_serie']) && $impostazioni['documenti_contabilita_settings_serie_default'] == $serie['documenti_contabilita_serie_id'])): ?>button_selected<?php endif;?>" data-serie="<?php echo $serie['documenti_contabilita_serie_value']; ?>">
                                        /<?php echo $serie['documenti_contabilita_serie_value']; ?></button>
                                <?php endforeach;?>
                                <input type="hidden" class="js_documenti_contabilita_serie" name="documenti_contabilita_serie" value="<?php if (!empty($documento['documenti_contabilita_serie'])): ?><?php echo $documento['documenti_contabilita_serie']; ?><?php else: ?><?php echo $impostazioni['documenti_contabilita_serie_value']; ?><?php endif;?>" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Template Documento: </label> <select name="documenti_contabilita_template_pdf" class="select2 form-control js_template_pdf">
                                <?php foreach ($templates as $template): ?>
                                    <option data-tipo="<?php echo $template['documenti_contabilita_template_pdf_tipo']; ?>" value='<?php echo $template['documenti_contabilita_template_pdf_id']; ?>' <?php if ((!empty($documento_id) || !empty($spesa_id)) && (!empty($documento['documenti_contabilita_template_pdf']) && $documento['documenti_contabilita_template_pdf'] == $template['documenti_contabilita_template_pdf_id'])): ?> selected="selected" <?php endif;?>><?php echo $template['documenti_contabilita_template_pdf_nome']; ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Centro di ricavo: </label>
                            <select name="documenti_contabilita_centro_di_ricavo" class="select2 form-control">
                                <?php foreach ($centri_di_costo as $centro): ?>
                                    <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>" <?php if (($centro['centri_di_costo_ricavo_id'] == '1' && empty($documento_id)) || (!empty($documento['documenti_contabilita_centro_di_ricavo']) && $documento['documenti_contabilita_centro_di_ricavo'] == $centro['centri_di_costo_ricavo_id'])): ?> selected="selected" <?php endif;?>><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Tipologia di fatturazione: </label>
                            <select name="documenti_contabilita_tipologia_fatturazione" class="select2 form-control js_tipologia_fatturazione">
                                <?php if (!empty($documento_id) && empty($documento['documenti_contabilita_tipologia_fatturazione'])): ?> <option value=""></option> <?php endif;?>
                                <?php foreach ($tipologie_fatturazione as $tipologia): ?>
                                    <option data-tipologia_codice="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_codice']; ?>" data-tipologia_descrizione="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_descrizione']; ?>" value="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_id']; ?>" <?php if (($tipologia['documenti_contabilita_tipologie_fatturazione_id'] == '1' && empty($documento_id)) || (!empty($documento['documenti_contabilita_tipologia_fatturazione']) && $documento['documenti_contabilita_tipologia_fatturazione'] == $tipologia['documenti_contabilita_tipologie_fatturazione_id'])): ?> selected="selected" <?php endif;?>><?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_codice'], " ", $tipologia['documenti_contabilita_tipologie_fatturazione_descrizione']; ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" style="display:none;">
                        <div class="form-group">
                            <label style="min-width:80px;">Rif. Documento: </label>
                            <input type="text" class="form-control" name="documenti_contabilita_rif_documento_id" value="<?php echo $rifDocId; ?>">
                        </div>
                    </div>
                </div>
                <div class="row mb-15" style="background-color:#b7d7ea;">
                    <div class="col-md-12">
                        <div class="form-group">
                            <span>
                                <label><strong>Formato elettronico</strong></label>
                                <input type="checkbox" class="minimal" name="documenti_contabilita_formato_elettronico" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_formato_elettronico']) && $documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                            </span>
                        </div>
                    </div>
                </div>



                <div class="row mb-15" style="background-color:#6bbf81 ">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Informazioni pagamento</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <select name="documenti_contabilita_metodo_pagamento" class="select2 form-control">
                                    <option value="">Metodo di pagamento</option>

                                    <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                        <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']; ?>" <?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == $metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']): ?> selected="selected" <?php endif;?>>
                                            <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                        </option>
                                    <?php endforeach;?>
                                    <!--
                                <option value="contanti"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'contanti'): ?> selected="selected"<?php endif;?>>
                                    Contanti
                                </option>
                                <option value="bonifico bancario"<?php if (empty($documento['documenti_contabilita_metodo_pagamento']) || (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'bonifico bancario')): ?> selected="selected"<?php endif;?>>
                                    Bonifico bancario
                                </option>
                                <option value="assegno"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'assegno'): ?> selected="selected"<?php endif;?>>
                                    Assegno
                                </option>
                                <option value="riba"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'riba'): ?> selected="selected"<?php endif;?>>
                                    RiBA
                                </option>
                                <option value="sepa_rid"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'sepa_rid'): ?> selected="selected"<?php endif;?>>
                                    SEPA RID
                                </option>
                                -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <select name="documenti_contabilita_conto_corrente" class="select2 form-control">
                                    <option value="">Scegli conto corrente....</option>

                                    <?php foreach ($conti_correnti as $key => $conto): ?>
                                        <option value="<?php echo $conto['conti_correnti_id']; ?>" <?php if ((empty($documento_id) && $conto['conti_correnti_default'] == DB_BOOL_TRUE) || (!empty($documento['documenti_contabilita_conto_corrente']) && $documento['documenti_contabilita_conto_corrente'] == $conto['conti_correnti_id'])): ?> selected="selected" <?php endif;?>><?php echo $conto['conti_correnti_nome_istituto']; ?></option>

                                    <?php endforeach;?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!--<div class="col-md-6">
                            <div class="form-group">
                                <span>
                                    <label><strong>Formato elettronico</strong></label>
                                    <input type="checkbox" class="minimal" name="documenti_contabilita_formato_elettronico" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_formato_elettronico']) && $documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                </span>
                            </div>
                        </div>-->
                        <div class="col-md-6">
                            <div class="form-group">
                                <span>
                                    <label>Applica Split Payment</label>
                                    <input type="checkbox" class="minimal" name="documenti_contabilita_split_payment" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_split_payment']) && $documento['documenti_contabilita_split_payment'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row js_rivalsa_container" style="background-color:#b7d7ea;">
                    <div class="col-md-12">
                        <h4>Rivalsa, Cassa INPS e Ritenuta d’acconto</h4>
                    </div>
                    <div class="row rcr_label">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>Rivalsa INPS: </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="documenti_contabilita_rivalsa_inps_perc" value="<?php if (!empty($documento['documenti_contabilita_rivalsa_inps_perc'])): ?><?php echo number_format((float) $documento['documenti_contabilita_rivalsa_inps_perc'], 2, '.', ''); ?><?php else: ?>0<?php endif;?>" />
                                    <span class="input-group-addon" id="basic-addon2">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>Cassa professionisti: </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="documenti_contabilita_cassa_professionisti_perc" value="<?php if (!empty($documento['documenti_contabilita_cassa_professionisti_perc'])): ?><?php echo number_format((float) $documento['documenti_contabilita_cassa_professionisti_perc'], 2, '.', ''); ?><?php else: ?>0<?php endif;?>" />
                                    <span class="input-group-addon" id="basic-addon2">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>Ritenuta d'acconto: </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="documenti_contabilita_ritenuta_acconto_perc" value="<?php if (!empty($documento['documenti_contabilita_ritenuta_acconto_perc'])): ?><?php echo number_format((float) $documento['documenti_contabilita_ritenuta_acconto_perc'], 2, '.', ''); ?><?php else: ?>0<?php endif;?>" />
                                    <span class="input-group-addon" id="basic-addon2">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>% sull'imponibile: </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="documenti_contabilita_ritenuta_acconto_perc_imponibile" value="<?php if (!empty($documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'])): ?><?php echo number_format((float) $documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'], 2, '.', ''); ?><?php else: ?>100<?php endif;?>" />
                                    <span class="input-group-addon" id="basic-addon2">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row rcr_label">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>Importo bollo: </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="documenti_contabilita_importo_bollo" value="<?php if (!empty($documento['documenti_contabilita_importo_bollo'])): ?><?php echo number_format((float) $documento['documenti_contabilita_importo_bollo'], 2, '.', ''); ?><?php else: ?>0<?php endif;?>" />
                                    <span class="input-group-addon" id="basic-addon2">€</span>
                                </div>
                                <!--<span>
                                    <label><strong>Applica Bollo</strong>
                                        <input type="checkbox" class="minimal" name="documenti_contabilita_applica_bollo" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (empty($documento_id) || !empty($documento['documenti_contabilita_applica_bollo']) && $documento['documenti_contabilita_applica_bollo'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                    </label>
                                    <label><strong>Bollo virtuale</strong>
                                        <input type="checkbox" class="minimal" name="documenti_contabilita_bollo_virtuale" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (empty($documento_id) || !empty($documento['documenti_contabilita_bollo_virtuale']) && $documento['documenti_contabilita_bollo_virtuale'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                    </label>
                                </span>-->
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <div class="causale-container">
                                    <label>Causale Pag. Rit.: </label>
                                    <button type="button" class="btn btn-xs btn-info btn-causale" data-toggle="modal" data-target="#modal-default">
                                        Legenda
                                    </button>
                                </div>
                                <select name="documenti_contabilita_causale_pagamento_ritenuta" class="select2 form-control">
                                    <option value="" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == ""): ?>selected="selected" <?php endif;?>></option>
                                    <option value="A" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "A"): ?>selected="selected" <?php endif;?>>A</option>
                                    <option value="B" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "B"): ?>selected="selected" <?php endif;?>>B</option>
                                    <option value="C" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "C"): ?>selected="selected" <?php endif;?>>C</option>
                                    <option value="D" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "D"): ?>selected="selected" <?php endif;?>>D</option>
                                    <option value="E" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "E"): ?>selected="selected" <?php endif;?>>E</option>
                                    <option value="F" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "F"): ?>selected="selected" <?php endif;?>>F</option>
                                    <option value="G" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "G"): ?>selected="selected" <?php endif;?>>G</option>
                                    <option value="H" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "H"): ?>selected="selected" <?php endif;?>>H</option>
                                    <option value="I" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "I"): ?>selected="selected" <?php endif;?>>I</option>
                                    <option value="J" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "J"): ?>selected="selected" <?php endif;?>>J</option>
                                    <option value="K" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "K"): ?>selected="selected" <?php endif;?>>K</option>
                                    <option value="L" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "L"): ?>selected="selected" <?php endif;?>>L</option>
                                    <option value="L1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "L1"): ?>selected="selected" <?php endif;?>>L1</option>
                                    <option value="M" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "M"): ?>selected="selected" <?php endif;?>>M</option>
                                    <option value="M1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "M1"): ?>selected="selected" <?php endif;?>>M1</option>
                                    <option value="M2" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "M2"): ?>selected="selected" <?php endif;?>>M2</option>
                                    <option value="N" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "N"): ?>selected="selected" <?php endif;?>>N</option>
                                    <option value="O" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "O"): ?>selected="selected" <?php endif;?>>O</option>
                                    <option value="O1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "O1"): ?>selected="selected" <?php endif;?>>O1</option>
                                    <option value="P" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "P"): ?>selected="selected" <?php endif;?>>P</option>
                                    <option value="Q" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "Q"): ?>selected="selected" <?php endif;?>>Q</option>
                                    <option value="R" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "R"): ?>selected="selected" <?php endif;?>>R</option>
                                    <option value="S" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "S"): ?>selected="selected" <?php endif;?>>S</option>
                                    <option value="T" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "T"): ?>selected="selected" <?php endif;?>>T</option>
                                    <option value="U" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "U"): ?>selected="selected" <?php endif;?>>U</option>
                                    <option value="V" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "V"): ?>selected="selected" <?php endif;?>>V</option>
                                    <option value="V1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "V1"): ?>selected="selected" <?php endif;?>>V1</option>
                                    <option value="V2" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "V2"): ?>selected="selected" <?php endif;?>>V2</option>
                                    <option value="W" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "W"): ?>selected="selected" <?php endif;?>>W</option>
                                    <option value="X" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "X"): ?>selected="selected" <?php endif;?>>X</option>
                                    <option value="Y" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "Y"): ?>selected="selected" <?php endif;?>>Y</option>
                                    <option value="Z" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "Z"): ?>selected="selected" <?php endif;?>>Z</option>
                                    <option value="ZO" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == "ZO"): ?>selected="selected" <?php endif;?>>ZO</option>
                                </select>
                                <!-- todo da completare in base alle richieste -->
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>Tipo ritenuta: </label>
                                <select name="documenti_contabilita_tipo_ritenuta" class="select2 form-control">
                                    <?php foreach ($tipi_ritenuta as $key => $tipo_ritenuta): ?>
                                        <option value="<?php echo $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_id']; ?>" <?php if (!empty($documento['documenti_contabilita_tipo_ritenuta']) && $documento['documenti_contabilita_tipo_ritenuta'] == $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_id']): ?> selected="selected" <?php endif;?>><?php echo $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_descrizione']; ?></option>
                                    <?php endforeach;?>
                                </select>
                                <!-- todo da completare in base alle richieste -->
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <span>
                                    <label>
                                        <strong>Applica Bollo</strong>
                                        <input type="checkbox" class="minimal" name="documenti_contabilita_applica_bollo" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (empty($documento_id) || !empty($documento['documenti_contabilita_applica_bollo']) && $documento['documenti_contabilita_applica_bollo'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                    </label>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <span>
                                    <label><strong>Bollo virtuale</strong>
                                        <input type="checkbox" class="minimal" name="documenti_contabilita_bollo_virtuale" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (empty($documento_id) || !empty($documento['documenti_contabilita_bollo_virtuale']) && $documento['documenti_contabilita_bollo_virtuale'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                    </label>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">


            <hr />


            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="js_product_table" class="table table-condensed table-striped table_prodotti">
                            <thead style="visibility:hidden;">
                                <tr>
                                    <th width="30">Codice</th>
                                    <th>Nome prodotto</th>
                                    <?php if (!empty($campi_personalizzati[1])): ?>
                                        <?php foreach ($campi_personalizzati[1] as $campo): ?>
                                            <th>

                                                <?php //debug($campo);
?>
                                                <?php echo $campo['fields_draw_label']; ?></th>
                                        <?php endforeach;?>
                                    <?php endif;?>
                                    <th width="20">U.M.</th>
                                    <th width="30">Quantit&agrave;</th>
                                    <th width="90">Prezzo</th>
                                    <th width="70">Sc. %</th>
                                    <?php if ($impostazioni['documenti_contabilita_settings_sconto2']): ?>
                                        <th width="50">Sc. 2</th>
                                    <?php endif;?>
                                    <?php if ($impostazioni['documenti_contabilita_settings_sconto3']): ?>
                                        <th width="50">Sc. 3</th>
                                    <?php endif;?>
                                    <?php if ($campo_centro_costo): ?>
                                        <th width="70">Costo/ricavo</th>
                                    <?php endif;?>


                                    <th width="75">IVA</th>
                                    <th width="90">Importo</th>
                                    <th width="35"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="hidden">


                                        <td colspan="<?php echo $colonne_count; ?>">

                                            <table class="table-condensed">
                                                <thead __style="visibility: hidden">
                                                    <tr>
                                                        <th width="30">Codice</th>
                                                        <th>Nome prodotto</th>
                                                        <?php if (!empty($campi_personalizzati[1])): ?>
                                                            <?php foreach ($campi_personalizzati[1] as $campo): ?>
                                                                <th>

                                                                    <?php //debug($campo);
?>
                                                                    <?php echo $campo['fields_draw_label']; ?>
                                                                </th>
                                                            <?php endforeach;?>
                                                        <?php endif;?>
                                                        <th width="20">U.M.</th>
                                                        <th width="30">Quantit&agrave;</th>
                                                        <th width="90">Prezzo</th>
                                                        <th width="70">Sc. %</th>
                                                        <?php if ($impostazioni['documenti_contabilita_settings_sconto2']): ?>
                                                            <th width="50">Sc. 2</th>
                                                        <?php endif;?>
                                                        <?php if ($impostazioni['documenti_contabilita_settings_sconto3']): ?>
                                                            <th width="50">Sc. 3</th>
                                                        <?php endif;?>
                                                        <?php if ($campo_centro_costo): ?>
                                                            <th width="70">Costo/ricavo</th>
                                                        <?php endif;?>


                                                        <th width="75">IVA</th>
                                                        <th width="90" class="js_column_importo">Importo</th>
                                                        <th width="35"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <input type="text" class="form-control input-sm js_documenti_contabilita_articoli_codice js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_codice" />
                                                            <input type="hidden" class="js_documenti_contabilita_articoli_codice_asin js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_codice_asin" />
                                                            <input type="hidden" class="js_documenti_contabilita_articoli_codice_ean js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_codice_ean" />
                                                            <input type="hidden" class="js_documenti_contabilita_articoli_rif_riga_articolo" data-id="1" data-name="documenti_contabilita_articoli_rif_riga_articolo" />


                                                            <input type="checkbox" class="_form-control js-riga_desc" data-name="documenti_contabilita_articoli_riga_desc" value="<?php echo DB_BOOL_TRUE; ?>" />
                                                            <small>Riga descrittiva</small>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control input-sm js_documenti_contabilita_articoli_name js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_name" />
                                                            <small>Descrizione aggiuntiva:</small>
                                                            <textarea class="form-control input-sm js_documenti_contabilita_articoli_descrizione" data-name="documenti_contabilita_articoli_descrizione" style="width:100%;" row="2"></textarea>
                                                        </td>
                                                        <?php if (!empty($campi_personalizzati[1])): ?>
                                                            <?php foreach ($campi_personalizzati[1] as $campo): ?>
                                                                <td>
                                                                    <?php echo $campo['html']; ?>
                                                                </td>
                                                            <?php endforeach;?>
                                                        <?php endif;?>
                                                        <td>
                                                            <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_unita_misura" data-name="documenti_contabilita_articoli_unita_misura" placeholder="(facoltativo)" />
                                                        </td>
                                                        <td><input type="text" class="form-control input-sm js_documenti_contabilita_articoli_quantita" data-name="documenti_contabilita_articoli_quantita" value="1" /></td>
                                                        <td>
                                                            <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_prezzo js_decimal" data-name="documenti_contabilita_articoli_prezzo" value="0.00" />
                                                            <small style="text-align:center;display:block;">Imponibile<br />
                                                                <span class="js_riga_imponibile">0.00</span>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto" data-name="documenti_contabilita_articoli_sconto" value="0" />
                                                        </td>
                                                        <?php if ($impostazioni['documenti_contabilita_settings_sconto2']): ?>
                                                            <td>
                                                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto2" data-name="documenti_contabilita_articoli_sconto2" value="0" />
                                                            </td>
                                                        <?php endif;?>
                                                        <?php if ($impostazioni['documenti_contabilita_settings_sconto3']): ?>
                                                            <td>
                                                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto3" data-name="documenti_contabilita_articoli_sconto3" value="0" />
                                                            </td>
                                                        <?php endif;?>
                                                        <?php if ($campo_centro_costo): ?>
                                                            <td>

                                                                <select class="form-control input-sm text-right js_documenti_contabilita_articoli_centro_costo" data-name="documenti_contabilita_articoli_centro_costo_ricavo">
                                                                    <?php foreach ($centri_di_costo as $centro): ?>
                                                                        <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>"><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                                                                    <?php endforeach;?>
                                                                </select>
                                                            </td>
                                                        <?php endif;?>


                                                        <td>
                                                            <?php //debug($impostazioni);
?>
                                                            <select class="form-control input-sm text-right js_documenti_contabilita_articoli_iva_id" data-name="documenti_contabilita_articoli_iva_id">
                                                                <?php foreach ($elenco_iva as $iva): ?>
                                                                    <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo (int) $iva['iva_valore']; ?>" <?php if ($iva['iva_id'] == $impostazioni['documenti_contabilita_settings_iva_default']): ?> selected="selected" <?php endif;?>><?php echo $iva['iva_label']; ?></option>
                                                                <?php endforeach;?>
                                                            </select>

                                                            <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_iva" data-name="documenti_contabilita_articoli_iva" value="0" />

                                                            <input type="hidden" class="js_documenti_contabilita_articoli_prodotto_id" data-name="documenti_contabilita_articoli_prodotto_id" />
                                                        </td>

                                                        <td class="js_column_importo">
                                                            <input type="text" class="form-control input-sm text-right js-importo js_decimal" data-name="documenti_contabilita_articoli_importo_totale" value="0" />

                                                            <input type="checkbox" class="_form-control js-applica_ritenute" data-name="documenti_contabilita_articoli_applica_ritenute" value="<?php echo DB_BOOL_TRUE; ?>" checked="checked" />
                                                            <small>Appl. ritenute</small>
                                                            <br /> <input type="checkbox" class="_form-control js-applica_sconto" data-name="documenti_contabilita_articoli_applica_sconto" value="<?php echo DB_BOOL_TRUE; ?>" checked="checked" />
                                                            <small>Appl. sconto</small>
                                                        </td>

                                                        <td class="text-center">
                                                            <button type="button" class="btn  btn-danger btn-xs js_remove_product">
                                                                <span class="fas fa-times"></span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php if (!empty($campi_personalizzati[2])): ?>
                                                        <tr>
                                                            <?php foreach ($campi_personalizzati[2] as $campo): ?>
                                                                    <td>
                                                                        <?php if ($campo): ?>
                                                                            <strong><?php echo $campo['fields_draw_label']; ?></strong><br />
                                                                            <?php echo $campo['html']; ?>
                                                                        <?php endif;?>
                                                                    </td>
                                                                <?php endforeach;?>
                                                        </tr>
                                                    <?php endif;?>
                                                    </tbody>
                                            </table>
                                        </td>
                                </tr>

                                <?php if (isset($documento['articoli']) && $documento['articoli']): ?>
                                    <?php foreach ($documento['articoli'] as $k => $prodotto): ?>


                                        <!-- DA RIVEDEER POTREBBERO MANCARE DEI CAMPI QUANDO SI FARA L EDIT -->
                                        <tr>

                                            <td colspan="<?php echo $colonne_count; ?>">

                                                <table class="table-condensed">
                                                    <thead __style="visibility: hidden">
                                                        <tr>
                                                            <th width="30">Codice</th>
                                                            <th>Nome prodotto</th>
                                                            <?php if (!empty($campi_personalizzati[1])): ?>
                                                                <?php foreach ($campi_personalizzati[1] as $campo): ?>
                                                                    <th>
                                                                        <?php if ($campo): ?>
                                                                        <?php echo $campo['fields_draw_label']; ?>
                                                                        <?php endif;?>
                                                                    </th>
                                                                <?php endforeach;?>
                                                            <?php endif;?>
                                                            <th width="20">U.M.</th>
                                                            <th width="30">Quantit&agrave;</th>
                                                            <th width="90">Prezzo</th>
                                                            <th width="70">Sc. %</th>
                                                            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']): ?>
                                                                <th width="50">Sc. 2</th>
                                                            <?php endif;?>
                                                            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']): ?>
                                                                <th width="50">Sc. 3</th>
                                                            <?php endif;?>
                                                            <?php if ($campo_centro_costo): ?>
                                                                <th width="70">Costo/ricavo</th>
                                                            <?php endif;?>


                                                            <th width="75">IVA</th>
                                                            <th width="90" class="js_column_importo">Importo</th>
                                                            <th width="35"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>

                                                            <td width="30"><input type="text" class="form-control input-sm js_autocomplete_prodotto" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_codice]" value="<?php echo $prodotto['documenti_contabilita_articoli_codice']; ?>" />
                                                                <input type="hidden" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_id]" value="<?php echo $prodotto['documenti_contabilita_articoli_id']; ?>" />
                                                                <input type="hidden" class="js_autocomplete_prodotto" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_codice_asin]" value="<?php echo $prodotto['documenti_contabilita_articoli_codice_asin']; ?>" />

                                                                <input type="hidden" class="js_autocomplete_prodotto" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_codice_ean]" value="<?php echo $prodotto['documenti_contabilita_articoli_codice_ean']; ?>" />

                                                                <input type="hidden" class="js_documenti_contabilita_articoli_rif_riga_articolo" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_rif_riga_articolo]" value="<?php echo $prodotto['documenti_contabilita_articoli_rif_riga_articolo']; ?>" />

                                                                <br />
                                                                <input type="checkbox" class="_form-control js-riga_desc" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_riga_desc]" value="<?php echo DB_BOOL_TRUE; ?>" <?php if ($prodotto['documenti_contabilita_articoli_riga_desc'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />

                                                                <small>Riga descrittiva</small>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control input-sm js_autocomplete_prodotto" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_name]" value="<?php echo str_replace('"', '&quot;', $prodotto['documenti_contabilita_articoli_name']); ?>" />
                                                                <small>Descrizione aggiuntiva:</small>
                                                                <textarea class="form-control input-sm js_documenti_contabilita_articoli_descrizione" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_descrizione]" style="width:100%;" row="2"><?php echo $prodotto['documenti_contabilita_articoli_descrizione']; ?></textarea>
                                                            </td>
                                                            <?php if (!empty($campi_personalizzati[1])): ?>
                                                                <?php foreach ($campi_personalizzati[1] as $campo): ?>
                                                                    <td>
                                                                        <?php //ricreare il campo html passando il value valorizzato di questo record. Attenzione a non usare il campo, ma direttamente il map_to...
$campo['fields_name'] = "products[" . ($k + 1) . "][{$campo['fields_name']}]";

$data = [
    'lang' => '',
    'field' => $campo,
    'value' => $prodotto[$campo['campi_righe_articoli_map_to']],
    'label' => '', // '<label class="control-label">' . $field['fields_draw_label'] . '</label>',
    'placeholder' => '',
    'help' => '',
    'class' => 'input-sm',
    'attr' => '',
    'onclick' => '',
    'subform' => '',
];

$campo['html'] = str_ireplace(
    'select2_standard',
    '',
    sprintf('<div class="form-group">%s</div>', $this->load->view("box/form_fields/{$campo['fields_draw_html_type']}", $data, true))
);
?>
                                                                        <?php echo $campo['html']; ?>
                                                                    </td>
                                                                <?php endforeach;?>
                                                            <?php endif;?>
                                                            <td width="20">
                                                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_unita_misura" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_unita_misura]" placeholder="(facoltativo)" value="<?php echo $prodotto['documenti_contabilita_articoli_unita_misura']; ?>" />
                                                            </td>
                                                            <td width="30"><input type="text" class="form-control input-sm js_documenti_contabilita_articoli_quantita" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_quantita]" value="<?php echo $prodotto['documenti_contabilita_articoli_quantita']; ?>" placeholder="1" /></td>
                                                            <td width="90">
                                                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_prezzo js_decimal" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_prezzo]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_prezzo'], 3, '.', ''); ?>" placeholder="0.00" />
                                                                <small style="text-align:center;display:block;">
                                                                    Imponibile<br /><span class="js_riga_imponibile"><?php echo number_format($prodotto['documenti_contabilita_articoli_prezzo'] * $prodotto['documenti_contabilita_articoli_quantita'], 2, '.', ''); ?></span>
                                                                </small>
                                                            </td>
                                                            <td width="70"><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_sconto]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_sconto'], 2, '.', ''); ?>" placeholder="0" /></td>

                                                            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']): ?>
                                                                <td width="50">
                                                                    <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto2" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_sconto2]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_sconto2'], 2, '.', ''); ?>" placeholder="0" />
                                                                </td>
                                                            <?php endif;?>
                                                            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']): ?>
                                                                <td width="50">
                                                                    <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto3" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_sconto3]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_sconto3'], 2, '.', ''); ?>" placeholder="0" />
                                                                </td>
                                                            <?php endif;?>


                                                            <?php if ($campo_centro_costo): ?>
                                                                <td width="50">
                                                                    <select class="form-control input-sm text-right js_documenti_contabilita_articoli_centro_costo_ricavo" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_centro_costo_ricavo]">
                                                                        <?php foreach ($centri_di_costo as $centro): ?>
                                                                            <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>" <?php if ($centro['centri_di_costo_ricavo_id'] == $prodotto['documenti_contabilita_articoli_centro_costo_ricavo']): ?> selected="selected" <?php endif;?>><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                                                                        <?php endforeach;?>
                                                                    </select>
                                                                </td>
                                                            <?php endif;?>
                                                            <td width="75">
                                                                <select class="form-control input-sm text-right js_documenti_contabilita_articoli_iva_id" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_iva_id]">
                                                                    <?php foreach ($elenco_iva as $iva): ?>
                                                                        <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo (int) $iva['iva_valore']; ?>" <?php if ($iva['iva_id'] == $prodotto['documenti_contabilita_articoli_iva_id']): ?> selected="selected" <?php endif;?>><?php echo $iva['iva_label']; ?></option>
                                                                    <?php endforeach;?>
                                                                </select> <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_iva" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_iva]" value="0" /> <input type="hidden" class="js_documenti_contabilita_articoli_prodotto_id" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_prodotto_id]" value="<?php echo $prodotto['documenti_contabilita_articoli_prodotto_id']; ?>" />
                                                            </td>
                                                            <td width="90" class="js_column_importo">
                                                                <input type="text" class="form-control input-sm text-right js-importo js_decimal" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_importo_totale]" placeholder="0" /> <input type="checkbox" class="_form-control js-applica_ritenute" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_applica_ritenute]" value="<?php echo DB_BOOL_TRUE; ?>" <?php if ($prodotto['documenti_contabilita_articoli_applica_ritenute'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                                                <small>Appl. ritenute</small>
                                                                <br /> <input type="checkbox" class="_form-control js-applica_sconto" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_applica_sconto]" value="<?php echo DB_BOOL_TRUE; ?>" <?php if ($prodotto['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?> />
                                                                <small>Appl. sconto</small>
                                                            </td>
                                                            <td width="35" class="text-center">
                                                                <button type="button" class="btn btn-danger btn-xs js_remove_product">
                                                                    <span class="fas fa-times"></span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php if (!empty($campi_personalizzati[2])): ?>
                                                            <tr>
                                                                <?php foreach ($campi_personalizzati[2] as $campo): ?>
                                                                        <td>
                                                                            <?php if ($campo): ?>
                                                                                <?php

//ricreare il campo html passando il value valorizzato di questo record. Attenzione a non usare il campo, ma direttamente il map_to...
$campo['fields_name'] = "products[" . ($k + 1) . "][{$campo['fields_name']}]";
$campo['forms_fields_dependent_on'] = '';

$data = [
    'lang' => '',
    'field' => $campo,
    'value' => $prodotto[$campo['campi_righe_articoli_map_to']],
    'label' => '', // '<label class="control-label">' . $field['fields_draw_label'] . '</label>',
    'placeholder' => '',
    'help' => '',
    'class' => 'input-sm',
    'attr' => '',
    'onclick' => '',
    'subform' => '',
];

$campo['html'] = str_ireplace(
    'select2_standard',
    '',
    sprintf('<div class="form-group">%s</div>', $this->load->view("box/form_fields/{$campo['fields_draw_html_type']}", $data, true))
);
?>
                                                                                <strong><?php echo $campo['fields_draw_label']; ?></strong><br />
                                                                                <?php echo $campo['html']; ?>
                                                                                <?php endif;?>
                                                                        </td>
                                                                    <?php endforeach;?>
                                                            </tr>
                                                        <?php endif;?>
                                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    <?php endforeach;?>

                                <?php endif;?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        <button id="js_add_product" type="button" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> Aggiungi prodotto
                                        </button>
                                    </td>
                                    <td colspan="<?php echo (3 + count($campi_personalizzati)); ?>"></td>
                                    <td class="totali" colspan="4" style="background: #faf6ea;background: #f3cf66;">

                                        <label>Competenze: <span class="js_competenze">€ 0</span></label>

                                        <label class="competenze_scontate">Competenze Scontate: <span class="js_competenze_scontate">€ 0</span></label>

                                        <label>Sconto percentuale: <input type="text" name="documenti_contabilita_sconto_percentuale" class="js_sconto_totale sconto_totale pull-right" value="<?php if (!empty($documento['documenti_contabilita_sconto_percentuale'])): ?><?php echo number_format((float) $documento['documenti_contabilita_sconto_percentuale'], 2, '.', ''); ?><?php else: ?>0<?php endif;?>" /></label>

                                        <label class="js_rivalsa"></label> <label class="js_competenze_rivalsa"></label>

                                        <label class="js_cassa_professionisti"></label> <label class="js_imponibile"></label>

                                        <label class="js_ritenuta_acconto"></label>

                                        <label class="js_tot_iva">IVA: <span class="___js_tot_iva">€ 0</span></label>

                                        <label class="js_split_payment"></label>

                                        <label>Totale da saldare: <span class="js_tot_da_saldare">€ 0</span></label>

                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <hr />
            <div class="row margin-bottom-5 col-md-12">
                <div class="form-group">
                    <label> <input type="checkbox" class="minimal js_fattura_accompagnatoria_checkbox" name="documenti_contabilita_fattura_accompagnatoria" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_fattura_accompagnatoria']) && $documento['documenti_contabilita_fattura_accompagnatoria'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?>>
                        Dati trasporto </label>
                    <label> <input type="checkbox" class="minimal js_attr_avanzati_fe_checkbox" name="documenti_contabilita_fe_attributi_avanzati" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_fe_attributi_avanzati']) && $documento['documenti_contabilita_fe_attributi_avanzati'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif;?>>
                        Attributi Avanzati Fattura Elettronica </label>
                </div>
            </div>
            <div class="row js_fattura_accompagnatoria_row hide">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>N. Colli: </label> <input type="text" class="form-control" placeholder="1" name="documenti_contabilita_n_colli" value="<?php echo (!empty($documento['documenti_contabilita_n_colli'])) ? number_format((float) $documento['documenti_contabilita_n_colli'], 0, ',', '') : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Peso: </label> <input type="text" class="form-control" placeholder="0 kg" name="documenti_contabilita_peso" value="<?php echo (!empty($documento['documenti_contabilita_peso'])) ? number_format((float) $documento['documenti_contabilita_peso'], 2, '.', '') : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Volume: </label> <input type="text" class="form-control" placeholder="0 m3" name="documenti_contabilita_volume" value="<?php echo (!empty($documento['documenti_contabilita_volume'])) ? number_format((float) $documento['documenti_contabilita_volume'], 2, '.', '') : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Trasporto a cura di: </label> <input type="text" class="form-control" placeholder="Azienda di trasporti" name="documenti_contabilita_trasporto_a_cura_di" value="<?php echo (!empty($documento['documenti_contabilita_trasporto_a_cura_di'])) ? $documento['documenti_contabilita_trasporto_a_cura_di'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Targhe: </label> <input type="text" class="form-control" placeholder="Targhe" name="documenti_contabilita_targhe" value="<?php echo (!empty($documento['documenti_contabilita_targhe'])) ? $documento['documenti_contabilita_targhe'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Data Ritiro Merce: </label>
                        <div class="input-group js_form_datepicker date ">
                            <input type="text" name="documenti_contabilita_data_ritiro_merce" class="form-control" placeholder="Data Ritiro Merce" value="<?php if (!empty($documento['documenti_contabilita_data_ritiro_merce']) && !$clone): ?><?php echo $documento['documenti_contabilita_data_ritiro_merce']; ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif;?>" data-name="documenti_contabilita_data_ritiro_merce" /> <span class="input-group-btn">
                                <button class="btn btn-default" type="button" style="display:none">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Porto: </label> <input type="text" class="form-control" placeholder="Porto" name="documenti_contabilita_porto" value="<?php echo (!empty($documento['documenti_contabilita_porto'])) ? $documento['documenti_contabilita_porto'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Descrizione Colli: </label> <input type="text" class="form-control" placeholder="Desc. Colli" name="documenti_contabilita_descrizione_colli" value="<?php echo (!empty($documento['documenti_contabilita_descrizione_colli'])) ? $documento['documenti_contabilita_descrizione_colli'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tracking code: </label>
                        <input type="text" class="form-control" placeholder="es.: ABC00012345" name="documenti_contabilita_tracking_code" value="<?php echo (!empty($documento['documenti_contabilita_tracking_code'])) ? $documento['documenti_contabilita_tracking_code'] : ''; ?>" />
                    </div>
                </div>
            </div>
            <div class="row js_fattura_accompagnatoria_row hide">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Luogo di destinazione: <a style="display:none;" class="js_choose_address js_open_modal">(mostra indirizzi cliente)</a></label>
                        <textarea class="form-control" placeholder="Luogo di Destinazione" rows="3" name="documenti_contabilita_luogo_destinazione"><?php echo (!empty($documento['documenti_contabilita_luogo_destinazione'])) ? $documento['documenti_contabilita_luogo_destinazione'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dati vettore: </label> <textarea class="form-control" placeholder="Annotazioni" rows="3" name="documenti_contabilita_vettori_residenza_domicilio"><?php echo (!empty($documento['documenti_contabilita_vettori_residenza_domicilio'])) ? $documento['documenti_contabilita_vettori_residenza_domicilio'] : ''; ?></textarea>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Causale di trasporto: </label> <textarea class="form-control" placeholder="Causale trasporto" rows="3" name="documenti_contabilita_causale_trasporto"><?php echo (!empty($documento['documenti_contabilita_causale_trasporto'])) ? $documento['documenti_contabilita_causale_trasporto'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Annotazioni: </label> <textarea class="form-control" placeholder="Annotazioni" rows="3" name="documenti_contabilita_annotazioni_trasporto"><?php echo (!empty($documento['documenti_contabilita_annotazioni_trasporto'])) ? $documento['documenti_contabilita_annotazioni_trasporto'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="row js_attributi_avanzati_fattura_elettronica hide">
                <!-- @todo 20190703 - Michael E. - in futuro questi campi saranno resi multicreazione, come per i prodotti -->
                <?php
$documento_fe = (!empty($documento['documenti_contabilita_fe_attributi_avanzati_json'])) ? json_decode($documento['documenti_contabilita_fe_attributi_avanzati_json'], true) : '';
?>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Riferimento N° Linea</label>
                        <input type="text" class="form-control" placeholder="1" name="documenti_contabilita_fe_rif_n_linea" value="<?php echo (!empty($documento_fe['RiferimentoNumeroLinea'])) ? number_format((int) $documento_fe['RiferimentoNumeroLinea'], 0, ',', '') : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Rif. Documento</label>
                        <input type="text" class="form-control" placeholder="" name="documenti_contabilita_fe_id_documento" value="<?php echo (!empty($documento_fe['IdDocumento'])) ? $documento_fe['IdDocumento'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Riferimento Documento DDT <small>(facoltativo)</small>:</label>
                        <select name="documenti_contabilita_rif_ddt" class="select2 form-control js_tipologia_fatturazione">
                            <option value=""></option>
                            <?php foreach ($ddts as $ddt): ?>
                                <option data-documento_id="<?php echo $ddt['documenti_contabilita_id']; ?>" value="<?php echo $ddt['documenti_contabilita_id']; ?>" <?php if ((!empty($documento['documenti_contabilita_rif_ddt']) && $documento['documenti_contabilita_rif_ddt'] == $ddt['documenti_contabilita_id'])): ?> selected="selected" <?php endif;?>>
                                    <?php echo "DDT N° ", $ddt['documenti_contabilita_numero'], " del ", date('d/m/Y', strtotime($ddt['documenti_contabilita_data_emissione'])), " - ", json_decode($ddt['documenti_contabilita_destinatario'], true)['ragione_sociale']; ?>
                                </option>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>

            </div>
            <div class="row js_attributi_avanzati_fattura_elettronica hide">
                <!--
                <DatiContratto>
                    <RiferimentoNumeroLinea>
                    <IdDocumento></IdDocumento>
                    <Data></Data>
                    <NumItem></NumItem>
                    <CodiceCommessaConvenzione></CodiceCommessaConvenzione>
                    <CodiceCUP></CodiceCUP>
                    <CodiceCIG></CodiceCIG>
                </DatiContratto>
                -->

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dati Contratto - Riferimento Numero Linea</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[riferimento_numero_linea]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['riferimento_numero_linea'])) ? $documento['documenti_contabilita_fe_dati_contratto']['riferimento_numero_linea'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Dati Contratto - Id Documento</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[id_documento]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['id_documento'])) ? $documento['documenti_contabilita_fe_dati_contratto']['id_documento'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Dati Contratto - Data</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[data]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['data'])) ? $documento['documenti_contabilita_fe_dati_contratto']['data'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Dati Contratto - Num Item</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[num_item]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['num_item'])) ? $documento['documenti_contabilita_fe_dati_contratto']['num_item'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dati Contratto - Codice Commessa Convenzione</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[codice_commessa_convenzione]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['codice_commessa_convenzione'])) ? $documento['documenti_contabilita_fe_dati_contratto']['codice_commessa_convenzione'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Dati Contratto - Codice CUP</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[codice_cup]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['codice_cup'])) ? $documento['documenti_contabilita_fe_dati_contratto']['codice_cup'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Dati Contratto - Codice CIG</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[codice_cig]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['codice_cig'])) ? $documento['documenti_contabilita_fe_dati_contratto']['codice_cig'] : ''; ?>" />
                    </div>
                </div>
            </div>


            <hr />


            <div class="row">
                <div class="col-md-5 mb-15">
                    <textarea name="documenti_contabilita_note" rows="10" class="form-control" placeholder="Note pagamento [opzionali]"><?php if ($documento_id): ?><?php echo $documento['documenti_contabilita_note_interne']; ?><?php endif;?></textarea>
                </div>
                <div class="col-md-7 scadenze_box" style="background-color: #b7d7ea;">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Scadenza pagamento</h4>
                        </div>
                    </div>

                    <div class="row js_rows_scadenze">
                        <?php if ($documento_id && !$clone): ?>
                            <?php foreach ($documento['scadenze'] as $key => $scadenza): ?>
                                <div class="row row_scadenza">
                                    <input type="hidden" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_template_json]" value="" class="documenti_contabilita_scadenze_template_json" data-name="documenti_contabilita_scadenze_template_json" />
                                    <div class=" col-md-3">
                                        <div class="form-group">
                                            <input class="js_documenti_contabilita_scadenze_id" type="hidden" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_id]" data-name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_id]" value="<?php echo $scadenza['documenti_contabilita_scadenze_id']; ?>" />
                                            <label>Ammontare</label> <input type="text" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_ammontare]" class="form-control documenti_contabilita_scadenze_ammontare js_decimal" placeholder="Ammontare" value="<?php echo number_format((float) $scadenza['documenti_contabilita_scadenze_ammontare'], 2, '.', ''); ?>" data-name="documenti_contabilita_scadenze_ammontare" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Scadenza</label>
                                            <div class="input-group js_form_datepicker date ">
                                                <input type="text" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_scadenza]" class="form-control documenti_contabilita_scadenze_scadenza" placeholder="Scadenza" value="<?php echo date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_scadenza'])); ?>" data-name="documenti_contabilita_scadenze_scadenza" /> <span class="input-group-btn">
                                                    <button class="btn btn-default" type="button" style="display:none"><i class="fa fa-calendar"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Metodo di pagamento</label>
                                            <select name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_saldato_con]" class="_select2 form-control _js_table_select2 _js_table_select2<?php echo $key; ?> documenti_contabilita_scadenze_saldato_con" data-name="documenti_contabilita_scadenze_saldato_con">

                                                <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                                    <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']; ?>" <?php if (stripos($scadenza['documenti_contabilita_scadenze_saldato_con'], $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']) !== false): ?> selected="selected" <?php endif;?>>
                                                        <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                                    </option>
                                                <?php endforeach;?>
                                                <!--
                                                <option value="Contanti" <?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Contanti'): ?> selectefd="selected"<?php endif;?>>
                                                    Contanti
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Bonifico bancario'): ?> selectefd="selected"<?php endif;?>>
                                                    Bonifico bancario
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Assegno'): ?> selectefd="selected"<?php endif;?>>
                                                    Assegno
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'RiBA'): ?> selectefd="selected"<?php endif;?>>
                                                    RiBA
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Sepa RID'): ?> selectefd="selected"<?php endif;?>>
                                                    Sepa RID
                                                </option>-->
                                            </select>

                                            <script>
                                                $('.js_table_select2<?php echo $key; ?>').val('<?php echo strtolower($scadenza['documenti_contabilita_scadenze_saldato_con']); ?>').trigger('change.select2');
                                            </script>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Data saldo</label>
                                            <div class="input-group js_form_datepicker date  field_68">
                                                <input type="text" class="form-control documenti_contabilita_scadenze_data_saldo" id="empty_date" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_data_saldo]" data-name="documenti_contabilita_scadenze_data_saldo" value="<?php echo ($scadenza['documenti_contabilita_scadenze_data_saldo']) ? date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_data_saldo'])) : ''; ?>">

                                                <span class="input-group-btn">
                                                    <button class="btn btn-default" type="button" style="display:none;"><i class="fa fa-calendar"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;?>
                        <?php else:$key = -1;?>
														                        <?php endif;?>
                        <div class="row row_scadenza">
                            <input type="hidden" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_template_json]" value="" class="documenti_contabilita_scadenze_template_json" data-name="documenti_contabilita_scadenze_template_json" />

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ammontare</label> <input type="text" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_ammontare]" class="form-control documenti_contabilita_scadenze_ammontare js_decimal" placeholder="Ammontare" value="" data-name="documenti_contabilita_scadenze_ammontare" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_scadenza]" class="form-control documenti_contabilita_scadenze_scadenza" placeholder="Scadenza" value="<?php echo date('d/m/Y'); ?>" data-name="documenti_contabilita_scadenze_scadenza" /> <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Metodo di pagamento</label>


                                    <select name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_saldato_con]" class="select2 form-control js_table_select2 documenti_contabilita_scadenze_saldato_con" data-name="documenti_contabilita_scadenze_saldato_con">

                                        <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                            <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']; ?>" <?php if ($metodo_pagamento['documenti_contabilita_metodi_pagamento_codice'] == 'MP05'): //bonifico
    ?> selected="selected" <?php endif;?>>
                                                <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                            </option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Data saldo</label>
                                    <div class="input-group js_form_datepicker date  field_68">
                                        <input type="text" class="form-control" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_data_saldo]" id="empty_date" data-name="documenti_contabilita_scadenze_data_saldo" value="">

                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <?php /*
<div class="row">
<div class="col-md-12 text-center">
<button style="display:none;" id="js_add_scadenza" class="btn btn-primary btn-sm">+ Aggiungi scadenza</button>
</div>
</div> */?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div id="msg_new_fattura" class="alert alert-danger hide"></div>
            </div>
        </div>
    </div>

    <div class="form-actions fluid">
        <div class="col-md-6">
            <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-success js_elenco_documenti"><i class="fa fa-arrow-left"></i> Elenco Documenti</a>
        </div>
        <div class="col-md-6">
            <div class="pull-right">
                <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-danger default">Annulla</a>
                <button type="submit" class="btn btn-success">Salva</button>
            </div>
        </div>
    </div>

    <!--<div class="form actions">
        <div class="row">
            <div class="col-sm-6 col-xs-12 mb-15">
                <div class="pull-left">
                    <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-success"><i class="fa fa-arrow-left"></i> Elenco Documenti</a>
                </div>
            </div>
            <div class="col-sm-6 col-xs-12">
                <div class="pull-right">
                    <a href="<?php echo base_url(); ?>" class="btn btn-danger default">Annulla</a>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </div>
        </div>
    </div>-->

    </div>

</form>


<!-- Modale for Causale Pag. rit.-->
<div class="modal fade" id="modal-default" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">Causale del pagamento</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Descrizione</th>
                            <th>Formato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">A</th>
                            <td>Prestazioni di lavoro autonomo rientranti nell’esercizio di arte o professione abituale.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">B</th>
                            <td>Utilizzazione economica, da parte dell’autore o dell’inventore, di opere dell’ingegno, di brevetti industriali e di processi, relativi a esperienze acquisite in campo industriale, commerciale o scientifico.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">C</th>
                            <td>Utili derivanti da contratti di associazione in partecipazione e da contratti di cointeressenza, quando l’apporto è costituito esclusivamente dalla prestazione di lavoro.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">D</th>
                            <td>Utili spettanti ai soci promotori e ai soci fondatori delle società di capitali.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">E</th>
                            <td>Levata di protesti cambiari da parte dei segretari comunali.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">G</th>
                            <td>Indennità corrisposte per la cessazione di attività sportiva professionale.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">H</th>
                            <td>Indennità corrisposte per la cessazione dei rapporti di agenzia delle persone fisiche e delle società di persone, con esclusione delle somme maturate entro il 31.12.2003, già imputate per competenza.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">I</th>
                            <td>Indennità corrisposte per la cessazione da funzioni notarili.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">J</th>
                            <td>Compensi corrisposti ai raccoglitori occasionali di tartufi non identificati ai fini dell’imposta sul valore.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">K</th>
                            <td>Assegni di servizio civile di cui all’art. 16 del D.lgs. n. 40 del 6 marzo 2017.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">L</th>
                            <td>Utilizzazione economica, da parte di soggetto diverso dall’autore o dall’inventore, di opere dell’ingegno, di brevetti industriali e di processi, formule e informazioni relative a esperienze acquisite.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">L1</th>
                            <td>Redditi derivanti dall’utilizzazione economica di opere dell’ingegno, di brevetti industriali e di processi, che sono percepiti da soggetti che abbiano acquistato a titolo oneroso i diritti alla loro utilizzazione.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">M</th>
                            <td>Prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">M1</th>
                            <td>Redditi derivanti dall’assunzione di obblighi di fare, di non fare o permettere.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">M2</th>
                            <td>Prestazioni di lavoro autonomo non esercitate abitualmente per le quali sussiste l’obbligo di iscrizione alla Gestione Separata ENPAPI.</td>
                            <td>V.202X</td>
                        </tr>
                        <tr>
                            <th scope="row">N</th>
                            <td>Indennità di trasferta, rimborso forfettario di spese, premi e compensi erogati: .. nell’esercizio diretto di attività sportive dilettantistiche.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">O</th>
                            <td>Prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere, per le quali non sussiste l’obbligo di iscrizione alla gestione separata (Circ. Inps 104/2001).</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">O1</th>
                            <td>Redditi derivanti dall’assunzione di obblighi di fare, di non fare o permettere, per le quali non sussiste l’obbligo di iscrizione alla gestione separata (Circ. INPS n. 104/2001).</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">P</th>
                            <td>Compensi corrisposti a soggetti non residenti privi di stabile organizzazione per l’uso o la concessione in uso di attrezzature industriali, commerciali o scientifiche che si trovano nel territorio dello Stato.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">Q</th>
                            <td>Provvigioni corrisposte ad agente o rappresentante di commercio monomandatario.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">R</th>
                            <td>Provvigioni corrisposte ad agente o rappresentante di commercio plurimandatario</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">S</th>
                            <td>Provvigioni corrisposte a commissionario.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">T</th>
                            <td>Provvigioni corrisposte a mediatore.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">U</th>
                            <td>Provvigioni corrisposte a procacciatore di affari.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">V</th>
                            <td>Provvigioni corrisposte a incaricato per le vendite a domicilio e provvigioni corrisposte a incaricato per la vendita porta a porta e per la vendita ambulante di giornali quotidiani e periodici (L. 25.02.1987, n. 67).</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">V1</th>
                            <td>Redditi derivanti da attività commerciali non esercitate abitualmente (ad esempio, provvigioni corrisposte per prestazioni occasionali ad agente o rappresentante di commercio, mediatore, procacciatore d’affari);.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">V2</th>
                            <td>Redditi derivanti dalle prestazioni non esercitate abitualmente rese dagli incaricati alla vendita diretta a domicilio.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">W</th>
                            <td>Corrispettivi erogati nel 2015 per prestazioni relative a contratti d’appalto cui si sono resi applicabili le disposizioni contenute nell’art. 25-ter D.P.R. 600/1973.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">X</th>
                            <td>Canoni corrisposti nel 2004 da società o enti residenti, ovvero da stabili organizzazioni di società estere di cui all’art. 26-quater, c. 1, lett. a) e b) D.P.R. 600/1973.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">Y</th>
                            <td>Canoni corrisposti dal 1.01.2005 al 26.07.2005 da soggetti di cui al punto precedente.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">Z</th>
                            <td>Titolo diverso dai precedenti. (Non idoneo con l'operatività corrente)</td>
                            <td>V.&#8804;2020</td>
                        </tr>
                        <tr>
                            <th scope="row">ZO</th>
                            <td>Titolo diverso dai precedenti.</td>
                            <td>V.20XX</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>



<script>
    var template_scadenze = <?php echo json_encode($template_scadenze); ?>;
    var listini = <?php echo json_encode($listini); ?>;
</script>

<script>
    var token = JSON.parse(atob('<?php echo base64_encode(json_encode(get_csrf())); ?>'));
    var token_name = token.name;
    var token_hash = token.hash;
    $(document).ready(function() {
        $("textarea[name='documenti_contabilita_vettori_residenza_domicilio'], input[name='documenti_contabilita_trasporto_a_cura_di']").autocomplete({
            source: function(request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "contabilita/documenti/autocomplete/vettori",
                    dataType: "json",
                    data: {
                        search: request.term,
                        [token_name]: token_hash
                    },
                    minLength: 2,
                    success: function(data) {
                        var collection = [];
                        loading(false);
                        $.each(data.results.data, function(i, p) {
                            collection.push({
                                "id": p.vettori_id,
                                "label": p.vettori_ragione_sociale + " - " + p.vettori_indirizzo,
                                "value": p
                            });
                        });
                        response(collection);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (event.keyCode === 9) return false;
                popolaVettore(ui.item.value);
                return false;
            }
        });

        function popolaVettore(vettore) {
            $('input[name="documenti_contabilita_trasporto_a_cura_di"]').val(vettore['vettori_ragione_sociale']);
            $('textarea[name=documenti_contabilita_vettori_residenza_domicilio]').val(vettore['vettori_indirizzo'] + "\n" + vettore['vettori_citta'] + " " + vettore['vettori_cap']);
        }
        $('.js_select2').each(function() {
            var select = $(this);
            var placeholder = select.attr('data-placeholder');
            select.select2({
                placeholder: placeholder ? placeholder : '',
                allowClear: true
            });
        });
    });

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

    $('[name="documenti_contabilita_applica_bollo"]').on('change', function() {
        calculateTotals();
    })

    $(".js_attr_avanzati_fe_checkbox").change(function() {

        if ($(this).is(':checked')) {
            $(".js_attributi_avanzati_fattura_elettronica").removeClass('hide');
        } else {
            $(".js_attributi_avanzati_fattura_elettronica").addClass('hide');
            var inputs = $(".js_attributi_avanzati_fattura_elettronica :input");
            $.each(inputs, function() {
                $(this).val('');
            });
        }
    });

    $(".js_attr_avanzati_fe_checkbox").trigger('change');
</script>

<script>
    var ricalcolaPrezzo = function(prezzo, prodotto) {
        //console.log('dentro funzione originale');
        return prezzo;
    }

    /****************** AUTOCOMPLETE Destinatario *************************/
    var initAutocomplete = function(autocomplete_selector) {

        autocomplete_selector.autocomplete({
            source: function(request, response) {

                $.ajax({
                    method: 'post',
                    url: base_url + "contabilita/documenti/autocomplete/<?php echo $entita; ?>",
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

                        //                        console.log(autocomplete_selector.data("id"));
                        //                        if (data.count_total == 1) {
                        //                            popolaProdotto(data.results.data[0], autocomplete_selector.data("id"));
                        //                        } else {

                        $.each(data.results.data, function(i, p) {
                            <?php if ($campo_codice && !empty($campo_fornitore_prodotto)): ?>

                                var label = <?php if ($campo_preview): ?>p.<?php echo $campo_codice; ?> + ' - ' + p.<?php echo $campo_preview; ?><?php else: ?> '*impostare campo preview*'
                            <?php endif;?> + ' - ' + p.<?php echo $campo_fornitore_prodotto; ?> + ' - ' + p.<?php echo $campo_prezzo; ?>;

                        <?php elseif ($campo_codice): ?>

                            var label = <?php if ($campo_preview): ?>p.<?php echo $campo_codice; ?> + ' - ' + p.<?php echo $campo_preview; ?><?php else: ?> '*impostare campo preview*';
                        <?php endif;?>
                        <?php else: ?>

                            var label = <?php if ($campo_preview): ?>p.<?php echo $campo_preview; ?><?php else: ?> '*impostare campo preview*';
                        <?php endif;?>
                        <?php endif;?>


                        <?php if ($campo_quantita): ?>
                            label += ' (qty: ' + p.<?php echo $campo_quantita; ?> + ')';
                        <?php endif;?>
                        collection.push({
                            "id": p.<?php echo $campo_id; ?>,
                            "label": label,
                            "value": p
                        });

                        });
                        //                        }

                        //console.log(collection);
                        response(collection);
                    }
                });
            },
            minLength: 2,
            response: function(event, ui) {
                if (ui.content.length == 1) {
                    // $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', {
                    //     item: {
                    //         value: ui.content[0].value
                    //     }
                    // });
                    //popolaProdotto(ui.content[0].value, autocomplete_selector.data("id"));
                }
            },
            select: function(event, ui) {
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                popolaProdotto(ui.item.value, autocomplete_selector.data("id"));

                return false;
            }
        });
    }

    var popolaProdotto = function(prodotto, rowid) {
        console.log(prodotto['<?php echo $campo_preview; ?>']);
        var tipo_documento = $('.js_documenti_contabilita_tipo').val();

        var data = {
            "PA": <?php echo (int) ($this->auth->get('provvigione')); ?>,
        };
        <?php if ($campo_codice): ?>
            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_codice]']").val(prodotto['<?php echo $campo_codice; ?>']);

        <?php endif;?>
        $("input[name='products[" + rowid + "][documenti_contabilita_articoli_codice_ean]']").val(prodotto['listino_prezzi_codice_ean_prodotto']);
        $("input[name='products[" + rowid + "][documenti_contabilita_articoli_codice_asin]']").val(prodotto['listino_prezzi_codice_asin_prodotto']);
        <?php if ($campo_unita_misura): ?>
            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_unita_misura]']").val(prodotto['<?php echo $campo_unita_misura; ?>']);
        <?php endif;?>

        <?php if ($campo_preview): ?>
            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_name]']").val(prodotto['<?php echo $campo_preview; ?>']);

        <?php endif;?>
        <?php if ($campo_descrizione): ?>
            $("textarea[name='products[" + rowid + "][documenti_contabilita_articoli_descrizione]']").html(prodotto['<?php echo $campo_descrizione; ?>']);
        <?php endif;?>

        if (
            typeof cliente_raw_data !== 'undefined' &&
            typeof cliente_raw_data.customers_iva_default !== 'undefined' &&
            !isNaN(parseInt(cliente_raw_data.customers_iva_default)) &&
            parseInt(cliente_raw_data.customers_iva_default) > 0
        ) {

            var iva_id = parseInt(cliente_raw_data.customers_iva_default);
            $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option[value='" + iva_id + "']").prop('selected', true).attr('selected', 'selected').val(iva_id).trigger('change');
        } else {
            <?php if ($campo_iva): ?>
                $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option").removeAttr('selected').prop('selected', false);

                console.log(prodotto['<?php echo $campo_iva; ?>']);



                if (isNaN(parseInt(prodotto['<?php echo $campo_iva; ?>']))) {
                    //$("select[name='products["+rowid+"][documenti_contabilita_articoli_iva_id]']").val('0');
                } else {

                    $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option[value='" + parseInt(prodotto['<?php echo $campo_iva; ?>']) + "']").prop('selected', true).attr('selected', 'selected').val(parseInt(prodotto['<?php echo $campo_iva; ?>'])).trigger('change');

                }


            <?php endif;?>
        }

        data.IV = $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option[selected]").data('perc');

        <?php if ($campo_provvigione): ?>
            data.PP = prodotto['<?php echo $campo_provvigione; ?>'];
        <?php else: ?>
            data.PP = 0;
        <?php endif;?>

        <?php if ($campo_ricarico): ?>
            data.RP = prodotto['<?php echo $campo_ricarico; ?>'];
        <?php else: ?>
            data.RP = 0;
        <?php endif;?>

        <?php if ($campo_sconto): ?>
            //Commento in quanto lo sconto prodotto viene sempre applicato
            //data.SP = prodotto['<?php echo $campo_sconto; ?>'];
            data.SP = 0;
        <?php else: ?>
            data.SP = 0;
        <?php endif;?>

        <?php if ($campo_prezzo): ?>
            prodotto['<?php echo $campo_prezzo; ?>'] = prodotto['<?php echo $campo_prezzo; ?>'].replace(',', '.');

            <?php if ($campo_prezzo_fornitore): ?>
                data.PF = prodotto['<?php echo $campo_prezzo_fornitore; ?>'];
            <?php endif;?>

            if (tipo_documento == 6 && '<?php echo $campo_prezzo_fornitore; ?>' != '') { //Se è un ordine fornitore e ho impostato un campo prezzo_fornitore nei settings
                prodotto['<?php echo $campo_prezzo_fornitore; ?>'] = ricalcolaPrezzo(prodotto['<?php echo $campo_prezzo_fornitore; ?>'], prodotto).toString().replace(',', '.');
                console.log(prodotto['<?php echo $campo_prezzo_fornitore; ?>']);
            } else {
                prodotto['<?php echo $campo_prezzo; ?>'] = ricalcolaPrezzo(prodotto['<?php echo $campo_prezzo; ?>'], prodotto).toString().replace(',', '.');

            }
            data.PB = prodotto['<?php echo $campo_prezzo; ?>'];
            //PF,IV,SC,PA,PP,PB,PV,RP,SP
            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_prezzo]']").val(parseFloat(applicaListino(data, tipo_documento)).toFixed(2)).trigger('change');

        <?php endif;?>
        <?php if ($campo_sconto): ?>
            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_sconto]']").val(prodotto['<?php echo $campo_sconto; ?>']).trigger('change');
        <?php endif;?>
        <?php if ($campo_sconto2): ?>

            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_sconto2]']").val(prodotto['<?php echo $campo_sconto2; ?>']).trigger('change');
        <?php endif;?>
        <?php if ($campo_sconto3): ?>
            $("input[name='products[" + rowid + "][documenti_contabilita_articoli_sconto3]']").val(prodotto['<?php echo $campo_sconto3; ?>']).trigger('change');
        <?php endif;?>

        <?php if ($campo_centro_costo): ?>
            $("select[name='products[" + rowid + "][documenti_contabilita_articoli_centro_costo_ricavo]'] option[value='" + parseInt(prodotto['<?php echo $campo_centro_costo; ?>']) + "']").prop('selected', true).attr('selected', 'selected').val(parseInt(prodotto['<?php echo $campo_centro_costo; ?>'])).trigger('change');
        <?php endif;?>

        <?php if (!empty($campi_personalizzati)): ?>
            <?php foreach ($campi_personalizzati as $riga => $campi_personalizzati_riga): ?>
                <?php foreach ($campi_personalizzati_riga as $campo): ?>
                    <?php if ($campo): ?>

                    <?php if (in_array($campo['fields_draw_html_type'], ['select', 'select_ajax'])): ?>
                        var sel = "select[name='products[" + rowid + "][<?php echo $campo['campi_righe_articoli_campo']; ?>]'] option[value=\"" + prodotto['<?php echo $campo['campi_righe_articoli_campo']; ?>'] + "\"]";
                        console.log(sel);
                        $(sel).attr('selected', 'selected');
                    <?php else: ?>
                        $("input[name='products[" + rowid + "][<?php echo $campo['campi_righe_articoli_campo']; ?>]']").val(prodotto['<?php echo $campo['campi_righe_articoli_campo']; ?>']).trigger('change');
                    <?php endif;?>
                    <?php endif;?>
                <?php endforeach;?>
            <?php endforeach;?>
        <?php endif;?>


        $("input[name='products[" + rowid + "][documenti_contabilita_articoli_prodotto_id]']").val(prodotto['<?php echo $campo_id; ?>']).trigger('change');

        $("input[name='products[" + rowid + "][documenti_contabilita_articoli_quantita]']").val(1).trigger('change');

        if (typeof afterPopolaProdotto === "function") {
            // This function exists
            afterPopolaProdotto(prodotto, rowid);
        }

        calculateTotals();
    }


    var cliente_raw_data;
    var documento;
    $(document).ready(function() {

        <?php if ($documento_id): ?>
            documento = <?php echo json_encode($documento); ?>;
        <?php endif;?>
        /****************** AUTOCOMPLETE Destinatario *************************/
        $(".search_cliente").autocomplete({
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
                            // console.log(p);
                            // 2021-07-01 - michael e. - commento in quanto suppliers è stato unificato dentro customers con type 2
                            // if ($('[name="dest_entity_name"]').val() == '<?php echo $entita_clienti; ?>') {
                            var cliente_codice;

                            if (p.<?php echo $clienti_codice; ?> != null && p.<?php echo $clienti_codice; ?>.length != 0) {
                                cliente_codice = p.<?php echo $clienti_codice; ?> + ' - ';
                            } else {
                                cliente_codice = '';
                            }

                            if (typeof p.<?php echo $clienti_ragione_sociale; ?> !== 'undefined' && p.<?php echo $clienti_ragione_sociale; ?> !== null && p.<?php echo $clienti_ragione_sociale; ?> !== '') {
                                collection.push({
                                    "id": p.<?php echo $clienti_id; ?>,
                                    "label": cliente_codice + p.<?php echo $clienti_ragione_sociale; ?>,
                                    "value": p
                                });
                            } else {
                                collection.push({
                                    "id": p.<?php echo $clienti_id; ?>,
                                    "label": cliente_codice + p.<?php echo $clienti_nome; ?> + ' ' + p.<?php echo $clienti_cognome; ?>,
                                    "value": p
                                });
                            }

                            // } else {
                            //     collection.push({
                            //         "id": p.<?php echo $clienti_id; ?>,
                            //         "label": p.suppliers_business_name,
                            //         "value": p
                            //     });
                            // }
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
                console.log('inside select');
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                //console.log(ui.item.value);

                // 2021-07-01 - michael e. - commento l'if e lascio solo "popolaCliente" in quanto suppliers è stato unificato dentro customers con type 2
                // if ($('[name="dest_entity_name"]').val() == '<?php echo $entita_clienti; ?>') {
                popolaCliente(ui.item.value);
                // } else {
                // popolaFornitore(ui.item.value);
                // }

                //Crea link per aprire dettaglio cliente in nuova scheda
                const customers_id = ui.item.value.customers_id;
                const btn_dettaglio_customer = $('#btn_dettaglio_customer');
                if (btn_dettaglio_customer.length != 0) {
                    console.log('devo modificare attr href con nuovo id cliente');
                    btn_dettaglio_customer.attr("href", "<?php echo base_url("main/layout/customer-detail/"); ?>" + customers_id);
                } else {
                    $('.js_dest_type').after('<a href="<?php echo base_url("main/layout/customer-detail/"); ?>' + customers_id + '" target="_blank" class="btn btn-primary btn-xs pull-right" id="btn_dettaglio_customer">Vai al dettaglio cliente</strong>');
                }

                //drawProdotto(ui.item.value, true);
                return false;
            }
        });

        function showAddressButton() {
            var customer_id = $('#js_dest_id').val();
            if (customer_id) {
                $('.js_choose_address').attr('href', base_url + 'get_ajax/modal_layout/customer-shipping-address-contabilita/' + customer_id).show();
            } else {
                $('.js_choose_address').hide();
            }

        }

        $('body').on('click', '.js_shipping_address_choose', function() {

            var shipping_data_base64 = atob($(this).data('shipping_data'));

            var shipping_data = JSON.parse(shipping_data_base64);
            console.log(shipping_data);
            var indirizzo = (shipping_data.customers_shipping_address_name) ? shipping_data.customers_shipping_address_name : shipping_data.customers_company;
            indirizzo += '\n';
            indirizzo += shipping_data.customers_shipping_address_street;
            indirizzo += '\n';
            indirizzo += shipping_data.customers_shipping_address_city + ', cap: ' + shipping_data.customers_shipping_address_zip_code;
            indirizzo += '\n';
            indirizzo += shipping_data.customers_shipping_address_country;

            $('[name="documenti_contabilita_luogo_destinazione"]').html(indirizzo).prop('readonly', true);
            $('[name="documenti_contabilita_luogo_destinazione_id"]').val(shipping_data.customers_shipping_address_id);

            $('#myModal').modal('toggle');
        });

        function popolaCliente(cliente) {
            $('.js_cliente_container').data('cliente', cliente);
            //Cambio la label
            $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');
            $('[name="documenti_contabilita_metodo_pagamento"]').val(cliente['documenti_contabilita_metodi_pagamento_valore']);
            if (cliente['documenti_contabilita_metodi_pagamento_valore'] != undefined) {
                $('[data-name="documenti_contabilita_scadenze_saldato_con"]').val(cliente['clienti_metodo_pagamento']);
            }

            <?php if (!empty($clienti_sottotipo)): ?>
            if (cliente['<?php echo $clienti_sottotipo; ?>']) {
                var sottotipo = 2;

                switch (cliente['<?php echo $clienti_sottotipo; ?>']) {
                    case '1':
                        sottotipo = 2; //privato
                        break;
                    case '2':
                    case '3':
                        sottotipo = 1; // azienda
                        break;
                    case '4':
                        sottotipo = 3; // pa
                        break;
                }

                $('.js_tipo_destinatario[value="' + sottotipo + '"]').trigger('click');
            }
            <?php endif;?>

            if (typeof cliente['<?php echo $clienti_ragione_sociale; ?>'] !== 'undefined' && cliente['<?php echo $clienti_ragione_sociale; ?>'] !== null && cliente['<?php echo $clienti_ragione_sociale; ?>'] !== '') {
                $('.js_dest_ragione_sociale').val(cliente['<?php echo $clienti_ragione_sociale; ?>']);
            } else {
                $('.js_dest_ragione_sociale').val(cliente['<?php echo $clienti_nome; ?>'] + ' ' + cliente['<?php echo $clienti_cognome; ?>']);
            }

            $('.js_dest_codice').val(cliente['<?php echo $clienti_codice; ?>']);

            $('.js_dest_indirizzo').val(cliente['<?php echo $clienti_indirizzo; ?>']);



            $('.js_dest_citta').val(cliente['<?php echo $clienti_citta; ?>']);

            //console.log(cliente['<?php //echo $mappature_autocomplete['clienti_nazione']; ?>//']);

            <?php if (!empty($clienti_nazione)): ?>

                <?php if (!empty($mappature_autocomplete['clienti_nazione']) && $mappature_autocomplete['clienti_nazione'] != $clienti_nazione): ?>
                    $('.js_dest_nazione').val(cliente['<?php echo $mappature_autocomplete['clienti_nazione']; ?>']).trigger('change');
                <?php else: ?>
                    $('.js_dest_nazione').val(cliente['<?php echo $clienti_nazione; ?>']).trigger('change');
                <?php endif;?>
            <?php endif;?>



            $('.js_dest_cap').val(cliente['<?php echo $clienti_cap; ?>']);

            $('.js_dest_provincia').val(cliente['<?php echo $clienti_provincia; ?>']);

            <?php if (!empty($clienti_partita_iva)): ?>
                $('.js_dest_partita_iva').val(cliente['<?php echo $clienti_partita_iva; ?>']);
            <?php endif;?>

            $('.js_dest_codice_fiscale').val(cliente['<?php echo $clienti_codice_fiscale; ?>']);
            <?php if (!empty($clienti_codice_sdi)): ?>
                if (cliente['<?php echo $clienti_codice_sdi; ?>']) {
                    $('.js_dest_codice_sdi').val(cliente['<?php echo $clienti_codice_sdi; ?>']);
                }
            <?php endif;?>
            <?php if (!empty($clienti_pec)): ?>
                $('.js_dest_pec').val(cliente['<?php echo $clienti_pec; ?>']);
            <?php endif;?>
            $('#js_dest_id').val(cliente['<?php echo $clienti_id; ?>']).trigger('change');

            cliente_raw_data = cliente;

            //Cambio iva default sulle righe prodotto
            if (typeof cliente_raw_data !== 'undefined' &&
                typeof cliente_raw_data.customers_iva_default !== 'undefined' &&
                !isNaN(parseInt(cliente_raw_data.customers_iva_default)) &&
                parseInt(cliente_raw_data.customers_iva_default) > 0
            ) {
                //alert(1);
                var iva_id = parseInt(cliente_raw_data.customers_iva_default);
                $(".js_documenti_contabilita_articoli_iva_id option[value='" + iva_id + "']").prop('selected', true).attr('selected', 'selected').val(iva_id).trigger('change');
            }

            showAddressButton();

            applicaListino(false, false);
            applicaMetodoPagamento();
        }

        function popolaFornitore(fornitore) {
            //Cambio la label
            $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');
            $('[name="documenti_contabilita_metodo_pagamento"]').val(fornitore['documenti_contabilita_metodi_pagamento_valore']);
            if (fornitore['documenti_contabilita_metodi_pagamento_valore'] != undefined) {
                $('[data-name="documenti_contabilita_scadenze_saldato_con"]').val(fornitore['suppliers_type_of_payment']);
            }
            $('.js_dest_nazione').val(fornitore['suppliers_country']);


            $('.js_dest_codice').val(fornitore['suppliers_code']);
            $('.js_dest_ragione_sociale').val(fornitore['suppliers_business_name']);
            $('.js_dest_indirizzo').val(fornitore['suppliers_address']);
            $('.js_dest_citta').val(fornitore['suppliers_city']);
            $('.js_dest_cap').val(fornitore['suppliers_zip_code']);
            $('.js_dest_provincia').val(fornitore['suppliers_province']);
            $('.js_dest_partita_iva').val(fornitore['suppliers_vat_number']);
            $('.js_dest_codice_fiscale').val(fornitore['suppliers_cf']);
            $('#js_dest_id').val(fornitore['suppliers_id']);
        }

        //20190712 - Matteo - Sbagliato! Non passa il data('id')... modifico con un foreach
        //initAutocomplete($('.js_autocomplete_prodotto'));
        $('.js_autocomplete_prodotto').each(function() {
            initAutocomplete($(this));
        });

        $('.js_select2').each(function() {
            var select = $(this);
            var placeholder = select.attr('data-placeholder');
            select.select2({
                placeholder: placeholder ? placeholder : '',
                allowClear: true
            });
        });

        <?php if ($documento_id || !empty($documento['articoli'])): ?>
            calculateTotals(<?php echo (!$clone) ? $documento_id : ''; ?>);

            cliente_raw_data = documento;
            $('#js_dest_id').filter(function() {
                return !this.value;
            }).trigger('change');

            showAddressButton();
            setTimeout(function() {
                applicaListino(false, false);
            }, 1000);



        <?php endif;?>
    });
</script>


<script>
    $(document).ready(function() {
        var tipo_documento = $('.js_documenti_contabilita_tipo').val();

        /*$('.js_tipologia_fatturazione').on('change', function(){
            var option = $(this);
            var codice = $(this).find(':selected').data('tipologia_codice');

            switch(codice){
                case 'TD01':
                case 'TD02':
                case 'TD03':
                case 'TD06':
                case 'TD07':
                    $('.js_btn_tipo[data-tipo="1"]').trigger('click');
                    break;
                case 'TD04':
                case 'TD05':
                    $('.js_btn_tipo[data-tipo="4"]').trigger('click');
                    break;
                default:
                    break;

            }

            console.log(codice + ' -> ' + option.val() );
        });*/

        $('.js_btn_tipo').click(function(e) {

            var documento_id = <?php echo (!empty($documento_id) ? $documento_id : 0); ?>;
            var tipo = $(this).data('tipo');
            //$(".js_template_pdf option[data-tipo='" + tipo + "']").prop("selected", true);
            if(documento_id == 0){
                $(".js_template_pdf option").each(function(){
                    if($(this).attr('data-tipo') == tipo){
                        $(this).prop('selected',true);
                        return false;
                    }
                })
            }
            //Cambio eventuali label
            $('.scadenze_box').show();
            $('.js_rivalsa_container').show();

            if (tipo == 12) {
                alert('Indicare gli importi col segno meno davanti!');
            }
            switch (tipo) {

                case 11: //Autofattura reverse
                case 12:
                    $('.js_dest_type').html('fornitore');
                    $('[name="dest_entity_name"]').val('customers');

                    //Toglie check da formato elettronico e nasconde campo
                    $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                    $('[name=documenti_contabilita_formato_elettronico]').closest('.row').show();
                    $.uniform.update();

                    $('.js_rivalsa_container').show();
                    break;
                case 6: //Ordine fornitore
                case 10: //DDT fornitore

                    $('.js_dest_type').html('fornitore');
                    $('[name="dest_entity_name"]').val('customers');
                    if (tipo_documento != tipo) {
                        if ($('.js_tipologia_fatturazione').is(':visible')) {
                            $('.js_tipologia_fatturazione').parent().hide();
                        }
                        $('.js_tipologia_fatturazione').val('');
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.row').hide();
                        $.uniform.update();
                    }
                    $('.js_rivalsa_container').hide();
                    break;
                case 3: //Pro forma
                    $('.js_dest_type').html('cliente');
                    if (tipo_documento != tipo) {
                        if ($('.js_tipologia_fatturazione').is(':visible')) {
                            $('.js_tipologia_fatturazione').parent().hide();
                        }
                        $('.js_tipologia_fatturazione').val('');
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.row').hide();
                        $.uniform.update();
                    }
                    break;
                case 1: //Fattura
                    $('.js_dest_type').html('cliente');
                    $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                    if (tipo_documento != tipo) {
                        //$('.js_tipologia_fatturazione').val('1').trigger('change');
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.row').show();
                        $.uniform.update();
                    }
                    //break;
                    case 2:
                        $('.js_dest_type').html('cliente');
                        $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                        if (tipo_documento != tipo<?php if (!$documento_id): ?> || true<?php endif;?>) {
                            if ($('.js_tipologia_fatturazione').is(':hidden')) {
                                $('.js_tipologia_fatturazione').parent().show();
                            }
                            $('.js_tipologia_fatturazione').val('1').trigger('change');
                            $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                            $.uniform.update();
                        }
                        break;
                    case 4: //Nota di credito
                        $('.js_dest_type').html('cliente');
                        $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                        if (tipo_documento != tipo<?php if (!$documento_id): ?> || true<?php endif;?>) {
                            if ($('.js_tipologia_fatturazione').is(':hidden')) {
                                $('.js_tipologia_fatturazione').parent().show();
                            }
                            $('.js_tipologia_fatturazione').val('4').trigger('change');
                            //Rimette cheeck e mostra campo
                            $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                            $('[name=documenti_contabilita_formato_elettronico]').closest('.row').show();
                            $.uniform.update();
                        }
                        break;
                    case 7: //Preventivo
                        if (tipo_documento != tipo) {
                            if ($('.js_tipologia_fatturazione').is(':visible')) {
                                $('.js_tipologia_fatturazione').parent().hide();
                            }
                            //Toglie check da formato elettronico e nasconde campo
                            $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                            $('[name=documenti_contabilita_formato_elettronico]').closest('.row').hide();
                            $.uniform.update();
                        }
                        $('.js_tipologia_fatturazione').val('');
                        $('.js_dest_type').html('cliente');
                        $('.scadenze_box').show();
                        $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                        break;
                    case 5: //Ordine cliente
                        if (tipo_documento != tipo) {
                            if ($('.js_tipologia_fatturazione').is(':visible')) {
                                $('.js_tipologia_fatturazione').parent().hide();
                            }
                            $('.js_tipologia_fatturazione').val('');
                            //Nascondo blocco scadenze

                            //Toglie check da formato elettronico e nasconde campo
                            $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                            $('[name=documenti_contabilita_formato_elettronico]').closest('.row').hide();
                            $.uniform.update();
                        }
                        $('.js_dest_type').html('cliente');
                        $('.scadenze_box').hide();
                        $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                        break;
                    case 8: //DDT cliente
                        if ($('.js_tipologia_fatturazione').is(':visible')) {
                            $('.js_tipologia_fatturazione').parent().hide();
                        }
                        $('.js_tipologia_fatturazione').val('');
                        $('.scadenze_box').hide();
                        if (tipo_documento != tipo) {
                            //Toglie check da formato elettronico e nasconde campo
                            $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                            $('[name=documenti_contabilita_formato_elettronico]').closest('.row').hide();
                            $.uniform.update();
                        }
                        break;
                    default:
                        if (tipo_documento != tipo) {
                            //Toglie check da formato elettronico e nasconde campo
                            $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                            $('[name=documenti_contabilita_formato_elettronico]').closest('.row').hide();
                            $.uniform.update();
                        }
                        break;
            }

            $('.js_btn_tipo').removeClass('btn-primary');
            $('.js_btn_tipo').addClass('btn-default');
            $(this).addClass('btn-primary');
            $(this).removeClass('btn-default');
            $('.js_documenti_contabilita_tipo').val(tipo).trigger('change');
            if (tipo_documento != tipo) {
                getNumeroDocumento();
            }
            tipo_documento = tipo;
            //getNumeroDocumento();
        });

        $('.js_btn_tipo[data-tipo="' + $('.js_documenti_contabilita_tipo').val() + '"]').trigger('click');
    });
    $('.documenti_contabilita_valuta').on('change', function() {

        //Se il tasso di cambio è uguale al default, nascondo il tasso di cambio perchè non ha senso
        if ($('option:selected', $(this)).data('id') == '<?php echo $impostazioni['documenti_contabilita_settings_valuta_base']; ?>') {
            $('.documenti_contabilita_tasso_di_cambio').parent().hide();
        } else {
            //Ajax per chiedere il tasso di cambio
            $.ajax({
                method: 'get',
                dataType: "json",
                url: base_url + "contabilita/documenti/tassoDiCambio/" + $('option:selected', $(this)).data('id'),
                success: function(data) {
                    $('.documenti_contabilita_tasso_di_cambio').val(data.tassi_di_cambio_tasso);
                    //console.log(data);
                }
            });
            $('.documenti_contabilita_tasso_di_cambio').parent().show();
        }
    });
    $('.documenti_contabilita_valuta').trigger('change');


    function getNumeroAjax(tipo, serie) {
        $.ajax({
            method: 'post',
            data: {
                data_emissione: $('[name="documenti_contabilita_data_emissione"]').val(),
                [token_name]: token_hash
            },
            url: base_url + "contabilita/documenti/numeroSucessivo/" + $('.documenti_contabilita_azienda').val() + '/' + $('.js_documenti_contabilita_tipo').val() + '/' + $('.js_documenti_contabilita_serie').val(),
            success: function(numero) {
                $('[name="documenti_contabilita_numero"]').val(numero);
            }
        });
    }

    function getNumeroDocumento() {
        var is_modifica = !isNaN($('[name="documento_id"]').val());
        var tipo = $('.js_btn_tipo.btn-primary').data('tipo');
        var serie = $('.js_btn_serie.button_selected').data('serie');
        if (is_modifica) {
            if (tipo == '<?php echo (empty($documento['documenti_contabilita_tipo'])) ? 'XXX' : $documento['documenti_contabilita_tipo']; ?>' && serie == '<?php echo (empty($documento['documenti_contabilita_serie'])) ? 'XXX' : $documento['documenti_contabilita_serie']; ?>') {
                $('[name="documenti_contabilita_numero"]').val(<?php echo (!empty($documento['documenti_contabilita_numero'])) ? $documento['documenti_contabilita_numero'] : ''; ?>);
            } else {
                getNumeroAjax(tipo, serie);
            }
        } else {
            getNumeroAjax(tipo, serie);
        }
    }

    function reloadTemplatePdf() {
        $.ajax({
            method: 'get',
            //type: 'json',
            url: base_url + "contabilita/documenti/getTemplatePdf/" + $('.documenti_contabilita_azienda').val(),
            success: function(templates) {
                $('[name="documenti_contabilita_template_pdf"] option').each(function() {
                    if ($(this).val()) {
                        $(this).remove();
                    }
                });
                templates = JSON.parse(templates);
                for (var i in templates) {
                    $('[name="documenti_contabilita_template_pdf"]').append('<option value="' + templates[i].documenti_contabilita_template_pdf_id + '">' + templates[i].documenti_contabilita_template_pdf_nome + '</option>');
                }
            }
        });
    }

    $('.js_btn_serie').click(function(e) {
        if ($(this).hasClass('button_selected')) {
            $('.js_btn_serie').removeClass('button_selected');
            $('.js_documenti_contabilita_serie').val('');
        } else {
            $('.js_btn_serie').removeClass('button_selected');
            $(this).addClass('button_selected');

            $('.js_documenti_contabilita_serie').val($(this).data('serie'));
        }
        getNumeroDocumento();
    });
    $('.documenti_contabilita_azienda').on('change', function() {

        getNumeroDocumento();

        reloadTemplatePdf();
    });

    if (!$('.js_documenti_contabilita_tipo').val()) {
        $('.js_btn_tipo').first().trigger('click');
    }
    $('[name="documenti_contabilita_data_emissione"]').on('change', function() {
        //getNumeroDocumento();
    });

    <?php if (empty($documento['documenti_contabilita_numero']) || $clone): ?>
        $('.js_btn_tipo[data-tipo="' + $('.js_documenti_contabilita_tipo').val() + '"]').trigger('click');
        getNumeroDocumento();
        //alert(1);
        //$('.js_btn_serie').first().trigger('click');
    <?php endif;?>


    var totale = 0;
    var totale_iva = 0;
    var competenze = 0;
    var competenze_scontate = 0;
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
        var qty = parseFloat($('.js_documenti_contabilita_articoli_quantita', tr).val());
        var sconto = parseFloat($('.js_documenti_contabilita_articoli_sconto', tr).val());
        var sconto2 = parseFloat($('.js_documenti_contabilita_articoli_sconto2', tr).val());
        var sconto3 = parseFloat($('.js_documenti_contabilita_articoli_sconto3', tr).val());
        var iva = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', tr).data('perc'));

        if (isNaN(qty)) {
            qty = 0;
        }
        if (isNaN(sconto)) {
            sconto = 0;
        }
        if (isNaN(sconto2)) {
            sconto2 = 0;
        }

        if (isNaN(sconto3)) {
            sconto3 = 0;
        }
        if (isNaN(iva)) {
            iva = 0;
        }

        var importo_ivato = parseFloat($('.js-importo', tr).val());

        //Applico lo sconto al rovescio
        var importo = parseFloat(importo_ivato / ((100 + iva) / 100));
        var importo_ricalcolato = (importo_ivato - ((importo_ivato / 100) * sconto));
        importo_ricalcolato = importo_ricalcolato / 100 * (100 - sconto2);
        importo_ricalcolato = parseFloat(importo_ricalcolato / 100 * (100 - sconto3));



        //console.log(importo);

        $('.js-importo', tr).val(importo_ricalcolato.toFixed(2));
        $('.js_documenti_contabilita_articoli_prezzo', tr).val(importo.toFixed(2));
        //
        calculateTotals();
    }
    var generaScadenze = function(totale, totale_iva) {

    };

    var applicaListino = function(data, tipo_documento) {
        //PF,IV,SC,PA,PP,PB,PV,RP,SP
        // console.log(data);


        if (tipo_documento == 6) { // fornitore
            return data.PF;
        } else {
            return data.PP;
        }


    };

    var applicaMetodoPagamento = function() {
        // console.log(cliente_raw_data);
        var template_pagamento_id = cliente_raw_data.customers_template_pagamento;
        for (var i in template_scadenze) { //Ciclo l'array delle scadenze (configurate a monte)
            var template_scadenza = template_scadenze[i];
            if (template_scadenza.documenti_contabilita_template_pagamenti_id == template_pagamento_id) {
                var prima_scadenza = template_scadenza.documenti_contabilita_tpl_pag_scadenze.pop();
                console.log(prima_scadenza);
                $('[name="documenti_contabilita_metodo_pagamento"]').val(prima_scadenza.documenti_contabilita_metodi_pagamento_valore);
            }
        }
    }

    function calculateTotals(documento_id) {
        totale = 0;
        iva_perc_max = 0;
        totale_iva = 0;
        totale_iva_divisa = {};
        totale_imponibile_divisa = {};
        competenze = 0;
        competenze_scontate = 0;
        competenze_no_ritenute = 0;
        sconto_totale = $('.js_sconto_totale').val();
        if (sconto_totale == 0) {
            $('label.competenze_scontate').hide();
        } else {
            $('label.competenze_scontate').show();
        }
        $('#js_product_table > tbody > tr:not(.hidden)').each(function() {
            var riga_desc = $('.js-riga_desc', $(this)).is(':checked');
            if (riga_desc) {
                $('.js_documenti_contabilita_articoli_unita_misura,.js_documenti_contabilita_articoli_quantita,.js_documenti_contabilita_articoli_prezzo,.js_documenti_contabilita_articoli_sconto,.js_documenti_contabilita_articoli_sconto2,.js_documenti_contabilita_articoli_sconto3,.js_documenti_contabilita_articoli_iva_id,.js-importo,.js-applica_ritenute,.js-applica_sconto', $(this)).attr('disabled', true);
                return;
            } else {
                $('.js_documenti_contabilita_articoli_unita_misura,.js_documenti_contabilita_articoli_quantita,.js_documenti_contabilita_articoli_prezzo,.js_documenti_contabilita_articoli_sconto,.js_documenti_contabilita_articoli_sconto2,.js_documenti_contabilita_articoli_sconto3,.js_documenti_contabilita_articoli_iva_id,.js-importo,.js-applica_ritenute,.js-applica_sconto', $(this)).removeAttr('disabled');
            }

            var qty = parseFloat($('.js_documenti_contabilita_articoli_quantita', $(this)).val());
            var prezzo = parseFloat($('.js_documenti_contabilita_articoli_prezzo', $(this)).val());
            var sconto = parseFloat($('.js_documenti_contabilita_articoli_sconto', $(this)).val());
            var sconto2 = parseFloat($('.js_documenti_contabilita_articoli_sconto2', $(this)).val());
            var sconto3 = parseFloat($('.js_documenti_contabilita_articoli_sconto3', $(this)).val());
            var iva = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', $(this)).data('perc'));
            //alert(iva);
            var iva_id = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', $(this)).val());
            var appl_ritenute = $('.js-applica_ritenute', $(this)).is(':checked');
            var appl_sconto = $('.js-applica_sconto', $(this)).is(':checked');

            //console.log(appl_ritenute);

            iva_perc_max = Math.max(iva_perc_max, iva);
            if (iva_perc_max == iva) {

                iva_id_perc_max = iva_id;
            }

            if (isNaN(qty)) {
                qty = 0;
            }
            if (isNaN(prezzo)) {
                prezzo = 0;
            }
            if (isNaN(sconto)) {
                sconto = 0;
            }
            if (isNaN(sconto2)) {
                sconto2 = 0;
            }
            if (isNaN(sconto3)) {
                sconto3 = 0;
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
            totale_riga_scontato = totale_riga_scontato / 100 * (100 - sconto2);
            totale_riga_scontato = totale_riga_scontato / 100 * (100 - sconto3);

            var totale_riga_scontato_con_sconto_totale = totale_riga_scontato;

            //competenze += totale_riga_scontato;

            if (appl_sconto) {
                competenze += totale_riga_scontato;
                competenze_scontate += (totale_riga_scontato * (100 - sconto_totale) / 100);
                totale_riga_scontato_con_sconto_totale = parseFloat(totale_riga_scontato * (100 - sconto_totale) / 100);
            } else {
                competenze += totale_riga_scontato;
                competenze_scontate += totale_riga_scontato;
            }
            var totale_riga_scontato_ivato = parseFloat((totale_riga_scontato_con_sconto_totale * (100 + iva)) / 100);

            if (totale_riga_scontato_ivato != totale_riga_scontato_con_sconto_totale) {
                //                 console.log(totale_riga_scontato_ivato);
                //                 console.log(totale_riga_scontato_con_sconto_totale);
            }

            if (!appl_ritenute) {
                competenze_no_ritenute += totale_riga_scontato_con_sconto_totale;
            }

            if (totale_iva_divisa[iva_id] == undefined) {
                //console.log(totale_iva_divisa);
                totale_iva_divisa[iva_id] = [iva, parseFloat((totale_riga_scontato_con_sconto_totale / 100) * iva)];
                totale_imponibile_divisa[iva_id] = [iva, totale_riga_scontato_con_sconto_totale];
            } else {
                totale_iva_divisa[iva_id][1] += parseFloat((totale_riga_scontato_con_sconto_totale / 100) * iva);
                totale_imponibile_divisa[iva_id][1] += totale_riga_scontato_con_sconto_totale;
            }
            //            console.log(totale_riga);
            //            console.log(totale_riga_scontato);
            //            console.log(totale_riga_scontato_con_sconto_totale);
            //
            //console.log(totale_iva_divisa);

            totale_iva += parseFloat((totale_riga_scontato_con_sconto_totale / 100) * iva);
            totale += totale_riga_scontato_ivato;


            $('.js-importo', $(this)).val(totale_riga_scontato_ivato.toFixed(2));
            $('.js_documenti_contabilita_articoli_iva', $(this)).val(parseFloat((totale_riga_scontato / 100) * iva).toFixed(2));
            $('.js_riga_imponibile', $(this)).html(parseFloat(totale_riga_scontato).toFixed(2));

        });

        //Fix per evitare di portarmi dietro troppe cifre decimali che poi creano problemi di arrotondamento...
        competenze = Math.round(competenze * 100) / 100;
        totale_iva = Math.round(totale_iva * 100) / 100;
        competenze_scontate = Math.round(competenze_scontate * 100) / 100;
        competenze_no_ritenute = Math.round(competenze_no_ritenute * 100) / 100;

        rivalsa_inps_percentuale = parseFloat($('[name="documenti_contabilita_rivalsa_inps_perc"]').val());
        rivalsa_inps_valore = parseFloat(((competenze_scontate - competenze_no_ritenute) / 100) * rivalsa_inps_percentuale);

        competenze_con_rivalsa = competenze_scontate + rivalsa_inps_valore;

        cassa_professionisti_perc = parseFloat($('[name="documenti_contabilita_cassa_professionisti_perc"]').val());
        cassa_professionisti_valore = parseFloat(((competenze_con_rivalsa - competenze_no_ritenute) / 100) * cassa_professionisti_perc);

        imponibile = competenze_con_rivalsa + cassa_professionisti_valore;

        var applica_split_payment = $('[name="documenti_contabilita_split_payment"]').is(':checked');

        var totale_imponibili_iva_diverse_da_max = 0;
        var totale_iva_diverse_da_max = 0;
        for (var iva_id in totale_iva_divisa) {
            if (totale_iva_divisa[iva_id][0] != iva_perc_max) {
                if (totale_iva_divisa[iva_id][0] != 0) {
                    totale_imponibili_iva_diverse_da_max += parseFloat((totale_iva_divisa[iva_id][1] / totale_iva_divisa[iva_id][0]) * 100);
                } else {
                    totale_imponibili_iva_diverse_da_max += totale_imponibile_divisa[iva_id][1];

                }
                totale_iva_diverse_da_max += parseFloat(totale_iva_divisa[iva_id][1]);
            }
        }

        //Aggiungo alla iva massima, ciò che manca tenendo conto delle modifiche ai totali dovute a rivalsa e cassa
        //        console.log(imponibile);
        //        console.log(totale_imponibili_iva_diverse_da_max);
        // console.log(iva_perc_max);
        // console.log(iva_id_perc_max);
        // console.log(totale_iva_divisa);
        totale_iva_divisa[iva_id_perc_max][1] = parseFloat(((imponibile - totale_imponibili_iva_diverse_da_max) / 100) * iva_perc_max);

        //        alert('imponibile '+imponibile);
        //        alert('totale' + totale);
        //        alert('totale ivato' + totale_iva);
        //        alert('competenze scontate' + competenze_scontate);
        //        alert('???' + competenze_scontate / 100 * 22);

        //Valuto le ritenute
        ritenuta_acconto_perc = parseFloat($('[name="documenti_contabilita_ritenuta_acconto_perc"]').val());
        ritenuta_acconto_perc_sull_imponibile = parseFloat($('[name="documenti_contabilita_ritenuta_acconto_perc_imponibile"]').val());
        ritenuta_acconto_valore_sull_imponibile = ((competenze_con_rivalsa - competenze_no_ritenute) / 100) * ritenuta_acconto_perc_sull_imponibile;
        totale_ritenuta = (ritenuta_acconto_valore_sull_imponibile / 100) * ritenuta_acconto_perc;

        //console.log(totale_iva_divisa);
        totale = imponibile + totale_iva_diverse_da_max + totale_iva_divisa[iva_id_perc_max][1] - totale_ritenuta;

        $('[name="documenti_contabilita_rivalsa_inps_valore"]').val(rivalsa_inps_valore);
        $('[name="documenti_contabilita_competenze_lordo_rivalsa"]').val(competenze_con_rivalsa);
        if (rivalsa_inps_percentuale && rivalsa_inps_valore > 0) {
            $('.js_rivalsa').html('Rivalsa INPS ' + rivalsa_inps_percentuale + '% <span>€ ' + rivalsa_inps_valore.toFixed(2) + '</span>').show();
            $('.js_competenze_rivalsa').html('Competenze (al lordo della rivalsa)<span>€ ' + competenze_con_rivalsa.toFixed(2) + '</span>').show();
        } else {
            $('.js_rivalsa').hide();
            $('.js_competenze_rivalsa').hide();
        }

        $('[name="documenti_contabilita_cassa_professionisti_valore"]').val(cassa_professionisti_valore);
        $('[name="documenti_contabilita_imponibile"]').val(imponibile.toFixed(2));
        $('[name="documenti_contabilita_imponibile_scontato"]').val(competenze_scontate.toFixed(2));

        if (cassa_professionisti_perc && cassa_professionisti_valore > 0) {
            $('.js_cassa_professionisti').html('Cassa professionisti ' + cassa_professionisti_perc + '% <span>€ ' + cassa_professionisti_valore.toFixed(2) + '</span>').show();
            $('.js_imponibile').html('Imponibile <span>€ ' + imponibile.toFixed(2) + '</span>').show();
        } else {
            $('.js_cassa_professionisti').hide();
            $('.js_imponibile').hide();
        }


        $('[name="documenti_contabilita_ritenuta_acconto_valore"]').val(totale_ritenuta);
        $('[name="documenti_contabilita_ritenuta_acconto_imponibile_valore"]').val(ritenuta_acconto_valore_sull_imponibile);
        if (ritenuta_acconto_perc > 0 && ritenuta_acconto_perc_sull_imponibile > 0 && totale_ritenuta > 0) {
            $('.js_ritenuta_acconto').html('Ritenuta d\'acconto -' + ritenuta_acconto_perc + '% di &euro; ' + ritenuta_acconto_valore_sull_imponibile.toFixed(2) + '<span>€ ' + totale_ritenuta.toFixed(2) + '</span>').show();
        } else {
            $('.js_ritenuta_acconto').hide();
        }

        $('[name="documenti_contabilita_competenze"]').val(competenze);
        $('.js_competenze').html('€ ' + competenze.toFixed(2));
        $('.js_competenze_scontate').html('€ ' + competenze_scontate.toFixed(2));

        $(".js_tot_iva:not(:first)").remove();
        $(".js_tot_iva:first").hide();


        $('[name="documenti_contabilita_iva_json"]').val(JSON.stringify(totale_iva_divisa));
        $('[name="documenti_contabilita_imponibile_iva_json"]').val(JSON.stringify(totale_imponibile_divisa));

        for (var iva_id in totale_iva_divisa) {

            //console.log(totale_iva_divisa);

            $(".js_tot_iva:last").clone().insertAfter(".js_tot_iva:last").show();
            $('.js_tot_iva:last').html(`IVA (` + (totale_iva_divisa[iva_id][0]) + `%): <span>€ ` + totale_iva_divisa[iva_id][1].toFixed(2) + `</span>`); //'€ '+totale_iva.toFixed(2));
        }

        if (applica_split_payment) {
            $('.js_split_payment').html('Iva non dovuta (split payment) <span>€ -' + (totale_iva_diverse_da_max + totale_iva_divisa[iva_id_perc_max][1]).toFixed(2) + '</span>').show();
            totale -= (totale_iva_diverse_da_max + totale_iva_divisa[iva_id_perc_max][1]);
        } else {
            $('.js_split_payment').hide();
        }

        //20191029 - MP - Aggiungo l'importo di bollo al totale
        if ($('[name="documenti_contabilita_importo_bollo"]').val() && $('[name="documenti_contabilita_applica_bollo"]').is(':checked')) {

            totale += parseFloat($('[name="documenti_contabilita_importo_bollo"]').val());

        }

        $('.js_tot_da_saldare').html('€ ' + totale.toFixed(2));

        $('[name="documenti_contabilita_totale"]').val(totale.toFixed(2));
        $('[name="documenti_contabilita_iva"]').val(totale_iva.toFixed(2));

        if (isNaN(documento_id)) {
            $('.documenti_contabilita_scadenze_ammontare').val(totale.toFixed(2));
            $('.documenti_contabilita_scadenze_ammontare:first').trigger('change');
        } else {
            //$('.documenti_contabilita_scadenze_ammontare:last').closest('.row_scadenza').remove();
            $('.documenti_contabilita_scadenze_ammontare:last').trigger('change');
        }

        generaScadenze(totale, totale_iva);

    }

    function increment_scadenza() {
        var counter_scad = $('.row_scadenza').length;
        var rows_scadenze = $('.js_rows_scadenze');

        // Fix per clonare select inizializzata
        if ($('.js_table_select2').filter(':first').data('select2')) {
            $('.js_table_select2').filter(':first').select2('destroy');
        } else {

        }

        var newScadRow = $('.row_scadenza').filter(':first').clone();
        $('.documenti_contabilita_scadenze_data_saldo', newScadRow).val('');
        // Fix per clonare select inizializzata
        $('.js_table_select2').filter(':first').select2();

        /* Line manipulation begin */
        //newScadRow.removeClass('hidden');
        $('input, select, textarea', newScadRow).each(function() {
            var control = $(this);
            var name = control.attr('data-name');
            control.attr('name', 'scadenze[' + counter_scad + '][' + name + ']').removeAttr('data-name');
        });

        $('.js_form_datepicker input', newScadRow).datepicker({
            todayBtn: 'linked',
            format: 'dd/mm/yyyy',
            todayHighlight: true,
            weekStart: 1,
            language: 'it'
        });
        $('.js_documenti_contabilita_scadenze_id', newScadRow).remove();
        /* Line manipulation end */
        counter_scad++;
        newScadRow.appendTo(rows_scadenze);

        // $('.js_table_select2', newScadRow).select2({
        //     //placeholder: "Seleziona prodotto",
        //     allowClear: true
        // });
    }

    $(document).ready(function() {
        var table = $('#js_product_table');
        var body = $('#js_product_table > tbody');
        var rows = $('tbody > tr', table);
        var increment = $('#js_add_product', table);

        var rows_scadenze = $('.js_rows_scadenze');
        //var increment_scadenza = $('#js_add_scadenza');


        var firstRow = rows.filter(':first');
        var counter = rows.length;

        $('#new_fattura').on('change', '[name="documenti_contabilita_importo_bollo"],[name="documenti_contabilita_split_payment"], [name="documenti_contabilita_rivalsa_inps_perc"],[name="documenti_contabilita_cassa_professionisti_perc"],[name="documenti_contabilita_ritenuta_acconto_perc"],[name="documenti_contabilita_ritenuta_acconto_perc_imponibile"]', function() {
            calculateTotals();
        });

        table.on('change', '.js-applica_ritenute,.js-applica_sconto, .js-riga_desc, .js_documenti_contabilita_articoli_quantita, .js_documenti_contabilita_articoli_prezzo, .js_documenti_contabilita_articoli_sconto,.js_documenti_contabilita_articoli_sconto2,.js_documenti_contabilita_articoli_sconto3, .js_documenti_contabilita_articoli_iva_id',
            function() {
                //console.log('dentro');
                setTimeout("calculateTotals()", 500);
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
            $('.js_autocomplete_prodotto', newRow).attr('data-id', counter);
            initAutocomplete($('.js_autocomplete_prodotto', newRow));

            /* Line manipulation end */

            counter++;
            newRow.appendTo(body);
        });


        table.on('click', '.js_remove_product', function() {
            $(this).parents('tr').remove();
            calculateTotals();
        });
        $('#offerproducttable .js_remove_product').on('click', function() {
            $(this).parents('tr').remove();
        });

        $('.js_sconto_totale').on('change', function() {
            calculateTotals();
        });

        //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
        rows_scadenze.on('change', '.documenti_contabilita_scadenze_ammontare', function() {
            //Se la somma degli ammontare è minore del totale procedo
            var totale_scadenze = 0;
            $('.documenti_contabilita_scadenze_ammontare').each(function() {
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
                    $('.documenti_contabilita_scadenze_ammontare', next_row).val((totale - totale_scadenze).toFixed(2));
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
    $(document).ready(function() {
        // trigger click on add product when tabkey is pressed and focus on last codice
        $('#js_add_product').on('keyup', function(e) {
            $(this).trigger('click');
            $('.js_documenti_contabilita_articoli_codice:last').focus();
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

    });
</script>

<script>
    var check_calculate = false;
    $(document).ready(function() {
        //Fix per essere sicuri che i calcoli siano stati tutti fatti prima di inviare e salvare il documento.
        $('#new_fattura').on('submit', function(e) {
            if (!check_calculate) {
                e.stopImmediatePropagation();
                e.stopPropagation();
                e.preventDefault();
                <?php if ($documento_id): ?>
                    calculateTotals(<?php echo (!$clone) ? $documento_id : ''; ?>);
                <?php else: ?>
                    calculateTotals();
                <?php endif;?>
                check_calculate = true;
                $('#new_fattura').trigger('submit');
            } else {
                check_calculate = false;
            }
        });
        <?php if (!$documento_id && !$clone): ?>
            $('#js_add_product').trigger('click');
            <?php endif;?>
    });
</script>
<!-- END Module Related Javascript -->

<?php $this->layout->addModuleJavascript('contabilita', 'gestione_scadenze.js');?>
<?php $this->layout->addModuleJavascript('contabilita', 'gestione_listini.js');?>