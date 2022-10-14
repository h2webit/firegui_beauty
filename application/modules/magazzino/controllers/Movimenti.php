<?php

class Movimenti extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('magazzino/mappature');
        $this->load->model('magazzino/mov');
        //$this->settings = $this->db->get('settings')->row_array();
    }

    public function getProdottoByCode($codice)
    {
        echo json_encode(['prodotto' => $this->apilib->searchFirst('prodotti', ['prodotti_codice_fornitore' => $codice])]);
    }

    public function searchBarcode()
    {
        $barcode = trim($this->input->post('barcode'));

        if (empty($barcode)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun barcode specificato']));
        }

        try {
            $mappature = $this->mappature->getMappature();
            extract($mappature);

            //$products = $this->apilib->search($entita_prodotti, [$campo_barcode_prodotto => $barcode, $campo_gestione_giacenza_prodotto => DB_BOOL_TRUE, $campo_prodotto_eliminato => DB_BOOL_FALSE]);
            $products = $this->apilib->search($entita_prodotti, ["{$campo_barcode_prodotto} LIKE '%{$barcode}%'", $campo_gestione_giacenza_prodotto => DB_BOOL_TRUE, $campo_prodotto_eliminato => DB_BOOL_FALSE]);

            if (empty($products)) {
                die(json_encode(['status' => 0, 'data' => 'Nessun prodotto trovato con il barcode specificato']));
            }

            if (count($products) == 1) {
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }

            if (count($products) > 1) {
                // ho aggiunto questa riga per mostrare comunque il primo prodotto, prima era vuoto.
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'data' => 'Si è verificato un errore tecnico']));
        }
    }

    public function searchBarcodeFiltered()
    {
        $magazzino = $this->input->post('magazzino');
        $barcode = trim($this->input->post('barcode'));

        if (empty($barcode)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun barcode specificato']));
        }

        if (empty($magazzino)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun magazzino specificato']));
        }

        try {
            $mappature = $this->mappature->getMappature();
            extract($mappature);

            $where = [
                "{$campo_barcode_prodotto} LIKE '%{$barcode}%'",
                $campo_gestione_giacenza_prodotto => DB_BOOL_TRUE,
                $campo_prodotto_eliminato => DB_BOOL_FALSE,
//"fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli LEFT JOIN movimenti ON movimenti_id = movimenti_articoli_movimento WHERE movimenti.movimenti_magazzino = '{$magazzino}')"

            ];

            $products = $this->apilib->search($entita_prodotti, $where);

            if (empty($products)) {
                die(json_encode(['status' => 0, 'data' => 'Nessun prodotto trovato con il barcode specificato']));
            }

            if (count($products) == 1) {
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }

            if (count($products) > 1) {
                // ho aggiunto questa riga per mostrare comunque il primo prodotto, prima era vuoto.
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'data' => 'Si è verificato un errore tecnico']));
        }
    }

    public function insertProduct()
    {
        $product = json_decode($this->input->post('product'), true);
        $magazzino = $this->input->post('magazzino');

        if (empty($product)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun prodotto specificato']));
        }
        if (empty($magazzino)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun magazzino specificato']));
        }

        try {
            $barcode = (array) (json_decode($product['fw_product_barcode']));
            $barcode = $barcode[0];

            //debug('TODO: Se ci sono movimenti di inventario (25,26 o 27), eliminare quella riga da movimenti_articoli. Se il movimento rimane privo di articoli, eliminare anche il movimento stesso in modo da ottimizzare il numero di movimenti...', true);
            $movimenti_precedenti = $this->apilib->search('movimenti_articoli', [
                'movimenti_articoli_prodotto_id' => $product['fw_products_id'],
                'movimenti_causale' => [25, 26, 27],
                'movimenti_magazzino' => $magazzino,
                'DATEDIFF(NOW(), movimenti_creation_date) < 30', //COnsidero solo inventari fatti nell'ultimo mese. Oltre questa data i movimenti rimangono bloccati
            ]);
            //debug($movimenti_precedenti,true);
            foreach ($movimenti_precedenti as $movimento_precedente) {
                $this->apilib->delete('movimenti_articoli', $movimento_precedente['movimenti_articoli_id']);

                //Se il movimento rimane orfano (senza prodotti), cancello direttamente il movimento.
                if ($this->apilib->count('movimenti_articoli', ['movimenti_articoli_movimento' => $movimento_precedente['movimenti_articoli_movimento']]) == 0) {
                    $this->apilib->delete('movimenti', $movimento_precedente['movimenti_articoli_movimento']);
                }
            }

            //debug($product,true);
            $quantity = $this->mov->calcolaGiacenzaAttuale($product, $magazzino);
            $exists = $this->db->get_where('prodotti_inventario', [
                'prodotti_inventario_prodotto_id' => $product['fw_products_id'],
                //'prodotti_inventario_movimento' => null,

            ]);
            if ($riga = $exists->row_array()) {

                $this->apilib->edit('prodotti_inventario', $riga['prodotti_inventario_id'], [
                    'prodotti_inventario_qta_sparata' => $riga['prodotti_inventario_qta_sparata'] + 1,
                    'prodotti_inventario_qta_attesa' => $quantity,
                    'prodotti_inventario_confermato' => DB_BOOL_FALSE,
                ]);
            } else {

                $this->apilib->create('prodotti_inventario', [
                    'prodotti_inventario_barcode' => $barcode,
                    'prodotti_inventario_nome' => $product['fw_products_name'],
                    'prodotti_inventario_prezzo' => $product['fw_products_sell_price'],
                    'prodotti_inventario_qta_attesa' => $quantity,
                    'prodotti_inventario_qta_sparata' => 1,
                    'prodotti_inventario_prodotto_id' => $product['fw_products_id'],
                    'prodotti_inventario_magazzino' => $magazzino,
                    'prodotti_inventario_confermato' => DB_BOOL_FALSE,
                ]);
            }

            //$this->mycache->clearCache();

            die(json_encode(['status' => 0, 'data' => 'Prodotti inseriti correttamente in inventario']));
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'data' => 'Si è verificato un errore tecnico']));
        }
    }
    public function azzera_quantita()
    {
        $filters = $this->session->userdata(SESS_WHERE_DATA);

        // Costruisco uno specchietto di filtri autogenerati leggibile
        $where = array();

        if (!empty($filters["filtro_inventario_azzeramento_quantita"])) {
            foreach ($filters["filtro_inventario_azzeramento_quantita"] as $field) {
                if ($field['value'] == '-1') {
                    continue;
                }
                $filter_field = $this->datab->get_field($field["field_id"], true);
                $field_name = $filter_field['fields_name'];
                switch ($field_name) {
                    case 'movimenti_magazzino':
                        $magazzino = $field['value'];
                        break;
                    case 'fw_products_categories':
                        if ($field['value']) {
                            $where[] = "fw_products_id IN (SELECT fw_products_id FROM fw_products_fw_categories  WHERE fw_categories_id IN (" . implode(',', $field['value']) . "))";
                        }

                        break;
                    default:
                        debug("Filtro {$field_name} non riconosciuto!");
                        break;
                }
            }

            if ($magazzino) {
                $where[] = "fw_products_id NOT IN (SELECT prodotti_inventario_prodotto_id FROM prodotti_inventario WHERE prodotti_inventario_magazzino = '$magazzino')";
                $where_str = implode(' AND ', $where);
                $prodotti_da_azzerare = $this->db->where($where_str, null, false)->get('fw_products')->result_array();

                //debug($prodotti_da_azzerare, true);
                $this->azzera_quantita_prodotti($prodotti_da_azzerare, $magazzino);
                redirect(base_url('main/layout/inventario'));

            } else {
                die("Errore: filtro magazzino non impostato!");

            }

        } else {
            die("Errore: filtro non impostato!");
        }

    }
    private function azzera_quantita_prodotti($prodotti, $magazzino)
    {
        //Creo due movimenti in tutto...
        $prodotti_scarico = $prodotti_carico = [];

        foreach ($prodotti as $prodotto_sparato) {

            $quantity = $this->mov->calcolaGiacenzaAttuale($prodotto_sparato, $magazzino);
            //debug($prodotto_sparato,true);

            $prodotto = [
                'movimenti_articoli_prodotto_id' => $prodotto_sparato['fw_products_id'],
                'movimenti_articoli_prezzo' => $prodotto_sparato['fw_products_sell_price'],
                'movimenti_articoli_descrizione' => $prodotto_sparato['fw_products_description'],
                'movimenti_articoli_name' => $prodotto_sparato['fw_products_name'],
                'movimenti_articoli_codice' => $prodotto_sparato['fw_products_sku'],
                'movimenti_articoli_iva_id' => $prodotto_sparato['fw_products_tax'],
                'movimenti_articoli_unita_misura' => $prodotto_sparato['fw_products_unita_misura'],
                'movimenti_articoli_codice_fornitore' => $prodotto_sparato['fw_products_provider_code'],
                'movimenti_articoli_quantita' => abs($quantity),
                'movimenti_articoli_importo_totale' => abs($quantity * $prodotto_sparato['fw_products_sell_price']),
                'movimenti_articoli_barcode' => (!empty($prodotto_sparato['fw_products_barcode'])) ? json_decode($prodotto_sparato['fw_products_barcode'])[0] : null,
            ];

            if ($quantity > 0) {

                $prodotti_scarico[] = $prodotto;
            }
            if ($quantity < 0) {

                $prodotti_carico[] = $prodotto;
            }

        }

        if ($prodotti_scarico) {
            $this->mov->creaMovimento(
                [
                    'movimenti_magazzino' => $magazzino,
                    'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                    'movimenti_articoli' => $prodotti_scarico,
                    'movimenti_tipo_movimento' => 2, //Scarico
                    'movimenti_causale' => 25, //    Inventario (scarico base)
                ]
            );
        }

        if ($prodotti_carico) {
            $this->mov->creaMovimento(
                [
                    'movimenti_magazzino' => $magazzino,
                    'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                    'movimenti_articoli' => $prodotti_carico,
                    'movimenti_tipo_movimento' => 1, //Carico
                    'movimenti_causale' => 26, //    Inventario (carico base)
                ]
            );
        }

    }
    public function salva_inventario($magazzino)
    {
        $prodotti_inventario = $this->apilib->search('prodotti_inventario', [
            'prodotti_inventario_magazzino' => $magazzino,
            'prodotti_inventario_confermato IS NULL OR prodotti_inventario_confermato = 0',
        ]);
        foreach ($prodotti_inventario as $key => $prodotto_sparato) {
            $prodotti_inventario[$key]['fw_products_id'] = $prodotto_sparato['prodotti_inventario_prodotto_id'];

        }
        $this->azzera_quantita_prodotti($prodotti_inventario, $magazzino);

        //Una volta portate a 0 le quantità creo un movimento di carico inventario per allineare le quantità
        $giacenze_finali = [];
        foreach ($prodotti_inventario as $prodotto_sparato) {
            $prodotto_sparato['fw_products_id'] = $prodotto_sparato['prodotti_inventario_prodotto_id'];
            $quantity = $this->mov->calcolaGiacenzaAttuale($prodotto_sparato, $magazzino);

            $prodotto = [
                'movimenti_articoli_prodotto_id' => $prodotto_sparato['prodotti_inventario_prodotto_id'],
                'movimenti_articoli_prezzo' => $prodotto_sparato['fw_products_sell_price'],
                'movimenti_articoli_descrizione' => $prodotto_sparato['fw_products_description'],
                'movimenti_articoli_name' => $prodotto_sparato['fw_products_name'],
                'movimenti_articoli_codice' => $prodotto_sparato['fw_products_sku'],
                'movimenti_articoli_iva_id' => $prodotto_sparato['fw_products_tax'],
                'movimenti_articoli_unita_misura' => $prodotto_sparato['fw_products_unita_misura'],
                'movimenti_articoli_codice_fornitore' => $prodotto_sparato['fw_products_provider_code'],
                'movimenti_articoli_quantita' => $prodotto_sparato['prodotti_inventario_qta_sparata'],
                'movimenti_articoli_importo_totale' => abs($quantity * $prodotto_sparato['fw_products_sell_price']),
                'movimenti_articoli_barcode' => (!empty($prodotto_sparato['fw_products_barcode'])) ? json_decode($prodotto_sparato['fw_products_barcode'])[0] : null,
            ];

            $giacenze_finali[] = $prodotto;

            $this->apilib->edit('prodotti_inventario', $prodotto_sparato['prodotti_inventario_id'], [
                'prodotti_inventario_confermato' => DB_BOOL_TRUE,
            ]);

        }
        $this->mov->creaMovimento(
            [
                'movimenti_magazzino' => $magazzino,
                'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                'movimenti_articoli' => $giacenze_finali,
                'movimenti_tipo_movimento' => 1, //Carico
                'movimenti_causale' => 27, //    Inventario (carico finale)
            ]
        );

        redirect(base_url('main/layout/inventario'));
    }

    public function nuovo_movimento()
    {
        $input = $this->input->post();

        // debug($input, true);

        $this->load->library('form_validation');

        $this->form_validation->set_rules('movimenti_magazzino', 'Magazzino', 'required');
        $this->form_validation->set_rules('movimenti_data_registrazione', 'Data movimento', 'required');
        $this->form_validation->set_rules('movimenti_causale', 'Causale', 'required');
        $this->form_validation->set_rules('movimenti_tipo_movimento', 'Tipo movimento', 'required');
        //$this->form_validation->set_rules('movimenti_documento_tipo', 'Tipo documento', 'required');
        $this->form_validation->set_rules('movimenti_mittente', 'Mittente', 'required');

        //Barbatrucco matteo: non è detto che sia 1 nel caso di riga eliminata (può partire da 2, da 3 o altro...)
        $chiave = 1;
        if (!empty($input['products'])) {
            foreach (@$input['products'] as $key => $p) {
                $this->form_validation->set_rules('products[' . $key . '][movimenti_articoli_name]', t('product name'), 'required');
                $this->form_validation->set_rules('products[' . $key . '][movimenti_articoli_quantita]', t('product quantity'), 'required|integer|greater_than[0]');
                break;
            }
        } else {
            $input['products'] = [];
        }

        if ($input['movimenti_mittente'] != 3) {
            $this->form_validation->set_rules('ragione_sociale', 'ragione sociale', 'required');
            // $this->form_validation->set_rules('indirizzo', 'indirizzo', 'required');
            // $this->form_validation->set_rules('citta', 'città', 'required');
            // $this->form_validation->set_rules('nazione', 'nazione', 'required');
            // $this->form_validation->set_rules('provincia', 'provincia', 'required');
            // $this->form_validation->set_rules('codice_fiscale', 'codice fiscale', 'required');
            // $this->form_validation->set_rules('cap', 'CAP', 'required');
        } else {
        }

        if ($this->form_validation->run() == false) {
            echo json_encode(array(
                'status' => 0,
                'txt' => validation_errors(),
                'data' => '',
            ));
        } else {
            $mappature = $this->mappature->getMappature();
            extract($mappature);

            $dest_entity_name = $input['dest_entity_name'];

            // **************** DESTINATARIO ****************** //

            $dest_fields = array("ragione_sociale", "indirizzo", "citta", "provincia", "nazione", "codice_fiscale", "partita_iva", 'cap');
            foreach ($input as $key => $value) {
                if (in_array($key, $dest_fields)) {
                    $destinatario_json[$key] = $value;
                    $destinatario_entity[$dest_entity_name . "_" . $key] = $value;
                }
            }

            // Serialize
            $movimento['movimenti_destinatario'] = json_encode($destinatario_json);

            // Se già censito lo collego altrimenti lo salvo se richiesto
            if ($input['dest_id']) {
                if ($dest_entity_name == 'customers') {
                    $movimento['movimenti_clienti_id'] = $input['dest_id'];
                } else {
                    $movimento['movimenti_fornitori_id'] = $input['dest_id'];
                }

                //Se ho comunque richiesto la sovrascrittura dei dati
                if (isset($input['save_dest']) && $input['save_dest'] == "true") {
                    $this->apilib->edit($dest_entity_name, $input['dest_id'], $destinatario_entity);
                }
            } elseif (isset($input['save_dest']) && $input['save_dest'] == "true") {
                $dest_id = $this->apilib->create($dest_entity_name, $destinatario_entity, false);
                if ($dest_entity_name == 'customers') {
                    $movimento['movimenti_clienti_id'] = $dest_id;
                } else {
                    $movimento['movimenti_fornitori_id'] = $dest_id;
                }
            }

            // **************** DOCUMENTO ****************** //

            $movimento['movimenti_magazzino'] = $input['movimenti_magazzino'];
            $movimento['movimenti_causale'] = $input['movimenti_causale'];
            $movimento['movimenti_tipo_movimento'] = $input['movimenti_tipo_movimento'];
            $movimento['movimenti_documento_tipo'] = $input['movimenti_documento_tipo'];
            $movimento['movimenti_mittente'] = $input['movimenti_mittente'];
            $movimento['movimenti_data_registrazione'] = $input['movimenti_data_registrazione'];
            $movimento['movimenti_numero_documento'] = $input['movimenti_numero_documento'];
            $movimento['movimenti_documento_id'] = $input['movimenti_documento_id'];

            $movimento['movimenti_data_documento'] = $input['movimenti_data_documento'];
            $movimento['movimenti_totale'] = $input['movimenti_totale'];

            if ($movimento['movimenti_documento_id']) {
                $stato_documento = $this->getDocumentoStato($this->input->post());
            }

            if ($input['movimenti_ordine_produzione_id']) {
                $movimento['movimenti_ordine_produzione_id'] = $input['movimenti_ordine_produzione_id'];
            }

            $movimento['movimenti_user'] = $this->auth->get('id');

            if (!empty($input['movimenti_id'])) {
                $movimenti_id = $input['movimenti_id'];
                // debug($movimento);
                // debug($this->input->post(), true);
                $this->apilib->edit('movimenti', $movimenti_id, $movimento);
            } else {
                //debug($movimento, true);
                $movimenti_id = $this->apilib->create('movimenti', $movimento, false);
            }

            // **************** PRODOTTI ****************** //
            if (!empty($input['movimenti_id'])) {
                //Devo usare le apilib per scatenare il pp!!!
                //debug($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $input['movimenti_id']]),true);
                foreach ($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $input['movimenti_id']]) as $movimento_articolo) {
                    $this->apilib->delete('movimenti_articoli', $movimento_articolo['movimenti_articoli_id']);
                }
            }

            foreach ($input['products'] as $prodotto) {
                if ($prodotto['movimenti_articoli_name']) { //Almeno il name ci deve essere, altrimenti devo considerarla come riga vuota.
                    $prodotto['movimenti_articoli_movimento'] = $movimenti_id;
                    if ($prodotto['movimenti_articoli_prodotto_id']) { //} && $prodotto_esistente = $this->apilib->view($entita_prodotti, $prodotto['movimenti_articoli_prodotto_id'])) {
                        //$prodotto['movimenti_articoli_prodotto_id'] = $prodotto_esistente[$campo_id_prodotto];
                    } else {
                        //debug($input['products'], true);
                        //TODO: se ho impostato un fornitore, metterlo nei supplier
                        $nuovo_prodotto = [
                            $campo_barcode_prodotto => $prodotto['movimenti_articoli_barcode'],
                            $campo_codice_prodotto => $prodotto['movimenti_articoli_codice'],

                            $campo_descrizione_prodotto => $prodotto['movimenti_articoli_descrizione'],
                            //'prodotti_unita_di_misura' => $prodotto['movimenti_articoli_unita_misura'],
                            $campo_prezzo_prodotto => ($prodotto['movimenti_articoli_prezzo']) ?: '0.0',
                            $campo_prezzo_fornitore_prodotto => $prodotto['movimenti_articoli_prezzo'],
                            $campo_preview_prodotto => $prodotto['movimenti_articoli_name'],

                            $campo_nascondi_prodotto => ($input['missing_products_insert']) ? DB_BOOL_FALSE : DB_BOOL_TRUE,

                            $campo_iva_prodotto => $prodotto['movimenti_articoli_iva_id'],
                            $campo_tipo_prodotto => '1',
                            $campo_unita_misura_prodotto => $prodotto['movimenti_articoli_unita_misura'],
                        ];

                        if ($campo_gestione_giacenza_prodotto) {
                            $nuovo_prodotto[$campo_gestione_giacenza_prodotto] = DB_BOOL_TRUE;
                        }
                        // debug($campo_prezzo_prodotto);
                        // debug($nuovo_prodotto, true);
                        $prodotto_id = $this->apilib->create($entita_prodotti, $nuovo_prodotto, false);
                        //die('prodid: ' . $prodotto_id);
                        $prodotto['movimenti_articoli_prodotto_id'] = $prodotto_id;
                    }

                    //die("C'è un pp che crea il prodotto... tenere o questo codice o l'altro...");
                    if ($prodotto['movimenti_articoli_name']) {
                        //TODO: serve ancora? Lo usava healthaid per non movimentare alcuni prodotti... secondo me se creo un movimento deve "movimentarli" e quindi scaricare correttamente le quantità...
                        $prodotto['movimenti_articoli_genera_movimenti'] = ($prodotto['movimenti_articoli_genera_movimenti']) ?: DB_BOOL_FALSE;
                        //debug($prodotto, true);
                        $this->apilib->create("movimenti_articoli", $prodotto);
                    }
                }
            }
            //die('test3');
            //Alla fine di tutto aggiorno il documento associato
            if ($movimento['movimenti_documento_id'] && $stato_documento && $movimento['movimenti_documento_id'] != -1) {
                $this->apilib->edit('documenti_contabilita', $movimento['movimenti_documento_id'], ['documenti_contabilita_stato' => $stato_documento]);
            }

            if ($movimento['movimenti_ordine_produzione_id']) {
                $ord_prod = $this->apilib->view('ordini_produzione', $movimento['movimenti_ordine_produzione_id']);
                //debug($ord_prod['ordini_produzione_distinta_base'],true);
                die(json_encode(['status' => 1, 'txt' => base_url('main/layout/dettaglio-distinta-base/' . $ord_prod['ordini_produzione_distinta_base'])]));
            }

            echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/movements-list?save=1&movimento=' . $movimenti_id)));
        }
    }

    public function autocomplete($entity = null)
    {
        if (!$entity) {
            echo json_encode(['count_total' => 0, 'results' => []]);
            die();
        }
        $input = $this->input->get_post('search');

        $count_total = 0;

        $input = trim($input);
        if (empty($input) or strlen($input) < 2) {
            echo json_encode(['count_total' => -1]);
            return;
        }

        $results = [];

        $input = strtolower($input);

        $input = str_ireplace("'", "''", $input);

        $res = [];

        $mappature = $this->mappature->getMappature();
        extract($mappature);

        if ($entity == $entita_prodotti) {
            $campo_preview = $campo_preview_prodotto;
            $campo_codice = $campo_codice_prodotto;
            $campo_barcode = $campo_barcode_prodotto;

            //$res = $this->apilib->search($entity, ["((LOWER($campo_barcode) LIKE '%{$input}%' OR LOWER($campo_preview) LIKE '%{$input}%' OR $campo_preview LIKE '{$input}%') OR (LOWER($campo_codice) LIKE '%{$input}%' OR $campo_codice LIKE '{$input}%'))"]);
            if (!empty($campo_prodotto_eliminato)) {
                $this->db->where($campo_prodotto_eliminato, DB_BOOL_FALSE);
            }
            if (!empty($campo_gestione_giacenza_prodotto)) {
                $this->db->where($campo_gestione_giacenza_prodotto, DB_BOOL_TRUE);
            }

            $res = $this->db->where("((LOWER($campo_barcode) LIKE '%{$input}%' OR LOWER($campo_preview) LIKE '%{$input}%' OR $campo_preview LIKE '{$input}%') OR (LOWER($campo_codice) LIKE '%{$input}%' OR $campo_codice LIKE '{$input}%'))", null, false)
                ->get($entity)
                ->result_array();
        } elseif ($entity == 'customers') {
            $res = $this->apilib->search('clienti', ["(LOWER(customers_company) LIKE '%{$input}%')"], 100, 0, 'customers_company', 'ASC');
        } elseif ($entity == 'suppliers') {
            $res = $this->apilib->search('suppliers', ["(LOWER(suppliers_business_name) LIKE '%{$input}%')", 100, 0, 'suppliers_business_name', 'ASC']);
        } elseif ($entity == 'vettori') {
            $res = $this->apilib->search('vettori', ["(LOWER(vettori_ragione_sociale) LIKE '%{$input}%') ORDER BY vettori_ragione_sociale ASC"]);
        }

        if ($res) {
            $count_total = count($res);
            $results = [
                'data' => $res,
            ];
        }

        echo json_encode(['count_total' => $count_total, 'results' => $results]);
    }

    private function getDocumentoStato($post)
    {
        $documenti_id = $post['movimenti_documento_id'];

        $stato = 1;
        if ($documenti_id) {
            $articoli = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documenti_id]);

            foreach ($articoli as $key => $articolo) {
                foreach ($post['products'] as $key2 => $p) {
                    if ($p['movimenti_articoli_prodotto_id'] == $articolo['documenti_contabilita_articoli_prodotto_id']) {
                        $stato = 2; //Chiuso parzialmente
                        if ($p['movimenti_articoli_quantita'] == $articolo['documenti_contabilita_articoli_quantita']) {
                            //Se anche la quantità coincide allora è un movimento perfetto. Tolgo dai due array e me ne dimentico
                            unset($post['products'][$key2]);
                            unset($articoli[$key]);
                            break;
                        }
                    }
                }
            }
            //A questo punto verifico: se sono entrambi vuoti, vuole dire che tutto coincide e posso chiudere l'ordine
            if (empty($articoli) && empty($post['products'])) {
                $stato = 3; //Chiuso
            }
        }

        return $stato;
    }
    public function check_quantity_icon_show($product_id, $movimenti_id = -1)
    {
        $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_id <> '$movimenti_id'")->row()->qty;
        $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_id <> '$movimenti_id'")->row()->qty;
        //debug($quantity_scarico);
        $quantity = $quantity_carico - $quantity_scarico;

        echo $quantity;
    }
    public function check_quantity_available($product_id, $magazzino_id)
    {
        $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_magazzino = '$magazzino_id'")->row()->qty;
        $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_magazzino = '$magazzino_id'")->row()->qty;
        $quantity = $quantity_carico - $quantity_scarico;

        echo $quantity;

        exit;
    }

    public function getProdotto($prodotto_id)
    {
        $mappature = $this->mappature->getMappature();
        extract($mappature);
        e_json($this->apilib->view($entita_prodotti, $prodotto_id));
    }

    public function getLotti($product_id, $magazzino)
    {
        if (empty($product_id)) {
            echo json_encode(['status' => 0, 'error' => 'Devi indicare un id prodotto']);
            exit;
        }

        //$getlottiprod = $this->apilib->search('movimenti_articoli', ['movimenti_articoli_prodotto_id' => $product_id]);
        $getlottiprod = $this->db->query("SELECT movimenti_articoli_lotto, movimenti_articoli_data_scadenza, SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) as movimenti_articoli_quantita FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_magazzino = '$magazzino' AND movimenti_articoli_prodotto_id = '$product_id' GROUP BY movimenti_articoli_lotto")->result_array();
        //TODO: non basta, bisogna mostrare le quantità corrette raggruppate per lotti

        if (empty($getlottiprod)) {
            echo json_encode(['status' => 0, 'error' => 'Questo prodotto non è presente in nessun lotto di questo magazzino!']);
            exit;
        }

        $response = ['status' => 1, 'data' => $getlottiprod];

        echo json_encode($response, 128);
    }

    /**
     *
     *
     * BELLES
     *
     *
     */

    public function autocompleteBarcodeProduct($movimento_id)
    {
        $keyword = trim($this->input->post('query'));

        if (empty($keyword)) {
            die(json_encode(['status' => 0, 'txt' => 'Ricerca vuota']));
        }
        $supplier = $this->apilib->view('movimenti', $movimento_id)['movimenti_fornitori_id'];
        $documenti_contabilita_articoli = $this->db->query("
            SELECT * FROM documenti_contabilita_articoli
                LEFT JOIN documenti_contabilita ON documenti_contabilita_id = documenti_contabilita_articoli_documento
                LEFT JOIN accantonamenti ON (accantonamenti_riga_ordine = documenti_contabilita_articoli_id AND accantonamenti_prodotto = documenti_contabilita_articoli_prodotto_id AND (accantonamenti_movimento = $movimento_id OR accantonamenti_movimento IS NULL))
                WHERE documenti_contabilita_articoli_prodotto_id IN (
                    SELECT fw_products_id FROM fw_products WHERE fw_products_barcode = '{$keyword}' AND fw_products_supplier = '$supplier'
                ) AND documenti_contabilita_stato IN (1,2,5,6)
                AND (documenti_contabilita_articoli_id,documenti_contabilita_articoli_quantita) NOT IN (
                    SELECT accantonamenti_riga_ordine,COALESCE(accantonamenti_stk,0)+COALESCE(accantonamenti_shp,0)+COALESCE(accantonamenti_del,0)-accantonamenti_qty  FROM accantonamenti

                    )
                AND documenti_contabilita_tipo = 5
                AND (
                    documenti_contabilita_articoli_quantita > (
                        accantonamenti_qty+
                        COALESCE(accantonamenti_shp,0)
                        +COALESCE(accantonamenti_stk,0)
                        +COALESCE(accantonamenti_del,0)
                    ) OR accantonamenti_qty IS NULL)

        ")->result_array();

        //die($this->db->last_query());
        // debug($documenti_contabilita_articoli, true);
        if (count($documenti_contabilita_articoli) == 1) {
            $riga_ordine = $documenti_contabilita_articoli[0];
            $accantonamento = $this->doAccantona($movimento_id, $riga_ordine['documenti_contabilita_articoli_prodotto_id'], $riga_ordine);
        }
        e_json([
            'status' => 1,
            'data' => $documenti_contabilita_articoli,
        ]);
    }

    public function accantona($movimento_id)
    {
        $riga_ordine = json_decode($this->input->post('riga_ordine'), true);

        $accantonamento = $this->doAccantona($movimento_id, $riga_ordine['documenti_contabilita_articoli_prodotto_id'], $riga_ordine);

        e_json([
            'status' => 1,
            'data' => $accantonamento,
        ]);
    }
    public function change_qty_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_qty' => $newqty]);
    }
    public function change_bo_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_bo' => $newqty]);
    }
    public function change_stk_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_stk' => $newqty]);
    }
    public function change_del_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_del' => $newqty]);
    }
    public function change_shp_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_shp' => $newqty]);
    }
    private function doAccantona($movimento_id, $prodotto_id, $riga_ordine)
    {

        //Verifico se il prodotto esiste già nel movimento
        $exists = $this->apilib->searchFirst('movimenti_articoli', [
            'movimenti_articoli_prodotto_id' => $prodotto_id,
            'movimenti_articoli_movimento' => $movimento_id,
        ]);

        //debug($exists, true);
        if ($exists) {
            $this->apilib->edit('movimenti_articoli', $exists['movimenti_articoli_id'], [
                'movimenti_articoli_quantita' => $exists['movimenti_articoli_quantita'] + 1,
            ]);
        } else {
            $prodotto = $this->apilib->view('fw_products', $prodotto_id);
            //debug($prodotto, true);
            $riga_articolo = [
                'movimenti_articoli_barcode' => $prodotto['fw_products_barcode'],
                'movimenti_articoli_codice' => $prodotto['fw_products_barcode'],
                'movimenti_articoli_name' => $prodotto['fw_products_name'],
                'movimenti_articoli_descrizione' => $prodotto['fw_products_description'],
                'movimenti_articoli_lotto' => '',
                'movimenti_articoli_data_scadenza' => '',
                'movimenti_articoli_quantita' => 1,
                'movimenti_articoli_prezzo' => $prodotto['fw_products_sell_price'],
                'movimenti_articoli_iva_id' => $prodotto['fw_products_tax'],
                'movimenti_articoli_iva' => 0,
                'movimenti_articoli_importo_totale' => $prodotto['fw_products_sell_price'],
                'movimenti_articoli_prodotto_id' => $prodotto_id,
                'movimenti_articoli_movimento' => $movimento_id,
                'movimenti_articoli_genera_movimenti' => 0,

            ];
            $this->apilib->create('movimenti_articoli', $riga_articolo);
        }

        //Verifico che non sia stato forzato un accantonamento privo di movimento per la gestione delle quantità nella pagina riepilogo ordini (vedi eval BO...)
        //Se è così associio quell'accantonamento a questo movimento.
        $accantonamento = $this->apilib->searchFirst('accantonamenti', [
            'accantonamenti_prodotto' => $prodotto_id,
            'accantonamenti_riga_ordine' => $riga_ordine['documenti_contabilita_articoli_id'],

        ]);
        if ($accantonamento) {
            $this->apilib->edit('accantonamenti', $accantonamento['accantonamenti_id'], ['accantonamenti_movimento' => $movimento_id]);
        }

        $exists = $this->apilib->searchFirst('accantonamenti', [
            'accantonamenti_prodotto' => $prodotto_id,
            'accantonamenti_riga_ordine' => $riga_ordine['documenti_contabilita_articoli_id'],
            'accantonamenti_movimento' => $movimento_id,
            'accantonamenti_bo > accantonamenti_qty - COALESCE(accantonamenti_stk,0)',
        ]);

        //debug($exists, true);

        $accantonamento = [
            'accantonamenti_prodotto' => $prodotto_id,
            'accantonamenti_riga_ordine' => $riga_ordine['documenti_contabilita_articoli_id'],
            'accantonamenti_movimento' => $movimento_id,

        ];
        if ($exists) {
            $accantonamento['accantonamenti_qty'] = $exists['accantonamenti_qty'] + 1;
            $this->apilib->edit('accantonamenti', $exists['accantonamenti_id'], $accantonamento);
        } else {
            $accantonamento['accantonamenti_qty'] = 1;
            $accantonamento['accantonamenti_stk'] = '0';
            $accantonamento['accantonamenti_del'] = '0';
            $accantonamento['accantonamenti_shp'] = '0';
            $accantonamento['accantonamenti_bo'] = $riga_ordine['documenti_contabilita_articoli_quantita'];

            $this->apilib->create('accantonamenti', $accantonamento);
        }

        return $accantonamento;
    }

    public function salva_accantonamento($movimento_id)
    {
        //$movimento = $this->apilib->view('movimenti', $movimento_id);
        $accantonamenti = $this->apilib->search('accantonamenti', ['accantonamenti_movimento' => $movimento_id]);
        foreach ($accantonamenti as $accantonamento) {
            //Sposto qty su stk e tolgo qty da bo
            $this->apilib->edit('accantonamenti', $accantonamento['accantonamenti_id'], [
                'accantonamenti_stk' => $accantonamento['accantonamenti_stk'] + $accantonamento['accantonamenti_qty'],
                'accantonamenti_qty' => 0,
                'accantonamenti_bo' => $accantonamento['accantonamenti_bo'] - $accantonamento['accantonamenti_qty'],
            ]);
        }
        redirect(base_url('main/layout/accantonamenti/' . $movimento_id));
    }
}
