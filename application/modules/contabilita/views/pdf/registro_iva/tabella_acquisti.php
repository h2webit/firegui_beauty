<?php

$this->load->model('contabilita/prima_nota');

$data = $this->prima_nota->getIvaData(
    ['prime_note_stampa_definitiva_acquisti IS NULL']
);
//debug($data['acquisti'], true);


extract($data['acquisti']);
//debug($totali_per_sezionale,true);
//Accorpo le iva a zero in un'unica voce
$_totali = $totali;
$totali = $iva_zero = [];
foreach ($_totali as $totale) {
    if ($totale['iva_valore'] == 0) {
        $iva_zero[] = $totale;
        if (array_key_exists('iva_zero', $totali)) {
            foreach (['intra', 'italia', 'reverse', 'indetraibile', 'split'] as $tipo) {
                $totali['iva_zero'][$tipo]['imponibile'] += $totale[$tipo]['imponibile'];
                $totali['iva_zero'][$tipo]['imposta'] += $totale[$tipo]['imposta'];
            }
        } else {
            $totali['iva_zero'] = $totale;
        }
    } else {
        $totali[] = $totale;
    }
    //debug($totale, true);
}
if (!empty($totali['iva_zero'])) {
    $totali['iva_zero']['iva_descrizione'] = '0%';
    $totali['iva_zero']['iva_label'] = 'Iva 0%';
}

?>
<style>
    .container {
        margin: 0 auto;
        width: 100%;
        font-size: 1em;
    }

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

    .js_prime_note {
        font-size: 0.7em;
    }

    .totali_documento {
        font-size: 0.9em;

    }

    .js_prime_note tbody tr td {
        padding: 2px;
        border-left: 1px dotted #CCC;
    }

    .js_prime_note tbody tr td:last-child {

        border-right: 1px dotted #CCC;
    }
.breakpage {
        page-break-before: always !important;
    }
    .totali_documento td {
        text-align: right;
        border-bottom: solid 1px #cccccc !important;
        font-size: 11px;
    }

    .totali_documento th {
        text-align: center;
        font-size: 11px;
    }

    .totali_documento tfoot td {
        font-weight: bold;
        font-size: 14px;
        text-align: right;
    }
</style>

<div style="margin-bottom:30px">

    <?php foreach ($filtri as $filtro) : ?>
        <p><strong><?php echo $filtro['label']; ?></strong>: <?php echo $filtro['value']; ?></p>
    <?php endforeach; ?>

</div>

