<?php
//debug($prime_note);
$this->load->model('contabilita/prima_nota');
$xls_data = [];
$colspanned_rows = [];
$row = 0;
$columns = [
    //TODO: 
];
$styles = $meta = [];

$first = true;

foreach ($prime_note as $key => $prima_nota) {
    //INTESTAZIONE PRIMA NOTA

    if ($first) {
        $first = false;
        $xls_data[$row] = [
            'N° RIGA',
            '',
            'CONTO DARE',
            'IMPORTO DARE',
            '',
            'CONTO AVERE',
            'IMPORTO AVERE',
            '',
            ''
        ];
        $styles['A' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['B' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['C' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['D' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['E' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['F' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['G' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['H' . ($row + 1)] = 'background-color:#e6e6e6';
        $styles['I' . ($row + 1)] = 'background-color:#e6e6e6';
        $row++;
        $xls_data[$row] = [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ];
        $row++;
    }


    //DATI PRIMA NOTA

    $prima_nota_numero_serie = '';

    if (!empty($prima_nota['prime_note_documento'])) {
        $documento = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $prima_nota['prime_note_documento']]);
        //debug($documento);
        $prima_nota_numero_serie = $documento['documenti_contabilita_numero'] . "/" . $documento['documenti_contabilita_serie'];
    } elseif (!empty($prima_nota['prime_note_spesa'])) {
        $spesa = $this->apilib->searchFirst('spese', ['spese_id' => $prima_nota['prime_note_spesa']]);
        if (!empty($spesa)) {
            //debug($spesa);
            $fornitore = json_decode($spesa['spese_fornitore'], true);
            $prima_nota_numero_serie = $spesa['spese_numero'] . ' - ' . $fornitore['ragione_sociale'];
        }
    } else {
    }

    $xls_data[$row] = [

        $prima_nota['documenti_contabilita_settings_company_name'],
        'N. ' . $prima_nota['prime_note_progressivo_annuo'],
        'Prot. ' . $prima_nota['prime_note_protocollo'],
        'Sez. ' . $prima_nota['sezionali_iva_sezionale'],
        'Reg. ' . $prima_nota['prime_note_progressivo_giornaliero'] . ' del ' . dateFormat($prima_nota['prime_note_data_registrazione']),
        'Caus.' . $prima_nota['prime_note_causali_codice'] . '<br />' . $prima_nota['prime_note_causali_descrizione'],

        'Rif. doc. ' . $prima_nota_numero_serie . '<br />' . dateFormat($prima_nota['prime_note_scadenza']),


        '<button class="btn btn-edit-primanota bg-purple" data-primanota="' . base64_encode(json_encode($prima_nota)) . '"><i class="fas fa-edit"></i> Modifica</button>&nbsp;' .
            '<a href="' . base_url('db_ajax/generic_delete/prime_note/' . $prima_nota['prime_note_id']) . '" onclick="return confirm(\'Confermi eliminazione?\');" class="btn btn-delete-primanota bg-red"><i class="fas fa-trash"></i></a>'

    ];
    $styles['A' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su azienda
    $styles['B' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su azienda
    $styles['C' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su numero
    $styles['D' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su data registrazione
    $styles['E' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su protocollo
    $styles['F' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su rif.doc
    $styles['G' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su rif.doc
    $styles['H' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su rif.doc
    $styles['I' . ($row + 1)] = 'font-weight: bold; background-color:#e6e6e6'; //bold su rif.doc
    $row++;

    //DATI REGISTRAZIONI PRIMA NOTA
    foreach ($prima_nota['registrazioni'] as $registrazione) {
        //debug($registrazione, true);
        if ($this->prima_nota->getCodiceCompleto($registrazione, 'avere')) {
            //debug($registrazione);
        }
        $xls_data[$row] = [
            $registrazione['prime_note_registrazioni_numero_riga'],
            '',
            $this->prima_nota->getCodiceCompleto($registrazione, 'dare', '.', true),
            //$registrazione['prime_note_registrazioni_codice_dare_testuale'] . '<br />' . $registrazione['sottocontodare_descrizione'],
            ($registrazione['prime_note_registrazioni_importo_dare'] > 0) ? '€ ' . number_format($registrazione['prime_note_registrazioni_importo_dare'], 2, ',', '.') : '',
            '',
            $this->prima_nota->getCodiceCompleto($registrazione, 'avere', '.', true),
            //$registrazione['prime_note_registrazioni_codice_avere_testuale'] . '<br />' . $registrazione['sottocontoavere_descrizione'],
            ($registrazione['prime_note_registrazioni_importo_avere'] > 0) ? '€ ' . number_format($registrazione['prime_note_registrazioni_importo_avere'], 2, ',', '.') : '',
            '',
            ''
        ];

        $styles['A' . ($row + 1)] = 'font-weight: bold;'; //bold sul n° riga di ogni registrazione
        $styles['D' . ($row + 1)] = 'color: #a94442;'; //color sull'importo dare
        $styles['G' . ($row + 1)] = 'color: #3c763d;'; //color sull'importo avere

        $row++;
    }

    //RIGA SEPARATORE
    $colspanned_rows[] = $row + 1;
    $xls_data[$row++] = [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ];
}
//debug($xls_data, true);

?>

<script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
<link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />

<script src="https://jsuites.net/v4/jsuites.js"></script>
<link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />

<div id="spreadsheet_prime_note"></div>

<div class="row">

    <div class="col-sm-12 text-center">
        <nav>
            <ul class="pagination">
                <?php
                for ($i = 1; $i <= $count_prime_note; $i += 50) :
                    $page = ($i - 1);

                    $current_page = false;

                    if (
                        ($page == $this->input->get('offset'))
                        || ($page == 0 && empty($this->input->get('offset')))
                    ) {
                        $current_page = true;
                    }
                ?>
                    <li <?php echo $current_page ? 'class="active"' : null; ?>>
                        <a href="<?php echo base_url('main/layout/prima-nota/?offset=' . $page); ?>"><?php echo ceil($i / 50); ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<script>
    $(() => {
        var data = <?php echo json_encode($xls_data); ?>;
        var colspanned_rows = <?php echo json_encode($colspanned_rows); ?>;
        var mergeCells = {};
        $.each(colspanned_rows, function(i, el) {
            mergeCells['A' + el] = [9, 1];
        });

        function waitForElement() {
            if (typeof jexcel !== "undefined") {
                var xls_prime_note = jexcel(document.getElementById('spreadsheet_prime_note'), {
                    data: data,
                    columns: [{
                            type: 'text',
                            title: ' ',
                            width: 100,
                        },
                        {
                            type: 'text',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'html',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'text',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'text',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'html',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'html',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'html',
                            title: ' ',
                            width: 90
                        },
                        {
                            type: 'html',
                            title: ' ',
                            width: 90
                        },
                    ],
                    mergeCells: mergeCells,
                    style: <?php echo json_encode($styles); ?>
                });

                xls_prime_note.hideIndex();



                $(() => {
                    var btn_edit_primanota = $('.btn-edit-primanota');
                    btn_edit_primanota.on('click', function() {
                        log('Bottone modifica primanota cliccato, inizializzo il popolamento dei campi')
                        var this_btn = $(this);
                        $('.js_riga_registrazione:visible').remove();
                        initPrimanotaForm(JSON.parse(atob(this_btn.data('primanota'))));
                    });
                });
            } else {
                setTimeout(waitForElement, 250);
            }
        }

        waitForElement();
    });
</script>

<style>
    table.jexcel>colgroup>col:first-child {
        width: 30px !important;
    }
</style>