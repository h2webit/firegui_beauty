<?php
ini_set('memory_limit', '8000M');
$this->load->model('contabilita/prima_nota');

$grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
$where_grid = $this->datab->generate_where("grids", $grid['grids_id'], null);
if (!$where_grid) {
    $where_grid = '(1=1)';
}

if ($customer_id) {
    $customer = $this->apilib->view('customers', $customer_id);
    $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND
    (prime_note_registrazioni_sottoconto_dare = '{$customer['customers_sottoconto']}'
    OR
    prime_note_registrazioni_sottoconto_avere = '{$customer['customers_sottoconto']}')
";
    $titolo = $customer['customers_company'];
} else {
    //Se non sto stampando un estratto conto cliente, mi aspetto di ricevere i filtri in get (mastro, conto o sottoconto)

    if ($mastro_id = $this->input->get('mastro')) {

        $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND
            (    prime_note_registrazioni_mastro_dare = '{$mastro_id}'
                OR
                prime_note_registrazioni_mastro_avere = '{$mastro_id}')
            ";
        $titolo = $this->apilib->view('documenti_contabilita_mastri', $mastro_id)['documenti_contabilita_mastri_descrizione'];
    }
    if ($conto_id = $this->input->get('conto')) {
        $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND
                (prime_note_registrazioni_conto_dare = '{$conto_id}'
                OR
                prime_note_registrazioni_conto_avere = '{$conto_id}')
            ";
        $titolo = $this->apilib->view('documenti_contabilita_conti', $conto_id)['documenti_contabilita_conti_descrizione'];
    }
    if ($sottoconto_id = $this->input->get('sottoconto')) {

        $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND
                (prime_note_registrazioni_sottoconto_dare = '{$sottoconto_id}'
                OR
                prime_note_registrazioni_sottoconto_avere = '{$sottoconto_id}')
            ";
        $titolo = $this->apilib->view('documenti_contabilita_sottoconti', $sottoconto_id)['documenti_contabilita_sottoconti_descrizione'];
    }
}

$where = [
    "prime_note_id IN (
        SELECT
            prime_note_registrazioni_prima_nota
        FROM
            prime_note_registrazioni
        WHERE
            prime_note_registrazioni_prima_nota IS NOT NULL AND
            ($where_grid) AND ($where_registrazioni)
    ) AND prime_note_modello <> 1",
];
$limit = 100;
$offset = 0;
$_primeNoteData = [];
while ($_primeNote = $this->prima_nota->getPrimeNoteData($where, $limit, 'prime_note_id ASC', $offset, false, false, $where_registrazioni)) {
    $offset += $limit;
    $_primeNoteData = array_merge($_primeNoteData, $_primeNote);
}
if ($this->input->get('mastro') && false) {
    foreach ($_primeNoteData as $pn_id => $pn) {
        foreach ($pn['registrazioni'] as $registrazione) {
            debug($registrazione, true);
        }
    }
} else if ($this->input->get('conto')) {
    usort($_primeNoteData, function ($a, $b) {

        $sottoconto_a = $a['registrazioni'][0]['sottocontodare_descrizione'] ?: $a['registrazioni'][0]['sottocontoavere_descrizione'];
        $sottoconto_b = $b['registrazioni'][0]['sottocontodare_descrizione'] ?: $b['registrazioni'][0]['sottocontoavere_descrizione'];

        return ($sottoconto_a < $sottoconto_b) ? -1 : 1;
    });
    $primeNoteData = $_primeNoteData;
} else {
    $primeNoteData = $_primeNoteData;
}

$settings = $this->apilib->searchFirst('settings');
$azienda = $this->apilib->searchFirst('documenti_contabilita_settings');
//dump($azienda);
$prima_registrazione = $primeNoteData[0]['registrazioni'][0];
$primo_conto = $prima_registrazione['sottocontodare_descrizione'] ?: $prima_registrazione['sottocontoavere_descrizione'];
?>

