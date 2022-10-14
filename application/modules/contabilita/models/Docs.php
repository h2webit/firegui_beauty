<?php

class Docs extends CI_Model
{
    public function generateXmlFilename($prefisso, $documento_id)
    {
        $algoritmo = $this->incrementalHash();
        $xmlfilename = $prefisso . "_" . $algoritmo . ".xml";
        for ($i = 1; $i < 100; $i++) {
            if ($this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_file_xml = '$xmlfilename'")->row_array()) {
                usleep(100000);
                $algoritmo = $this->incrementalHash();
                $xmlfilename = $prefisso . "_" . $algoritmo . ".xml";
            } else {
                break;
            }
        }
        //Se arrivato qua il file esiste ancora c'è proprio qualcosa che non va!
        if ($this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_file_xml = '$xmlfilename'")->row_array()) {
            log_message('debug', "Generati 100 random già esistenti per il documento id '{$documento_id}'! Ultimo generato: '$algoritmo'.");
            $algoritmo = '00000';
            $xmlfilename = $prefisso . "_" . $algoritmo . ".xml";
        }
        // Aggiorno il documento indicando il nome del file xml per fare match piu facilmente con le notifiche di scarto
        //$this->apilib->edit("documenti_contabilita", $documento_id, ["documenti_contabilita_nome_file_xml" => $xmlfilename]); // Sostituito perche dava problemi veniva svuotato dopo
        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_nome_file_xml = '$xmlfilename' WHERE documenti_contabilita_id = '$documento_id'");
        $this->mycache->clearCacheTags(['documenti_contabilita']);
        return $xmlfilename;
    }

    private function incrementalHash($len = 5)
    {
        $charset = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"; //abcdefghijklmnopqrstuvwxyz"; //Disabilitato perchè non chiaro nella documentazione se sia case insensitive o meno
        $base = strlen($charset);
        $result = '';

        $now = explode(' ', microtime())[1];
        while ($now >= $base) {
            $i = $now % $base;
            $result = $charset[$i] . $result;
            $now /= $base;
        }
        return substr($result, -5);
    }


    public function getMappature()
    {
        $mappature_data = $this->apilib->search('documenti_contabilita_mappature');
        return array_key_value_map($mappature_data, 'documenti_contabilita_mappature_key_value', 'documenti_contabilita_mappature_value');
    }
    public function getMappatureAutocomplete()
    {
        $mappature_data = $this->apilib->search('documenti_contabilita_mappature');
        return array_key_value_map($mappature_data, 'documenti_contabilita_mappature_key_value', 'documenti_contabilita_mappature_autocomplete');
    }
    public function getDocumentiPadri($id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $id);

        $return = [];
        $elaborated_ids = [];
        while ($documento['documenti_contabilita_rif_documento_id'] && $documento['documenti_contabilita_rif_documento_id'] != $id && !in_array($documento['documenti_contabilita_rif_documento_id'], $elaborated_ids)) {
            $elaborated_ids[] = $documento['documenti_contabilita_rif_documento_id'];
            $return[] = $this->apilib->view('documenti_contabilita', $documento['documenti_contabilita_rif_documento_id']);
            $documento = $this->apilib->view('documenti_contabilita', $documento['documenti_contabilita_rif_documento_id']);
        }

