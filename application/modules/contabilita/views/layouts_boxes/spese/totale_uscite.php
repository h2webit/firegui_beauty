<?php

// echo 'rimosso (riscrivere query sum per evitare di ciclare i dati)';
// return;

$grid_id = $this->db->get_where('grids', ['grids_name' => 'Spese scadenze in uscita'])->row();
if ($grid_id) :
    $grid = $this->datab->get_grid($grid_id->grids_id);
    // $grid_data = $this->datab->get_grid_data($grid, null, '', NULL, 0, null);
    // $totale_uscite = 0;
    // $uscite_da_saldare = 0;

    // foreach ($grid_data as $row) {
    //     $totale_uscite = $totale_uscite + $row['spese_scadenze_ammontare'];
    //     if ($row['spese_scadenze_saldata'] == DB_BOOL_FALSE) {
    //         $uscite_da_saldare = $uscite_da_saldare + $row['spese_scadenze_ammontare'];
    //     }
    // }


    $where = $this->datab->generate_where("grids", $grid_id->grids_id, $value_id);
    if (!$where) {
        $where = ' 1=1 ';
    }


    $totale_uscite = $this->db->query("SELECT SUM(spese_scadenze_ammontare) as s FROM spese_scadenze LEFT JOIN spese ON (spese_id = spese_scadenze_spesa) WHERE $where")->row()->s;
    $uscite_da_saldare = $this->db->query("SELECT SUM(spese_scadenze_ammontare) as s FROM spese_scadenze LEFT JOIN spese ON (spese_id = spese_scadenze_spesa) WHERE $where AND spese_scadenze_saldata = 0")->row()->s;



?>
    <div class="row">
        <div class="col-sm-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>€ <?php echo number_format($totale_uscite, 0, ',', '.'); ?></h3>

                    <p>
                        Totale scadenze fatture acquisto
                    </p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-check"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>€ <?php echo number_format($uscite_da_saldare, 0, ',', '.'); ?></h3>

                    <p>
                        Totale scadenze da saldare
                    </p>
                </div>
                <div class="icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>