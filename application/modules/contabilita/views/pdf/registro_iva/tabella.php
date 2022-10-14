<?php

$this->load->model('contabilita/prima_nota');

// Filtro tabella e dati prima nota
$grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
$where = [$this->datab->generate_where("grids", $grid['grids_id'], null)];

if ($this->input->get('vendite')) {

    $where[] = "prime_note_documento IS NOT NULL AND prime_note_documento <> ''";
} else {
    $where[] = "prime_note_spesa IS NOT NULL AND prime_note_spesa <> ''";
}

$primeNoteData = $this->prima_nota->getPrimeNoteData($where, 10, 0, false, true);

// Dati filtri impostati
$filters = $this->session->userdata(SESS_WHERE_DATA);

// Costruisco uno specchietto di filtri autogenerati leggibile
$filtri = array();

if (!empty($filters["filter_stampe_contabili"])) {
    foreach ($filters["filter_stampe_contabili"] as $field) {
        $filter_field = $this->datab->get_field($field["field_id"], true);
        // debug($filter_field);

        // Se ha una entità/support collegata
        if ($filter_field['fields_ref']) {

            $entity_data = $this->crmentity->getEntityPreview($filter_field['fields_ref']);
            $filtri[] = array("label" => $filter_field["fields_draw_label"], "value" => $entity_data[$field['value']]);
        } else {
            $filtri[] = array("label" => $filter_field["fields_draw_label"], "value" => $field['value']);
        }
    }
}

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

    .js_prime_note {
        font-size: 0.7em;
    }

    .js_prime_note tbody tr td {
        padding: 2px;
        border-left: 1px dotted #CCC;
    }

    .js_prime_note tbody tr td:last-child {

        border-right: 1px dotted #CCC;
    }
</style>

<div style="margin-bottom:30px">

    <?php foreach ($filtri as $filtro) : ?>
        <p><strong><?php echo $filtro['label']; ?></strong>: <?php echo $filtro['value']; ?></p>
    <?php endforeach; ?>

</div>

<h2><?php echo ($this->input->get('vendite')) ? 'Vendite' : 'Acquisti'; ?></h2>

<table class="table js_prime_note slim_table">
    <thead>
        <tr>
            <th>Riga</th>
            <th>Data</th>
            <th>Prot.</th>
            <th>Data doc</th>
            <th>Rif. reg</th>
            <th>Sez</th>
            <th>Rag. soc.</th>
            <th>Partita iva</th>
            <th>Imponibile</th>
            <th>Iva</th>
            <th>Imposta</th>
            <th>Desc</th>
            <th>Ind.</th>
            <th>Totale doc</th>

        </tr>
    </thead>
    <tbody>
        <?php $i = 0;
        foreach ($primeNoteData as $prime_note_id => $prima_nota) : $i++; ?>


            <?php foreach ($prima_nota["registrazioni_iva"] as $registrazione) : ?>
                <?php


                if ($this->input->get('vendite')) {
                    $destinatario = json_decode($registrazione['documenti_contabilita_destinatario'], true);
                } else {
                    $destinatario = json_decode($registrazione['spese_fornitore'], true);
                }

                ?>

                <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?>" data-id="<?php echo $prime_note_id; ?>">
                    <td></td>
                    <td><?php echo dateFormat($prima_nota['prime_note_data_registrazione']); ?></td>
                    <td class="text-center">
                        <?php echo ($registrazione['prime_note_protocollo']); ?>
                    </td>
                    <td><?php echo (dateFormat($registrazione['prime_note_scadenza'])); ?></td>
                    <td class="text-center">

                        <?php echo ($registrazione['prime_note_progressivo_annuo']); ?>

                    </td>
                    <td>
                        <?php echo ($registrazione['sezionali_iva_sezionale']); ?>

                    </td>
                    <td>
                        <?php echo ($destinatario['ragione_sociale']); ?>
                    </td>
                    <td class="text-center">
                        <?php echo ($destinatario['partita_iva']); ?>
                    </td>

                    <td class="text-success text-right">
                        <?php echo ($registrazione['prime_note_righe_iva_imponibile'] > 0) ? number_format($registrazione['prime_note_righe_iva_imponibile'], 2, ',', '.') : ''; ?>
                    </td>
                    <td class="text-center">

                        <?php echo (int)$registrazione['iva_valore']; ?>%
                    </td>

                    <td class="text-success">
                        <?php echo ($registrazione['prime_note_righe_iva_importo_iva'] > 0) ? number_format($registrazione['prime_note_righe_iva_importo_iva'], 2, ',', '.') : ''; ?>
                    </td>

                    <td>
                        <?php echo ($registrazione['iva_label']); ?>
                    </td>
                    <td>
                        <?php echo ($registrazione['prime_note_righe_iva_indetraibilie_perc']) ? ((int)$registrazione['prime_note_righe_iva_indetraibilie_perc'] . '%') : ''; ?>
                    </td>
                    <td class="text-right">
                        <?php echo ($registrazione['documenti_contabilita_totale'] > 0) ? number_format($registrazione['documenti_contabilita_totale'], 2, ',', '.') : ''; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php endforeach; ?>
    </tbody>
</table>