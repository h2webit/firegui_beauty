    <?php

    // echo 'rimosso (riscrivere query sum per evitare di ciclare i dati)';
    // return;

    $grid_id = $this->db->get_where('grids', ['grids_append_class' => 'js_scadenziario_entrate'])->row();
    if ($grid_id) :

        $grid = $this->datab->get_grid($grid_id->grids_id);
        $where = $this->datab->generate_where("grids", $grid_id->grids_id, $value_id);

        //debug($where);

        $totale_fatture = $this->db->query("SELECT SUM(documenti_contabilita_scadenze_ammontare) as s FROM documenti_contabilita_scadenze LEFT JOIN documenti_contabilita ON (documenti_contabilita_id = documenti_contabilita_scadenze_documento) WHERE $where")->row()->s;
        $fatture_non_saldate = $this->db->query("SELECT SUM(documenti_contabilita_scadenze_ammontare) as s FROM documenti_contabilita_scadenze LEFT JOIN documenti_contabilita ON (documenti_contabilita_id = documenti_contabilita_scadenze_documento) WHERE $where AND (documenti_contabilita_scadenze_saldata = 0 OR documenti_contabilita_scadenze_saldata IS NULL)")->row()->s;

        //debug($this->db->last_query());

    ?>
        <div class="row">
            <div class="col-sm-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>€ <?php echo number_format($totale_fatture, 0, ',', '.'); ?></h3>

                        <p>
                            Totale scadenze fatture vendita
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
                        <h3>€ <?php echo number_format($fatture_non_saldate, 0, ',', '.'); ?></h3>

                        <p>
                            Totale scadenze non saldate
                        </p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>