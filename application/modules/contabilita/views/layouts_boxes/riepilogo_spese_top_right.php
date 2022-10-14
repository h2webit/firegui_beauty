<?php

$grid_id = $this->datab->get_grid_id_by_identifier('contabilita_spese');
$where_spese_str = $this->datab->generate_where("grids", $grid_id, $value_id);

if (!$where_spese_str) {
    $where_spese_str = '(1=1)';
}

$query_spese = " SELECT
    SUM(spese_totale) as s
    FROM
        spese
    WHERE spese_tipologia_fatturazione NOT IN (4) AND $where_spese_str
    ";
$spese_totale = $this->db->query($query_spese)->row()->s;

$query_note_di_credito = " SELECT
    SUM(spese_totale) as s
    FROM
        spese
    WHERE spese_tipologia_fatturazione IN (4) AND $where_spese_str
    ";
$note_di_credito_totale = $this->db->query($query_note_di_credito)->row()->s;

$query_iva_spese = " SELECT
    SUM(spese_iva) as s
    FROM
        spese
    WHERE spese_tipologia_fatturazione NOT IN (4) AND $where_spese_str
    ";
$iva_spese = $this->db->query($query_iva_spese)->row()->s;

$query_iva_note_di_credito = " SELECT
    SUM(spese_iva) as s
    FROM
        spese
    WHERE spese_tipologia_fatturazione IN (4) AND $where_spese_str
    ";
$iva_note_di_credito = $this->db->query($query_iva_note_di_credito)->row()->s;

$spese_totale_imponibile = $spese_totale - $iva_spese;
$note_di_credito_totale_imponibile = $note_di_credito_totale - $iva_note_di_credito;

?>


<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fas fa-hourglass"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Riepilogo Totale</span>
                <span class="info-box-number ">€ <?php echo number_format($spese_totale_imponibile - $note_di_credito_totale_imponibile, 2, ',', '.'); ?> <small>Imponibile</small></span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100.00%"></div>
                </div>
                <span class="info-box-number">€ <?php echo number_format($iva_spese - $iva_note_di_credito, 2, ',', '.'); ?> <small>Iva</small></span>

                </div>

        </div>

    </div>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="info-box bg-aqua">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Riepilogo note di credito</span>
            <span class="info-box-number ">€ <?php echo number_format($note_di_credito_totale_imponibile, 2, ',', '.'); ?> <small>Imponibile</small></span>

            <div class="progress">
                    <div class="progress-bar" style="width: 100.00%"></div>
                </div>
                <span class="info-box-number">€ <?php echo number_format($iva_note_di_credito, 2, ',', '.'); ?> <small>Iva</small></span>


            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->

</div>