<?php

$this->load->model('contabilita/prima_nota');

// Filtro tabella e dati prima nota
$grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
$where = [$this->datab->generate_where("grids", $grid['grids_id'], null)];
$where[] = '(prime_note_modello = 0 OR prime_note_modello IS NULL)';
$primeNoteData = $this->prima_nota->getPrimeNoteData($where, null, ($this->input->get('orderby') ? $this->input->get('orderby') : 'prime_note_data_registrazione'), 0, false);

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

<script>
window.onload = function() {
    var vars = {};
    var x = document.location.search.substring(1).split('&');
    for (var i in x) {
        var z = x[i].split('=', 2);
        vars[z[0]] = unescape(z[1]);
    }

    //if current page number == last page number
    if (vars['page'] == vars['topage']) {
        //document.querySelectorAll('.extra')[0].textContent = 'extra text here';
    }


    /* var last_tr = document.getElementsByClassName('last_tr');
    console.log(last_tr);

    const last_row = last_tr[last_tr.length - 1];
    console.log(last_row);


    const last = Array.from(
      document.getElementsByClassName('last_tr')
    ).pop();


    const bottom = last.getBoundingClientRect().bottom;
    const left = last.getBoundingClientRect().left; */


    if (!document.body.classList.contains('imgActive')) { // CHECK IS IMAGE ALREADY ADDED 
        document.body.classList.add('imgActive');
        var body = document.getElementsByTagName("body")[0];
        var table = document.getElementById('tabella_prime_note');
        console.log(table);
        //console.log(body);

        var img = document.createElement("img");
        img.classList.add('img-responsive');
        img.src = "<?php echo base_url('uploads/test_pdf_bg.png'); ?>";
        /* img.style.position = 'absolute';
        img.style.top = bottom;
        img.style.left = left; */
        document.getElementsByClassName("__table-responsive")[0].appendChild(img);

        /* document.getElementsByClassName("__table-responsive")[0].style.backgroundColor = "red";
        document.getElementsByClassName("__table-responsive")[0].style.paddingBottom = "50px"; */
    }
};
</script>

<style>
.extra {
    color: red;
    background: #000000;
}

.prima_nota_odd {
    /*background-color: #FF7ffc;*/
    background-color: #eeeeee;
}

.prima_nota_even {
    background-color: #ffffff;
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
    padding: 2px 4px;
    border-left: 1px dotted #CCC;
}

.js_prime_note tbody tr td:last-child {

    border-right: 1px dotted #CCC;
}

.div_block_page {
    width: 100%;
    height: 5000px;
    background: black;
    color: #ffffff;
    positon: absolute;
    /*  top e left come ultima riga stampata */
}


/*     .page_container_custom {
        z-index: 10;
        position: relative;
        background: red;
    }
    .__table-responsive {
        z-index: 20;
        position: relative;
    }

    div.page-layout {
        background: green;
        z-index: 30;
        position: relative;
    } */
</style>

<div style="margin-bottom:30px">

    <?php foreach ($filtri as $filtro) : ?>
    <p><strong><?php echo $filtro['label']; ?></strong>: <?php echo $filtro['value']; ?></p>
    <?php endforeach; ?>

</div>

<div class="page_container_custom">
    <div class="__table-responsive">
        <table class="table js_prime_note" id="tabella_prime_note">
            <thead>
                <tr>
                    <th>Protocollo (num. stampa)</th>
                    <th>Data</th>
                    <th>Data doc.</th>
                    <th>N. doc.</th>
                    <th>Riga</th>
                    <th>Descrizione</th>
                    <th>Conto</th>
                    <th>Dare</th>
                    <th>Avere</th>
                    <th>Conto</th>
                </tr>
            </thead>

            <tbody>
                <?php $i = $j = 0;
                foreach ($primeNoteData as $prime_note_id => $prima_nota) : $i++; ?>


                <?php foreach ($prima_nota["registrazioni"] as $registrazione) : $j++; ?>
                <?php
                        $conto_dare = $this->prima_nota->getCodiceCompleto($registrazione, 'dare', '.');
                        $conto_avere = $this->prima_nota->getCodiceCompleto($registrazione, 'avere', '.');
                        ?>

                <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?> <?php echo ($i == count($primeNoteData)) ? 'last_tr' : '';?>" data-id="<?php echo $prime_note_id; ?>">
                    <td>
                        <?php echo $j; //($prima_nota['prime_note_progressivo_annuo']); ?>
                    </td>

                    <td><?php echo dateFormat($prima_nota['prime_note_data_registrazione']); ?></td>

                    <td>
                        <?php /*if (!empty($prima_nota["documenti_contabilita_data_emissione"])) : ?>
                        <?php echo dateFormat($prima_nota["documenti_contabilita_data_emissione"]); ?>

                        <?php elseif (!empty($prima_nota["spese_data_emissione"])) : ?>
                        <?php echo dateFormat($prima_nota["spese_data_emissione"]); ?>
                        <?php else : */ ?>
                        <?php echo dateFormat($prima_nota['prime_note_scadenza']); ?>
                        <?php //endif; 
                                ?>
                    </td>

                    <td>
                        <?php echo character_limiter($registrazione['prime_note_registrazioni_rif_doc'] ?: $prima_nota['prime_note_numero_documento'], 50); ?>
                    </td>

                    <td>
                        <?php echo ($registrazione['prime_note_registrazioni_numero_riga']); ?>
                    </td>

                    <td>
                        <?php

                                if ($registrazione['sottocontodare_descrizione']) {
                                    echo character_limiter($registrazione['sottocontodare_descrizione'],10);
                                } elseif ($registrazione['sottocontoavere_descrizione']) {
                                    echo character_limiter($registrazione['sottocontoavere_descrizione'],10);
                                } else {

                                    echo character_limiter($prima_nota['prime_note_causali_descrizione'], 10);
                                }
                                ?>
                    </td>

                    <td>
                        <?php if ($conto_dare) : ?>
                        <?php if (!empty($registrazione['customers_company'])) : ?>
                        <?php echo character_limiter($registrazione['customers_company'],50); ?><br />
                        <?php endif; ?>
                        <small><?php echo ($conto_dare); ?></small>
                        <?php else : ?>

                        <?php endif; ?>
                    </td>

                    <td class="text-danger text-right" style="white-space: nowrap">
                        <?php echo ($registrazione['prime_note_registrazioni_importo_dare'] > 0) ? '€ ' . number_format($registrazione['prime_note_registrazioni_importo_dare'], 2, ',', '.') : ''; ?>
                    </td>

                    <td class="text-success text-right" style="white-space: nowrap">
                        <?php echo ($registrazione['prime_note_registrazioni_importo_avere'] > 0) ? '€ ' . number_format($registrazione['prime_note_registrazioni_importo_avere'], 2, ',', '.') : ''; ?>
                    </td>

                    <td style="white-space: break-spaces; overflow: auto;">
                        <?php if ($conto_avere) : ?>

                        <?php if (!empty($registrazione['customers_company'])) : ?>
                        <?php echo $registrazione['customers_company']; ?><br />

                        <?php endif; ?>
                        <small><?php echo ($conto_avere); ?></small>

                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>

<!-- <div class="extra">
</div> -->