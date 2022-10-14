<?php

$filtro_fatture = @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];

$where_documenti = $where_spese = ["1=1"];

if (!empty($filtro_fatture)) {
    foreach ($filtro_fatture as $key => $filtro) {
        $field_id = $filtro['field_id'];
        $value = $filtro['value'];
        if ($value !== '' && $value != -1) {
            $field_data = $this->db->query("SELECT * FROM fields WHERE fields_id = '$field_id'")->row_array();
            //debug($filtro_fatture);
            $field_name = $field_data['fields_name'];
            switch ($field_name) {
                case 'documenti_contabilita_data_emissione': //Data emissione

                    $data_expl = explode(' - ', $value);

                    $data_da_expl = explode('/', $data_expl[0]);
                    $data_da = "{$data_da_expl[2]}-{$data_da_expl[1]}-{$data_da_expl[0]}";
                    $data_a_expl = explode('/', $data_expl[1]);
                    $data_a = "{$data_a_expl[2]}-{$data_a_expl[1]}-{$data_a_expl[0]}";
                    $where_documenti[] = "documenti_contabilita_data_emissione <= '$data_a' AND documenti_contabilita_data_emissione >= '$data_da'";
                    $where_spese[] = "spese_data_emissione <= '$data_a' AND spese_data_emissione >= '$data_da'";

                    break;
                case 'documenti_contabilita_serie':
                    $where_documenti[] = "documenti_contabilita_serie IN ('" . implode("','", $value) . "')";
                    break;
                case 'documenti_contabilita_stato_pagamenti':
                    $where_documenti[] = "documenti_contabilita_stato_pagamenti = '$value'";
                    break;
                case 'documenti_contabilita_customer_id':
                    $where_documenti[] = "documenti_contabilita_customer_id = '$value'";
                    break;
                case 'documenti_contabilita_azienda':
                    $where_documenti[] = "documenti_contabilita_azienda = '$value'";
                    break;
                default:

                    debug("Campo filtro non gestito {$field_name}(custom view iva).");
                    debug($filtro);
                    break;
            }
        }
    }
}

$where_documenti_str = implode(' AND ', $where_documenti);

$where_spese_str = implode(' AND ', $where_spese);

$fatturati = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as s,SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva, EXTRACT(MONTH FROM documenti_contabilita_data_emissione) as mese FROM documenti_contabilita WHERE (documenti_contabilita_tipo IN (1,4,11,12)) AND $where_documenti_str GROUP BY EXTRACT(MONTH FROM documenti_contabilita_data_emissione)")->result_array();

//debug($this->db->last_query());

$spese = $this->db->query("SELECT SUM(spese_totale) as s, SUM(spese_iva) as iva, SUM((spese_iva/100)*spese_deduc_iva) as iva_deducibile, EXTRACT(MONTH FROM spese_data_emissione) as mese FROM spese WHERE $where_spese_str GROUP BY EXTRACT(MONTH FROM spese_data_emissione)")->result_array();

$fatturati_totali = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo IN (1,4,11,12)) AND $where_documenti_str")->row_array();

$spese_totali = $this->db->query("SELECT SUM(spese_totale) as spese_fatturato, SUM(spese_iva) as iva, SUM((spese_iva/100)*spese_deduc_iva) as spese_iva_deducibile FROM spese WHERE $where_spese_str")->row_array();

$fatturati_totali['iva_esclusa'] = $fatturati_totali['fatturato'] - $fatturati_totali['iva'];
$spese_totali['spese_iva_esclusa'] = $spese_totali['spese_fatturato'] - $spese_totali['iva'];

$totali_mese = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [], 8 => [], 9 => [], 10 => [], 11 => [], 12 => []];
foreach ($fatturati as $fatturato) {
    $totali_mese[$fatturato['mese']]['fatturato'] = $fatturato['s'];
    $totali_mese[$fatturato['mese']]['iva'] = $fatturato['iva'];
    $totali_mese[$fatturato['mese']]['iva_esclusa'] = $fatturato['s'] - $fatturato['iva'];
}
foreach ($spese as $spesa) {
    $totali_mese[$spesa['mese']]['spese_fatturato'] = $spesa['s'];
    $totali_mese[$spesa['mese']]['spese_iva'] = $spesa['iva'];
    $totali_mese[$spesa['mese']]['spese_iva_deducibile'] = $spesa['iva_deducibile'];
    $totali_mese[$spesa['mese']]['spese_iva_esclusa'] = $spesa['s'] - $spesa['iva'];
}
?>
<h3>
    Riepilogo iva e fatturato <?php echo (!empty($data_a)) ? "dal: " . dateFormat($data_da, 'd-m-Y') . " al: " . dateFormat($data_a, 'd-m-Y') : ""; ?>
