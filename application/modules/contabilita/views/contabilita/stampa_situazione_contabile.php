<?php
$grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
$where = [$this->datab->generate_where("grids", $grid['grids_id'], null)];

if (empty($value_id)) {
    $azienda = $this->apilib->searchFirst('documenti_contabilita_settings');
    $azienda_id = $azienda['documenti_contabilita_settings_id'];
} else {
    $azienda = $this->apilib->view('documenti_contabilita_settings', $value_id);
    $azienda_id = $value_id;
}

$where_mastri = $where_conti = $where_sottoconti = [];
if ($mastro_id = $this->input->get('mastro')) {
    $where_mastri[] = "documenti_contabilita_mastri_id IN ($mastro_id)";
}
if ($conto_id = $this->input->get('conto')) {
    $where_conti[] = "documenti_contabilita_conti_id IN ($conto_id)";
    $where_mastri[] = "documenti_contabilita_mastri_id IN (SELECT documenti_contabilita_conti_mastro FROM documenti_contabilita_conti WHERE documenti_contabilita_conti_id IN ($conto_id))";
}
if ($sottoconto_id = $this->input->get('sottoconto')) {
    $where_sottoconti[] = "documenti_contabilita_sottoconti_id IN ($sottoconto_id)";
    $where_conti[] = "documenti_contabilita_conti_id IN (SELECT documenti_contabilita_sottoconti_conto FROM documenti_contabilita_sottoconti WHERE documenti_contabilita_sottoconti_id IN ($sottoconto_id))";
    $where_mastri[] = "documenti_contabilita_mastri_id IN (SELECT documenti_contabilita_conti_mastro FROM documenti_contabilita_conti WHERE documenti_contabilita_conti_id IN (SELECT documenti_contabilita_sottoconti_conto FROM documenti_contabilita_sottoconti WHERE documenti_contabilita_sottoconti_id IN ($sottoconto_id)))";
}

$mastri = $this->apilib->search('documenti_contabilita_mastri', $where_mastri);
$conti = $this->apilib->search('documenti_contabilita_conti', $where_conti);
if ($where_sottoconti) {
    $sottoconti = $this->apilib->search(
        'documenti_contabilita_sottoconti',
        $where_sottoconti
    );
} else {
    $sottoconti = $this->apilib->search(
        'documenti_contabilita_sottoconti',
        [
            'documenti_contabilita_sottoconti_id NOT IN (SELECT customers_sottoconto FROM customers WHERE customers_sottoconto IS NOT NULL)',
        ]
    );
}

$where = array_filter($where);

// $customers_sottoconti = $this->apilib->search('customers', ['documenti_contabilita_sottoconti_codice_completo IS NOT NULL']);
// debug($customers_sottoconti);