<?php foreach ($primeNoteDataGroupSezionale as $sezionale => $primeNoteData) : ?>

    

    <h5 class="breakpage">Sezionale n.<?php echo (!empty($primeNoteData[0])) ? $primeNoteData[0]['prime_note_sezionale'] . " " . $sezionale : '-'; ?></h5>
    <table class="table js_prime_note slim_table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Prot.</th>
                <th>Data doc</th>
                <th>Num. doc</th>
                <th>Rif. reg</th>
                <th>Sez</th>
                <th>Rag. soc.</th>
                <th>Partita iva</th>
                <th>Imponibile</th>
                
                <th>Imposta</th>
                <th>Descr. IVA</th>
                <th>Ind.</th>
                <th>Totale doc</th>

            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            foreach ($primeNoteData as $prime_note_id => $prima_nota) :  ?>


                <?php
                foreach ($prima_nota["registrazioni_iva"] as $registrazione) : $i++; ?>
                    <?php
                    $destinatario = json_decode($registrazione['spese_fornitore'], true);
                    if (!$destinatario) {
                        foreach ($prima_nota['registrazioni'] as $_riga) {
                            if ($_riga['sottocontoavere']) {

                                $fornitore = $this->apilib->searchFirst('customers', ['customers_sottoconto' => $_riga['prime_note_registrazioni_sottoconto_avere']]);
                                if (!$fornitore) {
                                    $fornitore = $this->apilib->searchFirst('customers', ['customers_codice_sottoconto' => "{$_riga['mastroavere']}.{$_riga['contoavere']}.{$_riga['sottocontoavere']}"]);
                                }
                                $destinatario = [
                                    'ragione_sociale' => $_riga['sottocontoavere_descrizione'],
                                    'partita_iva' => $fornitore['customers_vat_number'],
                                ];
                                break;
                            }
                        }
                    }
                    ?>

                    <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?>" data-id="<?php echo $prime_note_id; ?>">
                        <td><?php echo dateFormat($prima_nota['prime_note_data_registrazione']); ?></td>
                        <td class="text-center">
                            <?php echo ($registrazione['prime_note_protocollo']); ?>
                        </td>
                        <td><?php echo (dateFormat($registrazione['prime_note_scadenza'])); ?></td>
                        <td><?php //debug($registrazione);
                            echo ($registrazione['prime_note_numero_documento']) ? $registrazione['prime_note_numero_documento'] : $registrazione['spese_numero']; ?></td>

                        <td class="text-center">

                            <?php echo ($registrazione['prime_note_progressivo_giornaliero']); ?>

                        </td>
                        <td>
                            <?php echo ($registrazione['sezionali_iva_sezionale']); ?>

                        </td>
                        <td>
                            <?php echo (($destinatario) ? $destinatario['ragione_sociale'] : debug($registrazione)); ?>
                        </td>
                        <td class="text-center">
                            <?php echo (($destinatario) ? $destinatario['partita_iva'] : ''); ?>
                        </td>

                        <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                            <?php e_money($registrazione['prime_note_righe_iva_imponibile'], '€ {number}'); ?>
                        </td>
                        

                        <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                            <?php
                            e_money($registrazione['prime_note_righe_iva_importo_iva'], '€ {number}');
                            ?>
                        </td>


                        <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                            <?php echo ($registrazione['iva_label']); ?>
                        </td>
                        <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                            <?php echo ($registrazione['prime_note_righe_iva_indetraibilie_perc']) ? ((int)$registrazione['prime_note_righe_iva_indetraibilie_perc'] . '%') : ''; ?>
                        </td>
                        <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                            <!-- TODO Dubbio perche registrando una prima nota manuale di un documento cartaceo questo dato sarebbe vuoto? -->
                            € <?php echo ($registrazione['spese_totale'] > 0) ? number_format($registrazione['spese_totale'], 2, ',', '.') : ''; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php endforeach; ?>

            <tr>
                    <td colspan="8">
                        <strong>TOTALI (Sezionale <?php echo $sezionale; ?>)</strong>
                    </td>
                    <td  style="text-align:right;">
                        <strong><?php e_money( $totali_per_sezionale[$sezionale]['imponibile'], '€ {number}'); ?>
            </strong>
            </td>
               <td  style="text-align:right;">
                             <strong><?php e_money( $totali_per_sezionale[$sezionale]['imposta'], '€ {number}'); ?>
            </strong>
            </td>
            <td colspan=2></td>
            </tr>
        </tbody>
    </table>
    <hr />



    <h3 style="text-align:center;">Riepilogo totali sezionale (<?php echo $sezionale; ?>)</h3>
<table style="margin-bottom:150px; " class="table totali_documento slim_table">
    <thead>
        <tr>
            
            
            <th colspan="" style="text-align: right;">IMPONIBILE TOTALE</th>
            <th colspan="" style="text-align: right;">IMPOSTA TOTALE</th>
            
            <th colspan="" style="text-align: right;">IMPOSTA DETRAIBILE</th>
            <th colspan="" style="text-align: right;">IMPOSTA INDETRAIBILE</th>

        </tr>
        
    </thead>
    <tbody>

   
            <tr>
                
               
                <!-- italia imponibile-->
                <td><?php e_money($totali_per_sezionale[$sezionale]['imponibile'], '€ {number}');                    ?></td>
                <!-- italia imposta-->
                <td><?php e_money($totali_per_sezionale[$sezionale]['imposta'], '€ {number}');                    ?></td>
                <!-- indetraibile imponibile-->
                <td><?php e_money($totali_per_sezionale[$sezionale]['detraibile']['imposta'], '€ {number}');                    ?></td>
                <td><?php e_money($totali_per_sezionale[$sezionale]['indetraibile']['imposta'], '€ {number}');                    ?></td>
            </tr>
        
        
    </tbody>
    
</table>