</h3>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Mese</th>
            <th>Fatturato</th>
            <th>Iva emessa</th>
            <th>Spese registrate</th>
            <th>Iva deducibile</th>
            <th>Stima iva da versare</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($totali_mese as $mese_num => $totali): ?>
            <tr>
                <td><?php echo mese_testuale($mese_num); ?></td>
                <td>&euro; <?php echo @number_format($totali['fatturato'], 2, ',', '.'); ?><br />&euro;
                    <?php echo @number_format($totali['iva_esclusa'], 2, ',', '.'); ?></td>
                <td>&euro; <?php echo @number_format($totali['iva'], 2, ',', '.'); ?></td>
                <td>&euro; <?php echo @number_format($totali['spese_fatturato'], 2, ',', '.'); ?><br />&euro;
                    <?php echo @number_format($totali['spese_iva_esclusa'], 2, ',', '.'); ?></td>
                <td>&euro; <?php echo @number_format($totali['spese_iva_deducibile'], 2, ',', '.'); ?></td>
                <td>&euro; <?php echo @number_format($totali['iva'] - $totali['spese_iva_deducibile'], 2, ',', '.'); ?></td>
            </tr>
        <?php endforeach;?>

        <tr style="font-weight:bold">
            <td>Totali: </td>
            <td>&euro; <?php echo @number_format($fatturati_totali['fatturato'], 2, ',', '.'); ?><br />&euro;
                <?php echo @number_format($fatturati_totali['iva_esclusa'], 2, ',', '.'); ?></td>
            <td>&euro; <?php echo @number_format($fatturati_totali['iva'], 2, ',', '.'); ?></td>
            <td>&euro; <?php echo @number_format($spese_totali['spese_fatturato'], 2, ',', '.'); ?><br />&euro;
                <?php echo @number_format($spese_totali['spese_iva_esclusa'], 2, ',', '.'); ?></td>
            <td>&euro; <?php echo @number_format($spese_totali['spese_iva_deducibile'], 2, ',', '.'); ?></td>
            <td>&euro;
                <?php echo @number_format($fatturati_totali['iva'] - $spese_totali['spese_iva_deducibile'], 2, ',', '.'); ?>
            </td>
        </tr>
    </tbody>
</table>
<?php

/*

$anno_corrente = date('Y');
$liquidazione_iva = $this->apilib->searchFirst('documenti_contabilita_settings');
$totale_iva = $this->db->query("SELECT SUM(documenti_contabilita_iva) as totale, EXTRACT(MONTH FROM documenti_contabilita_data_emissione) as mese FROM documenti_contabilita WHERE documenti_contabilita_tipo = 1 AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '{$anno_corrente}' GROUP BY mese ORDER BY mese ASC")->result_array();
$totale_iva_trim = $this->db->query("SELECT SUM(documenti_contabilita_iva) as totale, EXTRACT(QUARTER FROM documenti_contabilita_data_emissione) as trimestre FROM documenti_contabilita WHERE documenti_contabilita_tipo = 1 AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '{$anno_corrente}' GROUP BY trimestre ORDER BY trimestre ASC")->result_array();

$spese_iva = $this->db->query("SELECT SUM(spese_iva) as totale, EXTRACT(MONTH FROM spese_data_emissione) as mese FROM spese WHERE EXTRACT(YEAR FROM spese_data_emissione) = '{$anno_corrente}' GROUP BY mese ORDER BY mese ASC")->result_array();

$spese_iva_trim = $this->db->query("SELECT SUM(spese_iva) as totale, EXTRACT(QUARTER FROM spese_data_emissione) as trimestre FROM spese WHERE EXTRACT(YEAR FROM spese_data_emissione) = '{$anno_corrente}' GROUP BY trimestre ORDER BY trimestre ASC")->result_array();
$spese_iva_deduc_trim = $this->db->query("SELECT (spese_iva/100)*spese_deduc_iva as totale, EXTRACT(QUARTER FROM spese_data_emissione) as trimestre FROM spese WHERE EXTRACT(YEAR FROM spese_data_emissione) = '{$anno_corrente}' GROUP BY spese_iva,spese_deduc_iva,trimestre ORDER BY trimestre ASC")->result_array();

//debug($totale_iva);
//debug($spese_iva);
?>

<?php if ($liquidazione_iva["documenti_contabilita_settings_liquidazione_iva"] == 1): ?>

<table class="table">
<thead>
<th>Trimestre</th>
<th>Totale iva emessa</th>
<th>Totale iva spesa</th>
<th>Totale iva spesa deducibile</th>
</thead>
<?php foreach($totale_iva as $foo): ?>
<tr>
<td><strong><?php echo @mese_testuale($foo['mese']); ?></strong></td>
<td>&euro;<?php echo @number_format($foo['totale'],2,'.',''); ?></td>
<td>&euro;<?php //echo @$spese_iva[$key]['totale']; ?></td>
<td></td>
</tr>
<?php endforeach; ?>
</table>

<?php elseif ($liquidazione_iva["documenti_contabilita_settings_liquidazione_iva"] == 2): ?>

<?php foreach($totale_iva_trim as $iva): ?>
<li><strong><?php echo $iva['trimestre']; ?>ÃÂÃÂ° trimestre</strong>: &euro;
<?php echo number_format($iva['totale'],2,'.',''); ?></li>
<?php endforeach; ?>
<?php endif; */?>