<style>
    .prima_nota_odd {
        /*background-color: #FF7ffc;*/
        background-color: #eeeeee;
    }

    .prima_nota_odd .table,
    .prima_nota_table_container_even .table {
        /*background-color: #FFAffA;
        background-color: #80b4d3;*/
        background-color: #9ac6e0;
    }



    .js_prime_note tbody tr td {
        padding: 2px;
        border-left: 1px dotted #CCC;
    }

    .js_prime_note tbody tr td:last-child {

        border-right: 1px dotted #CCC;
    }

    .intestazione {
        padding-top: 20px;
        padding-bottom: 30px;
    }

    .intestazione_estratto_conto {
        padding: 10px;
        border-radius: 3px;
        margin-bottom: 20px;
    }

    .intestazione_estratto_conto h3 {
        margin: 0;
        font-size: 22px;
    }

    .js_prime_note thead tr {
        font-size: 14px;
    }

    .js_prime_note tbody tr {
        font-size: 12px;
    }

    .js_prime_note tfoot tr {
        font-size: 14px;
    }
</style>

<!-- CDN Stylesheets -->
<link rel="stylesheet" href="<?php echo base_url("template/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css"); ?>" />

<div>

    <div class="page">

        <div class="container-fluid">

            <div class="row intestazione">
                <div class="col-sm-2">
                    <img src="<?php echo base_url('uploads/' . $azienda['documenti_contabilita_settings_company_logo']); ?>" class="img-responsive" style="max-height: 100px;">
                </div>
                <div class="col-sm-10 text-right">
                    <strong> <?php echo $azienda['documenti_contabilita_settings_company_name']; ?></strong> <br />
                    <?php echo $azienda['documenti_contabilita_settings_company_address'] ?> - <?php echo $azienda['documenti_contabilita_settings_company_city'] ? $azienda['documenti_contabilita_settings_company_city'] : '/' ?> <?php echo $azienda['documenti_contabilita_settings_company_zipcode'] ? $azienda['documenti_contabilita_settings_company_zipcode'] : ''; ?> <?php echo $azienda['documenti_contabilita_settings_company_province'] ? '(' . $azienda['documenti_contabilita_settings_company_province'] . ')' : ''; ?><br />
                    <?php echo t('C.F.'), ': ', $azienda['documenti_contabilita_settings_company_codice_fiscale'] ? $azienda['documenti_contabilita_settings_company_codice_fiscale'] : '/'; ?> - <?php echo t('P.IVA'), ': ', $azienda['documenti_contabilita_settings_company_vat_number'] ? $azienda['documenti_contabilita_settings_company_vat_number'] : '/'; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="intestazione_estratto_conto bg-primary text-uppercase">
                        <h3 class="text-center"><?php echo $titolo; ?></h3>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="intestazione_estratto_conto bg-primary text-uppercase">
                        <h3 class="text-center"><?php echo $primo_conto; ?></h3>
                    </div>
                </div>
            </div>

            <table class="table table-bordered table-condensed js_prime_note">
                <thead>
                    <tr>
                        <th>Pr.</th>
                        <th>Data Reg</th>
                        <th>Riga</th>
                        <th>Doc/Data</th>

                        <th>Causale</th>
                        <th>Conto/descrizione</th>
                        <th>Dare</th>
                        <th>Avere</th>
                        <th>Progressivo</th>
                        <!--<th>Contro conto</th>-->

                    </tr>
                </thead>
                <tbody>
                    <?php
