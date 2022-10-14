<?php

if (empty($value_id)) {
    $azienda = $this->apilib->searchFirst('documenti_contabilita_settings');
    $azienda_id = $azienda['documenti_contabilita_settings_id'];
} else {
    $azienda = $this->apilib->view('documenti_contabilita_settings', $value_id);
    $azienda_id = $value_id;
}

$where_mastri = $where_conti = $where_sottoconti = [];
// if ($mastro_id = $this->input->get('mastro')) {
//     $where_mastri[] = "documenti_contabilita_mastri_id = '$mastro_id'";
// }
// if ($conto_id = $this->input->get('conto')) {
//     $where_conti[] = "documenti_contabilita_conti_id = '$conto_id'";
//     $where_mastri[] = "documenti_contabilita_mastri_id IN (SELECT documenti_contabilita_conti_mastro FROM documenti_contabilita_conti WHERE documenti_contabilita_conti_id=  '$conto_id')";
// }

$piano_dei_conti_patrimoniale = [];

$mastri_patrimoniale = $this->apilib->search('documenti_contabilita_mastri', array_merge([
    'documenti_contabilita_mastri_tipo_value' => 'PATRIMONIALE',
    'documenti_contabilita_mastri_azienda' => $azienda_id
], $where_mastri));

foreach ($mastri_patrimoniale as $key => $mastro) {
    $mastro['conti'] = $this->apilib->search('documenti_contabilita_conti', ['documenti_contabilita_conti_mastro' => $mastro['documenti_contabilita_mastri_id']]);

    foreach ($mastro['conti'] as $key => $conto) {
        $mastro['conti'][$key]['sottoconti'] = $this->apilib->search(
            'documenti_contabilita_sottoconti',
            [
                'documenti_contabilita_sottoconti_conto' => $conto['documenti_contabilita_conti_id'],
                'documenti_contabilita_sottoconti_id NOT IN (SELECT customers_sottoconto FROM customers WHERE customers_sottoconto IS NOT NULL)'
            ]
        );
    }

    $piano_dei_conti_patrimoniale[$mastro['documenti_contabilita_mastri_natura_value']][] = $mastro;
}

$piano_dei_conti_economico = [];

$mastri_economico = $this->apilib->search('documenti_contabilita_mastri', array_merge(
    [
        'documenti_contabilita_mastri_tipo_value' => 'ECONOMICO',
        'documenti_contabilita_mastri_azienda' => $azienda_id
    ],
    $where_mastri
));

foreach ($mastri_economico as $key => $mastro) {
    $mastro['conti'] = $this->apilib->search('documenti_contabilita_conti', ['documenti_contabilita_conti_mastro' => $mastro['documenti_contabilita_mastri_id']]);

    foreach ($mastro['conti'] as $key => $conto) {
        $mastro['conti'][$key]['sottoconti'] = $this->apilib->search('documenti_contabilita_sottoconti', ['documenti_contabilita_sottoconti_conto' => $conto['documenti_contabilita_conti_id']]);
    }

    $piano_dei_conti_economico[$mastro['documenti_contabilita_mastri_natura_value']][] = $mastro;
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

    div.page:nth-child(1) {
        page-break-before: always !important;
    }

    div.page:nth-child(2) {
        page-break-before: always !important;
    }

    tr.pt>td {
        padding-top: 1em;
    }

    * {
        float: none !important
    }
</style>
<div>
    <?php if ($piano_dei_conti_patrimoniale) : ?>
        <div class="page">
            <div>
                <table class="table table-condesed">
                    <tr>
                        <td>
                            <h2 class="text-center"><?php echo $azienda['documenti_contabilita_settings_company_name'] ?></h2>
                        </td>
                    </tr>
                </table>

                <table class="table table-condesed">
                    <tr>
                        <td colspan="4">
                            <h3 class="title">Stato patrimoniale</h3>
                        </td>
                    </tr>
                    <tr>
                        <?php foreach ($piano_dei_conti_patrimoniale as $pianodeiconti => $mastri) : ?>
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td colspan="2">
                                            <div class="title" style="margin-top: 1rem;margin-bottom: 1rem"><?php echo $pianodeiconti ?></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td></td>
                                        <td></td>
                                    </tr>

                                    <?php foreach ($mastri as $mastro) : ?>
                                        <tr style="padding-top: 1rem;" class="pt">
                                            <td class="text-bold"><?php echo $mastro['documenti_contabilita_mastri_codice'] ?></td>
                                            <td class="text-bold"><?php echo $mastro['documenti_contabilita_mastri_descrizione'] ?></td>
                                        </tr>

                                        <?php foreach ($mastro['conti'] as $conto) : ?>
                                            <tr style="margin-top: 1rem;">
                                                <td><?php echo $conto['documenti_contabilita_conti_codice_completo'] ?></td=>
                                                <td><?php echo $conto['documenti_contabilita_conti_descrizione'] ?></td>
                                            </tr>

                                            <?php foreach ($conto['sottoconti'] as $sottoconto) : ?>
                                                <tr style="margin-top: 1rem;">
                                                    <td><?php echo $sottoconto['documenti_contabilita_sottoconti_codice_completo'] ?></td>
                                                    <td><?php echo $sottoconto['documenti_contabilita_sottoconti_descrizione'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </table>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>

            </div>
        </div>
    <?php endif; ?>
    <?php if ($piano_dei_conti_economico) : ?>
        <div class="page">
            <div>
                <table class="table table-condesed">
                    <tr>
                        <td>
                            <h2 class="text-center"><?php echo $azienda['documenti_contabilita_settings_company_name'] ?></h2>
                            <h3 class="text-center">Stampa piano dei conti</h3>
                        </td>
                    </tr>
                </table>

                <table class="table table-condesed">
                    <tr>
                        <td colspan="4">
                            <h3 class="title">Conto Economico</h3>
                        </td>
                    </tr>
                    <tr>
                        <?php foreach ($piano_dei_conti_economico as $pianodeiconti => $mastri) : ?>
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td colspan="2">
                                            <div class="title " style="margin-top: 1rem;margin-bottom: 1rem"><?php echo $pianodeiconti ?></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td></td>
                                        <td></td>
                                    </tr>

                                    <?php foreach ($mastri as $mastro) : ?>
                                        <tr style="margin-top: 1rem;" class="pt">
                                            <td class="text-bold"><?php echo $mastro['documenti_contabilita_mastri_codice'] ?></td>
                                            <td class="text-bold"><?php echo $mastro['documenti_contabilita_mastri_descrizione'] ?></td>
                                        </tr>

                                        <?php foreach ($mastro['conti'] as $conto) : ?>
                                            <tr style="margin-top: 1rem;">
                                                <td><?php echo $conto['documenti_contabilita_conti_codice_completo'] ?></td=>
                                                <td><?php echo $conto['documenti_contabilita_conti_descrizione'] ?></td>
                                            </tr>

                                            <?php foreach ($conto['sottoconti'] as $sottoconto) : ?>
                                                <tr style="margin-top: 1rem;">
                                                    <td><?php echo $sottoconto['documenti_contabilita_sottoconti_codice_completo'] ?></td>
                                                    <td><?php echo $sottoconto['documenti_contabilita_sottoconti_descrizione'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </table>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>