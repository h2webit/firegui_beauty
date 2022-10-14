<?php
//Prendo il filtro della grid movimenti
$grid_id = $this->datab->get_grid_id_by_identifier('flussi_cassa_movimenti');
$where = $this->datab->generate_where("grids", $grid_id, $value_id);


$_entrate = $this->db->query("SELECT SUM(flussi_cassa_importo) as s, CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) as d FROM flussi_cassa WHERE flussi_cassa_tipo = 1 AND $where GROUP BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) ORDER BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0))")->result_array();
$_uscite = $this->db->query("SELECT SUM(flussi_cassa_importo) as s, CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) as d FROM flussi_cassa WHERE flussi_cassa_tipo = 2 AND $where GROUP BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) ORDER BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0))")->result_array();
//die($this->db->last_query());
$righe = $entrate = $uscite = [];
$somma_entrate = $somma_uscite = 0;
foreach ($_entrate as $entrata) {
    $righe[$entrata['d']] = $entrata['d'];
    $entrate[$entrata['d']] = $entrata['s'];
    $uscite[$entrata['d']] = 0;
    $somma_entrate += $entrata['s'];
}
foreach ($_uscite as $uscita) {
    $righe[$uscita['d']] = $uscita['d'];
    $uscite[$uscita['d']] = $uscita['s'];
    if (!array_key_exists($uscita['d'], $entrate)) {
        $entrate[$uscita['d']] = 0;
    }
    $somma_uscite += $uscita['s'];
}

sort($righe);
?>
<table class="table bilancino" data-totalable="1">
    <thead>
        <tr>
            <th>Mese</th>
            <th style="text-align: right;">Entrate</th>
            <th style="text-align: right;">Uscite</th>
            <th style="text-align: right;">Differenza</th>
            <th style="text-align: right;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($righe as $data) : ?>
            <tr>
                <td><?php echo $data; ?></td>
                <td style="text-align: right; color: green;">&euro;
                    <?php
                    echo "<span style='display:none' class='entrata'>" . number_format($entrate[$data], 2, ',', '.') . "</span>";
                    echo number_format($entrate[$data], 2, ',', '.');
                    ?>
                </td>

                <td style="text-align: right; color: red">&euro; -
                    <?php
                    echo "<span style='display:none' class='uscita'>" . number_format($uscite[$data], 2, ',', '.') . "</span>";
                    echo number_format($uscite[$data], 2, ',', '.');
                    ?>
                </td>
                <td style="text-align: right;">&euro;
                    <?php
                    $differenza = number_format($entrate[$data] - $uscite[$data], 2, ',', '.');
                    echo "<span style='display:none' class='differenza'>{$differenza}</span>";
                    echo (substr($differenza, 0, 1) == '-') ? "<span style='color: red'>{$differenza}</span>" : "<span style='color: green;'>{$differenza}</span>";
                    ?>
                </td>
                <td style="text-align: right;">
                    <?php
                    $saldo_progressivo = $this->db->query("SELECT SUM(CASE flussi_cassa_tipo WHEN '1' THEN flussi_cassa_importo WHEN '2' THEN -1*flussi_cassa_importo ELSE 0 END) as s FROM flussi_cassa WHERE flussi_cassa_data <= '" . date('Y-m-t', strtotime($data . '-01')) . "' AND $where")->row()->s;
                    ?>
                    &euro;
                    <?php
                    $saldo = number_format($saldo_progressivo, 2, ',', '.');
                    echo "<span style='display:none' class='saldo_progressivo'>{$saldo}</span>";
                    echo (substr($saldo, 0, 1) == '-') ? "<span style='color: red'>{$saldo}</span>" : "<span style='color: green;'>{$saldo}</span>";
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td style="font-weight: bold;">Totali:</td>
            <td class="tot_entrate" style="color: green;text-align:right">&euro; <?php echo number_format($somma_entrate, 2, ',', '.'); ?></td>
            <td class="tot_uscite" style="color: red;text-align:right">&euro; -<?php echo number_format($somma_uscite, 2, ',', '.'); ?></td>
            <td class="tot_differenza" style="text-align:right"></td>
            <td class="tot_saldo_prog" style="text-align:right"></td>
        </tr>
    </tfoot>
</table>

<script>
    $(function() {
        $('.bilancino').dataTable({
            stateSave: true
        });
    });
</script>