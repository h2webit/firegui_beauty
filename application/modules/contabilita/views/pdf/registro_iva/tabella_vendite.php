<?php

$this->load->model('contabilita/prima_nota');

//Escludo i reverse
$data = $this->prima_nota->getIvaData([
    ['prime_note_stampa_definitiva_vendite IS NULL']
]);



extract($data['vendite']);

?>



<script>
window.onload = function() {
    var vars = {};
    var x = document.location.search.substring(1).split('&');
    console.log(x);
    for (var i in x) {
        var z = x[i].split('=', 2);
        vars[z[0]] = unescape(z[1]);
    }

    //if current page number == last page number
    if (vars['page'] == vars['topage']) {
        //document.querySelectorAll('.extra')[0].textContent = 'extra text here';
    }


    // HERE IS THE TRICK TO PLACE WATERMARK
    // HERE BODY ELEMENT IS DIFFERENT FOR EVERY PAGE OF PDF
    if (!document.body.classList.contains('imgActive')) { // CHECK IS IMAGE ALREADY ADDED 
        document.body.classList.add('imgActive');
        var body = document.getElementsByTagName("body")[0];
        console.log(body);

        var img = document.createElement("img");
        img.classList.add('img-responsive');
        img.src = "<?php echo base_url('uploads/test_pdf_bg.png'); ?>";
        body.appendChild(img);
    }
};
</script>



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
    text-align: center;
    border-bottom: solid 1px #cccccc !important;
    font-size: 11px;
}

.totali_documento th {
    text-align: center;
    font-size: 11px;
}

.totali_documento tfoot td {
    font-weight: bold;
    font-size: 12px;
    text-align: right;
}

.new_page {
    display: block !important;
    page-break-after: always !important;
}
</style>


<div style="margin-bottom:30px">

    <?php foreach ($filtri as $filtro) : ?>
    <p><strong><?php echo $filtro['label']; ?></strong>: <?php echo $filtro['value']; ?></p>
    <?php endforeach; ?>

</div>

<div class="new_page">
    <?php foreach ($primeNoteDataGroupSezionale as $sezionale => $primeNoteData) : ?>
    <?php


    if (empty($primeNoteData)) {
        //debug($primeNoteData,true);
        continue;
    } ?>
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
                <th>Totale doc</th>

            </tr>
        </thead>
        <tbody>
            <?php $i = 0;
            foreach ($primeNoteData as $prime_note_id => $prima_nota) :  ?>

            <?php
                foreach ($prima_nota["registrazioni_iva"] as $registrazione) : $i++; ?>
            <?php
if ($registrazione['prime_note_protocollo'] == 61) {
//debug($registrazione);

}

                    $destinatario = json_decode($registrazione['documenti_contabilita_destinatario'], true);
                    ?>

            <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?>" data-id="<?php echo $prime_note_id; ?>">
                <td><?php echo dateFormat($prima_nota['prime_note_data_registrazione']); ?></td>
                <td class="text-center">
                    <?php echo ($registrazione['prime_note_protocollo']); ?>
                </td>
                <td><?php echo (dateFormat($registrazione['prime_note_scadenza'])); ?></td>
                <td><?php 
                        $expl = explode(' - ',$registrazione['prime_note_numero_documento']);
                        if ($expl) {
                            $numero_documento = $expl[0];
                                                    } else {
                            $numero_documento = '';
                        }
                        echo $numero_documento; 
                        
                        ?>
                </td>
                <td class="text-center">
                    <?php echo ($registrazione['prime_note_progressivo_annuo']); ?>
                </td>
                <td>
                    <?php echo ($registrazione['sezionali_iva_sezionale']); ?>

                </td>
                <td>
                    <?php echo (($destinatario) ? $destinatario['ragione_sociale'] : ''); ?>
                </td>
                <td class="text-center">
                    <?php echo (($destinatario) ? $destinatario['partita_iva'] : ''); ?>
                </td>

                <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                    <?php e_money($registrazione['prime_note_righe_iva_imponibile'], '€ {number}'); ?>

                </td>

                <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                    <?php e_money($registrazione['prime_note_righe_iva_importo_iva'], '€ {number}'); ?>
                </td>

                <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                    <?php echo ($registrazione['iva_label']); ?>
                </td>

                <td class="<?php if ($registrazione['prime_note_righe_iva_imponibile'] > 0) : ?>text-success<?php else: ?>text-danger<?php endif; ?> text-right">
                    <?php e_money($registrazione['documenti_contabilita_totale'], '€ {number}'); ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php endforeach; ?>


            <tr>
                <td colspan="8">
                    <strong>TOTALI (Sezionale <?php echo $sezionale; ?>)</strong>
                </td>
                <td style="text-align:right;">
                    <strong><?php e_money( $totali_per_sezionale[$sezionale]['imponibile'], '€ {number}'); ?>
                    </strong>
                </td>
                <td style="text-align:right;">
                    <strong><?php e_money( $totali_per_sezionale[$sezionale]['imposta'], '€ {number}'); ?>
                    </strong>
                </td>
                <td colspan=2></td>
            </tr>

        </tbody>

    </table>

    <hr />


    <?php endforeach; ?>

    <table class="table slim_table">
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
    </table>
