<?php
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
    <?php if (!empty($titolo)): ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="intestazione_estratto_conto bg-success text-uppercase">
                        <h3 class="text-center"><?php echo $titolo; ?></h3>
                    </div>
                </div>
            </div>
<?php endif;?>

    <?php foreach ($primeNoteData as $conto => $dati): ?>
        <?php
//debug($dati, true);
$progressivo = $i = 0;
$totale_dare = $totale_avere = 0;
?>
         <div class="row">
                <div class="col-sm-12">
                    <div class="intestazione_estratto_conto bg-primary text-uppercase">
                        <h3 class="text-center"><?php echo $conto; ?></h3>
                    </div>
                </div>
            </div>
            <table class="table table-bordered table-condensed js_prime_note">
                <thead>
                    <tr>
                        <th>Pr.</th>
                        <th>Data doc</th>
                        <th>Riga</th>
                        <th>Doc/Data</th>

                        <th>Causale</th>
                        <!--<th>Conto/descrizione</th>-->
                        <th>Dare</th>
                        <th>Avere</th>
                        <th>Progressivo</th>
                        <!--<th>Contro conto</th>-->

                    </tr>
                </thead>
                <tbody>

        <?php foreach ($dati['registrazioni'] as $registrazione): ?>

            <?php
$i++;
$conto_dare = $this->prima_nota->getCodiceCompleto($registrazione, 'dare', '.');
$conto_avere = $this->prima_nota->getCodiceCompleto($registrazione, 'avere', '.');
//debug($registrazione);
$progressivo += $registrazione['prime_note_registrazioni_importo_dare'] - $registrazione['prime_note_registrazioni_importo_avere'];
$totale_dare += $registrazione['prime_note_registrazioni_importo_dare'];
$totale_avere += $registrazione['prime_note_registrazioni_importo_avere'];

?>
            <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?>" data-id="<?php echo $prime_note_id; ?>">
                                    <td>
                                        <?php echo ($registrazione['prime_note_progressivo_annuo']); ?>
                                    </td>
                                    <td>
                                        <?php echo dateFormat($registrazione['prime_note_scadenza']); ?>
                                    </td>
                                    <td>
                                        <?php echo ($registrazione['prime_note_registrazioni_numero_riga']); ?>
                                    </td>
                                    <td>
                                        <?php //debug($registrazione, true);
echo ($registrazione['prime_note_rif_doc'] ?: $registrazione['prime_note_numero_documento']); ?><br />
                                        <?php if (!empty($registrazione["documenti_contabilita_data_emissione"])): ?>
                                            <?php echo dateFormat($registrazione["documenti_contabilita_data_emissione"]); ?>
                                        <?php elseif (!empty($registrazione["spese_data_emissione"])): ?>
                            <?php echo dateFormat($registrazione["spese_data_emissione"]); ?>
                        <?php endif;?>
                    </td>


                    <td><?php echo ($registrazione['prime_note_causali_descrizione']); ?></td>
                    <!--<td>
                        <?php if ($customer_id): ?>
                            <?php echo ($registrazione['prime_note_registrazioni_rif_doc']); ?>
                        <?php else: ?>
                            <?php if ($registrazione['sottocontodare_descrizione']): ?>
                                <?php echo $registrazione['sottocontodare_descrizione']; ?>
                            <?php else: ?>
                                <?php echo $registrazione['sottocontoavere_descrizione']; ?>
                            <?php endif;?>
                        <?php endif;?>

                    </td>-->
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
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4"></th>
            <th class="text-left text-uppercase">Totali:</th>
            <th class="text-left"><?php e_money($totale_dare);?></th>
            <th class="text-left"><?php e_money($totale_avere);?></th>
            <th class="text-left"><?php e_money($progressivo);?></th>
            <!-- <th class="text-left"></th> -->
        </tr>
    </tfoot>
    </table>
    <?php endforeach;?>

        </div>
    </div>
</div>