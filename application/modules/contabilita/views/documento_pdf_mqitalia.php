<?php

//error_reporting(0);  //*********************** <!---------------- DISATTIVARE IN CASO DI DEBUG ------------------->

if (empty($this->input->get("documento_id")) && empty($documento_id)) {
    die("Documento non valido o non piu disponibile");
}
$id = (isset($documento_id)) ? $documento_id : $this->input->get("documento_id");

$documento = $this->apilib->view('documenti_contabilita', $id);

$segno = '';
if ($documento['documenti_contabilita_tipo'] == 4) { //Se è una nota di credito
    $segno = '-';
}

unset($documento['documenti_contabilita_file']);

//debug($documento,true);

$articoli = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $id], null, 0, "documenti_contabilita_articoli_iva_perc");

$iva = [];
foreach ($articoli as $articolo) {
    if (!empty($articolo['iva_descrizione'])) {
        $iva[] = ['valore' => $segno . $articolo['iva_valore'], 'descrizione' => $articolo['iva_descrizione']];
    }
}

$destinatario = json_decode($documento['documenti_contabilita_destinatario'], true);

$scadenze = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $id]);

$settings = $this->apilib->searchFirst('documenti_contabilita_settings', ['documenti_contabilita_settings_id' => $documento['documenti_contabilita_azienda']]);

