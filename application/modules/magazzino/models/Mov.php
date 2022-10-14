<?php

class Mov extends CI_Model
{
    public function creaMovimento(array $data = [])
    {
        $documento_id = array_get($data, 'documento_id', false);
        if ($documento_id) { //Sto creando un movimento da un documento, quindi è tutto relativamente semplice
            $check_movimento_presente = $this->db->query("SELECT * FROM movimenti 
                WHERE 
                    movimenti_creation_date >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) 
                    AND movimenti_user = '{$this->auth->get('id')}' 
                    AND movimenti_documento_id = $documento_id")->num_rows();
            if ($check_movimento_presente) {
                return false;
            }            //Per prima cosa verifico se è un documento di vendita o di acquisto. nel primo caso creo movimento di scarico, altrimenti di carico
            $documento = $this->apilib->view('documenti_contabilita', $documento_id);
            if (in_array($documento['documenti_contabilita_tipo'], [1, 3, 5, 8, 7])) { //SCARICO
                $movimenti_tipo_movimento = 2;
                //TODO: in base al documento posso recuperarmi i dati corretti, ma per ora va bene così
                $movimenti_causale = 18; //Vendita merci, generico
                $movimenti_documento_tipo = 5; //Ordine cliente
                $movimenti_mittente = 2; // Cliente
                $movimenti_clienti_id = $documento['documenti_contabilita_customer_id'];
                $movimenti_fornitori_id = null;
            } elseif (in_array($documento['documenti_contabilita_tipo'], [4, 6, 10])) { //CARICO
                $movimenti_tipo_movimento = 1;
                //TODO: in base al documento posso recuperarmi i dati corretti, ma per ora va bene così
                $movimenti_causale = 1; //Acquisto merci, generico
                $movimenti_documento_tipo = 6; //Ordine fornitore
                $movimenti_mittente = 1; // Fornitore
                $movimenti_fornitori_id = $documento['documenti_contabilita_supplier_id'];
                $movimenti_clienti_id = null;
            } else {
                throw new Exception("Documento di tipo '{$documento['documenti_contabilita_tipo']}' non riconosciuto");
            }
            $movimenti_documento_id = $documento_id;
            $movimenti_numero_documento = $documento['documenti_contabilita_numero'];

            if ($documento['documenti_contabilita_serie']) {
                $movimenti_numero_documento                .= '/' . $documento['documenti_contabilita_serie'];
            }

            $movimenti_data_documento = $documento['documenti_contabilita_data_emissione'];
            $movimenti_totale = $documento['documenti_contabilita_totale'];
            $movimenti_destinatario = $documento['documenti_contabilita_destinatario'];

            $movimenti_articoli = [];
            foreach ($this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]) as $documento_articolo) {
                // 'movimenti_articoli_iva_id' => $documento_articolo[''],
                // 'movimenti_articoli_accantonamento'
                // 'movimenti_articoli_prodotto_id'
                // 'movimenti_articoli_movimento'
                // 'movimenti_articoli_unita_misura'
                // 'movimenti_articoli_creation_date'
                // 'movimenti_articoli_modified_date'
                // 'movimenti_articoli_codice'
                // 'movimenti_articoli_codice_fornitore'
                // 'movimenti_articoli_descrizione'
                // 'movimenti_articoli_quantita'
                // 'movimenti_articoli_prezzo'
                // 'movimenti_articoli_iva'
                // 'movimenti_articoli_importo_totale'
                // 'movimenti_articoli_sconto'
                // 'movimenti_articoli_name'
                // 'movimenti_articoli_data_scadenza'
                // 'movimenti_articoli_genera_movimenti'
                // 'movimenti_articoli_lotto'
                // 'movimenti_articoli_barcode'
                //debug($documento_articolo, true);
                $barcode = (!empty($documento_articolo['fw_products_barcode'])) ? $documento_articolo['fw_products_barcode'] : null;
                if ($barcode && is_array(json_decode($barcode))) {
                    $barcode = json_decode($barcode)[0];
                }
                $movimenti_articoli[] = [
                    'movimenti_articoli_iva_id' => $documento_articolo['documenti_contabilita_articoli_iva_id'],
                    'movimenti_articoli_accantonamento' => null,
                    'movimenti_articoli_prodotto_id' => $documento_articolo['documenti_contabilita_articoli_prodotto_id'],
                    'movimenti_articoli_unita_misura' => $documento_articolo['documenti_contabilita_articoli_unita_misura'],
                    'movimenti_articoli_codice' => $documento_articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_codice_fornitore' => $documento_articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_descrizione' => $documento_articolo['documenti_contabilita_articoli_descrizione'],
                    'movimenti_articoli_quantita' => $documento_articolo['documenti_contabilita_articoli_quantita'],
                    'movimenti_articoli_prezzo' => $documento_articolo['documenti_contabilita_articoli_prezzo'],
                    'movimenti_articoli_iva' => $documento_articolo['documenti_contabilita_articoli_iva'],
                    'movimenti_articoli_importo_totale' => $documento_articolo['documenti_contabilita_articoli_importo_totale'],
                    'movimenti_articoli_sconto' => $documento_articolo['documenti_contabilita_articoli_sconto'],
                    'movimenti_articoli_name' => $documento_articolo['documenti_contabilita_articoli_name'],
                    'movimenti_articoli_data_scadenza' => null,
                    'movimenti_articoli_lotto' => null,
                    'movimenti_articoli_barcode' => $barcode,
                ];
            }
        } else {
            debug("CreaMovimento senza documento associato non ancora gestito");
        }
        $movimenti_magazzino = $this->getMagazzino($data);
        $movimenti_data_registrazione = date('Y-m-d H:i:s');
        $movimenti_user = $this->auth->get('users_id');

        //Faccio un extract perchè qualunque campo passo in data va a sovrascrivere eventuali valori che mi son calcolato io (es.: se passo la causale, viene forzata quella)
        extract($data);

        $movimento = [
            'movimenti_user' => $movimenti_user,
            'movimenti_fornitori_id' => $movimenti_fornitori_id,
            'movimenti_clienti_id' => $movimenti_clienti_id,
            'movimenti_documento_id' => $movimenti_documento_id,
            'movimenti_magazzino' => $movimenti_magazzino,
            'movimenti_causale' => $movimenti_causale,
            'movimenti_tipo_movimento' => $movimenti_tipo_movimento,
            'movimenti_documento_tipo' => $movimenti_documento_tipo,
            'movimenti_mittente'     => $movimenti_mittente,
            'movimenti_data_registrazione' => $movimenti_data_registrazione,
            'movimenti_numero_documento' => $movimenti_numero_documento,
            'movimenti_data_documento' => $movimenti_data_documento,
            'movimenti_totale' => $movimenti_totale,
            'movimenti_destinatario' => $movimenti_destinatario,
        ];

        //Rifaccio extract perchè nulla mi vieta di passare direttamente il movimento con tutti i dati pronti all'inserimento
        extract($data);

        //A questo punto posso creare il movimento
        $movimento_id = $this->apilib->create('movimenti', $movimento, false);
        //Ora procedo a inserire i prodotti nel movimento

        //debug($movimenti_articoli, true);

        foreach ($movimenti_articoli as $articolo) {
            $articolo['movimenti_articoli_movimento'] = $movimento_id;
            $this->apilib->create('movimenti_articoli', $articolo);

            //Aggiorno le quantità movimentate nell'ordine
            if ($documento_id) {
                if ($articolo['movimenti_articoli_accantonamento']) {
                    $riga_ordine = $this->apilib->searchFirst('documenti_contabilita_articoli', [
                        'documenti_contabilita_articoli_documento' => $documento_id,
                        'documenti_contabilita_articoli_id' => $articolo['movimenti_articoli_accantonamento']
                    ]);
                    //debug($riga_ordine, true);
                    if ($riga_ordine) {
                        $this->apilib->edit('documenti_contabilita_articoli', $riga_ordine['documenti_contabilita_articoli_id'], [
                            'documenti_contabilita_articoli_qty_movimentate' => (int)$riga_ordine['documenti_contabilita_articoli_qty_movimentate'] + $articolo['movimenti_articoli_quantita']
                        ]);
                    }
                } else {
                    //debug('test', true);
                    $riga_ordine = $this->apilib->searchFirst('documenti_contabilita_articoli', [
                        'documenti_contabilita_articoli_documento' => $documento_id,
                        'documenti_contabilita_articoli_prodotto_id' => $articolo['movimenti_articoli_prodotto_id']
                    ]);
                    if ($riga_ordine) {
                        $this->apilib->edit('documenti_contabilita_articoli', $riga_ordine['documenti_contabilita_articoli_id'], [
                            'documenti_contabilita_articoli_qty_movimentate' => (int)$riga_ordine['documenti_contabilita_articoli_qty_movimentate'] + $articolo['movimenti_articoli_quantita']
                        ]);
                    }
                }
            }
        }

        return $this->apilib->view('movimenti', $movimento_id);
    }

    public function getMagazzino($data)
    {
        //TODO: funzione intelligente che scarica dal magazzino più pieno
        return $this->apilib->searchFirst('magazzini')['magazzini_id'];
    }

    public function calcolaGiacenzaAttuale($product, $magazzino = null) {
        //debug($product);
        if ($magazzino) {
            $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}' AND movimenti_magazzino = '{$magazzino}'")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}' AND movimenti_magazzino = '{$magazzino}'")->row()->qty;
        } else {
            $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}'")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}'")->row()->qty;
        }
        
        $quantity = $quantity_carico - $quantity_scarico;

        return $quantity;
    }
}