</div>
<!-- Specchietto Riepilogativo Totali -->

<!-- <div class="breakpage" style="page-break-after: always !important;"> -->
<div class="breakpage">

    <h2 style=" margin-top:70px; text-align:center;">Riepilogo totali</h2>
    <table class="table totali_documento ">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th colspan="2">ITALIA</th>

                <th colspan="2">Intra UE</th>
                <th colspan="2">Extra UE</th>
                <th colspan="2">SPLIT PAYMENT</th>
            </tr>
            <tr>
                <th>Cod</th>
                <th width="250px">Descrizione</th>
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
            <?php //debug($totali); 
        ?>
            <?php foreach ($totali as $totale) : ?>


            <tr>
                <!-- Cod-->
                <td><?php echo $totale['iva_id']; ?></td>
                <!-- Descrizione iva -->
                <td style="text-align:left"><?php echo $totale['iva_descrizione'];  ?></td>
                <!-- perc iva-->
                <td><?php echo number_format($totale['iva_valore'], 0); ?>%</td>
                <!-- indetraibilitò-->
                <td><?php echo number_format($totale['iva_percentuale_indetraibilita'], 0); ?>%</td>
                <!-- italia imponibile-->
                <td>
                    <?php e_money($totale['italia']['imponibile'], '€ {number}'); ?>
                </td>
                <!-- italia imposta-->
                <td>


                    <?php e_money($totale['italia']['imposta'], '€ {number}'); ?>

                </td>

                <!-- intra ue imponibile-->
                <td>
                    <?php e_money($totale['intra']['imponibile'], '€ {number}'); ?>
                </td>
                <!-- intra ue imponibile-->
                <td>
                    <?php e_money($totale['intra']['imposta'], '€ {number}'); ?>
                </td>

                <!-- extra ue imponibile-->
                <td>
                    <?php e_money($totale['extra']['imponibile'], '€ {number}'); ?>
                </td>
                <!-- extra ue imponibile-->
                <td>
                    <?php e_money($totale['extra']['imposta'], '€ {number}'); ?>
                </td>



                <!-- split payment imponibile-->
                <td>
                    <?php e_money($totale['split']['imponibile'], '€ {number}'); ?>

                </td>
                <!-- split payment imposta-->
                <td>
                    <?php e_money($totale['split']['imposta'], '€ {number}'); ?>

                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="t_foot_totali">
            <tr>
                <td colspan="2">Totali</td>
                <td></td>
                <td></td>
                <!-- italia imponibile -->
                <td><?php e_money($totale_italia_imponibile, '€ {number}'); ?></td>
                <!-- italia imposta -->
                <td><?php e_money($totale_italia_imposta, '€ {number}'); ?></td>

                <!-- intra imponibile -->
                <td><?php e_money($totale_intra_imponibile, '€ {number}'); ?></td>
                <!-- intra imposta -->
                <td><?php e_money($totale_intra_imposta, '€ {number}'); ?></td>

                <!-- extra imponibile -->
                <td><?php e_money($totale_extra_imponibile, '€ {number}'); ?></td>
                <!-- extra imposta -->
                <td><?php e_money($totale_extra_imposta, '€ {number}'); ?></td>

                <!-- split imponibile -->
                <td><?php e_money($totale_split_imponibile, '€ {number}'); ?></td>
                <!-- split imposta -->
                <td><?php e_money($totale_split_imposta, '€ {number}'); ?></td>
            </tr>
            <tr>
                <td colspan="6">
                    <h4>TOTALE IMPONIBILE: € <?php e_money($imponibili); ?></h4>
                </td>
                <td colspan="6">
                    <h4>TOTALE IMPOSTA: € <?php e_money($imposte); ?></h4>
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>



    <h2 style="margin-top:70px; text-align:center;">Riepilogo per sezionale</h2>
    <table class="table totali_documento slim_table">
        <thead>
            <tr>
                <th>Sezionale</th>
                <th>Imponibile</th>
                <th>imposta</th>
            </tr>
        </thead>
        <tbody>
            <?php //debug($totali); 
        ?>
            <?php foreach ($totali_per_sezionale as $sezionale => $totale) : ?>


            <tr>
                <!-- Cod-->
                <td> <strong><?php echo $sezionale; ?></strong></td>

                <td>
                    <strong> <?php e_money($totale['imponibile'], '€ {number}'); ?></strong>
                </td>
                <!-- italia imposta-->
                <td>


                    <strong><?php e_money($totale['imposta'], '€ {number}'); ?></strong>

                </td>


            </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
</div>