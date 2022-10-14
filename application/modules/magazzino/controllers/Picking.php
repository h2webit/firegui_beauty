<?php
class Picking extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function stampaNumeroOrdine($testo)
    {
        echo "<div style='font-size: 100px'>{$testo}</div>";
    }

    public function getArticoli()
    {
        $post = $this->input->post();

        $articoli = $this->apilib->search('documenti_contabilita_articoli', ["documenti_contabilita_articoli_id IN ({$post['articoli_id']})"]);
        $quantities = $post['quantities'];
        //debug($quantities);
        $res = '';
        foreach ($articoli as $articolo) {
            $image = null;
            $fw_product = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $articolo['documenti_contabilita_articoli_prodotto_id']]);

            $images = json_decode($fw_product['fw_products_images'], true);
            if ($images) {
                $image = array_shift($images)['path_local'];
            } else {
                $image = false;
            }

            $res .= '
                <tr class="text-uppercase">
                    <td>
                        <a href="' . base_url("uploads/{$image}") . '" class="fancybox">
                            <img src="' . base_url("imgn/1/50/50/uploads/{$image}") . '" class="img-thumbnail" alt="">
                        </a>
                    </td>
                    <td width="500">' . $articolo['documenti_contabilita_articoli_name'] . '</td>
                    <td>' . $quantities['art_' . $articolo['documenti_contabilita_articoli_id']] . ' ' . $articolo['documenti_contabilita_articoli_unita_misura'] . '</td>
                    <td width="200">' . $fw_product['fw_products_warehouse_location'] . '</td>
                </tr>
                ';
        }

        echo trim($res);
        exit;
    }

    public function save()
    {
        $data = $this->input->post();
        $documento_id = $data['documento_id'];

        if (empty($documento_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Documento id non dichiarato']));
        }

        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        if (empty($documento)) {
            die(json_encode(['status' => 0, 'txt' => 'Documento non trovato.']));
        }

        try {
            //20210929 - MP - Rimosso tutto in favore del nuovo metodo del model Mov.php
            // $movimento['movimenti_destinatario'] = $documento['documenti_contabilita_destinatario'];
            // $movimento['movimenti_magazzino'] = $data['magazzino_id'];
            // $movimento['movimenti_causale'] = 18;
            // $movimento['movimenti_tipo_movimento'] = 2;
            // $movimento['movimenti_documento_tipo'] = $documento['documenti_contabilita_tipo'];
            // $movimento['movimenti_mittente'] = 3; // da chiedere a matteo se è Movimento Cliente oppure Movimento Semplice
            // $movimento['movimenti_data_registrazione'] = date('d/m/Y');
            // $movimento['movimenti_numero_documento'] = $documento['documenti_contabilita_numero'];
            // $movimento['movimenti_documento_id'] = $documento_id;
            // $movimento['movimenti_data_documento'] = dateFormat($documento['documenti_contabilita_data_emissione']);
            // $movimento['movimenti_totale'] = $documento['documenti_contabilita_totale'];

            // $movimento = $this->apilib->create('movimenti', $movimento);

            // if (!empty($movimento['movimenti_id'])) {
            //     foreach ($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $movimento['movimenti_id']]) as $movimento_articolo) {
            //         $this->apilib->delete('movimenti_articoli', $movimento_articolo['movimenti_articoli_id']);
            //     }
            // }
            $articoli = $this->apilib->search('documenti_contabilita_articoli', ["documenti_contabilita_articoli_id IN ({$data['articoli_id']})"]);
            $prodotti = [];

            $quantities = json_decode($data['quantities'], true);
            //debug($quantities, true);

            foreach ($articoli as $articolo) {
                $fw_product = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $articolo['documenti_contabilita_articoli_prodotto_id']]);

                if (empty($fw_product)) {
                    die(json_encode(['status' => 0, 'txt' => 'Prodotto corrispondente a documento articolo non trovato']));
                    exit;
                }

                $prodotto = [
                    'movimenti_articoli_prodotto_id' => $articolo['documenti_contabilita_articoli_prodotto_id'],
                    'movimenti_articoli_prezzo' => $articolo['documenti_contabilita_articoli_prezzo'],
                    'movimenti_articoli_descrizione' => $articolo['documenti_contabilita_articoli_descrizione'],
                    'movimenti_articoli_name' => $articolo['documenti_contabilita_articoli_name'],
                    'movimenti_articoli_codice' => $articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_iva_id' => $articolo['documenti_contabilita_articoli_iva_id'],
                    'movimenti_articoli_accantonamento' => $articolo['documenti_contabilita_articoli_id'],
                    'movimenti_articoli_unita_misura' => $articolo['documenti_contabilita_articoli_unita_misura'],
                    'movimenti_articoli_codice' => $articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_codice_fornitore' => $articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_descrizione' => $articolo['documenti_contabilita_articoli_descrizione'],
                    'movimenti_articoli_quantita' => $quantities['art_' . $articolo['documenti_contabilita_articoli_id']],
                    'movimenti_articoli_iva' => $articolo['documenti_contabilita_articoli_iva'],
                    'movimenti_articoli_importo_totale' => $articolo['documenti_contabilita_articoli_importo_totale'],
                    'movimenti_articoli_sconto' => $articolo['documenti_contabilita_articoli_sconto'],
                    'movimenti_articoli_data_scadenza' => null,
                    'movimenti_articoli_lotto' => null,
                    'movimenti_articoli_barcode' => (!empty($articolo['fw_products_barcode'])) ? json_decode($articolo['fw_products_barcode'])[0] : null,
                ];



                $prodotti[] = $prodotto;
                //$this->apilib->create("movimenti_articoli", $prodotto);
            }
            $this->load->model('magazzino/mov');
            $this->mov->creaMovimento(
                [
                    'documento_id' => $documento_id,
                    'movimenti_magazzino' => $data['magazzino_id'],
                    'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                    'movimenti_articoli' => $prodotti
                ]
            );

            $this->apilib->edit('documenti_contabilita', $documento_id, [
                'documenti_contabilita_tracking_code' => $data['tracking_code'],
                'documenti_contabilita_trasporto_a_cura_di' => $data['vettore'],
                'documenti_contabilita_stato' => (empty($data['stato']) || $data['stato'] == 'closed') ? 3 : 2,
                'documenti_contabilita_fattura_accompagnatoria' => DB_BOOL_TRUE
            ]);

            die(json_encode(['status' => 4, 'txt' => 'Salvato correttamente']));
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Si è verificato un errore imprevisto']));
        }
    }

    public function saveFilters()
    {
        $data = $this->input->post();

        $filters = [];
        foreach ($data['picking_filter'] as $key => $filter) {
            if (!empty($filter)) {
                $filters[$key] = $filter;
            }
        }

        if (!empty($filters)) {
            $this->session->unset_userdata('picking_filter');
            $this->session->set_userdata('picking_filter', $filters);

            die(json_encode(['status' => 4, 'txt' => 'Filtri impostati']));
        }

        die(json_encode(['status' => 2, 'txt' => 'nessun filtro impostato']));
    }

    public function clearFilters()
    {
        $this->session->unset_userdata('picking_filter');

        redirect(base_url('main/layout/picking'));
    }
}
