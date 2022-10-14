<?php

$this->load->model('contabilita/prima_nota');
if ($customer_id) {
    $customer = $this->apilib->view('customers', $customer_id);
    $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND 
    (prime_note_registrazioni_sottoconto_dare = '{$customer['customers_sottoconto']}'
    OR 
    prime_note_registrazioni_sottoconto_avere = '{$customer['customers_sottoconto']}')
";
} else {
    //Se non sto stampando un estratto conto cliente, mi aspetto di ricevere i filtri in get (mastro, conto o sottoconto)

    if ($mastro_id = $this->input->get('mastro')) {

        $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND 
            (    prime_note_registrazioni_mastro_dare = '{$mastro_id}'
                OR 
                prime_note_registrazioni_mastro_avere = '{$mastro_id}')
            ";
        $customer['customers_company'] = $this->apilib->view('documenti_contabilita_mastri', $mastro_id)['documenti_contabilita_mastri_descrizione'];
    }
    if ($conto_id = $this->input->get('conto')) {
        $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND 
                (prime_note_registrazioni_conto_dare = '{$conto_id}'
                OR 
                prime_note_registrazioni_conto_avere = '{$conto_id}')
            ";
        $customer['customers_company'] = $this->apilib->view('documenti_contabilita_conti', $conto_id)['documenti_contabilita_conti_descrizione'];
    }
    if ($sottoconto_id = $this->input->get('sottoconto')) {


        $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND 
                (prime_note_registrazioni_sottoconto_dare = '{$sottoconto_id}'
                OR 
                prime_note_registrazioni_sottoconto_avere = '{$sottoconto_id}')
            ";
        $customer['customers_company'] = $this->apilib->view('documenti_contabilita_sottoconti', $sottoconto_id)['documenti_contabilita_sottoconti_descrizione'];
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
            $where_registrazioni
    ) AND prime_note_modello <> 1"
];
$primeNoteData = $this->prima_nota->getPrimeNoteData($where, 10, 'prime_note_id ASC', 0, false, false, $where_registrazioni);
//$primeNoteData = $this->prima_nota->getPrimeNoteData($where, 10, 'prime_note_id ASC', 0, false, false);

$settings = $this->apilib->searchFirst('settings');
$azienda = $this->apilib->searchFirst('documenti_contabilita_settings');
//dump($azienda);
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
                        <h3 class="text-center"><?php echo $customer['customers_company'] ?> - Estratto conto</h3>
                    </div>
                </div>
            </div>


            <table class="table table-bordered table-condensed js_prime_note">
                <thead>
                    <tr>
                        <th>Pr.</th>
                        <th>Data Reg</th>
                        <th>Riga</th>
                        <th>Doc</th>
                        <th>Data doc</th>
                        <th>Conto</th>
                        <th>Dare</th>
                        <th>Avere</th>
                        <th>Progressivo</th>
                        <!--<th>Contro conto</th>-->

                    </tr>
                </thead>
                <tbody>
                    <?php $i = 0;
                    $progressivo = 0;
                    $totale_dare = $totale_avere = 0;
                    foreach ($primeNoteData as $prime_note_id => $prima_nota) : $i++; ?>


                        <?php foreach ($prima_nota["registrazioni"] as $registrazione) : ?>
                            <?php
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
                                    <?php echo ($registrazione['prime_note_rif_doc']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($prima_nota["documenti_contabilita_data_emissione"])) : ?>
                                        <?php echo dateFormat($prima_nota["documenti_contabilita_data_emissione"]); ?>
                                    <?php elseif (!empty($prima_nota["spese_data_emissione"])) : ?>
                                        <?php echo dateFormat($prima_nota["spese_data_emissione"]); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($registrazione['sottocontodare_descrizione']) : ?>
                                        <?php echo $registrazione['sottocontodare_descrizione']; ?>
                                    <?php else : ?>
                                        <?php echo $registrazione['sottocontoavere_descrizione']; ?>
                                    <?php endif; ?>

                                </td>
                                <td class="text-danger">
                                    <?php echo ($registrazione['prime_note_registrazioni_importo_dare'] > 0) ?  number_format($registrazione['prime_note_registrazioni_importo_dare'], 2, ',', '.') : ''; ?>
                                </td>

                                <td class="text-success">
                                    <?php echo ($registrazione['prime_note_registrazioni_importo_avere'] > 0) ?  number_format($registrazione['prime_note_registrazioni_importo_avere'], 2, ',', '.') : ''; ?>
                                </td>
                                <td>
                                    <?php e_money($progressivo); ?>
                                </td>
                                <!--<td></td>-->

                                <!-- se associato ad una spesa o a un documento stampare la data di emissione document-->

                            </tr>
                        <?php endforeach; ?>

                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5"></th>
                        <th class="text-left text-uppercase">Totali:</th>
                        <th class="text-left"><?php e_money($totale_dare); ?></th>
                        <th class="text-left"><?php e_money($totale_avere); ?></th>
                        <th class="text-left"><?php e_money($progressivo); ?></th>
                        <!-- <th class="text-left"></th> -->
                    </tr>
                </tfoot>

            </table>
        </div>
    </div>
</div>