<?php endforeach; ?>
    <!-- <table class="table slim_table">
        <thead>
            <tr>
                <th>TOTALI:</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>

                <th>Imponibile:</th>
                <th>
                    <?php e_money($imponibili, '€ {number}'); ?>
                </th>
                <th>Imposta:</th>
            <th>

                <?php e_money($imposte, '€ {number}'); ?>
            </th>
            <th>Totale:</th>
            <th>

                <?php e_money($imponibili + $imposte, '€ {number}'); ?>
            </th>
            </tr>
        </thead>
    </table> -->

<!-- Specchietto Riepilogativo Totali -->


<?php 
$totali_accorpati = [];
foreach ($totali as $iva) {
    $iva['iva_valore'] = (int)$iva['iva_valore'];
    if (empty($totali_accorpati[$iva['iva_valore']])) {
        $totali_accorpati[$iva['iva_valore']] = $iva;
    } else {
        $totali_accorpati[$iva['iva_valore']]['intra']['imponibile'] += $iva['intra']['imponibile'];
        $totali_accorpati[$iva['iva_valore']]['intra']['imposta']+= $iva['intra']['imposta'];

        $totali_accorpati[$iva['iva_valore']]['italia']['imponibile'] += $iva['italia']['imponibile'];
        $totali_accorpati[$iva['iva_valore']]['italia']['imposta']+= $iva['italia']['imposta'];

        $totali_accorpati[$iva['iva_valore']]['reverse']['imponibile'] += $iva['reverse']['imponibile'];
        $totali_accorpati[$iva['iva_valore']]['reverse']['imposta']+= $iva['reverse']['imposta'];

        $totali_accorpati[$iva['iva_valore']]['indetraibile']['imponibile'] += $iva['indetraibile']['imponibile'];
        $totali_accorpati[$iva['iva_valore']]['indetraibile']['imposta']+= $iva['indetraibile']['imposta'];

        $totali_accorpati[$iva['iva_valore']]['split']['imponibile'] += $iva['split']['imponibile'];
        $totali_accorpati[$iva['iva_valore']]['split']['imposta']+= $iva['split']['imposta'];
    }
    if ($iva['iva_valore'] == 22) {
        //debug($totali_accorpati[$iva['iva_valore']]);
    }

}

?>


<h2 style="margin-top:70px; text-align:center;">Riepilogo totali</h2>
<table class="table totali_documento slim_table">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            
            <th colspan="2">ITALIA</th>
            <th colspan="2">INDETRAIBILE</th>
            
            <th colspan="2">INTRA</th>
            <th colspan="2">EXTRA</th>

        </tr>
        <tr>
            
            <th>Descrizione</th>
            <th>%%</th>
            <th>Ind.</th>
            <th>Imponibile</th>
            <th>Imposta</th>
            
            <th>Imponibile</th>
            <th>Imposta</th>
            <th>Imponibile</th>
            <th>Imposta</th>