        return $return;
    }
    public function get_content_fattura_elettronica($id, $reverse = false)
    {
        $dati['fattura'] = $this->apilib->view('documenti_contabilita', $id);
        $dati['fattura']['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $id]);
        $dati['fattura']['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $id]);
        foreach ($dati['fattura']['scadenze'] as $key => $scadenza) {
            if ($scadenza['documenti_contabilita_scadenze_ammontare'] == '0.00') {
                unset($dati['fattura']['scadenze'][$key]);
            }
        }
        //        echo '<pre>';
        //        print_r($this->db->where('documenti_contabilita_id', $id)->get('documenti_contabilita')->row_array());
        //        die();
        //$pagina = $this->load->view("pages/layouts/custom_views/contabilita/xml_fattura_elettronica", compact('dati'), true);
        //die('test');
        //debug($reverse,true);
        if (!$reverse) {
            $pagina = $this->load->module_view("contabilita/views", 'xml_fattura_elettronica', ['dati' => $dati], true);
        } else {
            $pagina = $this->load->module_view("contabilita/views", 'xml_fattura_elettronica_reverse', ['dati' => $dati], true);
        }

        return $pagina;
    }
    public function generate_xml($documento)
    {
        $documento_id = $documento['documenti_contabilita_id'];

        if ($this->db->dbdriver != 'postgre') {
            $progressivo_invio = $this->db->query("SELECT MAX(CAST(documenti_contabilita_progressivo_invio AS integer)) as m FROM documenti_contabilita");
        } else {
            $progressivo_invio = $this->db->query("SELECT MAX(documenti_contabilita_progressivo_invio::int4) as m FROM documenti_contabilita");
        }

        if ($progressivo_invio->num_rows() == 0) {
            $progressivo_invio = 1;
        } else {
            $progressivo_invio = (int) ($progressivo_invio->row()->m) + 1;
        }

        $this->db->where('documenti_contabilita_id', $documento_id)->update('documenti_contabilita', ['documenti_contabilita_progressivo_invio' => $progressivo_invio]);

        $reverse = in_array($documento['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19']);
        $pdf_b64 = base64_encode($this->get_content_fattura_elettronica($documento_id, $reverse));
        //die(file_get_contents(base_url('contabilita/documenti/xml_fattura_elettronica/' . $documento_id)));
        $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file' => $pdf_b64]);

        // Storicizzo comunque un pdf dato il mio template
        // Storicizzo PDF
        if ($documento['documenti_contabilita_template_pdf']) {
            $template = $this->apilib->view('documenti_contabilita_template_pdf', $documento['documenti_contabilita_template_pdf']);

            // Se caricato un file che contiene un html da priorità a quello
            if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
            } else {
                $content_html = $template['documenti_contabilita_template_pdf_html'];
            }

            $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
        } else {
            //die('test');
            $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
        }

        if (file_exists($pdfFile)) {
            $contents = file_get_contents($pdfFile, true);
            $pdf_b64 = base64_encode($contents);
            $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_preview' => $pdf_b64]);
        }
    }
    public function numero_sucessivo($azienda, $tipo, $serie, $data)
    {
        $data_emissione = DateTime::createFromFormat("d/m/Y", $data);
        $year = $data_emissione->format('Y');

        //20220916 - Le note di credito non devono più seguire una numerazione a sè, ma proseguire con la numerazione delle fatture (anche per i reverse)
        if (in_array($tipo, [1, 4, 11, 12])) { //1,4,11 e 12 vanno insieme
            $tipo_replace = '1,4,11,12';
        } else {
            $tipo_replace = $tipo;
        }

        if ($serie) {
            $serie_where = " AND documenti_contabilita_serie = '$serie'";
        } else {
            $serie_where = " AND (documenti_contabilita_serie IS NULL OR documenti_contabilita_serie = '')";
        }
        $serie_where .= " AND documenti_contabilita_azienda = '$azienda'";

        //debug($serie_where, true);

        if ($this->db->dbdriver != 'postgre') {
            $next = $this->db->query("SELECT MAX(documenti_contabilita_numero) + 1 as numero FROM documenti_contabilita WHERE documenti_contabilita_tipo IN ($tipo_replace) $serie_where AND YEAR(documenti_contabilita_data_emissione) = $year")->row()->numero;
        //debug($this->db->last_query(), true);
        } else {
            $next = $this->db->query("SELECT MAX(documenti_contabilita_numero::int4)::int4 + 1 as numero FROM documenti_contabilita WHERE documenti_contabilita_tipo IN ($tipo_replace) $serie_where AND date_part('year', documenti_contabilita_data_emissione) = '$year'")->row()->numero;
        }

        return ($next) ?: 1;
    }

    public function doc_express_save(array $data = [])
    {
        extract($data);
        $mappature = $this->docs->getMappature();
        extract($mappature);
        if (!empty($cliente_id) || !empty($fornitore_id)) {

            $cliente = $this->apilib->view($entita_clienti, $cliente_id ?? $fornitore_id);
            $customer['ragione_sociale'] = (!empty($cliente[$clienti_ragione_sociale])) ? $cliente[$clienti_ragione_sociale] : $cliente[$clienti_nome] . ' ' . $cliente[$clienti_cognome];
            $customer['indirizzo'] = $cliente[$clienti_indirizzo];
            $customer['citta'] = $cliente[$clienti_citta];
            $customer['provincia'] = $cliente[$clienti_provincia];
            $customer['nazione'] = $cliente[$clienti_nazione];
            $customer['cap'] = $cliente[$clienti_cap];
            $customer['pec'] = $cliente[$clienti_pec];
            $customer['partita_iva'] = $cliente[$clienti_partita_iva];
            $customer['codice_fiscale'] = $cliente[$clienti_codice_fiscale];
            $customer['codice_sdi'] = $cliente[$clienti_codice_sdi];

            $destinario = json_encode($customer);
        } else {
            // die(json_encode(['status' => 0, 'txt' => 'Id cliente o fornitore non trovato.']));
            throw new ApiException('Id cliente o fornitore non trovato.');
            exit;
        }

        $settings_db = $this->apilib->searchFirst('documenti_contabilita_settings', [], 0, 'documenti_contabilita_settings_id', 'DESC');

        if ($settings_db['documenti_contabilita_settings_serie_default']) {

            $serie_db = $this->apilib->view('documenti_contabilita_serie', $settings_db['documenti_contabilita_settings_serie_default']);
        } else {
            $serie_db = $this->apilib->searchFirst('documenti_contabilita_serie');
        }

        $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'DESC');

        $serie = array_get($data, 'serie', $serie_db['documenti_contabilita_serie_value'] ?: null);
        $azienda = array_get($data, 'azienda', $settings_db['documenti_contabilita_settings_id'] ?: null);
        $tpl_pdf = array_get($data, 'template', $tpl_pdf_db['documenti_contabilita_template_pdf_id'] ?: null);
        $totale = array_get($data, 'totale', 0);
        $costo_spedizione = array_get($data, 'costo_spedizione', false);
        $tipo_destinatario = array_get($data, 'tipo_destinatario', null);
        $tipo = array_get($data, 'tipo_documento', 1);

        $dati_documento = [
            'documenti_contabilita_numero' => $this->numero_sucessivo($azienda, $tipo, $serie, date('d/m/Y')),
            'documenti_contabilita_serie' => $serie,
            'documenti_contabilita_destinatario' => $destinario,
            'documenti_contabilita_customer_id' => $cliente_id,
            'documenti_contabilita_supplier_id' => $fornitore_id ?? null,
            'documenti_contabilita_data_emissione' => array_get($data, 'data_emisssione', date('Y-m-d H:i:s')),
            'documenti_contabilita_metodo_pagamento' => array_get($data, 'metodo_pagamento', 'carta di credito'),
            'documenti_contabilita_tipo_destinatario' => $tipo_destinatario,
            'documenti_contabilita_azienda' => $azienda,
            'documenti_contabilita_utente_id' => $this->auth->get('users_id'),
            'documenti_contabilita_template_pdf' => $tpl_pdf,
            'documenti_contabilita_stato' => array_get($data, 'stato', 1),
            'documenti_contabilita_codice_esterno' => array_get($data, 'codice_esterno', null),
        ];

        $dati_documento['documenti_contabilita_note_interne'] = null;
        $dati_documento['documenti_contabilita_tipo'] = $tipo;
        $dati_documento['documenti_contabilita_valuta'] = array_get($data, 'valuta', 'EUR');
        $dati_documento['documenti_contabilita_tasso_di_cambio'] = null;
        $dati_documento['documenti_contabilita_conto_corrente'] = array_get($data, 'conto_corrente', null);
        $dati_documento['documenti_contabilita_formato_elettronico'] = array_get($data, 'formato_elettronico', DB_BOOL_FALSE);
        $dati_documento['documenti_contabilita_extra_param'] = null;
        $dati_documento['documenti_contabilita_rif_documento_id'] = null;
        $dati_documento['documenti_contabilita_da_sollecitare'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_tipologia_fatturazione'] = array_get($data, 'tipo_fatturazione', 1);
        $dati_documento['documenti_contabilita_rivalsa_inps_perc'] = null;
        $dati_documento['documenti_contabilita_stato_invio_sdi'] = array_get($data, 'stato_invio_sdi', 1);
        $dati_documento['documenti_contabilita_cassa_professionisti_perc'] = null;

        //Accompagnatoria/DDT
        $dati_documento['documenti_contabilita_fattura_accompagnatoria'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_n_colli'] = array_get($data, 'n_colli', null);
        $dati_documento['documenti_contabilita_peso'] = array_get($data, 'peso', null);
        $dati_documento['documenti_contabilita_volume'] = array_get($data, 'volume', null);
        $dati_documento['documenti_contabilita_targhe'] = null;
        $dati_documento['documenti_contabilita_descrizione_colli'] = array_get($data, 'descrizione_colli', null);
        $dati_documento['documenti_contabilita_luogo_destinazione'] = array_get($data, 'luogo_destinazione', null);
        $dati_documento['documenti_contabilita_trasporto_a_cura_di'] = array_get($data, 'vettore', null);
        $dati_documento['documenti_contabilita_causale_trasporto'] = null;
        $dati_documento['documenti_contabilita_annotazioni_trasporto'] = null;
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc'] = null;
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'] = null;
        $dati_documento['documenti_contabilita_porto'] = null;
        $dati_documento['documenti_contabilita_vettori_residenza_domicilio'] = null;
        $dati_documento['documenti_contabilita_data_ritiro_merce'] = array_get($data, 'data_ritiro_merce', null);
        $dati_documento['documenti_contabilita_rif_ddt'] = null;
        $dati_documento['documenti_contabilita_codice_esterno'] = array_get($data, 'codice_esterno', null);
        $dati_documento['documenti_contabilita_tracking_code'] = array_get($data, 'tracking_code', null);

        // Attributi avanzati Fattura Elettronica
        $dati_documento['documenti_contabilita_fe_attributi_avanzati'] = DB_BOOL_FALSE;

        /*$json = [];
        if (!empty($input['documenti_contabilita_fe_rif_n_linea'])) {
        $json['RiferimentoNumeroLinea'] = $input['documenti_contabilita_fe_rif_n_linea'];
        }

        if (!empty($input['documenti_contabilita_fe_id_documento'])) {
        $json['IdDocumento'] = $input['documenti_contabilita_fe_id_documento'];
        }*/

        $dati_documento['documenti_contabilita_fe_attributi_avanzati_json'] = '';
        $dati_documento['documenti_contabilita_fe_dati_contratto'] = '';

        //Pagamento
        $dati_documento['documenti_contabilita_accetta_paypal'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_split_payment'] = DB_BOOL_FALSE;

        $dati_documento['documenti_contabilita_centro_di_ricavo'] = array_get($data, 'centro_di_costo', null);

        $iva = [];
        $competenze = 0;
        $imponibile = 0;
        $iva_tot = 0;

        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                $importo = ($prezzo_unit * $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int) $articolo['documenti_contabilita_articoli_sconto']);
                $iva_valore = ($importo * $iva_perc) / 100;

                $iva[$articolo['documenti_contabilita_articoli_iva_perc']][] = $iva_valore;

                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $qty) {
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);

                if (!empty($fornitore_id) && $campo_prezzo_fornitore) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                } else {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                }
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);
                $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;

                $iva[$articolo['iva_valore']][] = $iva_valore;

                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);
            }
        }

        if ($costo_spedizione) {
            $imponibile += $costo_spedizione;
            $competenze += $costo_spedizione;
        }

        //Importi
        $dati_documento['documenti_contabilita_imponibile'] = array_get($data, 'imponibile', $imponibile);
        $dati_documento['documenti_contabilita_imponibile_scontato'] = array_get($data, 'imponibile_scontato', 0);
        $dati_documento['documenti_contabilita_iva'] = array_get($data, 'iva', $iva_tot);
        $dati_documento['documenti_contabilita_competenze'] = array_get($data, 'competenze', $competenze);
        $dati_documento['documenti_contabilita_iva_json'] = array_get($data, 'iva_json', json_encode($iva));
        $dati_documento['documenti_contabilita_imponibile_iva_json'] = array_get($data, 'imponibile_iva_json', json_encode([]));

        $dati_documento['documenti_contabilita_totale'] = array_get($data, 'documenti_contabilita_totale', number_format($imponibile, 2, '.', ''));

        $dati_documento['documenti_contabilita_rivalsa_inps_valore'] = 0;
        $dati_documento['documenti_contabilita_competenze_lordo_rivalsa'] = 0;
        $dati_documento['documenti_contabilita_cassa_professionisti_valore'] = 0;
        $dati_documento['documenti_contabilita_ritenuta_acconto_valore'] = 0;
        $dati_documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'] = 0;
        $dati_documento['documenti_contabilita_importo_bollo'] = 0;
        $dati_documento['documenti_contabilita_applica_bollo'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_sconto_percentuale'] = null;
        $dati_documento['documenti_contabilita_causale_pagamento_ritenuta'] = null;
        $dati_documento['documenti_contabilita_stato_pagamenti'] = array_get($data, 'stato_pagamenti', 1);

        try {
            $documento = $this->apilib->create('documenti_contabilita', $dati_documento);
            $documento_id = $documento['documenti_contabilita_id'];
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }

        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                $importo = ($prezzo_unit * $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int) $articolo['documenti_contabilita_articoli_sconto']);
                $iva_valore = ($importo * $iva_perc) / 100;

                $iva[$articolo['documenti_contabilita_articoli_iva_perc']][] = $iva_valore;

                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);

                $articolo['documenti_contabilita_articoli_imponibile'] = ($importo + $iva_valore);
                $articolo['documenti_contabilita_articoli_documento'] = $documento_id;
                //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                $articolo['documenti_contabilita_articoli_importo_totale'] = ($importo + $iva_valore);
                try {
                    $this->apilib->create("documenti_contabilita_articoli", $articolo);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $qty) {
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);

                if (!empty($fornitore_id) && $campo_prezzo_fornitore) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                } else {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                }
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);

                $sconto = (!empty($articolo['documenti_contabilita_articoli_sconto']) ? $articolo['documenti_contabilita_articoli_sconto'] : 0);

                $importo = ($prezzo_unit * $qty / 100) * (100 - (int) $sconto);

                // $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;

                $prodotto = [
                    'documenti_contabilita_articoli_documento' => $documento_id,
                    'documenti_contabilita_articoli_iva_id' => ($articolo['listino_prezzi_perc_iva'] ?? null),
                    'documenti_contabilita_articoli_name' => $articolo[$campo_preview_prodotto],
                    'documenti_contabilita_articoli_quantita' => $qty,
                    'documenti_contabilita_articoli_prodotto_id' => $articolo_id,
                    'documenti_contabilita_articoli_iva' => $iva_valore,
                    'documenti_contabilita_articoli_imponibile' => ($importo + $iva_valore),
                    'documenti_contabilita_articoli_prezzo' => $prezzo_unit,
                    'documenti_contabilita_articoli_codice' => $articolo[$campo_codice_prodotto],
                    'documenti_contabilita_articoli_iva_perc' => $iva_perc,
                    'documenti_contabilita_articoli_descrizione' => $articolo[$campo_descrizione_prodotto],
                    //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                    'documenti_contabilita_articoli_importo_totale' => ($importo + $iva_valore),
                ];

                // dump($prodotto);

                try {
                    $this->apilib->create("documenti_contabilita_articoli", $prodotto);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        }

        if ($costo_spedizione) {
            $this->apilib->create("documenti_contabilita_articoli", [
                'documenti_contabilita_articoli_documento' => $documento_id,
                'documenti_contabilita_articoli_iva_id' => $iva_default_id,
                'documenti_contabilita_articoli_name' => 'Shipping cost',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_prodotto_id' => null,
                'documenti_contabilita_articoli_iva' => 0,
                'documenti_contabilita_articoli_imponibile' => $costo_spedizione,
                'documenti_contabilita_articoli_prezzo' => $costo_spedizione,
                'documenti_contabilita_articoli_codice' => '',
                'documenti_contabilita_articoli_iva_perc' => $iva_default_valore,
                'documenti_contabilita_articoli_descrizione' => '',
                'documenti_contabilita_articoli_importo_totale' => $costo_spedizione,
            ]);
        }

        $dati_scadenza = [
            'documenti_contabilita_scadenze_documento' => $documento_id,
            'documenti_contabilita_scadenze_ammontare' => $dati_documento['documenti_contabilita_totale'],
            'documenti_contabilita_scadenze_saldato_con' => $dati_documento['documenti_contabilita_metodo_pagamento'],
            'documenti_contabilita_scadenze_saldata' => array_get($data, 'saldato', DB_BOOL_FALSE),
            'documenti_contabilita_scadenze_utente_id' => $this->auth->get('users_id'),
            'documenti_contabilita_scadenze_data_saldo' => array_get($data, 'data_saldo', null),
            'documenti_contabilita_scadenze_scadenza' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->apilib->create('documenti_contabilita_scadenze', $dati_scadenza);

            if ($dati_documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $this->docs->generate_xml($dati_documento);
            } else {
                if ($dati_documento['documenti_contabilita_template_pdf']) {
                    $template = $this->apilib->view('documenti_contabilita_template_pdf', $dati_documento['documenti_contabilita_template_pdf']);

                    // Se caricato un file che contiene un html da priorità a quello
                    if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                        $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                    } else {
                        $content_html = $template['documenti_contabilita_template_pdf_html'];
                    }
                    //die($content_html);
                    $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
                } else {
                    $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
                }

                if (file_exists($pdfFile)) {
                    $contents = file_get_contents($pdfFile, true);
                    $pdf_b64 = base64_encode($contents);
                    $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file' => $pdf_b64]);
                }
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }
        return $documento_id;
    }
    public function doc_express_save_import(array $data = [])
    {
        extract($data);
        $mappature = $this->docs->getMappature();
        extract($mappature);
        if (!empty($cliente_id) || !empty($fornitore_id)) {
            if(!empty($cliente_id)){
                $cliente = $this->apilib->view($entita_clienti, $cliente_id);
            }else {
                $cliente = $this->apilib->view($entita_clienti, $fornitore_id);
            }
            $customer['ragione_sociale'] = (!empty($cliente[$clienti_ragione_sociale])) ? $cliente[$clienti_ragione_sociale] : $cliente[$clienti_nome] . ' ' . $cliente[$clienti_cognome];
            $customer['indirizzo'] = $cliente[$clienti_indirizzo];
            $customer['citta'] = $cliente[$clienti_citta];
            $customer['provincia'] = $cliente[$clienti_provincia];
            $customer['nazione'] = $cliente[$clienti_nazione];
            $customer['cap'] = $cliente[$clienti_cap];
            $customer['pec'] = $cliente[$clienti_pec];
            $customer['partita_iva'] = $cliente[$clienti_partita_iva];
            $customer['codice_fiscale'] = $cliente[$clienti_codice_fiscale];
            $customer['codice_sdi'] = $cliente[$clienti_codice_sdi];

            $destinario = json_encode($customer);
        } else {
            // die(json_encode(['status' => 0, 'txt' => 'Id cliente o fornitore non trovato.']));
            throw new ApiException('Id cliente o fornitore non trovato.');
            exit;
        }

        $settings_db = $this->apilib->searchFirst('documenti_contabilita_settings', [], 0, 'documenti_contabilita_settings_id', 'DESC');

        if ($settings_db['documenti_contabilita_settings_serie_default']) {
            $serie_db = $this->apilib->view('documenti_contabilita_serie', $settings_db['documenti_contabilita_settings_serie_default']);
        } else {
            $serie_db = $this->apilib->searchFirst('documenti_contabilita_serie');
        }


        $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'DESC');

        $serie = array_get($data['dati_fattura'], 'documenti_contabilita_serie', $serie_db['documenti_contabilita_serie_value'] ?: null);
        $azienda = array_get($data['dati_fattura'], 'azienda', $settings_db['documenti_contabilita_settings_id'] ?: null);
        $tpl_pdf = array_get($data['dati_fattura'], 'template', $tpl_pdf_db['documenti_contabilita_template_pdf_id'] ?: null);
        $totale = array_get($data['dati_fattura'], 'totale', 0);
        $costo_spedizione = array_get($data['dati_fattura'], 'costo_spedizione', false);
        $tipo_destinatario = array_get($data['dati_fattura'], 'tipo_destinatario', null);
        $tipo = array_get($data['dati_fattura'], 'documenti_contabilita_tipo', 1);

        if(array_get($data['dati_fattura'], 'documenti_contabilita_numero')){
            $numero_documento = array_get($data['dati_fattura'], 'documenti_contabilita_numero');
        }else {
            $numero_documento = $this->numero_sucessivo($azienda, $tipo, $serie, date('d/m/Y'));
        }

        $dati_documento = [
            'documenti_contabilita_numero' => $numero_documento,
            'documenti_contabilita_serie' => $serie,
            'documenti_contabilita_destinatario' => $destinario,
            'documenti_contabilita_customer_id' => $cliente_id,
            'documenti_contabilita_supplier_id' => $fornitore_id ?? null,
            'documenti_contabilita_data_emissione' => array_get($data['dati_fattura'], 'documenti_contabilita_data_emissione', date('Y-m-d H:i:s')),
            'documenti_contabilita_metodo_pagamento' => array_get($data, 'metodo_pagamento', 'carta di credito'),
            'documenti_contabilita_tipo_destinatario' => $tipo_destinatario,
            'documenti_contabilita_azienda' => $azienda,
            'documenti_contabilita_utente_id' => $this->auth->get('users_id'),
            'documenti_contabilita_template_pdf' => $tpl_pdf,
            'documenti_contabilita_stato' => array_get($data, 'stato', 1),
            'documenti_contabilita_codice_esterno' => array_get($data, 'codice_esterno', null),
        ];

        $dati_documento['documenti_contabilita_note_interne'] = null;
        $dati_documento['documenti_contabilita_tipo'] = $tipo;
        $dati_documento['documenti_contabilita_valuta'] = array_get($data, 'valuta', 'EUR');
        $dati_documento['documenti_contabilita_tasso_di_cambio'] = null;
        $dati_documento['documenti_contabilita_conto_corrente'] = array_get($data, 'conto_corrente', null);
        $dati_documento['documenti_contabilita_formato_elettronico'] = array_get($data, 'formato_elettronico', DB_BOOL_FALSE);
        $dati_documento['documenti_contabilita_extra_param'] = null;
        $dati_documento['documenti_contabilita_rif_documento_id'] = null;
        $dati_documento['documenti_contabilita_da_sollecitare'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_tipologia_fatturazione'] = array_get($data, 'tipo_fatturazione', 1);
        $dati_documento['documenti_contabilita_rivalsa_inps_perc'] = null;
        $dati_documento['documenti_contabilita_stato_invio_sdi'] = array_get($data, 'stato_invio_sdi', 1);
        $dati_documento['documenti_contabilita_cassa_professionisti_perc'] = null;

        //Accompagnatoria/DDT
        $dati_documento['documenti_contabilita_fattura_accompagnatoria'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_n_colli'] = array_get($data, 'n_colli', null);
        $dati_documento['documenti_contabilita_peso'] = array_get($data, 'peso', null);
        $dati_documento['documenti_contabilita_volume'] = array_get($data, 'volume', null);
        $dati_documento['documenti_contabilita_targhe'] = null;
        $dati_documento['documenti_contabilita_descrizione_colli'] = array_get($data, 'descrizione_colli', null);
        $dati_documento['documenti_contabilita_luogo_destinazione'] = array_get($data, 'luogo_destinazione', null);
        $dati_documento['documenti_contabilita_trasporto_a_cura_di'] = array_get($data, 'vettore', null);
        $dati_documento['documenti_contabilita_causale_trasporto'] = null;
        $dati_documento['documenti_contabilita_annotazioni_trasporto'] = null;
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc'] = null;
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'] = null;
        $dati_documento['documenti_contabilita_porto'] = null;
        $dati_documento['documenti_contabilita_vettori_residenza_domicilio'] = null;
        $dati_documento['documenti_contabilita_data_ritiro_merce'] = array_get($data, 'data_ritiro_merce', null);
        $dati_documento['documenti_contabilita_rif_ddt'] = null;
        $dati_documento['documenti_contabilita_codice_esterno'] = array_get($data, 'codice_esterno', null);
        $dati_documento['documenti_contabilita_tracking_code'] = array_get($data, 'tracking_code', null);

        // Attributi avanzati Fattura Elettronica
        $dati_documento['documenti_contabilita_fe_attributi_avanzati'] = DB_BOOL_FALSE;

        /*$json = [];
        if (!empty($input['documenti_contabilita_fe_rif_n_linea'])) {
            $json['RiferimentoNumeroLinea'] = $input['documenti_contabilita_fe_rif_n_linea'];
        }

        if (!empty($input['documenti_contabilita_fe_id_documento'])) {
            $json['IdDocumento'] = $input['documenti_contabilita_fe_id_documento'];
        }*/

        $dati_documento['documenti_contabilita_fe_attributi_avanzati_json'] = '';
        $dati_documento['documenti_contabilita_fe_dati_contratto'] = '';

        //Pagamento
        $dati_documento['documenti_contabilita_accetta_paypal'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_split_payment'] = DB_BOOL_FALSE;

        $dati_documento['documenti_contabilita_centro_di_ricavo'] = array_get($data, 'centro_di_costo', null);

        $iva = [];
        $competenze = 0;
        $imponibile = 0;
        $iva_tot = 0;

        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                $importo = ($prezzo_unit * $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int)$articolo['documenti_contabilita_articoli_sconto']);
                $iva_valore = ($importo * $iva_perc) / 100;

                $iva[$articolo['documenti_contabilita_articoli_iva_perc']][] = $iva_valore;

                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $qty) {
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);

                if (!empty($fornitore_id) && $campo_prezzo_fornitore) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                } else {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                }
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);
                $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;

                $iva[$articolo['iva_valore']][] = $iva_valore;

                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);
            }
        }

        if ($costo_spedizione) {
            $imponibile += $costo_spedizione;
            $competenze += $costo_spedizione;
        }
        $dati_documento['documenti_contabilita_sconto_percentuale'] =  array_get($data['dati_fattura'], 'sconto_percentuale', null);
        //$dati_documento['documenti_contabilita_imponibile_scontato'] = array_get($data['dati_fattura'], 'imponibile_scontato', 0);
        //Importi
        $dati_documento['documenti_contabilita_imponibile'] = array_get($data, 'imponibile', $imponibile);
        if(array_get($data['dati_fattura'], 'importo_scontato')){
            $iva_tot = $iva_tot- ( $iva_tot*$dati_documento['documenti_contabilita_sconto_percentuale']/100 );
            $sconto_imponibile = $dati_documento['documenti_contabilita_imponibile']*$dati_documento['documenti_contabilita_sconto_percentuale']/100;
            $dati_documento['documenti_contabilita_imponibile_scontato'] = $dati_documento['documenti_contabilita_imponibile']-$sconto_imponibile;
            $dati_documento['documenti_contabilita_imponibile_scontato'] = number_format($dati_documento['documenti_contabilita_imponibile_scontato'], 2, '.', '');
            $imponibile = $dati_documento['documenti_contabilita_imponibile']-$sconto_imponibile;
            $dati_documento['documenti_contabilita_imponibile'] = number_format($competenze-($competenze*$dati_documento['documenti_contabilita_sconto_percentuale']/100),2, '.', '');
            $dati_documento['documenti_contabilita_imponibile_scontato'] = $dati_documento['documenti_contabilita_imponibile'];
        }
        $dati_documento['documenti_contabilita_iva'] = array_get($data, 'iva', $iva_tot);
        $dati_documento['documenti_contabilita_competenze'] = array_get($data, 'competenze', $competenze);
        $dati_documento['documenti_contabilita_iva_json'] = array_get($data, 'iva_json', json_encode($iva));
        $dati_documento['documenti_contabilita_imponibile_iva_json'] = array_get($data, 'imponibile_iva_json', json_encode([]));

        $dati_documento['documenti_contabilita_totale'] = array_get($data, 'documenti_contabilita_totale', number_format($imponibile, 2, '.', ''));

        $dati_documento['documenti_contabilita_rivalsa_inps_valore'] = 0;
        $dati_documento['documenti_contabilita_competenze_lordo_rivalsa'] = 0;
        $dati_documento['documenti_contabilita_cassa_professionisti_valore'] = 0;
        $dati_documento['documenti_contabilita_ritenuta_acconto_valore'] = 0;
        $dati_documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'] = 0;
        $dati_documento['documenti_contabilita_importo_bollo'] = 0;
        $dati_documento['documenti_contabilita_applica_bollo'] = DB_BOOL_FALSE;
        //$dati_documento['documenti_contabilita_importo_scontato'] =  array_get($data['dati_fattura'], 'importo_scontato', null);
        
        $dati_documento['documenti_contabilita_causale_pagamento_ritenuta'] = null;
        $dati_documento['documenti_contabilita_stato_pagamenti'] = array_get($data, 'stato_pagamenti', 1);
        try {
            $documento = $this->apilib->create('documenti_contabilita', $dati_documento);
            $documento_id = $documento['documenti_contabilita_id'];
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }

        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                $importo = ($prezzo_unit * $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int)$articolo['documenti_contabilita_articoli_sconto']);
                $iva_valore = ($importo * $iva_perc) / 100;
                $iva[$articolo['documenti_contabilita_articoli_iva_perc']][] = $iva_valore;
                //trovo il valore dell'iva, se ce ne sono di più, prendo la prima
                if($iva_perc=='0.00'){
                    $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string)$articolo['documenti_contabilita_articoli_iva_id']]);
                }else{
                    $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $iva_perc]);

                }
                $articolo['documenti_contabilita_articoli_iva_id'] = $iva['iva_id'];
                $articolo['documenti_contabilita_articoli_iva'] = $iva_valore;
                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);
                $articolo['documenti_contabilita_articoli_applica_sconto'] = DB_BOOL_TRUE;

                $articolo['documenti_contabilita_articoli_imponibile'] = ($importo + $iva_valore);
                $articolo['documenti_contabilita_articoli_documento'] = $documento_id;
                //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                $articolo['documenti_contabilita_articoli_importo_totale'] = ($importo + $iva_valore);
                try {
                    $this->apilib->create("documenti_contabilita_articoli", $articolo);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $qty) {
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);

                if (!empty($fornitore_id) && $campo_prezzo_fornitore) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                } else {
                    $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                }
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);

                $sconto = (!empty($articolo['documenti_contabilita_articoli_sconto']) ?$articolo['documenti_contabilita_articoli_sconto']:0 );

                $importo = ($prezzo_unit * $qty / 100) * (100 - (int)$sconto);

                // $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;

                $prodotto = [
                    'documenti_contabilita_articoli_documento' => $documento_id,
                    'documenti_contabilita_articoli_iva_id' => ($articolo['listino_prezzi_perc_iva'] ?? null),
                    'documenti_contabilita_articoli_name' => $articolo[$campo_preview_prodotto],
                    'documenti_contabilita_articoli_quantita' => $qty,
                    'documenti_contabilita_articoli_prodotto_id' => $articolo_id,
                    'documenti_contabilita_articoli_iva' => $iva_valore,
                    'documenti_contabilita_articoli_imponibile' => ($importo + $iva_valore),
                    'documenti_contabilita_articoli_prezzo' => $prezzo_unit,
                    'documenti_contabilita_articoli_codice' => $articolo[$campo_codice_prodotto],
                    'documenti_contabilita_articoli_iva_perc' => $iva_perc,
                    'documenti_contabilita_articoli_descrizione' => $articolo[$campo_descrizione_prodotto],
                    'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_TRUE,

                    //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                    'documenti_contabilita_articoli_importo_totale' => ($importo + $iva_valore),
                ];

                // dump($prodotto);

                try {
                    $this->apilib->create("documenti_contabilita_articoli", $prodotto);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        }

        if ($costo_spedizione) {
            $this->apilib->create("documenti_contabilita_articoli", [
                'documenti_contabilita_articoli_documento' => $documento_id,
                'documenti_contabilita_articoli_iva_id' => $iva_default_id,
                'documenti_contabilita_articoli_name' => 'Shipping cost',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_prodotto_id' => null,
                'documenti_contabilita_articoli_iva' => 0,
                'documenti_contabilita_articoli_imponibile' => $costo_spedizione,
                'documenti_contabilita_articoli_prezzo' => $costo_spedizione,
                'documenti_contabilita_articoli_codice' => '',
                'documenti_contabilita_articoli_iva_perc' => $iva_default_valore,
                'documenti_contabilita_articoli_descrizione' => '',
                'documenti_contabilita_articoli_importo_totale' => $costo_spedizione,
            ]);
        }
        $dati_scadenza = [
            'documenti_contabilita_scadenze_documento' => $documento_id,
            'documenti_contabilita_scadenze_ammontare' => $dati_documento['documenti_contabilita_totale'],
            'documenti_contabilita_scadenze_saldato_con' => $dati_documento['documenti_contabilita_metodo_pagamento'],
            'documenti_contabilita_scadenze_saldata' => array_get($data, 'saldato', DB_BOOL_TRUE),
            'documenti_contabilita_scadenze_utente_id' => $this->auth->get('users_id'),
            'documenti_contabilita_scadenze_data_saldo' =>            array_get($data, 'data_saldo', null),
            'documenti_contabilita_scadenze_scadenza' => array_get($data['dati_fattura'], 'documenti_contabilita_data_emissione', date('Y-m-d H:i:s'))
        ];

        try {
            $this->apilib->create('documenti_contabilita_scadenze', $dati_scadenza);

            if ($dati_documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $this->docs->generate_xml($dati_documento);
            } else {
                if ($dati_documento['documenti_contabilita_template_pdf']) {
                    $template = $this->apilib->view('documenti_contabilita_template_pdf', $dati_documento['documenti_contabilita_template_pdf']);

                    // Se caricato un file che contiene un html da priorità a quello
                    if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {

                        $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                    } else {
                        $content_html = $template['documenti_contabilita_template_pdf_html'];
                    }
                    //die($content_html);
                    $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', TRUE);
                } else {
                    $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
                }

                if (file_exists($pdfFile)) {
                    $contents = file_get_contents($pdfFile, true);
                    $pdf_b64 = base64_encode($contents);
                    $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file' => $pdf_b64]);
                }
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }
        return $documento_id;
    }
    public function extractIbanData($iban)
    {
        // IT 94 P 02008 12310 000101714112
        $iban = str_ireplace(' ', '', $iban);
        return [
            'sigla' => substr($iban, 0, 2),
            'controllo' => substr($iban, 2, 2),
            'cin' => substr($iban, 4, 1),
            'abi' => substr($iban, 5, 5),
            'cab' => substr($iban, 10, 5),
            'cc' => substr($iban, 15),
        ];
    }
}
