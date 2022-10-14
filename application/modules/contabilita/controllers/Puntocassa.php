<?php
class Puntocassa extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function save()
    {
        $post = json_decode($this->input->post('cartContent'), true);

        if (empty($post['cart'])) {
            die(json_encode(['status' => 0, 'txt' => t('Cart is empty')]));
        }

        /* if (empty($post['user'])) {
            die(json_encode(['status' => 0, 'txt' => t('Customer not declared')]));
        } */

        $cart = $post['cart'];

        $articoli = [];
        foreach ($cart as $articolo) {
            $doc_cont_art = [
                'documenti_contabilita_articoli_codice' => $articolo['prodotto']['fw_products_sku'],
                'documenti_contabilita_articoli_prezzo' => $articolo['prezzo_no_iva'],
                'documenti_contabilita_articoli_prodotto_id' => $articolo['prodotto']['fw_products_id'],
                'documenti_contabilita_articoli_iva_perc' => $articolo['perc_iva'],
                'documenti_contabilita_articoli_quantita' => $articolo['quantita'],
                'documenti_contabilita_articoli_name' => $articolo['prodotto']['fw_products_name'],
                'documenti_contabilita_articoli_descrizione' => $articolo['prodotto']['fw_products_description'],
                'documenti_contabilita_articoli_codice_ean' => $articolo['prodotto']['fw_products_barcode'],
                'documenti_contabilita_articoli_unita_misura' => 'Pz', // @todo Michael - 2021-07-20 - Da capire se andrebbe reso dinamico
                'documenti_contabilita_articoli_sconto' => $articolo['sconto'],
                'documenti_contabilita_articoli_iva_id' => $articolo['iva_id'],
                'documenti_contabilita_articoli_importo_totale' => $articolo['prezzo_ivato'],
                'documenti_contabilita_articoli_iva' => $articolo['_iva_totale_scontato'],
            ];

            $articoli[] = $doc_cont_art;
        }


        $magazzino_id = $post['magazzino'] ?? $this->auth->get('magazzino');
        $magazzino_db = $this->apilib->view('magazzini', $magazzino_id);
        $magazzino_nome = $magazzino_db['magazzini_titolo'] ?? '';
        //Se cliente non c'è lo creo al volo mettendo "Cliente punto cassa - magazzino NOME MAGAZZINO"
        if(empty($post['user'])) {
            //Se esiste già un cliente per quel magazzino devo associare questo ordine a quel cliente così da averli ragruppati
            $customer_exists = $this->apilib->searchFirst('customers', ['customers_name' => 'Cliente punto cassa', 'customers_last_name' => 'Magazzino '.$magazzino_nome]);
            if(!empty($customer_exists)) {
                $customer_id = $customer_exists['customers_id'];
            } else {
                try {
                    $fake_customer = $this->apilib->create('customers', [
                        'customers_type' => 1,
                        'customers_status' => 1,
                        'customers_group' => 1,
                        'customers_name' => 'Cliente punto cassa',
                        'customers_last_name' => 'Magazzino '.$magazzino_nome,
                        'customers_description' => 'Cliente creato per ordine in punto cassa dal '.date('Y-m-d H:i'),
                    ]);
                    $customer_id = $fake_customer['customers_id'];
                } catch (Exception $e) {
                    //die(json_encode(['status' => 0, 'txt' => 'Errore durante la creazione del cliente']));
                    die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                }
            }
        } else {
            $customer_id = $post['user'];
        }
    
        $tpl_pdf = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
    
        $data = [
            'cliente_id' => $customer_id,
            'articoli_data' => $articoli,
            'tipo_documento' => 5,
            'stato' => 3,
            'template' => $tpl_pdf['documenti_contabilita_template_pdf_id'],
        ];
        
        if ($this->input->post('totale_scontato') > 0) {
            $data['documenti_contabilita_totale'] = $this->input->post('totale_scontato');
        }

        if (!empty($post['centro_di_costo'])) {
            $data['centro_di_costo'] = $post['centro_di_costo'];
        }

        $this->load->model('contabilita/docs');

        try {
            $documento_id = $this->docs->doc_express_save($data);
            //redirect al dettaglio documento
            //die(json_encode(['status' => 1, 'txt' => base_url()]));

            if ($this->datab->module_installed('magazzino')) {
                $this->load->model('magazzino/mov');
                $data = ['documento_id' => $documento_id];
                //se magazzino non è stato scelto dalla select lo imposto prendendolo dalla sessione
                if(empty($post['magazzino']) && $this->auth->get('magazzino')) {
                    $data['movimenti_magazzino'] = $this->auth->get('magazzino');
                } else {
                    $data['movimenti_magazzino'] = $post['magazzino'];
                }
                $this->mov->creaMovimento($data);
            }

            echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/punto-cassa')));
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
        }
    }
}