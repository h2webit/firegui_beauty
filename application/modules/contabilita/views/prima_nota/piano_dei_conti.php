<?php
$this->load->model('contabilita/prima_nota');
$conteggi = $this->input->get('nascondi_conteggi') != 1;
$piano_dei_conti = $this->prima_nota->getPianoDeiConti($this->input->get('completo') == 1, $this->input->get('nascondi_orfani') == 1, $conteggi);

//debug("Completare conteggi true/false", true);


?>
<style>
    .piano_dei_conti .btn {}

    .pl-15 {
        padding-left: 15px;
    }


    .mastro_container h5 {
        padding: 5px;
        border-radius: 3px;
    }

    .mastro_container h5 .mastro_actions {
        margin-left: 15px;
        visibility: hidden;
        opacity: 0;
        transition: visibility .25s linear, opacity .25s linear;
    }

    .mastro_container:hover h5 .mastro_actions {
        visibility: visible;
        opacity: 1;
    }

    .mastro_totale {
        font-size: 18px;
    }

    tr .conto_actions {
        margin-left: 15px;
        visibility: hidden;
        opacity: 0;
        transition: visibility .25s linear, opacity .25s linear;
    }

    tr:hover .conto_actions {
        visibility: visible;
        opacity: 1;
    }

    .wk_header_filter ul {
        margin: 0;
        padding: 0;
        list-style-type: none;
    }

    .wk_header_filter ul li {

        margin-right: 20px;
        display: inline;
        float: left;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="wk_header_filter">
            <ul>
                <li><label class="container-checkbox ">
                        <input type="checkbox" class="js_checkbox_conteggi" value="1" <?php if ($this->input->get('nascondi_conteggi')) : ?> checked<?php endif; ?> />
                        <span class="checkmark"></span>
                        Nascondi conteggi </label>
                </li>

                <li><label class="container-checkbox ">
                        <input type="checkbox" class="js_checkbox_clienti_fornitori" value="1" <?php if ($this->input->get('completo')) : ?> checked<?php endif; ?> />
                        <span class="checkmark"></span>
                        Includi sottoconti clienti/fornitori </label>
                </li>

                <li><label class="container-checkbox ">
                        <input type="checkbox" class="js_checkbox_nasconti_conti_senza_registrazioni" value="1" <?php if ($this->input->get('nascondi_orfani')) : ?> checked<?php endif; ?> />
                        <span class="checkmark"></span>
                        Nascondi conti privi di registrazioni </label>
                </li>

                <li><label class="container-checkbox ">
                        <input type="checkbox" class="js_checkbox_nascondi_importi_a_zero" value="1" <?php if ($this->input->get('nascondi_zero')) : ?> checked<?php endif; ?> />
                        <span class="checkmark"></span>
                        Nascondi conti a zero</label>
                </li>

            </ul>
        </div>
    </div>
</div>

<div class="container-fluid box box-danger">

    <div class="row">
        <?php foreach ($piano_dei_conti as $mastro_tipo) : ?>



            <div class="piano_dei_conti col-md-6">
                <div class="box-header">
                    <h3 class="box-title">
                        <?php echo $mastro_tipo['documenti_contabilita_mastri_tipo_value']; ?>

                        <a class="btn btn-xs btn-success js_open_modal" data-toggle="tooltip" title="Aggiungi mastro" href="<?php echo base_url('get_ajax/modal_form/new-mastro/?documenti_contabilita_mastri_tipo=' . $mastro_tipo['documenti_contabilita_mastri_tipo_id']); ?>">
                            <i class="fa fa-plus"></i>
                        </a>
                    </h3>
                </div>

                <div class="box-body">
                    <?php foreach ($mastro_tipo['mastri'] as $mastro) : ?>
                        <div class="mastro_container" id="mastro_<?php echo $mastro['documenti_contabilita_mastri_id']; ?>">
                            <h5 class="bg-primary">
                                <strong class="text-uppercase"><?php echo $mastro['documenti_contabilita_mastri_codice']; ?> <?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?></strong>
                                <span class="mastro_actions">
                                    <a class="btn btn-xs btn-success js_open_modal" data-toggle="tooltip" title="Aggiungi conto" href="<?php echo base_url('get_ajax/modal_form/new_documenti_contabilita_conto?documenti_contabilita_conti_mastro=' . $mastro['documenti_contabilita_mastri_id']); ?>">
                                        <i class="fa fa-plus"></i>
                                    </a>
                                    <!--<a class="btn bg-orange btn-xs" data-toggle="tooltip" title="Stampa mastro" href="<?php echo base_url('contabilita/primanota/stampa_mastro/' . $mastro['documenti_contabilita_mastri_id']); ?>" target="_blank">
                                        <i class="fa fa-print"></i>
                                    </a>-->
                                    <a data-toggle="tooltip" title="Modifica mastro" class="btn bg-purple btn-xs js_open_modal" href="<?php echo base_url('get_ajax/modal_form/new-mastro/' . $mastro['documenti_contabilita_mastri_id']); ?>">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </span>
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Descrizione</th>
                                            <?php if ($conteggi) : ?>
                                                <th class="text-center">Importo</th>
                                            <?php endif; ?>
                                            <th style="min-width: 100px" class="text-right">Azioni</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($mastro['conti'] as $conto) : ?>
                                            <?php if ($this->input->get('nascondi_zero') == 1 && $conteggi && $conto['totale'] == 0) {
                                                continue;
                                            } ?>
                                            <tr style="opacity: 1; color:#14518d;">
                                                <td>
                                                    <span class="clickable" id="movimenti-55">
                                                        &nbsp;<b><?php echo $conto['documenti_contabilita_conti_codice_completo']; ?></b> <?php echo $conto['documenti_contabilita_conti_descrizione']; ?>
                                                    </span>
                                                    <span class="conto_actions">
                                                        <a class="btn btn-xs btn-success js_open_modal" data-toggle="tooltip" title="Aggiungi sottoconto" href="<?php echo base_url('get_ajax/modal_form/new-sottoconto?documenti_contabilita_sottoconti_mastro=' . $mastro['documenti_contabilita_mastri_id'] . '&documenti_contabilita_sottoconti_conto=' . $conto['documenti_contabilita_conti_id']); ?>">
                                                            <i class="fa fa-plus"></i>
                                                        </a>
                                                        <!--<a class="btn bg-orange btn-xs" data-toggle="tooltip" title="Stampa mastro" href="<?php echo base_url('contabilita/primanota/stampa_mastro/' . $mastro['documenti_contabilita_mastri_id']); ?>" target="_blank">
                                        <i class="fa fa-print"></i>
                                    </a>-->

                                                    </span>
                                                </td>
                                                <?php if ($conteggi) : ?>
                                                    <td class="text-right">
                                                        <?php e_money($conto['totale']); ?> €
                                                    </td>
                                                <?php endif; ?>
                                                <td class="text-right">
                                                    <span class="tools">
                                                        <a class="btn bg-orange btn-xs" data-toggle="tooltip" title="Stampa conto" href="<?php echo base_url('contabilita/primanota/stampa_conto/' . $conto['documenti_contabilita_conti_id']); ?>" target="_blank">
                                                            <i class="fa fa-print"></i>
                                                        </a>
                                                        <a class="btn bg-purple btn-xs js_open_modal" data-toggle="tooltip" title="Modifica conto" href="<?php echo base_url('get_ajax/modal_form/new_documenti_contabilita_conto/' . $conto['documenti_contabilita_conti_id']); ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a class="btn btn-danger btn-xs js_confirm_button" href="<?php echo base_url('db_ajax/generic_delete/documenti_contabilita_conti/' . $conto['documenti_contabilita_conti_id']); ?>" data-toggle="tooltip" title="Elimina">
                                                            <i class="fa fa-trash"></i>
                                                        </a>

                                                    </span>
                                                </td>

                                            </tr>
                                            <?php foreach ($conto['sottoconti'] as $sottoconto) : ?>

                                                <?php if ($this->input->get('nascondi_zero') == 1 && $conteggi && $sottoconto['totale'] == 0) {
                                                    continue;
                                                } ?>

                                                <tr>
                                                    <td style="padding-left: 30px;opacity: 0.75;">
                                                        <span class="clickable">
                                                            &nbsp;<b><?php echo $sottoconto['documenti_contabilita_sottoconti_codice_completo']; ?></b> <?php echo $sottoconto['documenti_contabilita_sottoconti_descrizione']; ?>
                                                        </span>
                                                    </td>
                                                    <?php if ($conteggi) : ?>
                                                        <td class="text-right">
                                                            <?php e_money($sottoconto['totale']); ?> €
                                                        </td>
                                                    <?php endif; ?>
                                                    <td class="text-right">
                                                        <span class="tools">
                                                            <a class="btn bg-orange btn-xs" data-toggle="tooltip" title="Stampa conto" href="<?php echo base_url('contabilita/primanota/stampa_sottoconto/' . $sottoconto['documenti_contabilita_sottoconti_id']); ?>" target="_blank">
                                                                <i class="fa fa-print"></i>
                                                            </a>
                                                            <a class="btn bg-purple btn-xs js_open_modal" data-toggle="tooltip" title="Modifica sottoconto" href="<?php echo base_url('get_ajax/modal_form/new-sottoconto/'  . $sottoconto['documenti_contabilita_sottoconti_id']); ?>">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a class="btn btn-danger btn-xs js_confirm_button" href="<?php echo base_url('db_ajax/generic_delete/documenti_contabilita_sottoconti/' . $sottoconto['documenti_contabilita_sottoconti_id']); ?>" data-toggle="tooltip" title="Elimina">
                                                                <i class="fa fa-trash"></i>
                                                            </a>

                                                        </span>
                                                    </td>

                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>

                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <th class="text-right"></th>
                                            <th class="text-right text-uppercase">Totale</th>
                                            <?php if ($conteggi) : ?>
                                                <th class="text-right"><?php e_money($mastro['totale']); ?> €</th>
                                            <?php endif; ?>
                                        </tr>
                                    </tfoot>
                                </table>

                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

                <table class="table table-condensed table-hover">
                    <tbody>
                        <tr>
                            <th class="text-right">
                                <strong class="mastro_totale text-uppercase">Ricavi:</strong>
                            </th>
                            <td class="text-right" width="150">
                                <strong class="mastro_totale text-uppercase">---,__ €</strong>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-right">
                                <strong class="mastro_totale text-uppercase">Costi:</strong>
                            </th>
                            <td class="text-right" width="150">
                                <strong class="mastro_totale text-uppercase">---,__ €</strong>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-right">
                                <strong class="mastro_totale text-uppercase">Utile/perdita:</strong>
                            </th>
                            <td class="text-right" width="150">
                                <strong class="mastro_totale text-uppercase">---,-- €</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function PrintElem(elem) {
        var mywindow = window.open('', 'PRINT', 'height=400,width=600');

        mywindow.document.write('<html><head><title>' + document.title + '</title>');
        mywindow.document.write('</head><body >');
        mywindow.document.write('<h1>' + document.title + '</h1>');
        mywindow.document.write(document.getElementById(elem).innerHTML);
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10*/

        mywindow.print();
        mywindow.close();

        return true;
    }

    $(() => {
        $('.js_checkbox_conteggi,.js_checkbox_clienti_fornitori,.js_checkbox_nasconti_conti_senza_registrazioni,.js_checkbox_nascondi_importi_a_zero').on('click', function() {
            var nascondi_conteggi = (!$('.js_checkbox_conteggi').is(':checked') ? '0' : '1');
            var completo = (!$('.js_checkbox_clienti_fornitori').is(':checked') ? '0' : '1');
            var nascondi_orfani = (!$('.js_checkbox_nasconti_conti_senza_registrazioni').is(':checked') ? '0' : '1');
            var nascondi_zero = (!$('.js_checkbox_nascondi_importi_a_zero').is(':checked') ? '0' : '1');
            var redirect = base_url + 'main/layout/piano-dei-conti?completo=' + completo + '&nascondi_conteggi=' + nascondi_conteggi + '&nascondi_orfani=' + nascondi_orfani + '&nascondi_zero=' + nascondi_zero;
            location.href = redirect;
        });


    });
</script>