$totale_attivo = $totale_passivo = $totale_costi = $totale_ricavi = $perdita_del_periodo = $utile_del_periodo = $totale_a_pareggio_attivita = $totale_a_pareggio_passivita = 0;
$alberatura_attivita = $alberatura_costi = $alberatura_ricavi = $alberatura_passivita = [];
foreach ($mastri as $mastro) {

    $where_append = ($where) ? ' AND ' . implode(' and ', $where) : '';
    $mastro['tot'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare - prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE (prime_note_registrazioni_mastro_dare = '{$mastro['documenti_contabilita_mastri_id']}' OR prime_note_registrazioni_mastro_avere = '{$mastro['documenti_contabilita_mastri_id']}') $where_append")->row()->s;
    switch ($mastro['documenti_contabilita_mastri_natura_value']) {
        case 'Attività': //Attività
            $alberatura = &$alberatura_attivita;
            $totale_attivo += $mastro['tot'];
            break;
        case 'Passività': //Attività
            $mastro['tot'] = -$mastro['tot'];
            $totale_passivo += $mastro['tot'];
            $alberatura = &$alberatura_passivita;
            break;
        case 'Costi': //Attività
            $alberatura = &$alberatura_costi;
            $totale_costi += $mastro['tot'];
            break;
        case 'Ricavi': //Attività
            $mastro['tot'] = -$mastro['tot'];
            $alberatura = &$alberatura_ricavi;
            $totale_ricavi += $mastro['tot'];
            break;
        default:
            debug($mastro, true);
            break;
    }

    $alberatura[$mastro['documenti_contabilita_mastri_id']] = $mastro;
    $alberatura[$mastro['documenti_contabilita_mastri_id']]['conti'] = [];
    foreach ($conti as $conto) {
        if ($conto['documenti_contabilita_conti_mastro'] != $mastro['documenti_contabilita_mastri_id']) {
            continue;
        }
        $conto['tot'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare-prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE (prime_note_registrazioni_conto_dare = '{$conto['documenti_contabilita_conti_id']}' OR prime_note_registrazioni_conto_avere = '{$conto['documenti_contabilita_conti_id']}') $where_append")->row()->s;
        switch ($mastro['documenti_contabilita_mastri_natura_value']) {
            case 'Passività': //Attività
                $conto['tot'] = -$conto['tot'];
                break;
            case 'Ricavi': //Attività
                $conto['tot'] = -$conto['tot'];
                break;
            default:
                //debug($mastro, true);
                break;
        }
        $alberatura[$mastro['documenti_contabilita_mastri_id']]['conti'][$conto['documenti_contabilita_conti_id']] = $conto;
        $alberatura[$mastro['documenti_contabilita_mastri_id']]['conti'][$conto['documenti_contabilita_conti_id']]['sottoconti'] = [];
        foreach ($sottoconti as $key => $sottoconto) {

            if ($sottoconto['documenti_contabilita_sottoconti_conto'] != $conto['documenti_contabilita_conti_id']) {
                continue;
            }
            //debug($sottoconto, true);
            if (!$this->apilib->searchFirst('customers', ['documenti_contabilita_sottoconti_codice_completo' => $sottoconto['documenti_contabilita_sottoconti_codice_completo']])) { //Se non è il sottoconto di un cliente
                $sottoconto['tot'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare-prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE (prime_note_registrazioni_sottoconto_dare = '{$sottoconto['documenti_contabilita_sottoconti_id']}' OR prime_note_registrazioni_sottoconto_avere = '{$sottoconto['documenti_contabilita_sottoconti_id']}') $where_append")->row()->s;

                if ($sottoconto['documenti_contabilita_sottoconti_codice_completo'] == '440.1015.15') {
                    //debug($this->db->last_query(),true);
                }

                if ($sottoconto['tot'] != 0) {
                    switch ($mastro['documenti_contabilita_mastri_natura_value']) {
                        case 'Passività': //Attività
                            $sottoconto['tot'] = -$sottoconto['tot'];
                            break;
                        case 'Ricavi': //Attività
                            $sottoconto['tot'] = -$sottoconto['tot'];
                            break;
                        default:
                            //debug($mastro, true);
                            break;
                    }

                    $alberatura[$mastro['documenti_contabilita_mastri_id']]['conti'][$conto['documenti_contabilita_conti_id']]['sottoconti'][$sottoconto['documenti_contabilita_sottoconti_id']] = $sottoconto;
                }
            }
        }
    }
}

$differenza_att_pass = $totale_attivo - $totale_passivo;
if ($differenza_att_pass >= 0) {
    $utile_del_periodo = $differenza_att_pass;
    $perdita_del_periodo = 0;

    $totale_a_pareggio_attivita = $totale_attivo - $perdita_del_periodo;
    $totale_a_pareggio_passivita = $totale_passivo + $utile_del_periodo;
} else {
    $perdita_del_periodo = -$differenza_att_pass;
    $utile_del_periodo = 0;
    $totale_a_pareggio_passivita = $totale_attivo - $utile_del_periodo;
    $totale_a_pareggio_attivita = 0;
}

$differenza_ric_cos = $totale_ricavi - $totale_costi;
if ($differenza_ric_cos >= 0) {
    $utile_del_periodo_costi = $differenza_ric_cos;
    $perdita_del_periodo_ricavi = 0;
} else {
    $perdita_del_periodo_ricavi = -$differenza_ric_cos;

    $utile_del_periodo_costi = 0;
}
$totale_a_pareggio_ricavi = $totale_ricavi + $perdita_del_periodo_ricavi;
$totale_a_pareggio_costi = $totale_costi + $utile_del_periodo_costi;

// Dati filtri impostati
$filters = $this->session->userdata(SESS_WHERE_DATA);

// Costruisco uno specchietto di filtri autogenerati leggibile
$filtri = array();

if (!empty($filters["filter_stampe_contabili"])) {
    foreach ($filters["filter_stampe_contabili"] as $field) {
        if ($field['value'] == '-1') {
            continue;
        }
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
.title {
    font-weight: bold;
    text-align: center;
    text-transform: uppercase;
}

h3.title {
    font-size: 20px
}

.text-bold {
    font-weight: bold;
}

.container {
    margin: 0 auto;
    width: 100%;
    font-size: 1em;
}

div.page:nth-child(0) {
    page-break-before: always !important;
}

div.page:nth-child(1) {
    page-break-before: always !important;
}

/* tr.pt>td {
        padding-top: 1em;
    } */

tr>td {
    white-space: nowrap;
}

table {
    border-collapse: separate;
    border-spacing: 5px 0;
}

/* * {
        float: none !important
    } */

.square {
    border: 1px solid black;
    margin: 5px;
    padding: 5px;
}

.yellow {
    background-color: lightyellow;
}

.blue {
    background-color: lightblue;
}

.green {
    background-color: lightgreen;
}
</style>
<div style="margin-bottom:px">
    <?php foreach ($filtri as $filtro): ?>
    <p style="margin-bottom: 5px;"><strong><?php echo $filtro['label']; ?></strong>: <?php echo $filtro['value']; ?></p>
    <?php endforeach;?>
</div>

<h2 class="text-center"><?php echo $azienda['documenti_contabilita_settings_company_name'] ?></h2>

<div>
    <?php if ($alberatura_attivita || $alberatura_passivita): ?>
    <div class="page">
        <div>
            <table>
                <tr>
                    <td colspan="3">
                        <div class="square blue">
                            <h3 class="text-center text-uppercase text-bold" style="margin:0">Stato Patrimoniale</h3>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">
                        <div class="square yellow">
                            <h4 class="text-center text-uppercase text-bold" style="margin:0">Attività</h4>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Conto</th>
                                    <th>Descrizione</th>
                                    <th>Saldo finale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alberatura_attivita as $mastro_id => $mastro): ?>

                                <tr style="font-weight:bold;">
                                    <td><?php echo $mastro['documenti_contabilita_mastri_codice']; ?></td>
                                    <td><?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?></td>
                                    <td>
                                        <?php echo number_format(($mastro['tot']), 2, '.', ','); ?>
                                    </td>
                                </tr>
                                <?php foreach ($mastro['conti'] as $conto_id => $conto): ?>
                                <tr>
                                    <td><?php echo $conto['documenti_contabilita_conti_codice']; ?></td>
                                    <td><?php echo $conto['documenti_contabilita_conti_descrizione']; ?></td>
                                    <td><?php echo number_format(($conto['tot']), 2, '.', ','); ?></td>
                                </tr>
                                <?php foreach ($conto['sottoconti'] as $sottoconto_id => $sottoconto): ?>
                                <tr>
                                    <td><?php echo $conto['documenti_contabilita_conti_codice']; ?>.<?php echo $sottoconto['documenti_contabilita_sottoconti_codice']; ?></td>
                                    <td><?php echo $sottoconto['documenti_contabilita_sottoconti_descrizione']; ?></td>
                                    <td><?php echo number_format(($sottoconto['tot']), 2, '.', ','); ?></td>
                                </tr>
                                <?php endforeach;?>
                                <?php endforeach;?>
                                <?php endforeach;?>
                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                </tr>
                                <tr class="square yellow">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale Attivo</h5>
                                    </td>
                                    <td><?php e_money($totale_attivo);?></td>
                                </tr>
                                <tr class="square yellow">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Perdita del periodo</h5>
                                    </td>

                                    <td><?php e_money($perdita_del_periodo);?></td>
                                </tr>
                                <tr class="square yellow">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale a pareggio</h5>
                                    </td>
                                    <td><?php e_money($totale_a_pareggio_attivita);?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>

                    <td style="vertical-align: top;">
                        <div class="square yellow">
                            <h4 class="text-center text-uppercase text-bold" style="margin:0">Passività</h4>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Conto</th>
                                    <th>Descrizione</th>
                                    <th>Saldo finale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alberatura_passivita as $mastro_id => $mastro): ?>
                                <tr style="font-weight:bold;">
                                    <td><?php echo $mastro['documenti_contabilita_mastri_codice']; ?></td>
                                    <td><?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?></td>
                                    <td>
                                        <?php echo number_format(($mastro['tot']), 2, '.', ','); ?>
                                    </td>
                                </tr>
                                <?php foreach ($mastro['conti'] as $conto_id => $conto): ?>
                                <tr>
                                    <td><?php echo $conto['documenti_contabilita_conti_codice']; ?></td>
                                    <td><?php echo $conto['documenti_contabilita_conti_descrizione']; ?></td>
                                    <td><?php echo number_format(($conto['tot']), 2, '.', ','); ?></td>
                                </tr>
                                <?php foreach ($conto['sottoconti'] as $sottoconto_id => $sottoconto): ?>
                                <tr>
                                    <td><?php echo $conto['documenti_contabilita_conti_codice']; ?>.<?php echo $sottoconto['documenti_contabilita_sottoconti_codice']; ?></td>
                                    <td><?php echo $sottoconto['documenti_contabilita_sottoconti_descrizione']; ?></td>
                                    <td><?php echo number_format(($sottoconto['tot']), 2, '.', ','); ?></td>
                                </tr>
                                <?php endforeach;?>
                                <?php endforeach;?>
                                <?php endforeach;?>

                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                </tr>
                                <tr class="square yellow">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale Passivo</h5>
                                    </td>
                                    <td><?php e_money($totale_passivo);?></td>
                                </tr>
                                <tr class="square yellow">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Utile del periodo</h5>
                                    </td>

                                    <td><?php e_money($utile_del_periodo);?></td>
                                </tr>
                                <tr class="square yellow">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale a pareggio</h5>
                                    </td>
                                    <td><?php e_money($totale_a_pareggio_passivita);?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif;?>
    <?php if ($alberatura_costi || $alberatura_ricavi): ?>
    <div class="page">
        <div>
            <table>
                <tr>
                    <td colspan="3">
                        <div class="square blue">
                            <h3 class="text-center text-uppercase text-bold" style="margin:0">Conto Economico</h3>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">
                        <div class="square green">
                            <h4 class="text-center text-uppercase text-bold" style="margin:0">Costi</h4>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Conto</th>
                                    <th>Descrizione</th>
                                    <th>Saldo finale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alberatura_costi as $mastro_id => $mastro): ?>
                                <tr style="font-weight:bold;">
                                    <td><?php echo $mastro['documenti_contabilita_mastri_codice']; ?></td>
                                    <td><?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?></td>
                                    <td>
                                        <?php echo number_format($mastro['tot'], 2, '.', ','); ?>
                                    </td>
                                </tr>
                                <?php foreach ($mastro['conti'] as $conto_id => $conto): ?>
                                <tr>
                                    <td><?php echo $conto['documenti_contabilita_conti_codice']; ?></td>
                                    <td><?php echo $conto['documenti_contabilita_conti_descrizione']; ?></td>
                                    <td><?php echo number_format($conto['tot'], 2, '.', ','); ?></td>
                                </tr>
                                <?php endforeach;?>
                                <?php endforeach;?>
                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                </tr>
                                <tr class="square green">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale Costi</h5>
                                    </td>
                                    <td><?php e_money($totale_costi);?></td>
                                </tr>
                                <tr class="square green">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Utile del periodo</h5>
                                    </td>

                                    <td><?php e_money($utile_del_periodo_costi);?></td>
                                </tr>
                                <tr class="square green">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale a pareggio</h5>
                                    </td>
                                    <td><?php e_money($totale_a_pareggio_costi);?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>

                    <td style="vertical-align: top;">
                        <div class="square green">
                            <h4 class="text-center text-uppercase text-bold" style="margin:0">Ricavi</h4>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Conto</th>
                                    <th>Descrizione</th>
                                    <th>Saldo finale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alberatura_ricavi as $mastro_id => $mastro): ?>
                                <tr style="font-weight:bold;">
                                    <td><?php echo $mastro['documenti_contabilita_mastri_codice']; ?></td>
                                    <td><?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?></td>
                                    <td>
                                        <?php echo number_format($mastro['tot'], 2, '.', ','); ?>
                                    </td>
                                </tr>
                                <?php foreach ($mastro['conti'] as $conto_id => $conto): ?>
                                <tr>
                                    <td><?php echo $conto['documenti_contabilita_conti_codice']; ?></td>
                                    <td><?php echo $conto['documenti_contabilita_conti_descrizione']; ?></td>
                                    <td><?php echo number_format($conto['tot'], 2, '.', ','); ?></td>
                                </tr>
                                <?php endforeach;?>
                                <?php endforeach;?>

                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                </tr>
                                <tr class="square green">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale Ricavi</h5>
                                    </td>
                                    <td><?php e_money($totale_ricavi);?></td>
                                </tr>
                                <tr class="square green">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Perdita del periodo</h5>
                                    </td>

                                    <td><?php e_money($perdita_del_periodo_ricavi);?></td>
                                </tr>
                                <tr class="square green">
                                    <td colspan="2">
                                        <h5 class="text-center text-uppercase text-bold">Totale a pareggio</h5>
                                    </td>
                                    <td><?php e_money($totale_a_pareggio_ricavi);?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif;?>
</div>