$previus_sottoconto = false;
$i = 0;
$progressivo = 0;
$totale_dare = $totale_avere = 0;
foreach ($primeNoteData as $prime_note_id => $prima_nota): $i++;?>



	                        <?php foreach ($prima_nota["registrazioni"] as $registrazione): ?>
	                            <?php
    $new_conto = $registrazione['sottocontodare_descrizione'] ?: $registrazione['sottocontoavere_descrizione'];
    if ($previus_sottoconto !== false && $previus_sottoconto != $new_conto) {
        $previus_sottoconto = $new_conto;
        //Se è cambiato il conto che sto ciclando, spacco la pagina e creo un break per una nuova tabella.
        ?>
	                </tbody>
	                <tfoot>
	                    <tr>
	                        <th colspan="5"></th>
	                        <th class="text-left text-uppercase">Totali:</th>
	                        <th class="text-left"><?php e_money($totale_dare);?></th>
	                        <th class="text-left"><?php e_money($totale_avere);?></th>
	                        <th class="text-left"><?php e_money($progressivo);?></th>
	                        <!-- <th class="text-left"></th> -->
	                    </tr>
	                </tfoot>
	            </table>



	            <div class="row">
	                <div class="col-sm-12">
	                    <div class="intestazione_estratto_conto bg-primary text-uppercase">
	                        <h3 class="text-center"><?php echo $new_conto; ?></h3>
	                    </div>
	                </div>
	            </div>

	            <table class="table table-bordered table-condensed js_prime_note">
	                <thead>
	                    <tr>
	                        <th>Pr.</th>
	                        <th>Data Reg</th>
	                        <th>Riga</th>
	                        <th>Doc/Data</th>

	                        <th>Causale</th>
	                        <th>Conto/descrizione</th>
	                        <th>Dare</th>
	                        <th>Avere</th>
	                        <th>Progressivo</th>
	                        <!--<th>Contro conto</th>-->

	                    </tr>
	                </thead>
	                <tbody>
	                <?php
    } else {
        $previus_sottoconto = $new_conto;
    }

    $conto_dare = $this->prima_nota->getCodiceCompleto($registrazione, 'dare', '.');
    $conto_avere = $this->prima_nota->getCodiceCompleto($registrazione, 'avere', '.');
    //debug($registrazione);
    $progressivo += $registrazione['prime_note_registrazioni_importo_dare'] - $registrazione['prime_note_registrazioni_importo_avere'];
    $totale_dare += $registrazione['prime_note_registrazioni_importo_dare'];
    $totale_avere += $registrazione['prime_note_registrazioni_importo_avere'];
    ?>

	                <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?>" data-id="<?php echo $prime_note_id; ?>">
	                    <td>
	                        <?php echo ($prima_nota['prime_note_progressivo_annuo']); ?>
	                    </td>
	                    <td>
	                        <?php echo dateFormat($prima_nota['prime_note_data_registrazione']); ?>
	                    </td>
	                    <td>
	                        <?php echo ($registrazione['prime_note_registrazioni_numero_riga']); ?>
	                    </td>
	                    <td>
	                        <?php echo ($registrazione['prime_note_rif_doc'] ?: $prima_nota['prime_note_numero_documento']); ?><br />
	                        <?php if (!empty($prima_nota["documenti_contabilita_data_emissione"])): ?>
	                            <?php echo dateFormat($prima_nota["documenti_contabilita_data_emissione"]); ?>
	                        <?php elseif (!empty($prima_nota["spese_data_emissione"])): ?>
                            <?php echo dateFormat($prima_nota["spese_data_emissione"]); ?>
                        <?php endif;?>
                    </td>


                    <td><?php echo ($prima_nota['prime_note_causali_descrizione']); ?></td>
                    <td>
                        <?php if ($customer_id): ?>
                            <?php echo ($registrazione['prime_note_registrazioni_rif_doc']); ?>
                        <?php else: ?>
                            <?php if ($registrazione['sottocontodare_descrizione']): ?>
                                <?php echo $registrazione['sottocontodare_descrizione']; ?>
                            <?php else: ?>
                                <?php echo $registrazione['sottocontoavere_descrizione']; ?>
                            <?php endif;?>
                        <?php endif;?>

                    </td>
                    <td class="text-danger">
                        <?php echo ($registrazione['prime_note_registrazioni_importo_dare'] > 0) ? number_format($registrazione['prime_note_registrazioni_importo_dare'], 2, ',', '.') : ''; ?>
                    </td>

                    <td class="text-success">
                        <?php echo ($registrazione['prime_note_registrazioni_importo_avere'] > 0) ? number_format($registrazione['prime_note_registrazioni_importo_avere'], 2, ',', '.') : ''; ?>
                    </td>
                    <td>
                        <?php e_money($progressivo);?>
                    </td>
                    <!--<td></td>-->

                    <!-- se associato ad una spesa o a un documento stampare la data di emissione document-->

                </tr>
            <?php endforeach;?>

        <?php endforeach;?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5"></th>
                        <th class="text-left text-uppercase">Totali:</th>
                        <th class="text-left"><?php e_money($totale_dare);?></th>
                        <th class="text-left"><?php e_money($totale_avere);?></th>
                        <th class="text-left"><?php e_money($progressivo);?></th>
                        <!-- <th class="text-left"></th> -->
                    </tr>
                </tfoot>

            </table>
        </div>
    </div>
</div>