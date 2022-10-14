<?php

class Giacenze extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->settings = $this->db->get('settings')->row_array();
    }



    public function get_giacenze($prodotto_id)
    {
        echo json_encode($this->db->query("SELECT SUM(giacenza_quantita) AS c FROM giacenza WHERE giacenza_prodotto = '{$prodotto_id}'")->row()->c);
    }

    public function get_grid_data($valueID = null)
    {

        /**
         * VECCHIO EVAL
         * 
            <?php
            $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$data['movimenti_articoli_prodotto_id']}' AND movimenti_magazzino = '{$data['movimenti_magazzino']}'")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$data['movimenti_articoli_prodotto_id']}' AND movimenti_magazzino = '{$data['movimenti_magazzino']}'")->row()->qty;
            $quantity = $quantity_carico - $quantity_scarico;
            ?>
            <?php echo $quantity; 

            ?>
            <?php if ($this->datab->module_installed('magazzino')) : ?>
            <?php 
            $data = array_merge($data, $this->apilib->view('fw_products', $data['movimenti_articoli_prodotto_id']));
            ?>
            <?php if ($data['fw_products_quantity'] && $data['fw_products_type'] == 1) : ?>
                <span data-toggle="tooltip" title="" data-original-title="Show stocks">
                <a id="js_showhide_stock<?php echo $data['fw_products_id']; ?>" class="btn btn-xs js_open_modal" href="<?php echo base_url('get_ajax/layout_modal/product-quantities/'.$data['fw_products_id']); ?>" style="background-color: rgb(33, 150, 243)">
                    <span class="fas fa-warehouse" style="color:white !important;"></span>
                    </a>
                </span>
                <?php endif; ?>
            <?php endif; ?>
         */


        header("Cache-Control: no-cache, must-revalidate");

        if ($this->auth->guest()) {
            echo (json_encode(array('iTotalRecords' => 0, 'iTotalDisplayRecords' => 0, 'sEcho' => null, 'aaData' => [])));
        } else {
            $grid_id = $this->datab->get_grid_id_by_identifier('giacenze_prodotti');
            /**
             * Info da datatable
             */
            $limit = $this->input->post('iDisplayLength') ?: 10;
            $offset = $this->input->post('iDisplayStart') ?: 0;
            $search = $this->input->post('sSearch') ?: null;
            $s_echo = $this->input->post('sEcho') ?: null;

            //$order_col = $this->input->post('iSortCol_0') ?: null;
            $order_col = $this->input->post('iSortCol_0');
            $order_dir = $this->input->post('sSortDir_0') ?: null;

            // Prendo i dati della grid
            $grid = $this->datab->get_grid($grid_id);

            $has_bulk = !empty($grid['grids']['grids_bulk_mode']);

            $preview_fields = $this->db->join('entity', 'fields_entity_id = entity_id')->get_where(
                'fields',
                array('fields_entity_id' => $grid['grids']['grids_entity_id'], 'fields_preview' => DB_BOOL_TRUE)
            )
                ->result_array();

            $where = $this->datab->search_like($search, array_merge($grid['grids_fields'], $preview_fields));



            if (preg_match('/(\()+(\))+/', $where)) {
                $where = '';
            }

            // fix da cui sopra per prendere il default order
            //if ($order_col !== null && isset($grid['grids_fields'][$order_col]['fields_name'])) {
            if ($order_col !== null && $order_col !== false) {

                if ($has_bulk) {
                    $order_col -= 1;
                }

                if (isset($grid['grids_fields'][$order_col]['fields_name'])) {
                    //Se il campo è multilingua, forzo l'ordinamento per chiave lingua corrente
                    if ($grid['grids_fields'][$order_col]['fields_multilingual'] == DB_BOOL_TRUE) {
                        $order_by = "{$grid['grids_fields'][$order_col]['fields_name']}->>'{$this->datab->getLanguage()['id']}' {$order_dir}";
                    } elseif ($grid['grids_fields'][$order_col]['fields_type'] == 'JSON') {
                        $order_by = "{$grid['grids_fields'][$order_col]['fields_name']}::TEXT {$order_dir}";
                    } else {
                        $order_by = "{$grid['grids_fields'][$order_col]['fields_name']} {$order_dir}";
                    }
                } else {
                    //Se entro qui, verifico se il campo passato per l'ordinamento non sia per caso un eval cachable...
                    if ($grid['grids_fields'][$order_col]['grids_fields_eval_cache_type'] == 'query_equivalent' || !empty($grid['grids_fields'][$order_col]['grids_fields_eval_cache_data'])) {
                        $order_by = "{$grid['grids_fields'][$order_col]['grids_fields_eval_cache_data']} {$order_dir}";
                    } else {
                        $order_by = null;
                    }
                }
            } else {
                $order_by = null;
            }

            $group_by = ($grid['grids']['grids_group_by']) ?: null;

            // Added where_append in get ajax
            if ($where_append = $this->input->get('where_append')) {
                if ($where) {
                    $where .= ' AND ' . $where_append;
                } else {
                    $where = $where_append;
                }
            }
            $where = $this->datab->generate_where("grids", $grid['grids']['grids_id'], $valueID, is_array($where) ? implode(' AND ', $where) : $where);

            //$grid_data = $this->datab->get_grid_data($grid, $valueID, $where, (is_numeric($limit) && $limit > 0) ? $limit : NULL, $offset, $order_by, false, ['group_by' => $group_by, 'search' => $search, 'preview_fields' => $preview_fields]);
            $query = "SELECT *, 
                    SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) as fw_products_quantity
                    FROM fw_products 
                    LEFT JOIN movimenti_articoli ON movimenti_articoli.movimenti_articoli_prodotto_id = fw_products.fw_products_id
                    LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) 
                    LEFT JOIN magazzini ON (movimenti_magazzino = magazzini.magazzini_id)
                    
                    ";
            if ($where) {
                $query .= " WHERE $where ";
            }
            $query .= "GROUP BY fw_products.fw_products_id,movimenti_magazzino";
            if ($order_by) {
                $query .= " ORDER BY $order_by ";
            }


            if ($limit > 0) {

                $query .= " LIMIT $limit ";
            }
            if ($offset) {
                $query .= " OFFSET $offset ";
            }


            $grid_data = $this->db->query($query)->result_array();

            $out_array = array();
            foreach ($grid_data as $dato) {
                $dato['value_id'] = $valueID;
                $tr = array();
                if ($has_bulk) {
                    $tr[] = '<input type="checkbox" class="js_bulk_check" value="' . $dato[$grid['grids']['entity_name'] . "_id"] . '" />';
                }
                foreach ($grid['grids_fields'] as $field) {
                    // debug($dato);
                    //debug($field);
                    if (in_array(strtoupper($field['fields_type']), ['FLOAT', 'DOUBLE'])) {
                        $dato[$field['fields_name']] = number_format($dato[$field['fields_name']], 2, ',', '.');
                    }
                    $tr[] = $this->datab->build_grid_cell($field, $dato);
                }

                //Unset to avoi override
                unset($dato['value_id']);
                // Controlla se ho delle action da stampare in fondo
                if ($grid['grids']['grids_layout'] == 'datatable_ajax_inline') {
                    $tr[] = $this->load->view('box/grid/inline_edit', array('id' => $dato[$grid['grids']['entity_name'] . "_id"]), TRUE);
                    $tr[] = $this->load->view('box/grid/inline_delete', array('id' => $dato[$grid['grids']['entity_name'] . "_id"]), TRUE);
                } elseif (($grid['grids']['grids_layout'] == 'datatable_ajax_inline_form' || $grid['grids']['grids_inline_edit']) && $grid['grids']['grids_actions_column'] == DB_BOOL_TRUE) {
                    $tr[] = $this->load->view('box/grid/inline_form_actions', array(
                        'id' => $dato[$grid['grids']['entity_name'] . "_id"],
                        'links' => $grid['grids']['links'],
                        'row_data' => $dato,
                        'grid' => $grid['grids']
                    ), TRUE);
                } elseif (grid_has_action($grid['grids']) && $grid['grids']['grids_actions_column'] == DB_BOOL_TRUE) {
                    $tr[] = $this->load->view('box/grid/actions', array(
                        'links' => $grid['grids']['links'],
                        'id' => $dato[$grid['grids']['entity_name'] . "_id"],
                        'row_data' => $dato,
                        'grid' => $grid['grids'],
                    ), TRUE);
                }

                $out_array[] = $tr;
            }

            $totalRecords = $this->datab->get_grid_data($grid, $valueID, null, null, 0, null, true, ['group_by' => $grid['grids']['grids_group_by']]);
            $totalDisplayRecord = $this->datab->get_grid_data($grid, $valueID, $where, null, 0, null, true, ['group_by' => $grid['grids']['grids_group_by']]);


            echo json_encode(array(
                'iTotalRecords' => $totalRecords,
                'iTotalDisplayRecords' => $totalDisplayRecord,
                'sEcho' => $s_echo,
                'aaData' => $out_array
            ));
        }
    }
}