$valuta = array(
    'AED' => '&#1583;.&#1573;', // ?
    'AFN' => '&#65;&#102;',
    'ALL' => '&#76;&#101;&#107;',
    'AMD' => '',
    'ANG' => '&#402;',
    'AOA' => '&#75;&#122;', // ?
    'ARS' => '&#36;',
    'AUD' => '&#36;',
    'AWG' => '&#402;',
    'AZN' => '&#1084;&#1072;&#1085;',
    'BAM' => '&#75;&#77;',
    'BBD' => '&#36;',
    'BDT' => '&#2547;', // ?
    'BGN' => '&#1083;&#1074;',
    'BHD' => '.&#1583;.&#1576;', // ?
    'BIF' => '&#70;&#66;&#117;', // ?
    'BMD' => '&#36;',
    'BND' => '&#36;',
    'BOB' => '&#36;&#98;',
    'BRL' => '&#82;&#36;',
    'BSD' => '&#36;',
    'BTN' => '&#78;&#117;&#46;', // ?
    'BWP' => '&#80;',
    'BYR' => '&#112;&#46;',
    'BZD' => '&#66;&#90;&#36;',
    'CAD' => '&#36;',
    'CDF' => '&#70;&#67;',
    'CHF' => '&#67;&#72;&#70;',
    'CLF' => '', // ?
    'CLP' => '&#36;',
    'CNY' => '&#165;',
    'COP' => '&#36;',
    'CRC' => '&#8353;',
    'CUP' => '&#8396;',
    'CVE' => '&#36;', // ?
    'CZK' => '&#75;&#269;',
    'DJF' => '&#70;&#100;&#106;', // ?
    'DKK' => '&#107;&#114;',
    'DOP' => '&#82;&#68;&#36;',
    'DZD' => '&#1583;&#1580;', // ?
    'EGP' => '&#163;',
    'ETB' => '&#66;&#114;',
    'EUR' => '&#8364;',
    'FJD' => '&#36;',
    'FKP' => '&#163;',
    'GBP' => '&#163;',
    'GEL' => '&#4314;', // ?
    'GHS' => '&#162;',
    'GIP' => '&#163;',
    'GMD' => '&#68;', // ?
    'GNF' => '&#70;&#71;', // ?
    'GTQ' => '&#81;',
    'GYD' => '&#36;',
    'HKD' => '&#36;',
    'HNL' => '&#76;',
    'HRK' => '&#107;&#110;',
    'HTG' => '&#71;', // ?
    'HUF' => '&#70;&#116;',
    'IDR' => '&#82;&#112;',
    'ILS' => '&#8362;',
    'INR' => '&#8377;',
    'IQD' => '&#1593;.&#1583;', // ?
    'IRR' => '&#65020;',
    'ISK' => '&#107;&#114;',
    'JEP' => '&#163;',
    'JMD' => '&#74;&#36;',
    'JOD' => '&#74;&#68;', // ?
    'JPY' => '&#165;',
    'KES' => '&#75;&#83;&#104;', // ?
    'KGS' => '&#1083;&#1074;',
    'KHR' => '&#6107;',
    'KMF' => '&#67;&#70;', // ?
    'KPW' => '&#8361;',
    'KRW' => '&#8361;',
    'KWD' => '&#1583;.&#1603;', // ?
    'KYD' => '&#36;',
    'KZT' => '&#1083;&#1074;',
    'LAK' => '&#8365;',
    'LBP' => '&#163;',
    'LKR' => '&#8360;',
    'LRD' => '&#36;',
    'LSL' => '&#76;', // ?
    'LTL' => '&#76;&#116;',
    'LVL' => '&#76;&#115;',
    'LYD' => '&#1604;.&#1583;', // ?
    'MAD' => '&#1583;.&#1605;.', //?
    'MDL' => '&#76;',
    'MGA' => '&#65;&#114;', // ?
    'MKD' => '&#1076;&#1077;&#1085;',
    'MMK' => '&#75;',
    'MNT' => '&#8366;',
    'MOP' => '&#77;&#79;&#80;&#36;', // ?
    'MRO' => '&#85;&#77;', // ?
    'MUR' => '&#8360;', // ?
    'MVR' => '.&#1923;', // ?
    'MWK' => '&#77;&#75;',
    'MXN' => '&#36;',
    'MYR' => '&#82;&#77;',
    'MZN' => '&#77;&#84;',
    'NAD' => '&#36;',
    'NGN' => '&#8358;',
    'NIO' => '&#67;&#36;',
    'NOK' => '&#107;&#114;',
    'NPR' => '&#8360;',
    'NZD' => '&#36;',
    'OMR' => '&#65020;',
    'PAB' => '&#66;&#47;&#46;',
    'PEN' => '&#83;&#47;&#46;',
    'PGK' => '&#75;', // ?
    'PHP' => '&#8369;',
    'PKR' => '&#8360;',
    'PLN' => '&#122;&#322;',
    'PYG' => '&#71;&#115;',
    'QAR' => '&#65020;',
    'RON' => '&#108;&#101;&#105;',
    'RSD' => '&#1044;&#1080;&#1085;&#46;',
    'RUB' => '&#1088;&#1091;&#1073;',
    'RWF' => '&#1585;.&#1587;',
    'SAR' => '&#65020;',
    'SBD' => '&#36;',
    'SCR' => '&#8360;',
    'SDG' => '&#163;', // ?
    'SEK' => '&#107;&#114;',
    'SGD' => '&#36;',
    'SHP' => '&#163;',
    'SLL' => '&#76;&#101;', // ?
    'SOS' => '&#83;',
    'SRD' => '&#36;',
    'STD' => '&#68;&#98;', // ?
    'SVC' => '&#36;',
    'SYP' => '&#163;',
    'SZL' => '&#76;', // ?
    'THB' => '&#3647;',
    'TJS' => '&#84;&#74;&#83;', // ? TJS (guess)
    'TMT' => '&#109;',
    'TND' => '&#1583;.&#1578;',
    'TOP' => '&#84;&#36;',
    'TRY' => '&#8356;', // New Turkey Lira (old symbol used)
    'TTD' => '&#36;',
    'TWD' => '&#78;&#84;&#36;',
    'TZS' => '',
    'UAH' => '&#8372;',
    'UGX' => '&#85;&#83;&#104;',
    'USD' => '&#36;',
    'UYU' => '&#36;&#85;',
    'UZS' => '&#1083;&#1074;',
    'VEF' => '&#66;&#115;',
    'VND' => '&#8363;',
    'VUV' => '&#86;&#84;',
    'WST' => '&#87;&#83;&#36;',
    'XAF' => '&#70;&#67;&#70;&#65;',
    'XCD' => '&#36;',
    'XDR' => '',
    'XOF' => '',
    'XPF' => '&#70;',
    'YER' => '&#65020;',
    'ZAR' => '&#82;',
    'ZMK' => '&#90;&#75;', // ?
    'ZWL' => '&#90;&#36;',
);

