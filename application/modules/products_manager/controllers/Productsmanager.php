<?php


class Productsmanager extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function print_barcode($type)
    {
        $val = $this->input->get('val');
        $w = $this->input->get('w');
        $h = $this->input->get('h');
        $left = $this->input->get('left');
        $top = $this->input->get('top');
        $val = base64_decode($val);
        $product_name = base64_decode($this->input->get('product_name'));
        $this->load->view('products_manager/barcode/print', compact('type', 'product_name', 'val', 'w', 'h', 'left', 'top'));
    }
    
    public function create_product()
    {
        $input = $this->input->post();
        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('fw_products_name', t('article name'), 'trim|required');
        $this->form_validation->set_rules('fw_products_sku', t('sku'), 'trim');
        $this->form_validation->set_rules('fw_products_sell_price', t('price'), 'required');
        
        //        if (empty($input['fw_products_categories'])) {
        //            echo json_encode(array(
        //                'status' => 3,
        //                'txt' => t("Choose at least one category"),
        //                'data' => ''
        //            ));
        //            exit;
        //        }
        
        if ($this->form_validation->run() == false) {
            echo json_encode([
                'status' => 3,
                'txt'    => validation_errors(),
                'data'   => '',
            ]);
        } else {
            $input['fw_products_barcode'] = array_filter($input['fw_products_barcode']);
            $provider_codes = [];
            if (!empty($input['fw_products_provider_code'])) {
                foreach ($input['fw_products_provider_code'] as $name => $vals) {
                    foreach ($vals as $key => $val) {
                        if ($val) {
                            $provider_codes[$key][$name] = $val;
                        }
                    }
                }
            }
            
            $this->session->set_userdata('files', []);
            
            $prodotto = [
                'fw_products_name'               => $input['fw_products_name'],
                'fw_products_brand'              => (empty($input['fw_products_brand'])) ? null : $input['fw_products_brand'],
                'fw_products_supplier'           => (empty($input['fw_products_supplier'])) ? null : $input['fw_products_supplier'],
                'fw_products_description'        => $input['fw_products_description'],
                'fw_products_categories'         => (empty($input['fw_products_categories'])) ? null : $input['fw_products_categories'],
                'fw_products_punto_vendita'      => (!empty($input['fw_products_punto_vendita'])) ? $input['fw_products_punto_vendita'] : DB_BOOL_FALSE,
                'fw_products_provider_price'     => $input['fw_products_provider_price'],
                'fw_products_discounted_price'   => (empty($input['fw_products_discounted_price'])) ? null : $input['fw_products_discounted_price'],
                'fw_products_tax'                => $input['fw_products_tax'],
                //'fw_products_discount_percentage' => $input['fw_products_discount_percentage'],
                'fw_products_sell_price_tax_included' => $input['fw_products_sell_price_tax_included'],
                'fw_products_sell_price'         => $input['fw_products_sell_price'],
                'fw_products_is_active'          => DB_BOOL_TRUE,
                //'fw_products_fidelity_points ' => 0,
                'fw_products_sku'                => $input['fw_products_sku'],
                //'fw_products_provider_code' => $input['fw_products_provider_code'],
                'fw_products_provider_code'      => (!empty($provider_codes)) ? json_encode($provider_codes) : null,
                'fw_products_barcode'            => json_encode(($input['fw_products_barcode']) ?: [time()]),
                'fw_products_markup_percentage'  => $input['fw_products_markup_percentage'],
                'fw_products_show_in_counter'    => (empty($input['fw_products_show_in_counter'])) ? 0 : $input['fw_products_show_in_counter'], //show in punto cassa
                'fw_products_sconto'             => $input['fw_products_sconto'],
                'fw_products_sconto2'            => $input['fw_products_sconto2'],
                'fw_products_sconto3'            => $input['fw_products_sconto3'],
                'fw_products_images'             => $input['fw_products_images'] ?? null,
                'fw_products_quantity'           => $input['fw_products_quantity'],
                'fw_products_min_quantity'       => $input['fw_products_min_quantity'],
                'fw_products_weight'             => $input['fw_products_weight'],
                'fw_products_width'              => $input['fw_products_width'],
                'fw_products_height'             => $input['fw_products_height'],
                'fw_products_depth'              => $input['fw_products_depth'],
                'fw_products_peso_specifico'     => $input['fw_products_peso_specifico'],
                'fw_products_type'               => $input['fw_products_type'], //Simple o Configurable
                'fw_products_warehouse_location' => $input['fw_products_warehouse_location'],
                'fw_products_notes'              => $input['fw_products_notes'],
                'fw_products_unita_misura'       => (!empty($input['fw_products_unita_misura'])) ? $input['fw_products_unita_misura'] : null,
                'fw_products_fuori_listino'      => (empty($input['fw_products_fuori_listino'])) ? null : $input['fw_products_fuori_listino'],
                'fw_products_gruppi'             => (empty($input['fw_products_gruppi'])) ? null : $input['fw_products_gruppi'],
                'fw_products_stock_management'   => (empty($input['fw_products_stock_management'])) ? 0 : $input['fw_products_stock_management'],
                'fw_products_kind'               => (empty($input['fw_products_kind'])) ? 1 : $input['fw_products_kind'],
                'fw_products_out_of_production'  => (empty($input['fw_products_out_of_production'])) ? 0 : $input['fw_products_out_of_production'],
            
            ];
            if ($input['fw_products_type'] == 1) {
                $prodotto['fw_products_json_attributes'] = (!empty($input['fw_products_json_attributes_simple']) ? json_encode($input['fw_products_json_attributes_simple']) : null);
            } else {
                $prodotto['fw_products_json_attributes'] = (!empty($input['fw_products_json_attributes_configurable']) ? json_encode($input['fw_products_json_attributes_configurable']) : null);
            }
            
            if (!empty($input['applica_prezzo_subscriptions'])) {
                $prodotto['applica_prezzo_subscriptions'] = DB_BOOL_TRUE;
            }
            
            //debug($prodotto, true);
            
            if (!empty($input['prodotto_id'])) {
                $prodotto_id = $input['prodotto_id'];
                $this->apilib->edit('fw_products', $prodotto_id, $prodotto);
            } else {
                $prodotto_id = $this->apilib->create('fw_products', $prodotto, false);
            }
            if (!empty($input['product_prices'])) {
                foreach ($input['product_prices'] as $price_list_label_id => $price) {
                    $this->db->query("DELETE FROM price_list WHERE price_list_product = '$prodotto_id' AND price_list_label = '$price_list_label_id'");
                    $this->apilib->create('price_list', [
                        'price_list_product' => $prodotto_id,
                        'price_list_label'   => $price_list_label_id,
                        'price_list_price'   => $price ?: $input['fw_products_sell_price'],
                    ]);
                }
            }
            
            //die('TODO: passaggio a entità unica per tutti i prodotti. Andrà proprio droppata la tabella...');
            if ($input['prodotto_id']) { //Se sono in modifica rimuovo i vecchi prodotti configurati
                // $this->db->query("DELETE FROM fw_products WHERE fw_products_parent = '$prodotto_id'");
            }
            
            if ($input['fw_products_type'] == '2') { // devo creare le varianti solo se il tipo prodotto è "configurabile"
                $variations_ids = [];
                foreach ($input['config_nome_prodotto'] as $key => $c_prod) {
                    //Skippo il primo perchè è quello hidden che viene usato nella maschera di inserimento per fare il .clone() in jquery. Non mi piace tantissimo sta cosa ma funziona.
                    if ($key == 0) {
                        continue;
                    }
                    
                    //debug($input, true);
                    
                    $configurabile = [
                        'fw_products_parent'          => $prodotto_id,
                        'fw_products_name'            => $c_prod,
                        'fw_products_type'            => 1,
                        'fw_products_sku'             => $input['config_sku_code'][$key],
                        'fw_products_provider_price'  => $input['config_p_acquisto'][$key],
                        'fw_products_sell_price'      => $input['config_p_vendita'][$key],
                        'fw_products_quantity'        => $input['config_q_disponibile'][$key],
                        'fw_products_min_quantity'    => $input['config_soglia_riordine'][$key],
                        'fw_products_json_attributes' => $input['config_attributi'][$key], //json_encode($attributi)
                    ];
                    
                    $immagini_configurabile = [];
                    if (!empty($input['fw_products_images'])) {
                        // foreach ($input['fw_products_images'] as $rel_id) {
                        //     $immagine = $this->db->get_where('prodotti_immagini', ['prodotti_immagini_id' => $rel_id])->row_array();
                        //     $file = $immagine['prodotti_immagini_immagine'];
                        //     $ext = pathinfo($file, PATHINFO_EXTENSION);
                        
                        //     $newFile = str_ireplace('.' . $ext, '', $file) . '_' . $key . '.' . $ext;
                        
                        //     if (file_exists(FCPATH . 'uploads/' . $file) && copy(FCPATH . 'uploads/' . $file, FCPATH . 'uploads/' . $newFile)) {
                        //         $immagine_id = $this->apilib->create('prodotti_immagini', ['prodotti_immagini_immagine' => $newFile], false);
                        //         $immagini_configurabile[] = $immagine_id;
                        //     }
                        // }
                        $configurabile['fw_products_images'] = $input['fw_products_images'];
                    }
                    
                    if (!empty($input['config_id'][$key])) {
                        $variations_ids[] = $input['config_id'][$key];
                        //debug($input['config_id'][$key]);
                        // debug($input['config_id'][$key]);
                        // debug($configurabile);
                        $this->apilib->edit('fw_products', $input['config_id'][$key], $configurabile);
                        $configurabile_id = $input['config_id'][$key];
                    } else {
                        //debug('test');
                        $new_configurabile = [
                            'fw_products_brand'              => (empty($input['fw_products_brand'])) ? null : $input['fw_products_brand'],
                            'fw_products_provider'           => (empty($input['fw_products_provider'])) ? null : $input['fw_products_provider'],
                            'fw_products_description'        => $input['fw_products_description'],
                            'fw_products_categories'         => (empty($input['fw_products_categories'])) ? null : $input['fw_products_categories'],
                            'fw_products_tax'                => $input['fw_products_tax'],
                            'fw_products_is_active'          => DB_BOOL_TRUE,
                            'fw_products_provider_code'      => (!empty($provider_codes)) ? json_encode($provider_codes) : null,
                            'fw_products_barcode'            => ($input['config_barcode'][$key]) ?: time(),
                            'fw_products_markup_percentage'  => $input['fw_products_markup_percentage'],
                            'fw_products_weight'             => $input['fw_products_weight'],
                            'fw_products_width'              => $input['fw_products_width'],
                            'fw_products_height'             => $input['fw_products_height'],
                            'fw_products_depth'              => $input['fw_products_depth'],
                            'fw_products_peso_specifico'     => $input['fw_products_peso_specifico'],
                            'fw_products_warehouse_location' => $input['fw_products_warehouse_location'],
                            'fw_products_notes'              => $input['fw_products_notes'],
                        ];
                        
                        $configurabile = array_merge($configurabile, $new_configurabile);
                        
                        $configurabile_id = $this->apilib->create('fw_products', $configurabile, false);
                        
                        $variations_ids[] = $configurabile_id;
                    }
                    //debug('test2');
                    //Eredito la tabella dei prezzi...
                    if (!empty($input['product_prices'])) {
                        foreach ($input['product_prices'] as $price_list_label_id => $price) {
                            $this->db->query("DELETE FROM price_list WHERE price_list_product = '$configurabile_id' AND price_list_label = '$price_list_label_id'");
                            $this->apilib->create('price_list', [
                                'price_list_product' => $configurabile_id,
                                'price_list_label'   => $price_list_label_id,
                                'price_list_price'   => $price,
                            ]);
                        }
                    }
                }
                
                if (!empty($variations_ids)) {
                    $deleted_variations = $this->db->query("SELECT * FROM fw_products WHERE fw_products_parent = '{$prodotto_id}' AND fw_products_id NOT IN (" . implode(',', $variations_ids) . ") AND fw_products_deleted = 0")->result_array();
                    
                    if (!empty($deleted_variations)) {
                        foreach ($deleted_variations as $variation) {
                            try {
                                $this->apilib->delete('fw_products', $variation['fw_products_id']);
                            } catch (Exception $e) {
                                log_message('error', $e->getMessage());
                            }
                        }
                        $this->apilib->clearCache();
                    }
                }
            }
            
            
            //            $session_files = (array) ($this->session->userdata('files'));
            
            //debug($session_files,true);
            
            //            if (!empty($session_files)) {
            //                foreach ($session_files as $key => $file) {
            //                    if (!empty($file)) {
            //                        $this->apilib->create('prodotti_immagini', [
            //                            'prodotti_immagini_prodotto' => $prodotto_id,
            //                            'prodotti_immagini_immagine' => $file['path_local'],
            //                            'prodotti_immagini_ordine' => $key + 1,
            //                        ]);
            //                    }
            //                }
            //            }
            //
            //            $this->session->set_userdata('files', []);
            //echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/dettaglio_prodotto/' . $prodotto_id)));
            echo json_encode(['status' => 1, 'txt' => base_url('main/layout/products-list/?product_id=' . $prodotto_id)]);
        }
    }
    
    public function addFile()
    {
        //debug($_FILES, true);
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = md5($_FILES['file']['name']) . '.' . $ext;
        $uploadDepthLevel = defined('UPLOAD_DEPTH_LEVEL') ? (int)UPLOAD_DEPTH_LEVEL : 0;
        
        if ($uploadDepthLevel > 0) {
            // Voglio comporre il nome locale in modo che se il nome del file fosse
            // pippo.jpg la cartella finale sarà: ./uploads/p/i/p/pippo.jpg
            $localFolder = '';
            for ($i = 0; $i < $uploadDepthLevel; $i++) {
                // Assumo che le lettere siano tutte alfanumeriche,
                // alla fine le immagini sono tutte delle hash md5
                $localFolder .= strtolower(isset($filename[$i]) ? $filename[$i] . DIRECTORY_SEPARATOR : '');
            }
            
            if (!is_dir(FCPATH . 'uploads/' . $localFolder)) {
                mkdir(FCPATH . 'uploads/' . $localFolder, DIR_WRITE_MODE, true);
            }
        }
        
        $this->load->library('upload', [
            'upload_path'   => FCPATH . 'uploads/' . $localFolder,
            'allowed_types' => '*',
            'max_size'      => '50000',
            'encrypt_name'  => false,
            'file_name'     => $filename,
        ]);
        
        $uploaded = $this->upload->do_upload('file');
        if (!$uploaded) {
            debug($this->upload->display_errors());
            die();
        }
        
        $up_data = $this->upload->data();
        $up_data['path_local'] = $localFolder . $filename;
        $session = (array)($this->session->userdata('files'));
        $session[] = $up_data;
        $this->session->set_userdata('files', $session);
        usleep(100);
        echo json_encode(['status' => 1, 'file' => $up_data]);
    }
    
    public function removeFile($immagine_id)
    {
        $this->apilib->delete('prodotti_immagini', $immagine_id);
    }
    
    public function bulkEditAttributi()
    {
        $post = $this->input->post();
        
        try {
            $products = $this->db->query("SELECT * FROM fw_products WHERE fw_products_id IN ({$post['products']})")->result_array();
        } catch (Exception $e) {
            throw new ApiException('Si è verificato un errore (1)');
            exit;
        }
        
        $attributes = $post['attributes'];
        
        if (empty($products) || empty($attributes)) {
            die(json_encode(['status' => 0, 'txt' => 'attributi e/o prodotti non selezionati']));
        }
        
        
        foreach ($attributes as $attribute_id => $attribute_value_id) {
            // se il valore dell'attributo è -1, vuol dire che deve essere rimosso dal json.
            if ($attribute_value_id == '-1') {
                unset($attributes[$attribute_id]);
            }
        }
        
        foreach ($products as $key => $product) {
            // se è configurabile lo skippo
            if ($product['fw_products_type'] == '2') {
                continue;
            }
            
            $product_attributes = json_decode($product['fw_products_json_attributes'], true);
            
            $attributi_da_aggiornare = $attributes;
            
            // debug($product_attributes);
            // debug($attributi_da_aggiornare);
            foreach ($product_attributes as $attribute_id => $values) {
                
                //Se l'attributo che sto ciclando devo lasciarlo invariato (0) allora prendo dall'array attributi del prodotto (data old...)
                // michael - Metto un doppio controllo array_key_exists perchè se tolgo il primo (quello dell' &&) ed avevo già rimosso in precedenza l'attributo (tendina "rimuovi"), poi da un undefined offset
                if (array_key_exists($attribute_id, $attributes) && $attributes[$attribute_id] === '0') {
                    if (array_key_exists($attribute_id, $attributes)) {
                        $attributi_da_aggiornare[$attribute_id] = $product_attributes[$attribute_id];
                    } else {
                        //Prima non aveva l'attributo, a questo punto unsetto
                        unset($attributi_da_aggiornare[$attribute_id]);
                    }
                } else {
                    //Sovrascrivo con quello che mi sta arrivando (sopra ho già unsettato quelli che andavano rimossi, quindi non ci saranno più in questo array)
                    /*
                    La struttura è
                    [5] => Array
                        (
                            [0] => 56
                        )
                    non
                    [5] => 56
                    */
                    // $attributes[$attribute_id] = !is_array($attributes[$attribute_id]) ? [$attributes[$attribute_id]] : $attributes[$attribute_id];
                }
            }
            // debug($attributi_da_aggiornare, true);
            $json_attributes = json_encode($attributi_da_aggiornare);
            
            try {
                $this->db->where('fw_products_id', $product['fw_products_id'])->update('fw_products', ['fw_products_json_attributes' => $json_attributes]);
                
                // pulisco la cache, dato che ho usato $this->db
                $this->apilib->clearCache();
                
                $this->load->model('custom/webdev');
                
                // ripesco da db il prodotto con apilib, così da avere tutto joinato
                $prodotto = $this->apilib->view('fw_products', $product['fw_products_id']);
                
                // CREO LE QUEUE PER ECOMMERCE WEBDEV
                $what = 'modifica attributi';
                
                $prodottoApiData = $this->webdev->buildProductApiData($prodotto);
                $this->webdev->createQueue('product', $prodottoApiData, "{$what} prodotto {$prodotto['fw_products_sku']}", $product['fw_products_id']);
                
                // $prodottoPriceApiData = $this->webdev->buildProductPriceApiData($prodotto);
                // $this->webdev->createQueue('product/price', $prodottoPriceApiData, "{$what} prezzo prodotto {$prodotto['fw_products_sku']}");
                
                // $prodottoQtyApiData = $this->webdev->buildProductQtyApiData($prodotto);
                // $this->webdev->createQueue('product/stock', $prodottoQtyApiData, "{$what} quantita prodotto {$prodotto['fw_products_sku']}");
            } catch (Exception $e) {
                throw new ApiException('Si è verificato un errore (2)');
                exit;
            }
        }
        
        die(json_encode(['status' => 2, 'txt' => 'salvato']));
    }
    
    public function removeProductImage($product_id = null)
    {
        if (!$product_id) {
            die(json_encode([
                'status' => 0,
                'txt'    => t('Product not declared'),
            ]));
        }
        
        $product = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $product_id]);
        
        if (empty($product)) {
            die(json_encode([
                'status' => 0,
                'txt'    => t('Product not found'),
            ]));
        }
        
        try {
            $this->apilib->edit('fw_products', $product_id, [
                'fw_products_main_image' => null,
            ]);
            
            die(json_encode([
                'status' => 1,
                'txt'    => t('Image removed'),
            ]));
        } catch (Exception $e) {
            log_message('error', "Apilib error: " . $e->getMessage());
            die(json_encode([
                'status' => 0,
                'txt'    => t('Internal error. Retry later or contact support'),
            ]));
        }
    }
}