<th>Imponibile</th>
            <th>Imposta</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($totali_accorpati as $totale) : ?>
            <tr>
                
                <!-- Descrizione iva -->
                <td style="text-align:left"><?php echo (int)$totale['iva_valore'];  ?>%</td>
                <!-- perc iva-->
                <td><?php echo number_format($totale['iva_valore'], 0); ?>%</td>
                <!-- indetraibilitò-->
                <td><?php echo number_format($totale['iva_percentuale_indetraibilita'], 0); ?>%</td>
                <!-- italia imponibile-->
                <td><?php e_money($totale['italia']['imponibile'], '€ {number}');
                    ?></td>
                <!-- italia imposta-->
                <td><?php e_money($totale['italia']['imposta'], '€ {number}');
                    ?></td>
                <!-- indetraibile imponibile-->
                <td><?php e_money($totale['indetraibile']['imponibile'], '€ {number}');
                    ?></td>
                <!-- indetraibile imposta-->
                <td><?php e_money($totale['indetraibile']['imposta'], '€ {number}');
                    ?></td>
                
                
                <!-- intra imponibile-->
                <td><?php e_money($totale['intra']['imponibile'], '€ {number}');
                    ?></td>
                <!-- extra imponibile-->
                <td><?php e_money($totale['intra']['imposta'], '€ {number}');
                    ?></td>
                <td><?php e_money($totale['extra']['imponibile'], '€ {number}');
                    ?></td>
                <!-- extra imponibile-->
                <td><?php e_money($totale['extra']['imposta'], '€ {number}');
                    ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">Totali</td>
            <!-- italia imponibile -->
            <td><?php e_money($totale_italia_imponibile, '€ {number}'); ?></td>
            <!-- italia imposta -->
            <td><?php e_money($totale_italia_imposta, '€ {number}'); ?></td>
            <!-- indetraibile imponibile -->
            <td><?php e_money($totale_indetraibile_imponibile, '€ {number}'); ?></td>
            <!-- indetraibile imposta -->
            <td><?php e_money($totale_indetraibile_imposta, '€ {number}'); ?></td>
            
            <!-- intra imponibile -->
            <td><?php e_money($totale_intra_imponibile, '€ {number}'); ?></td>
            <!-- intra imposta -->
            <td><?php e_money($totale_intra_imposta, '€ {number}'); ?></td>
            <!-- extra imponibile -->
            <td><?php e_money($totale_extra_imponibile, '€ {number}'); ?></td>
            <!-- extra imposta -->
            <td><?php e_money($totale_extra_imposta, '€ {number}'); ?></td>
        </tr>
        <tr>
            <td colspan="5">
                
                <h4>TOTALE IMPONIBILE DETRAIBILE: € <?php 
                    echo $totale_italia_imponibile 
                        
                        + $totale_intra_imponibile
                        + $totale_extra_imponibile 
                        
                        ?></h4>
            </td>
            <td colspan="6">
                <h4>TOTALE IMPOSTA DETRAIBILE: € <?php echo 
                    $totale_italia_imposta 
                    
                    + $totale_intra_imposta
                    + $totale_extra_imposta
                    
                    ?></h4>
            </td>

        </tr>
    </tfoot>
</table>




<h2 style="margin-top:70px; text-align:center;">Totali IVA 0% - dettaglio</h2>
<table class="table totali_documento slim_table">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th colspan="2">ITALIA</th>
            <th colspan="2">INDETRAIBILE</th>
            
            <th colspan="2">INTRA</th>
            <th colspan="2">EXTRA</th>

        </tr>
        <tr>
            <th>Cod</th>
            <th>Descrizione</th>
            <th>%%</th>
            <th>Ind.</th>
            <th>Imponibile</th>
            <th>Imposta</th>
            <th>Imponibile</th>
            <th>Imposta</th>
            
            <th>Imponibile</th>
            <th>Imposta</th>

            <th>Imponibile</th>
            <th>Imposta</th>

        </tr>
    </thead>
    <tbody>

        <?php foreach ($iva_zero as $totale) : ?>
            <tr>
                <!-- Cod-->
                <td><?php echo $totale['iva_id']; ?></td>
                <!-- Descrizione iva -->
                <td style="text-align:left"><?php echo $totale['iva_label'];  ?></td>
                <!-- perc iva-->
                <td><?php echo number_format($totale['iva_valore'], 0); ?>%</td>
                <!-- indetraibilitò-->
                <td><?php echo number_format($totale['iva_percentuale_indetraibilita'], 0); ?>%</td>
                <!-- italia imponibile-->
                <td><?php e_money($totale['italia']['imponibile'], '€ {number}');
                    ?></td>
                <!-- italia imposta-->
                <td><?php e_money($totale['italia']['imposta'], '€ {number}');
                    ?></td>
                <!-- indetraibile imponibile-->
                <td><?php e_money($totale['indetraibile']['imponibile'], '€ {number}');
                    ?></td>
                <!-- indetraibile imposta-->
                <td><?php e_money($totale['indetraibile']['imposta'], '€ {number}');
                    ?></td>
               
                <!-- intra imponibile-->
                <td><?php e_money($totale['intra']['imponibile'], '€ {number}');
                    ?></td>
                <!-- intra imponibile-->
                <td><?php e_money($totale['intra']['imposta'], '€ {number}');
                    ?></td>
     <!-- extra imponibile-->
     <td><?php e_money($totale['extra']['imponibile'], '€ {number}');
                    ?></td>
                <!-- extra imponibile-->
                <td><?php e_money($totale['extra']['imposta'], '€ {number}');
                    ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>

</table>