$gruppi_articoli = [];

$max_items = 8;
for ($i = 1; $i <= ceil(count($articoli) / $max_items); $i++) {
    $gruppi_articoli[$i] = array_slice($articoli, $max_items * ($i - 1), $max_items, true);
}


if (!empty($this->input->get('debug'))) {
    switch ($this->input->get('debug')) {
        case '1':
            debug($documento, true);
            break;
        case '2':
            debug($articoli, true);
            break;
        case '3':
            debug($settings, true);
            break;
        case '4':
            debug($old_settings, true);
            break;
        case '5':
            debug($scadenze, true);
            break;
    }
}

$_metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');
$metodi_pagamento = array_key_value_map($_metodi_pagamento, 'documenti_contabilita_metodi_pagamento_id', 'documenti_contabilita_metodi_pagamento_valore');

$this->load->model('contabilita/docs');

$documenti_tree = $this->docs->getDocumentiPadri($id);

//    debug($documenti_tree, true);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Documento</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>template/adminlte/bower_components/bootstrap/dist/css/bootstrap.css?v=1" />
    <style>
        .a4_paper {
            width: 100%;
            height: 29.7cm;
            display: block;
            margin: 0 auto;
            margin-bottom: 0.5cm;
        }

        div.big_table {
            align-content: center;
            margin: 50px;
            margin-left: 0;
            margin-right: 0;
            /*font-size: 16px*/
            font-size: 14px;
        }

        div.card-body {
            margin-top: 50px
        }

        .center {
            text-align: center;
        }

        .right {
            width: 140px;
            text-align: right;
        }

        .right1 {
            width: 190px;
            text-align: left;
            margin: 5px;
            padding: 10px;
        }

        .h1 {
            margin-top: 0px;
            margin-bottom: 0px
        }

        .m_logo {
            max-width: 400px;
            height: auto;
            align-content: center;
            text-align: center;
            vertical-align: middle;
            float: left;
        }

        .table_articoli {
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .table_articoli table td,
        .order_info {
            font-size: 85% !important;
        }

        .row {
            margin-top: -20px !important;
        }

        .container {
            width: 97%;
        }

        .paginator {
            text-align: center;
            margin-top: 40px;
        }

        .alt_logo {
            font-weight: 200;
            font-size: xx-large;
        }

        .new_page {
            display: block;
            page-break-after: always !important;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .company_name {
            font-size: 24px;
            font-weight: bold;
        }

        body {
            font-size: 14px !important;
        }

        table {
            border: 1px solid #000;
        }

        td,
        th {
            border-top: none !important;
        }

        .border_right {
            border-right: 1px solid #000 !important;
        }

        .border_bottom {
            border-bottom: 1px solid #000 !important;
        }

        .border_top,
        th {
            border-top: 1px solid #000 !important;
        }
    </style>
</head>

<body class="a4_paper">
    ​<?php foreach ($gruppi_articoli as $pagina => $gruppo) : ?>
    <div id="page_<?php echo $pagina; ?>" class="new_page">
        <div class="big_table">
            <div class="container">
                <div class="row">
                    <div class="col-md-2">
                        <?php if (!empty($settings['documenti_contabilita_settings_company_logo'])) : ?>
                            <img src="<?php echo $settings['documenti_contabilita_settings_company_logo']; ?>" alt="" class="m_logo" />
                        <?php else : ?>
                            <div class="alt_logo"><?php echo $settings['documenti_contabilita_settings_company_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <div class="company_name">MQ Italia srl</div>
                        <div>Zona Industriale Nord D/60</div>
                        <div>33097 Spilimbergo PN</div>
                        <div>C.F. P.IVA Nr. Reg. Imp. 07049710960</div>
                        <div>Tel. +39 0427 948279 - Fax +39 0427 948050</div>
                        <div>e-mail info@mqitaliasrl.it</div>
                    </div>
                    <div class="col-md-5">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="border_right border_bottom">FATTURA / INVOICE</th>
                                    <th class="border_bottom">
                                        NR. <?php echo $documento['documenti_contabilita_numero']; ?><?php echo ($documento['documenti_contabilita_numero']) ? '/' . date('Y', strtotime($documento['documenti_contabilita_data_emissione'])) : ''; ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="border_right border_bottom"><strong>DATA / DATE</strong></td>
                                    <td class="border_bottom"><?php echo date('d/m/Y', strtotime($documento['documenti_contabilita_data_emissione'])); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div><small><strong>Spett.le</strong></small></div>
                                        <div><strong><?php echo $destinatario['ragione_sociale']; ?></strong></div>
                                        <div><?php echo $destinatario['indirizzo']; ?></div>
                                        <div><?php echo $destinatario['cap']; ?> <?php echo $destinatario['citta']; ?></div>
                                        <div><?php echo $destinatario['nazione']; ?></div>
                                    </td>
                                </tr>
                                <tr class="border_top border_right border_bottom">
                                    <td colspan="2">
                                        <div><small><strong>Destinazione / Destination</strong></small></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div>same</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- PAGAMENTO -->
                <div class="row">
                    <div class="col-md-7">
                        <table class="table">
                            <tr class="border_top border_bottom">
                                <td class="border_right">
                                    <small>Cond. Pag.</small>
                                    <div class="text-uppercase">
                                        <?php foreach ($scadenze as $scadenza) : ?>
                                            <?php $data_saldo = strtotime($scadenza['documenti_contabilita_scadenze_data_saldo']);
                                            $data_scadenza = strtotime($scadenza['documenti_contabilita_scadenze_scadenza']);
                                            $data_saldo = strtotime($scadenza['documenti_contabilita_scadenze_data_saldo']);

                                            echo $metodi_pagamento[$scadenza['documenti_contabilita_scadenze_saldato_con']];
                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <small>Banca d'appoggio</small>
                                    <div class="text-uppercase"><?php echo $documento['conti_correnti_nome_istituto']; ?> </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- PAGINAZIONE -->
                <div class="row">
                    <div class="col-md-12 text-right">
                        <strong>Pag. <?php echo $pagina; ?>/<?php echo count($gruppi_articoli); ?></strong>
                    </div>
                </div>

                <div class="card-body">
                    <!-- ARTICOLI ORDINE -->
                    <div class="table_articoli">
                        <table class="table">
                            <?php
                            $iva_perc_before = "";
                            $iva_totale = array();
                            $totale_imponibile = 0;
                            ?>

                            <?php foreach ($gruppo as $articolo) : if (!empty($articolo['documenti_contabilita_articoli_name'])) : ?>
                                    <?php if ($articolo['documenti_contabilita_articoli_iva_perc'] != $iva_perc_before or !$iva_perc_before) : ?>
                                        <?php $iva_totale[$articolo['documenti_contabilita_articoli_iva_perc']] = 0; ?>
                                        <tr class=" text-uppercase border_bottom border_top">
                                            <th class="text-center border_right" width="120">Quantità</th>
                                            <th class="text-center" width="800">Descrizione</th>
                                            <th class="text-center" width="170">Hs Code</th>
                                            <th class="text-center border_right">Origine</th>
                                            <th class="text-center border_right">Prezzo</th>
                                            <th class="text-center" width="250">Tot. imponibile</th>
                                        </tr>
                                        <?php $iva_perc_before = number_format((float)$articolo['documenti_contabilita_articoli_iva_perc'], 2, '.', ''); ?>
                                    <?php endif; ?>

                                    <!-- INTESTAZIONE -->
                                    <tr>
                                        <td class="border_right"></td>
                                        <td class="order_info">
                                            <div class="mb-10">
                                                <strong></strong>
                                            </div>
                                            <div></div>
                                        </td>
                                        <td>
                                        </td>
                                        <td class="border_right"></td>
                                        <td class="border_right"></td>
                                        <td></td>
                                    </tr>
                                    <!-- ./INTESTAZIONE -->

                                    <?php $iva_totale[$articolo['documenti_contabilita_articoli_iva_perc']] = $iva_totale[$articolo['documenti_contabilita_articoli_iva_perc']] + $articolo['documenti_contabilita_articoli_iva']; ?>
                                    <tr>
                                        <td class="border_right">
                                            <span class="pull-left">NO.</span>
                                            <span class="pull-right"><?php echo number_format($articolo['documenti_contabilita_articoli_quantita']); ?></span>
                                        </td>
                                        <td class="strong"><?php echo $articolo['documenti_contabilita_articoli_name']; ?>
                                            <br>
                                            <small><?php echo $articolo['documenti_contabilita_articoli_descrizione']; ?></small>
                                        </td>
                                        <td></td>
                                        <td class="border_right"></td>
                                        <td class="border_right">
                                            <span class="pull-left"><?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?></span>
                                            <span class="pull-right"><?php echo number_format((float)$articolo['documenti_contabilita_articoli_prezzo'], 2, '.', ''); ?>
                                                <!--<?php echo ($articolo['documenti_contabilita_articoli_sconto']) ? "<br /><small>Sconto " . number_format((float)$articolo['documenti_contabilita_articoli_sconto'], 2, '.', '') . '% </small>' : ''; ?>--></span>
                                        </td>
                                        <td>
                                            <span class="pull-left"><?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?></span>
                                            <span class="pull-right"><?php echo $segno . number_format((float)$articolo['documenti_contabilita_articoli_prezzo'] * (float)$articolo['documenti_contabilita_articoli_quantita'], 2, '.', ''); ?></span>
                                        </td>
                                    </tr>
                            <?php endif;
                            endforeach; ?>
                        </table>

                        <!-- ALIQUOTA, IMPONIBILE, IMPOSTA, TOTALE FATTURA -->
                        <table class="table" style="margin-top:50px;">
                            <?php
                            $iva_perc_before = "";
                            $iva_totale = array();
                            $totale_imponibile = 0;
                            ?>

                            <tr class="border_top">
                                <th width="120" class="border_right">Aliquota Iva</th>
                                <th class="border_right">Imponibile</th>
                                <th class="border_right">Imposta</th>
                                <th width="250">Totale fattura</th>
                            </tr>
                            <tr>
                                <?php foreach (json_decode($documento['documenti_contabilita_imponibile_iva_json']) as $id_iva => $foo) : ?>
                                    <?php
                                    $percentuale = $foo[0];
                                    $imponibile_iva = $foo[1];
                                    ?>
                                    <td class="border_right">
                                        <?php echo $percentuale; ?>%
                                        <?php
                                        //echo $valuta[$documento['documenti_contabilita_valuta']]; 
                                        ?>
                                        <?php
                                        //echo $segno . number_format(($imponibile_iva * $percentuale / 100), 2); 
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="border_right">
                                    <span class="pull-left"><?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?> </span>
                                    <span class="pull-right"><?php echo $segno . number_format((float)$documento['documenti_contabilita_imponibile'], 2, '.', ''); ?></span>
                                </td>
                                <td class="border_right">
                                    <span class="pull-left"><?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?> </span>
                                    <span class="pull-right"><?php echo number_format((float)$documento['documenti_contabilita_totale'] - (float)$articolo['documenti_contabilita_articoli_prezzo'], 2, '.', ''); ?></span>
                                </td>
                                <td>
                                    <span class="pull-left"><?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?> </span>
                                    <span class="pull-right"><?php echo $segno . number_format((float)$documento['documenti_contabilita_totale'], 2, '.', ''); ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>
<?php endforeach; ?>
</body>

</html>