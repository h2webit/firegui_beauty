<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Documenti extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Se non sono loggato allora semplicemente uccido la richiesta
        if ($this->auth->guest()) {
            set_status_header(401); // Unauthorized
            die('Non sei loggato nel sistema');
        }

        $this->load->model('contabilita/docs');

        $this->settings = $this->db->get('settings')->row_array();
        $this->contabilita_settings = $this->apilib->searchFirst('documenti_contabilita_settings');
    }

    public function create_document()
    {
        $input = $this->input->post();

        if (!empty($input['spesa_id'])) {
            $spesa_id = $input['spesa_id'];
            unset($input['spesa_id']);
        }

        if (!empty($input['documento_id'])) {
            //die('NON GESTITO SALVATAGGIO IN MODIFICA.... Da completare!');
        }

        $this->load->library('form_validation');

        $this->form_validation->set_rules('documenti_contabilita_tipo', 'Tipo documento', 'required');
        $this->form_validation->set_rules('documenti_contabilita_numero', 'Numero documento', 'required');
        $this->form_validation->set_rules('documenti_contabilita_data_emissione', 'data emissione', 'required');

        //Barbatrucco matteo: non è detto che sia 1 nel caso di riga eliminata (può partire da 2, da 3 o altro...)
        $chiave = 1;
        foreach (@$input['products'] as $key => $p) {
            $chiave = $key;
            break;
        }

        //Verifico che il numero di fattura e la serie rispettino le regole di fatturazione
        $numero = $this->input->post('documenti_contabilita_numero');
        $serie = $this->input->post('documenti_contabilita_serie');
        $azienda = $this->input->post('documenti_contabilita_azienda');
        $tipo = $this->input->post('documenti_contabilita_tipo');

        $this->form_validation->set_rules('products[' . $chiave . '][documenti_contabilita_articoli_name]', 'nome prodotto', 'required');
        $this->form_validation->set_rules('ragione_sociale', 'ragione sociale', 'required');
        $this->form_validation->set_rules('indirizzo', 'indirizzo', 'required');
        $this->form_validation->set_rules('citta', 'città', 'required');
        $this->form_validation->set_rules('nazione', 'nazione', 'required');
        $this->form_validation->set_rules('provincia', 'provincia', 'required|max_length[2]');

        if ($this->input->post('documenti_contabilita_formato_elettronico') == DB_BOOL_TRUE) {
            if (in_array($tipo, ['1', '4'])) {
                if ($this->input->post('provincia') != 'EE' || $this->input->post('nazione') == 'Italia') {
                    if (empty($input['partita_iva'])) {
                        $this->form_validation->set_rules('codice_fiscale', 'codice fiscale', 'required');
                    }
                }
            }
            $this->form_validation->set_rules('documenti_contabilita_tipologia_fatturazione', 'Tipologia di fatturazione', 'required');
        } else {
        }

        $this->form_validation->set_rules('cap', 'CAP', 'required');

        // DATA EMISSIONE
        if ($this->db->dbdriver != 'postgre') {
            //debug($input['documenti_contabilita_data_emissione'],true);

            $date = DateTime::createFromFormat("d/m/Y", $input['documenti_contabilita_data_emissione']);
            $data_emissione = $date->format('Y-m-d H:i:s');
            $year = $date->format('Y');
            $filtro_anno = "AND YEAR(documenti_contabilita_data_emissione) = $year";
        } else {
            //$data_emissione = $input['documenti_contabilita_data_emissione'];
            $date = DateTime::createFromFormat("d/m/Y", $input['documenti_contabilita_data_emissione']);
            $data_emissione = $date->format('Y-m-d H:i:s');
            $year = $date->format('Y');
            $filtro_anno = "AND date_part('year', documenti_contabilita_data_emissione) = '$year'";
        }

        //Controllo se esiste una fattura o una nota di credito con stesso numero. Per gli altri tipi di documento, ignoro il controllo
        if (in_array($input['documenti_contabilita_tipo'], [1, 4, 11, 12])) {
            if (!empty($input['documento_id'])) {
                $exists = $this->db->query("SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_serie = '$serie' AND documenti_contabilita_azienda = '$azienda' AND documenti_contabilita_numero = '$numero' AND documenti_contabilita_id <> '{$input['documento_id']}' AND documenti_contabilita_tipo = '{$tipo}' $filtro_anno")->num_rows();
                if ($exists) {
                    echo json_encode(array(
                        'status' => 0,
                        'txt' => "Esiste già un documento con questo numero!",
                        'data' => '',
                    ));
                    exit;
                }
            } else {
                $exists = $this->db->query("SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_serie = '$serie' AND documenti_contabilita_azienda = '$azienda' AND documenti_contabilita_numero = '$numero' AND documenti_contabilita_tipo = '{$tipo}' $filtro_anno")->num_rows();
                if ($exists) {
                    echo json_encode(array(
                        'status' => 0,
                        'txt' => "Esiste già un documento con questo numero!",
                        'data' => '',
                    ));
                    exit;
                }
            }

            if ($this->db->dbdriver != 'postgre') {
                $filtro_data = "AND date(documenti_contabilita_data_emissione) < date('{$data_emissione}')";
            } else {
                $filtro_data = "AND documenti_contabilita_data_emissione::date < '{$data_emissione}'::date";
            }
            $exists_with_number_next = $this->db->query("SELECT documenti_contabilita_id,documenti_contabilita_numero,documenti_contabilita_data_emissione FROM documenti_contabilita WHERE documenti_contabilita_serie = '$serie' AND documenti_contabilita_azienda = '$azienda' AND documenti_contabilita_numero > $numero AND documenti_contabilita_tipo = '{$tipo}' $filtro_data $filtro_anno");

            if ($exists_with_number_next->num_rows()) {
                $fattura = $exists_with_number_next->row();
                echo json_encode(array(
                    'status' => 0,
                    'txt' => "Esiste un documento (numero '{$fattura->documenti_contabilita_numero}' del '{$fattura->documenti_contabilita_data_emissione}') con numero maggiore ma data inferiore!",
                    'data' => '',
                ));
                exit;
            }

            if ($this->db->dbdriver != 'postgre') {
                $filtro_data = "AND date(documenti_contabilita_data_emissione) > date('{$data_emissione}')";
            } else {
                $filtro_data = "AND documenti_contabilita_data_emissione::date > '{$data_emissione}'::date";
            }

            if (!empty($input['documento_id'])) {
                $exists_with_date_next = $this->db->query("SELECT documenti_contabilita_id,documenti_contabilita_numero,documenti_contabilita_data_emissione FROM documenti_contabilita WHERE
                    documenti_contabilita_serie = '$serie'
                    AND documenti_contabilita_azienda = '$azienda'
                    AND documenti_contabilita_numero < $numero
                    AND documenti_contabilita_tipo = '{$tipo}'
                    $filtro_data
                    $filtro_anno
                    AND documenti_contabilita_id <> '{$input['documento_id']}'");
            } else {
                $exists_with_date_next = $this->db->query("SELECT documenti_contabilita_id,documenti_contabilita_numero,documenti_contabilita_data_emissione FROM documenti_contabilita WHERE
                    documenti_contabilita_serie = '$serie'
                    AND documenti_contabilita_azienda = '$azienda'
                    AND documenti_contabilita_numero < $numero
                    AND documenti_contabilita_tipo = '{$tipo}'
                    $filtro_data
                    $filtro_anno");
            }
            if ($exists_with_date_next->num_rows()) {
                $fattura = $exists_with_date_next->row();
                echo json_encode(array(
                    'status' => 0,
                    'txt' => "Esiste una fattura (la numero '{$fattura->documenti_contabilita_numero}' del '{$fattura->documenti_contabilita_data_emissione}') con numero minore ma data superiore!",
                    'data' => '',
                ));
                exit;
            }
        }

        if ($this->form_validation->run() == false) {
            echo json_encode(array(
                'status' => 0,
                'txt' => validation_errors(),
                'data' => '',
            ));
        } else {
            //debug($input, true);

            $dest_entity_name = $input['dest_entity_name'];

            // **************** DESTINATARIO ****************** //

            //TODO: tutto sbagliato qua! Vanno prese le mappature dai settings!
            $dest_fields = array("codice", "ragione_sociale", "indirizzo", "citta", "provincia", "nazione", "codice_fiscale", "partita_iva", 'cap', 'pec', 'codice_sdi');
            foreach ($input as $key => $value) {
                if (in_array($key, $dest_fields)) {
                    $destinatario_json[$key] = trim($value);
                    //$destinatario_entity[$dest_entity_name . "_" . $key] = trim($value);
                }
            }
            if ($input['documenti_contabilita_tipo'] != 11 && $input['documenti_contabilita_tipo'] != 12 && $input['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                if ($destinatario_json['codice_sdi'] !== '0000000' || !empty($this->contabilita_settings['partita_iva'])) {
                    if (empty($destinatario_json['codice_sdi']) && empty($destinatario_json['pec'])) {
                        echo json_encode(array(
                            'status' => 0,
                            'txt' => "Per le aziende la PEC o il Codice destinatario SDI devono essere compilati",
                            'data' => '',
                        ));
                        exit;
                    }
                }
            }

            // Serialize
            $documents['documenti_contabilita_destinatario'] = json_encode($destinatario_json);

            $mappature = $this->docs->getMappature();
            extract($mappature);

            if ($this->input->post('documenti_contabilita_tipo_destinatario') == 2) { //Privato
                $customer = [
                    $clienti_ragione_sociale => $input['ragione_sociale'],
                ];
            } else {
                $customer = [
                    $clienti_nome => $input['ragione_sociale'],
                ];
            }

            $nazione = $this->db->get_where('countries', ['countries_iso' => $input['nazione']])->row_array();

            $customer[$clienti_indirizzo] = $input['indirizzo'];
            $customer[$clienti_citta] = $input['citta'];
            $customer[$clienti_provincia] = $input['provincia'];
            $customer[$clienti_nazione] = $nazione['countries_id'];
            $customer[$clienti_cap] = $input['cap'];
            $customer[$clienti_pec] = $input['pec'];

            $customer[$clienti_partita_iva] = $input['partita_iva'];
            $customer[$clienti_codice_fiscale] = $input['codice_fiscale'];
            $customer[$clienti_codice_sdi] = $input['codice_sdi'];

            // Se già censito lo collego altrimenti lo salvo se richiesto
            if ($input['dest_id']) {
                //TODO: salvare su clienti id o su suppliers id a seconda dell'entità dest
                if ($dest_entity_name == 'suppliers') {
                    $documents['documenti_contabilita_supplier_id'] = $input['dest_id'];
                } else {
                    $documents['documenti_contabilita_customer_id'] = $input['dest_id'];
                }

                //Se ho comunque richiesto la sovrascrittura dei dati
                if (isset($input['save_dest']) && isset($input['save_dest']) && $input['save_dest'] == "true") {
                    $this->apilib->edit($entita_clienti, $input['dest_id'], $customer);
                }
            } elseif (isset($input['save_dest']) && $input['save_dest'] == "true") {
                if ($dest_entity_name == 'suppliers') {
                    $supplier = [
                        'customers_company' => $input['ragione_sociale'],
                    ];

                    // 2021-07-01 - michael e. - commento in quanto suppliers è stato unificato dentro customers con type 2
                    // $supplier['suppliers_address'] = $input['indirizzo'];
                    // $supplier['suppliers_city'] = $input['citta'];
                    // $supplier['suppliers_province'] = $input['provincia'];
                    // $supplier['suppliers_country'] = $input['nazione'];
                    // $supplier['suppliers_zip_code'] = $input['cap'];
                    // $supplier['suppliers_pec'] = $input['pec'];

                    // $supplier['suppliers_vat_number'] = $input['partita_iva'];
                    // $supplier['suppliers_cf'] = $input['codice_fiscale'];
                    // $supplier['suppliers_sdi'] = $input['codice_sdi'];

                    $supplier[$clienti_indirizzo] = $input['indirizzo'];
                    $supplier[$clienti_citta] = $input['citta'];
                    $supplier[$clienti_provincia] = $input['provincia'];
                    $supplier[$clienti_nazione] = $nazione['countries_id'];
                    $supplier[$clienti_cap] = $input['cap'];
                    $supplier[$clienti_pec] = $input['pec'];

                    $supplier[$clienti_partita_iva] = $input['partita_iva'];
                    $supplier[$clienti_codice_fiscale] = $input['codice_fiscale'];
                    $supplier[$clienti_codice_sdi] = $input['codice_sdi'];

                    $dest_id = $this->apilib->create('suppliers', $supplier, false);
                    $documents['documenti_contabilita_supplier_id'] = $dest_id;
                } else {
                    $dest_id = $this->apilib->create($entita_clienti, $customer, false);
                    $documents['documenti_contabilita_customer_id'] = $dest_id;
                }
            }

            // **************** DOCUMENTO ****************** //
            // debug($input, true);

            $documents['documenti_contabilita_note_interne'] = $input['documenti_contabilita_note'];
            $documents['documenti_contabilita_tipo'] = $input['documenti_contabilita_tipo'];
            $documents['documenti_contabilita_numero'] = $input['documenti_contabilita_numero'];
            $documents['documenti_contabilita_serie'] = $input['documenti_contabilita_serie'];
            $documents['documenti_contabilita_valuta'] = $input['documenti_contabilita_valuta'];
            $documents['documenti_contabilita_tasso_di_cambio'] = $input['documenti_contabilita_tasso_di_cambio'];
            $documents['documenti_contabilita_metodo_pagamento'] = $input['documenti_contabilita_metodo_pagamento'];
            $documents['documenti_contabilita_conto_corrente'] = ($input['documenti_contabilita_conto_corrente']) ?: null;
            $documents['documenti_contabilita_data_emissione'] = $data_emissione;
            $documents['documenti_contabilita_formato_elettronico'] = (!empty($input['documenti_contabilita_formato_elettronico']) ? $input['documenti_contabilita_formato_elettronico'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_extra_param'] = ($input['documenti_contabilita_extra_param']) ?: null;
            $documents['documenti_contabilita_rif_documento_id'] = ($input['documenti_contabilita_rif_documento_id']) ?: null;
            $documents['documenti_contabilita_da_sollecitare'] = (!empty($input['documenti_contabilita_da_sollecitare']) ? $input['documenti_contabilita_da_sollecitare'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_tipologia_fatturazione'] = (!empty($input['documenti_contabilita_tipologia_fatturazione'])) ? $input['documenti_contabilita_tipologia_fatturazione'] : null;
            //debug($input['documenti_contabilita_competenze'],true);

            $documents['documenti_contabilita_rivalsa_inps_perc'] = $input['documenti_contabilita_rivalsa_inps_perc'];

            $documents['documenti_contabilita_cassa_professionisti_perc'] = $input['documenti_contabilita_cassa_professionisti_perc'];

            //Accompagnatoria/DDT
            $documents['documenti_contabilita_fattura_accompagnatoria'] = (!empty($input['documenti_contabilita_fattura_accompagnatoria']) ? $input['documenti_contabilita_fattura_accompagnatoria'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_n_colli'] = ($input['documenti_contabilita_n_colli'] ?: null);
            $documents['documenti_contabilita_peso'] = ($input['documenti_contabilita_peso'] ?: null);
            $documents['documenti_contabilita_volume'] = ($input['documenti_contabilita_volume'] ?: null);
            $documents['documenti_contabilita_targhe'] = ($input['documenti_contabilita_targhe'] ?: null);
            $documents['documenti_contabilita_descrizione_colli'] = ($input['documenti_contabilita_descrizione_colli'] ?: null);
            $documents['documenti_contabilita_luogo_destinazione'] = $input['documenti_contabilita_luogo_destinazione'];
            $documents['documenti_contabilita_luogo_destinazione_id'] = $input['documenti_contabilita_luogo_destinazione_id'];
            $documents['documenti_contabilita_trasporto_a_cura_di'] = $input['documenti_contabilita_trasporto_a_cura_di'];
            $documents['documenti_contabilita_causale_trasporto'] = $input['documenti_contabilita_causale_trasporto'];
            $documents['documenti_contabilita_annotazioni_trasporto'] = $input['documenti_contabilita_annotazioni_trasporto'];
            $documents['documenti_contabilita_ritenuta_acconto_perc'] = $input['documenti_contabilita_ritenuta_acconto_perc'];
            $documents['documenti_contabilita_ritenuta_acconto_perc_imponibile'] = $input['documenti_contabilita_ritenuta_acconto_perc_imponibile'];
            $documents['documenti_contabilita_porto'] = $input['documenti_contabilita_porto'];
            $documents['documenti_contabilita_vettori_residenza_domicilio'] = $input['documenti_contabilita_vettori_residenza_domicilio'];
            $documents['documenti_contabilita_data_ritiro_merce'] = $input['documenti_contabilita_data_ritiro_merce'];
            $documents['documenti_contabilita_tipo_destinatario'] = (!empty($input['documenti_contabilita_tipo_destinatario'])) ? $input['documenti_contabilita_tipo_destinatario'] : null;
            $documents['documenti_contabilita_utente_id'] = (!empty($input['documenti_contabilita_utente_id'])) ? $input['documenti_contabilita_utente_id'] : null;
            $documents['documenti_contabilita_rif_ddt'] = (!empty($input['documenti_contabilita_rif_ddt'])) ? $input['documenti_contabilita_rif_ddt'] : null;

            $documents['documenti_contabilita_tracking_code'] = ($input['documenti_contabilita_tracking_code'] ?: null);

            // Attributi avanzati Fattura Elettronica
            $documents['documenti_contabilita_fe_attributi_avanzati'] = (!empty($input['documenti_contabilita_fe_attributi_avanzati']) ? $input['documenti_contabilita_fe_attributi_avanzati'] : DB_BOOL_FALSE);

            $json = [];
            if (!empty($input['documenti_contabilita_fe_rif_n_linea'])) {
                $json['RiferimentoNumeroLinea'] = $input['documenti_contabilita_fe_rif_n_linea'];
            }

            if (!empty($input['documenti_contabilita_fe_id_documento'])) {
                $json['IdDocumento'] = $input['documenti_contabilita_fe_id_documento'];
            }

            $documents['documenti_contabilita_fe_attributi_avanzati_json'] = (!empty($json)) ? json_encode($json) : '';
            $documents['documenti_contabilita_fe_dati_contratto'] = json_encode($input['documenti_contabilita_fe_dati_contratto']);

            //Pagamento
            $documents['documenti_contabilita_accetta_paypal'] = (!empty($input['documenti_contabilita_accetta_paypal']) ? $input['documenti_contabilita_accetta_paypal'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_split_payment'] = (!empty($input['documenti_contabilita_split_payment']) ? $input['documenti_contabilita_split_payment'] : DB_BOOL_FALSE);

            $documents['documenti_contabilita_centro_di_ricavo'] = (!empty($input['documenti_contabilita_centro_di_ricavo']) ? $input['documenti_contabilita_centro_di_ricavo'] : null);
            $documents['documenti_contabilita_template_pdf'] = (!empty($input['documenti_contabilita_template_pdf']) ? $input['documenti_contabilita_template_pdf'] : null);

            //Importi
            $documents['documenti_contabilita_totale'] = $input['documenti_contabilita_totale'];
            $documents['documenti_contabilita_iva'] = $input['documenti_contabilita_iva'];
            $documents['documenti_contabilita_competenze'] = ($input['documenti_contabilita_competenze']) ?: 0;
            $documents['documenti_contabilita_rivalsa_inps_valore'] = $input['documenti_contabilita_rivalsa_inps_valore'];
            $documents['documenti_contabilita_competenze_lordo_rivalsa'] = $input['documenti_contabilita_competenze_lordo_rivalsa'];
            $documents['documenti_contabilita_cassa_professionisti_valore'] = $input['documenti_contabilita_cassa_professionisti_valore'];
            $documents['documenti_contabilita_imponibile'] = $input['documenti_contabilita_imponibile'];
            $documents['documenti_contabilita_imponibile_scontato'] = $input['documenti_contabilita_imponibile_scontato'];
            $documents['documenti_contabilita_ritenuta_acconto_valore'] = $input['documenti_contabilita_ritenuta_acconto_valore'];
            $documents['documenti_contabilita_ritenuta_acconto_imponibile_valore'] = $input['documenti_contabilita_ritenuta_acconto_imponibile_valore'];
            $documents['documenti_contabilita_importo_bollo'] = $input['documenti_contabilita_importo_bollo'];
            $documents['documenti_contabilita_applica_bollo'] = (!empty($input['documenti_contabilita_applica_bollo'])) ? $input['documenti_contabilita_applica_bollo'] : DB_BOOL_FALSE;
            $documents['documenti_contabilita_bollo_virtuale'] = (!empty($input['documenti_contabilita_bollo_virtuale'])) ? $input['documenti_contabilita_bollo_virtuale'] : DB_BOOL_FALSE;
            $documents['documenti_contabilita_iva_json'] = $input['documenti_contabilita_iva_json'];
            $documents['documenti_contabilita_imponibile_iva_json'] = $input['documenti_contabilita_imponibile_iva_json'];
            $documents['documenti_contabilita_sconto_percentuale'] = $input['documenti_contabilita_sconto_percentuale'];

            $documents['documenti_contabilita_azienda'] = $input['documenti_contabilita_azienda'];

            $documents['documenti_contabilita_causale_pagamento_ritenuta'] = $input['documenti_contabilita_causale_pagamento_ritenuta'];
            $documents['documenti_contabilita_tipo_ritenuta'] = $input['documenti_contabilita_tipo_ritenuta'];

            if (!empty($input['documento_id'])) {
                $documento_id = $input['documento_id'];

                $documento = $this->apilib->view('documenti_contabilita', $documento_id);
                $this->apilib->edit("documenti_contabilita", $input['documento_id'], $documents); // Come mai è stato commentato e ora si usa update su db diretto? non vanno cosi i post process
            } else {
                $documents['documenti_contabilita_stato_invio_sdi'] = 1;
                $documents['documenti_contabilita_stato'] = 1;
                //debug($documents, true);
                $documento = $this->apilib->create('documenti_contabilita', $documents);
                $documento_id = $documento['documenti_contabilita_id'];
            }

            // Genero nome file xml e salvo sul db
            if ($documents['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE && empty($documento['documenti_contabilita_nome_file_xml'])) {
                $settings = $this->contabilita_settings;
                $prefisso = "IT" . $settings['documenti_contabilita_settings_company_vat_number'];
                $xmlfilename = $this->docs->generateXmlFilename($prefisso, $documento_id);
                $documents['documenti_contabilita_nome_file_xml'] = $xmlfilename;
            }

            //Imposto lo stato pagamento a non pagato per poi modificarlo in caso nel foreach scadenze

            $scadenze_ids = [-1];

            foreach ($input['scadenze'] as $key => $scadenza) {
                if ($scadenza['documenti_contabilita_scadenze_ammontare'] > 0) {
                    if (!empty($scadenza['documenti_contabilita_scadenze_scadenza'])) {
                        if ($this->db->dbdriver != 'postgre') {
                            $date = DateTime::createFromFormat("d/m/Y", $scadenza['documenti_contabilita_scadenze_scadenza']);
                            $scadenza['documenti_contabilita_scadenze_scadenza'] = $date->format('Y-m-d H:i:s');
                        } else {
                            //$data_emissione = $input['documenti_contabilita_data_emissione'];
                            $date = DateTime::createFromFormat("d/m/Y", $scadenza['documenti_contabilita_scadenze_scadenza']);
                            $scadenza['documenti_contabilita_scadenze_scadenza'] = $date->format('Y-m-d H:i:s');
                        }
                    }

                    if (!empty($scadenza['documenti_contabilita_scadenze_id'])) {
                        $scadenze_ids[] = $scadenza['documenti_contabilita_scadenze_id'];
                        $this->apilib->edit('documenti_contabilita_scadenze', $scadenza['documenti_contabilita_scadenze_id'], [
                            'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                            'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                            'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'],
                            'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                            'documenti_contabilita_scadenze_documento' => $documento_id,
                        ]);
                    } else {
                        $scadenze_ids[] = $this->apilib->create('documenti_contabilita_scadenze', [
                            'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                            'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                            'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'],
                            'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                            'documenti_contabilita_scadenze_documento' => $documento_id,
                        ], false);
                    }
                } else {
                    unset($input['scadenze'][$key]);
                }
            }

            $this->db->query("DELETE FROM documenti_contabilita_scadenze where documenti_contabilita_scadenze_documento = $documento_id AND documenti_contabilita_scadenze_id NOT IN (" . implode(',', $scadenze_ids) . ")");
            $this->mycache->clearCacheTags(['documenti_contabilita_scadenze', 'documenti_contabilita']);

            // **************** PRODOTTI ****************** //
            if (!empty($input['documento_id'])) {
                //Rimosso perchè ora fa update se siamo in edit anche di ogni riga articolo come per le scadenze
                //$this->db->delete('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $input['documento_id']]);
            }

            $raw_iva = $this->db->get('iva')->result_array();
            $iva = array_combine(
                array_map(function ($_iva) {
                    return $_iva['iva_id'];
                }, $raw_iva),
                array_map(function ($_iva) {
                    return $_iva['iva_valore'];
                }, $raw_iva)
            );
            //debug($iva,true);
            $articoli_ids = ['-1'];
            $campi_personalizzati = $this->apilib->search('campi_righe_articoli');
            foreach ($input['products'] as $prodotto) {
                foreach ($campi_personalizzati as $campo) {
                    $prodotto[$campo['campi_righe_articoli_map_to']] = $prodotto[$campo['campi_righe_articoli_campo']];
                    unset($prodotto[$campo['campi_righe_articoli_campo']]);
                }

                //unset($prodotto['documenti_contabilita_articoli_id']);
                $prodotto['documenti_contabilita_articoli_documento'] = $documento_id;
                //Mi arriva l'id dell'iva, quindi recupero il valore
                //                debug($iva);
                //                debug($prodotto);
                $prodotto['documenti_contabilita_articoli_iva_perc'] = $iva[$prodotto['documenti_contabilita_articoli_iva_id']];

                if (!empty($prodotto['documenti_contabilita_articoli_id'])) {
                    $articoli_ids[] = $prodotto['documenti_contabilita_articoli_id'];
                    $this->apilib->edit('documenti_contabilita_articoli', $prodotto['documenti_contabilita_articoli_id'], $prodotto);
                } else {
                    $articoli_ids[] = $this->apilib->create("documenti_contabilita_articoli", $prodotto, false);
                }
                //Se il documento è stato generato da un'ordine cliente (o più ordini cliente), marco quella riga come "fattura_evasa"...
                //TODO: CAMBAIRE CON 1!
                if ($documents['documenti_contabilita_tipo'] == 1 && $prodotto['documenti_contabilita_articoli_rif_riga_articolo']) {
                    $this->db->where('documenti_contabilita_articoli_id', $prodotto['documenti_contabilita_articoli_rif_riga_articolo'])->update('documenti_contabilita_articoli', [
                        'documenti_contabilita_articoli_evaso_in_fattura' => DB_BOOL_TRUE,
                    ]);
                }
            }
            //debug($articoli_ids, true);
            $this->db->query("DELETE FROM documenti_contabilita_articoli where documenti_contabilita_articoli_documento = $documento_id AND documenti_contabilita_articoli_id NOT IN (" . implode(',', $articoli_ids) . ")");
            $this->mycache->clearCacheTags(['documenti_contabilita_articoli', 'documenti_contabilita']);

            if ($documents['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $this->docs->generate_xml($documento);
            //die('test');
            } else {
                // Storicizzo PDF
                if ($documents['documenti_contabilita_template_pdf']) {
                    $template = $this->apilib->view('documenti_contabilita_template_pdf', $documents['documenti_contabilita_template_pdf']);

                    // Se caricato un file che contiene un html da priorità a quello
                    if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                        $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                    } else {
                        $content_html = $template['documenti_contabilita_template_pdf_html'];
                    }

                    $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
                } else {
                    $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
                    //debug($input,true);
                }

                if (file_exists($pdfFile)) {
                    $contents = file_get_contents($pdfFile, true);
                    $pdf_b64 = base64_encode($contents);
                    $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file' => $pdf_b64]);
                }
            }

            $this->apilib->clearCache();

            if (empty($input['documento_id']) && !empty($spesa_id)) {
                $spesa = $this->apilib->view('spese', $spesa_id);
                if ($spesa['spese_modello_prima_nota']) {
                    //Se entro qua vuol dire che ho assegnato un modello... chiedo se si vuole andare in prima nota o tornare all'elenco spese
                    echo json_encode(array('status' => 9, 'txt' => "

                    if (confirm('Vuoi procedere anche con la registrazione in prima nota?') == true) {
                        location.href='" . base_url("main/layout/prima-nota?modello={$spesa['spese_modello_prima_nota']}&spesa_id={$spesa_id}") . "';
                    } else {
                        location.href='" . base_url('main/layout/contabilita_dettaglio_documento/' . $documento_id . '?first_save=1') . "';
                    }
                "));
                } else {
                    echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/contabilita_dettaglio_documento/' . $documento_id . '?first_save=1')));
                }
            } else {
                echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/contabilita_dettaglio_documento/' . $documento_id . '?first_save=1')));
            }
        }
    }

    public function edit_scadenze()
    {
        $input = $this->input->post();
        $documento_id = $input['documento_id'];

        //$this->db->delete('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
        $scadenze_ids = [-1];
        foreach ($input['scadenze'] as $key => $scadenza) {
            if ($scadenza['documenti_contabilita_scadenze_ammontare'] > 0) {
                if (!empty($scadenza['documenti_contabilita_scadenze_id'])) {
                    $scadenze_ids[] = $scadenza['documenti_contabilita_scadenze_id'];
                    $this->apilib->edit('documenti_contabilita_scadenze', $scadenza['documenti_contabilita_scadenze_id'], [
                        'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                        'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                        'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'],
                        'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                        'documenti_contabilita_scadenze_documento' => $documento_id,
                    ]);
                } else {
                    $scadenze_ids[] = $this->apilib->create('documenti_contabilita_scadenze', [
                        'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                        'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                        'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'],
                        'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                        'documenti_contabilita_scadenze_documento' => $documento_id,
                    ], false);
                }
            } else {
                unset($input['scadenze'][$key]);
            }
        }

        $this->db->query("DELETE FROM documenti_contabilita_scadenze where documenti_contabilita_scadenze_documento = $documento_id AND documenti_contabilita_scadenze_id NOT IN (" . implode(',', $scadenze_ids) . ")");
        $this->mycache->clearCacheTags(['documenti_contabilita_scadenze']);

        echo json_encode(array('status' => 2));
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

        $res = "";

        //L'entità clienti è configurale, come anche i vari campi di preview...
        $mappature = $this->docs->getMappatureAutocomplete();
        extract($mappature);

        $entita_fornitori = 'suppliers';

        if ($entity == $entita_prodotti) {
            $campo_codice = $campo_codice_prodotto;
            $campo_preview = $campo_preview_prodotto;
            $where = ["(LOWER($campo_preview) LIKE '%{$input}%' OR $campo_preview LIKE '{$input}%') OR (LOWER($campo_codice) LIKE '%{$input}%' OR $campo_codice LIKE '{$input}%')"];

            if (!empty($campo_gestione_giacenza_prodotto)) {
                $where[] = "$campo_gestione_giacenza_prodotto => '1'";
            }

            $res = $this->apilib->search($entity, $where, 20);
        //die("(LOWER(fw_products_name) LIKE '%{$input}%' OR fw_products_sku LIKE '{$input}%' OR CAST(fw_products_ean AS CHAR) = '{$input}')");
        } elseif ($entity == $entita_clienti) {
            if ($clienti_tipo) {
                $where = ["(LOWER({$clienti_codice}) LIKE '%{$input}%' OR LOWER({$clienti_ragione_sociale}) LIKE '%{$input}%' OR LOWER({$clienti_nome}) LIKE '%{$input}%' OR LOWER({$clienti_cognome}) LIKE '%{$input}%') AND ({$clienti_tipo} IN (1,3,4))"];
                //debug($where, true);
                $res = $this->apilib->search($entita_clienti, $where, 10, 0, "{$clienti_codice},{$clienti_ragione_sociale},{$clienti_cognome},{$clienti_nome}");
            } else {
                $res = $this->apilib->search($entita_clienti, ["(LOWER({$clienti_codice}) LIKE '%{$input}%' OR LOWER({$clienti_ragione_sociale}) LIKE '%{$input}%' OR LOWER({$clienti_nome}) LIKE '%{$input}%' OR LOWER({$clienti_cognome}) LIKE '%{$input}%')"], 10, 0, "{$clienti_codice},{$clienti_ragione_sociale},{$clienti_cognome},{$clienti_nome}");
            }
        } elseif ($entity == $entita_fornitori) {
            $res = $this->apilib->search($entita_clienti, ["(LOWER({$clienti_codice}) LIKE '%{$input}%' OR LOWER({$clienti_ragione_sociale}) LIKE '%{$input}%' OR LOWER({$clienti_nome}) LIKE '%{$input}%' OR LOWER({$clienti_cognome}) LIKE '%{$input}%') AND ({$clienti_tipo} = '2' OR  {$clienti_tipo} = '3' )"], 10, 0, "{$clienti_codice},{$clienti_ragione_sociale},{$clienti_cognome},{$clienti_nome}");
        } elseif ($entity == $entita_vettori) {
            $res = $this->apilib->search($entita_vettori, ["(LOWER(vettori_ragione_sociale) LIKE '%{$input}%') ORDER BY vettori_ragione_sociale ASC"]);
        }

        if ($res) {
            $count_total = count($res);
            $results = [
                'data' => $res,
            ];
        }

        echo json_encode(['count_total' => $count_total, 'results' => $results]);
    }
    public function getTemplatePdf($azienda)
    {
        echo json_encode($this->apilib->search('documenti_contabilita_template_pdf', ['documenti_contabilita_template_pdf_azienda' => $azienda]));
    }
    public function numeroSucessivo($azienda, $tipo, $serie = null)
    {
        $data = $this->input->post('data_emissione');
        echo $this->docs->numero_sucessivo($azienda, $tipo, $serie, $data);
    }

    public function uploadImage($prodotto)
    {
        if (!isset($_FILES['prodotti_immagini_immagine']) && isset($_FILES['file'])) {
            $_FILES['prodotti_immagini_immagine'] = $_FILES['file'];
        }

        if (!isset($_FILES['prodotti_immagini_immagine']['type']) or !in_array($_FILES['prodotti_immagini_immagine']['type'], ['image/jpeg', 'image/png'])) {
            die('Tipo file non supportato');
        }

        unset($_FILES['file']);

        try {
            $newMedia = $this->apilib->create('prodotti_immagini', ['prodotti_immagini_prodotto' => $prodotto]);
        } catch (Exception $ex) {
            set_status_header(500);
            die($ex->getMessage());
        }

        echo json_encode($newMedia);
    }

    public function ajax_get_templates($template_id, $documento_id = null)
    {
        $result = $this->apilib->view('documenti_contabilita_mail_template', $template_id);

        if (!empty($documento_id)) {
            $documento = $this->apilib->view('documenti_contabilita', $documento_id);

            if ($documento['documenti_contabilita_customer_id']) {
                $mappature = $this->docs->getMappature();
                extract($mappature);

                $destinatario_email = '';
                $destinatario = $this->apilib->view($entita_clienti, $documento['documenti_contabilita_customer_id']);

                if ($destinatario[$clienti_email]) {
                    $destinatario_email = $destinatario[$clienti_email];
                }

                // michael - 07/07/2022 - commentato perchè è un po' problematico per la fatturazione
                // if ($this->module->moduleExists('customers') && $entita_clienti === 'customers' && $this->db->table_exists('customers_contacts') && $this->db->field_exists('customers_contacts_refer_to', 'customers_contacts')) {
                //     $contatto = $this->apilib->searchFirst('customers_contacts', ['customers_contacts_customer_id' => $documento['documenti_contabilita_customer_id'], 'customers_contacts_refer_to' => '1']);

                //     if (!empty($contatto) && !empty($contatto['customers_contacts_email'])) {
                //         $destinatario_email = $contatto['customers_contacts_email'];
                //     }
                // }

                if ($destinatario_email) {
                    $result['email_destinatario'] = $destinatario_email;
                }
            }
        }

        echo json_encode($result);
    }

    public function tassoDiCambio($valuta_id)
    {
        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
        $tasso = $this->apilib->searchFirst('tassi_di_cambio', [
            'tassi_di_cambio_valuta_2' => $valuta_id,
            'tassi_di_cambio_valuta_1' => $settings['documenti_contabilita_settings_valuta_base'],
        ], 0, 'tassi_di_cambio_creation_date', 'DESC');

        if (empty($tasso)) {
            echo json_encode([]);
        } else {
            echo json_encode($tasso);
        }
    }

    public function print_pdf($documento_id, $field_name = 'documenti_contabilita_file')
    {
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
            $field_name = 'documenti_contabilita_file_preview';
        }
        //TODO: non deve generare documento_pdf ma valutare se il documento ha un template associato e usare quello (occhio al controllo tra html e file di template caricato)
        if (!empty($documento_id)) {
            if ($this->input->get('regenerate')) {
                $pdfFile = $this->layout->generate_pdf(($this->input->get('view')) ?: "documento_pdf", "portrait", "", [
                    'documento_id' => $documento_id,
                ], 'contabilita');

                if (file_exists($pdfFile)) {
                    $contents = file_get_contents($pdfFile, true);
                    $pdf_b64 = base64_encode($contents);
                    $this->apilib->edit("documenti_contabilita", $documento_id, [$field_name => $pdf_b64]);
                }

                $documento = $this->apilib->view('documenti_contabilita', $documento_id);
            }
            //die(base64_decode($documento[$field_name]));
            if ($this->input->get('html')) {
                die($contents);
            }
            header('Content-Type: application/pdf');
            header('Content-disposition: inline; filename="' . $documento['documenti_contabilita_tipo_value'] . '_' . $documento['documenti_contabilita_numero'] . $documento['documenti_contabilita_serie'] . '.pdf"');

            echo base64_decode($documento[$field_name]);
        } else {
            echo "Errore, documento non esistente";
        }
    }

    public function xml_fattura_elettronica($id, $reverse = false)
    {
        //Ho dovuto centralizzare questo metodo perchè utilizzato anche da altre parti...
        $pagina = $this->docs->get_content_fattura_elettronica($id, $reverse);
        header("Content-Type:text/xml");
        echo $pagina;
    }
    public function visualizza_formato_compatto($id)
    {
        $reverse = in_array($this->apilib->view('documenti_contabilita', $id)['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19']);

        $pagina = $this->docs->get_content_fattura_elettronica($id, $reverse);
        $pagina = str_ireplace('<?xml version="1.0" encoding="UTF-8" ?>', '', $pagina);
        header("Content-Type:text/xml");
        $this->load->view('contabilita/pdf/visualizzazione_compatta', ['xml' => $pagina]);
    }
    public function visualizza_formato_completo($id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $id);
        $reverse = in_array($documento['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19']);
        $this->load->helper('download');
        $general_settings = $this->apilib->searchFirst('documenti_contabilita_general_settings');
        $xsl_url_ordinaria = $general_settings['documenti_contabilita_general_settings_xsl_fattura_ordinaria'];
        $xsl_url_pa = $general_settings['documenti_contabilita_general_settings_xsl_fattura_pa'];

        $physicalDir = FCPATH . "application/modules/contabilita/assets/uploads/";

        // Verifico se è per soggetti privati (e aziende) oppure pubblica amministrazione perche hanno due xslt diversi
        if ($documento['documenti_contabilita_tipo_destinatario'] == 3) {
            $xsl_url = $xsl_url_pa;
            $file_xsl = 'fattura_completa_pa.xsl';
            $tmpXsl = $physicalDir . $file_xsl;
        } else {
            $xsl_url = $xsl_url_ordinaria;
            $file_xsl = 'fattura_completa_ordinaria.xsl';
            $tmpXsl = $physicalDir . $file_xsl;
        }

        // Se non ce foglio xsl salvarlo.. Per modificare il file caricarlo sui general settings.
        if (!file_exists($tmpXsl)) {
            log_message('debug', "XSL Foglio di stile fattura elettronica non trovato, lo scarico e lo salvo.");
            file_put_contents($tmpXsl, file_get_contents($xsl_url));
        } else {
            log_message('debug', "XSL Foglio di stile fattura elettronica trovato correttamente: " . $tmpXsl);
        }

        $pagina = $this->docs->get_content_fattura_elettronica($id, $reverse);
        $pagina = str_ireplace('<?xml version="1.0" encoding="UTF-8" ?>', '', $pagina);
        header("Content-Type:text/xml");
        $this->load->view('contabilita/pdf/visualizzazione_completa', ['xml' => $pagina, 'file_xsl' => $file_xsl]);
    }

    //20220316 - MP - Deprecato a favore del nuovo metodo con generazione a runtime da xsl
    public function _____visualizza_fattura_elettronica($id)
    {
        die('DEPRECATO');

        $this->load->helper('download');

        $dati['fattura'] = $this->apilib->view('documenti_contabilita', $id);

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
        $general_settings = $this->apilib->searchFirst('documenti_contabilita_general_settings');
        $xsl_url_ordinaria = $general_settings['documenti_contabilita_general_settings_xsl_fattura_ordinaria'];
        $xsl_url_pa = $general_settings['documenti_contabilita_general_settings_xsl_fattura_pa'];

        $filename = date('Ymd-H-i-s');
        $physicalDir = FCPATH . "uploads/";

        // Create a temporary file with the view html
        $tmpHtml = "{$physicalDir}/{$filename}.html";
        $tmpXml = "{$physicalDir}/{$filename}.xml";

        // Verifico se è per soggetti privati (e aziende) oppure pubblica amministrazione perche hanno due xslt diversi
        if ($dati['fattura']['documenti_contabilita_tipo_destinatario'] == 3) {
            $xsl_url = $xsl_url_pa;
            $tmpXsl = $physicalDir . basename($xsl_url_pa);
        } else {
            $xsl_url = $xsl_url_ordinaria;
            $tmpXsl = $physicalDir . basename($xsl_url_ordinaria);
        }

        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }

        // Se non ce foglio xsl salvarlo.. Per modificare il file caricarlo sui general settings.
        if (!file_exists($tmpXsl)) {
            log_message('debug', "XSL Foglio di stile fattura elettronica non trovato, lo scarico e lo salvo.");
            file_put_contents($tmpXsl, file_get_contents($xsl_url));
        } else {
            log_message('debug', "XSL Foglio di stile fattura elettronica trovato correttamente: " . $tmpXsl);
        }
        // Scarico sempre il file di stile
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        //file_put_contents($tmpXsl, file_get_contents("https://www.fatturapa.gov.it/export/documenti/fatturapa/v1.2/fatturaordinaria_v1.2.xsl", false, stream_context_create($arrContextOptions)));

        // Creo xml temporaneo
        file_put_contents($tmpXml, base64_decode($dati['fattura']['documenti_contabilita_file']), LOCK_EX);

        // Tento di generarlo
        //echo "xsltproc -o {$tmpHtml} {$tmpXsl} {$tmpXml}"; // Per debug comando decommentare
        exec("xsltproc -o {$tmpHtml} {$tmpXsl} {$tmpXml}");

        // Check se html generato correttamente lo mostro altrimenti carico xml perchè potrebbe avere dei die con gli errori
        if (file_exists($tmpHtml)) {
            // Storicizzo la preview
            $pdfFile = $this->layout->generate_pdf(file_get_contents($tmpHtml), "portrait", "", [], null, true);
            $this->apilib->edit("documenti_contabilita", $id, ['documenti_contabilita_file_preview_xml' => base64_encode(file_get_contents($pdfFile))]);

            header('Content-Type: application/pdf');
            echo file_get_contents($pdfFile);
        } else {
            echo file_get_contents($tmpXml);
        }
    }

    public function download_fattura_elettronica($id)
    {
        $this->load->helper('download');

        $dati['fattura'] = $this->apilib->view('documenti_contabilita', $id);
        $settings = $this->contabilita_settings;

        $file = base64_decode($dati['fattura']['documenti_contabilita_file']);

        $prefisso = "IT" . $settings['documenti_contabilita_settings_company_vat_number'];

        if (empty($dati['fattura']['documenti_contabilita_nome_file_xml'])) {
            $xmlfilename = $this->docs->generateXmlFilename($prefisso, $dati['fattura']['documenti_contabilita_id']);
        } else {
            $xmlfilename = $dati['fattura']['documenti_contabilita_nome_file_xml'];
        }
        //debug($dati['fattura']['documenti_contabilita_nome_file_xml'],true);
        force_download($xmlfilename, $file);
    }

    public function generaRiba()
    {
        $ids = json_decode($this->input->post('ids'));

        $documenti = $this->apilib->search('documenti_contabilita_scadenze', "documenti_contabilita_scadenze_id IN (" . implode(',', $ids) . ")");

        //debug($documenti, true);

        $this->load->model('contabilita/ribaabicbi');
        $file_content = $this->ribaabicbi->creaFileFromDocumenti($this->apilib->searchFirst('documenti_contabilita_settings'), $documenti);

        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename=riba.dat");
        echo $file_content;
        die();
    }

    public function generaSdd()
    {
        $ids = json_decode($this->input->post('ids'));
        $conto_id = $this->input->post('conto_sdd');
        $conto = $this->apilib->view('conti_correnti', $conto_id);
        //debug($conto, true);
        $documenti = $this->apilib->search('documenti_contabilita_scadenze', "documenti_contabilita_scadenze_id IN (" . implode(',', $ids) . ")");

        foreach ($documenti as $index => $documento) {
            $documenti[$index]['cliente'] = $this->apilib->searchFirst('customers_bank_accounts', ['customers_bank_accounts_customer_id' => $documento['documenti_contabilita_customer_id']]);
        }

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings', [], 0, 'documenti_contabilita_settings_creation_date', 'ASC');

        // dump($settings);
        // dump($documenti);

        $sdd = [];

        $tot_docs = array_reduce($documenti, function ($sum, $doc) {
            if (empty($doc['documenti_contabilita_scadenze_ammontare'])) {
                $doc['documenti_contabilita_scadenze_ammontare'] = 0;
            }

            return $sum + $doc['documenti_contabilita_scadenze_ammontare'];
        }, 0);

        $sdd['total'] = number_format($tot_docs, 2, '.', '');
        $sdd['sdd_id'] = 'SDDXml-' . date('dmY-Hi');
        $sdd['creation_datetime'] = (new DateTime())->format(DateTime::ATOM);
        $sdd['number_of_transactions'] = (int) count($ids);
        $sdd['azienda'] = $settings;
        $sdd['documenti'] = $documenti;

        $sdd['conto'] = $conto;

        // dd($sdd);

        $file_content = $this->load->view('contabilita/xml_sdd', $sdd, true);

        header("Content-type: text/xml");
        header("Content-Disposition: attachment; filename=sdd.xml");

        die($file_content);
    }

    public function downloadZip()
    {
        $ids = json_decode($this->input->post('ids'));

        //debug($ids);

        $fatture = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_id IN (' . implode(',', $ids) . ')']);

        //debug($fatture,true);
        $this->load->helper('download');
        $this->load->library('zip');
        $dest_folder = FCPATH . "uploads";

        $destination_file = "{$dest_folder}/fatture.zip";

        //die('test');
        //Ci aggiungo il json e la versione, poi rizippo il pacchetto...
        $zip = new ZipArchive();

        if ($zip->open($destination_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            exit("cannot open <$destination_file>\n");
        }

        foreach ($fatture as $fattura) {
            //debug($fattura,true);
            $xml_content = base64_decode($fattura['documenti_contabilita_file']);
            $pdf_content = base64_decode($fattura['documenti_contabilita_file_preview']);

            // Todo andrebbe usato il metodo generaxml che salva il nome nuovo del file
            if (!empty($fattura['documenti_contabilita_nome_file_xml'])) {
                $zip->addFromString("xml/{$fattura['documenti_contabilita_nome_file_xml']}", $xml_content);
            } else {
                $zip->addFromString("xml/{$fattura['documenti_contabilita_numero']}{$fattura['documenti_contabilita_serie']}.xml", $xml_content);
            }
            $zip->addFromString("pdf/{$fattura['documenti_contabilita_numero']}{$fattura['documenti_contabilita_serie']}.pdf", $pdf_content);
        }

        $zip->close();

        force_download('fatture.zip', file_get_contents($destination_file));
    }

    public function print_all()
    {
        if (!command_exists('pdfunite')) {
            //throw new ApiException('Errore generico durante la generazione del pdf.');
            //echo json_encode(['status' => 0, 'txt' => 'Errore generico durante la generazione del pdf.']);
            echo "<alert>Errore generico durante la generazione del pdf (pdfunite non installato).</alert>";
            exit;
        }

        $ids = json_decode($this->input->post('ids'));

        $documenti = $this->apilib->search('documenti_contabilita', ["documenti_contabilita_id IN (" . implode(',', $ids) . ")"]);

        $files = [];
        foreach ($documenti as $key => $documento) {
            if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $field_name = 'documenti_contabilita_file_preview';
            } else {
                $field_name = 'documenti_contabilita_file';
            }
            //var_dump(base64_decode($documento[$field_name]));
            file_put_contents(FCPATH . $key . '.pdf', base64_decode($documento[$field_name]));
            $files[] = FCPATH . $key . '.pdf';
        }
        $output = '';
        //echo "pdfunite ".implode(' ', $files)." ".FCPATH."merge.pdf";
        exec("pdfunite " . implode(' ', $files) . " " . FCPATH . "documenti.pdf", $output);

        foreach ($documenti as $key => $documento) {
            unlink(FCPATH . $key . '.pdf');
        }
        $fp = fopen(FCPATH . "documenti.pdf", 'rb');
        header("Content-Type: application/force-download");
        header("Content-Length: " . filesize(FCPATH . "documenti.pdf"));
        header("Content-Disposition: attachment; filename=documenti.pdf");
        fpassthru($fp);
        unlink(FCPATH . "documenti.pdf");
        exit;
    }

    public function genera_fatture_distinte()
    {
        $ddt_ids = json_decode($this->input->post('ddt_ids'), true);
        //debug($ddt_ids, true);
        foreach ($ddt_ids as $ddt_id) {
            $documento_old = $this->db->where('documenti_contabilita_id', $ddt_id)->get('documenti_contabilita')->row_array();
            $documento_new = $documento_old;

            //debug($documento_new);

            unset($documento_new['documenti_contabilita_id']);

            //Cambio il tipo documento
            $documento_new['documenti_contabilita_tipo'] = 1;
            //Calcolo il nuovo numero
            $numero_sucessivo = $this->docs->numero_sucessivo($documento_new['documenti_contabilita_azienda'], $documento_new['documenti_contabilita_tipo'], $documento_new['documenti_contabilita_serie'], date("d/m/Y"));
            $documento_new['documenti_contabilita_numero'] = $numero_sucessivo;
            //Associo il documento al ddt
            $documento_new['documenti_contabilita_rif_documento_id'] = $ddt_id;

            //Cambio la data emissione
            $documento_new['documenti_contabilita_data_emissione'] = date("Y-m-d H:i:s");

            //Creo il nuovo documento
            //$new_documento_id = $this->apilib->create('documenti_contabilita', $documento_new, false);
            $this->db->insert('documenti_contabilita', $documento_new);
            $new_documento_id = $this->db->insert_id();

            //Copio gli articoli nel nuovo documento
            $articoli = $this->db->where('documenti_contabilita_articoli_documento', $ddt_id)->get('documenti_contabilita_articoli')->result_array();
            foreach ($articoli as $articolo) {
                unset($articolo['documenti_contabilita_articoli_id']);
                $articolo['documenti_contabilita_articoli_documento'] = $new_documento_id;

                $this->db->insert('documenti_contabilita_articoli', $articolo);
            }

            //Creo una scadenza
            $this->db->insert('documenti_contabilita_scadenze', [
                'documenti_contabilita_scadenze_documento' => $new_documento_id,
                'documenti_contabilita_scadenze_ammontare' => $documento_old['documenti_contabilita_totale'],
                'documenti_contabilita_scadenze_scadenza' => date("Y-m-d"),
            ]);

            //Genero il pdf
            if ($documento_new['documenti_contabilita_template_pdf']) {
                $template = $this->apilib->view('documenti_contabilita_template_pdf', $documento_new['documenti_contabilita_template_pdf']);

                // Se caricato un file che contiene un html da priorità a quello
                if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                    $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                } else {
                    $content_html = $template['documenti_contabilita_template_pdf_html'];
                }

                $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $new_documento_id], 'contabilita', true);
            } else {
                $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $new_documento_id], 'contabilita');
            }

            if (file_exists($pdfFile)) {
                $contents = file_get_contents($pdfFile, true);
                $pdf_b64 = base64_encode($contents);
                $this->apilib->edit("documenti_contabilita", $new_documento_id, ['documenti_contabilita_file' => $pdf_b64]);
            }
        }

        redirect('main/layout/elenco_documenti');
    }

    public function genera_ordini_fornitori()
    {
        $mappature = $this->docs->getMappature();
        extract($mappature);

        if (!$this->input->post('ddt_ids')) {
            $righe_articoli_ids = json_decode($this->input->post('righe_articoli_ids'), true);

            $articoli = $this->db
                ->where_in('documenti_contabilita_articoli_id', $righe_articoli_ids)
                ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
                ->join('customers', "fw_products_supplier = customers_id", 'LEFT')
                ->get('documenti_contabilita_articoli')
                ->result_array();

        } else {
            $ddt_ids = json_decode($this->input->post('ddt_ids'), true);

            $articoli = $this->db
                ->where_in('documenti_contabilita_articoli_documento', $ddt_ids)
                ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
                ->join('customers', "fw_products_supplier = customers_id", 'LEFT')
                ->get('documenti_contabilita_articoli')
                ->result_array();

        }

        $ordini_fornitori = [];

        foreach ($articoli as $articolo) {
            if ($articolo['fw_products_supplier']) {
                if (!array_key_exists($articolo['fw_products_supplier'], $ordini_fornitori)) {
                    $ordini_fornitori[$articolo['fw_products_supplier']] = [];
                }
                $ordini_fornitori[$articolo['fw_products_supplier']][] = $articolo;
            } else {
                //debug($articolo, true);
                die("Il prodotto '{$articolo['documenti_contabilita_articoli_name']}' (id: {$articolo[$campo_id_prodotto]}) non ha fornitore associato!");
            }
        }

        foreach ($ordini_fornitori as $supplier_id => $articoli) {
            //debug($articoli);
            $articoli_qty = [];
            foreach ($articoli as $articolo) {
                if (empty($articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']])) {
                    $articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']] = 0;
                }
                $articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']] += $articolo['documenti_contabilita_articoli_quantita'];
            }
            //debug($articoli_qty, true);
            //Creo ordine per questo fornitore
            $this->docs->doc_express_save([
                'tipo_documento' => 6,
                'tipo_destinatario' => 1,
                'fornitore_id' => $supplier_id,

                'articoli' => $articoli_qty,
            ]);
        }
        if ($this->input->post('ddt_ids')) {
//A questo punto marco "in attesa" gli ordini cliente
            foreach ($ddt_ids as $id) {
                $this->apilib->edit('documenti_contabilita', $id, ['documenti_contabilita_stato' => 5]);
            }

        }

        redirect('main/layout/elenco_documenti');
    }

    public function download_prima_nota()
    {
        require_once APPPATH . 'third_party/PHPExcel.php';

        $filtro_fatture = @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];

        $where_documenti = ['documenti_contabilita_tipo IN (1,4,11,12)'];
        $where_spese = ["1=1"];

        if (!empty($filtro_fatture)) {
            foreach ($filtro_fatture as $field => $filtro) {
                $value = $filtro['value'];
                switch ($field) {
                    case '778': //Data emissione
                        $data_expl = explode(' - ', $value);
                        $data_da = $data_expl[0];
                        $data_a = $data_expl[1];
                        $where_documenti[] = "documenti_contabilita_data_emissione <= '$data_a' AND documenti_contabilita_data_emissione >= '$data_da'";
                        $where_spese[] = "spese_data_emissione <= '$data_a' AND spese_data_emissione >= '$data_da'";
                        break;
                    default:
                        debug("Campo filtro non gestito (custom view iva).");
                        debug($filtro);
                        break;
                }
            }
        }

        $where_documenti_str = implode(' AND ', $where_documenti);
        $where_spese_str = implode(' AND ', $where_spese);

        //die($where_documenti_str);

        $fatture = $this->db->query("SELECT * FROM documenti_contabilita LEFT JOIN documenti_contabilita_tipo ON (documenti_contabilita_tipo = documenti_contabilita_tipo_id) LEFT JOIN conti_correnti ON (conti_correnti_id = documenti_contabilita_conto_corrente) WHERE $where_documenti_str")->result_array();
        $spese = $this->db->query("SELECT * FROM spese WHERE $where_spese_str ")->result_array();

        $out = [];
        $saldo_progressivo = 0;
        foreach ($fatture as $fattura) {
            $out[] = [
                'Data' => $fattura['documenti_contabilita_data_emissione'],
                'Conto' => $fattura['conti_correnti_nome_istituto'],
                'Descrizione' => "{$fattura['documenti_contabilita_tipo_value']} n. {$fattura['documenti_contabilita_numero']}" . (($fattura['documenti_contabilita_serie']) ? "/{$fattura['documenti_contabilita_serie']}" : ''),
                'Cliente/fornitore' => json_decode($fattura['documenti_contabilita_destinatario'], true)['ragione_sociale'],
                'Entrate' => ($fattura['documenti_contabilita_tipo'] == 1) ? $fattura['documenti_contabilita_totale'] : 0,
                'Uscite' => ($fattura['documenti_contabilita_tipo'] == 4) ? $fattura['documenti_contabilita_totale'] : 0,
                //'Saldo progressivo' => $saldo_progressivo
            ];
        }
        foreach ($spese as $spesa) {
            //debug($spesa,true);

            $out[] = [
                'Data' => $spesa['spese_data_emissione'],
                'Conto' => '',
                'Descrizione' => "{$spesa['spese_numero']}",
                'Cliente/fornitore' => json_decode($spesa['spese_fornitore'], true)['ragione_sociale'],
                'Entrate' => 0,
                'Uscite' => $spesa['spese_totale'],
                //'Saldo progressivo' => $saldo_progressivo
            ];
        }

        usort($out, function ($a, $b) {
            if ($a['Data'] == $b['Data']) {
                return 0;
            }
            return ($a['Data'] < $b['Data']) ? -1 : 1;
        });

        setlocale(LC_MONETARY, 'it_IT');

        foreach ($out as $key => $dato) {
            if ($dato['Uscite']) {
                $out[$key]['Uscite'] = $dato['Uscite'];
                $saldo_progressivo -= $dato['Uscite'];
            } else {
                $out[$key]['Entrate'] = $dato['Entrate'];
                $saldo_progressivo += $dato['Entrate'];
            }

            $out[$key]['Data'] = date('d-m-Y', strtotime($dato['Data']));
            $out[$key]['Saldo progressivo'] = number_format($saldo_progressivo, 2, ',', '');
            $out[$key]['Uscite'] = number_format($dato['Uscite'], 2, ',', '');
            $out[$key]['Entrate'] = number_format($dato['Entrate'], 2, ',', '');
        }

        //debug($out);

        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getActiveSheet()->fromArray(array_keys($out[0]), '', 'A1');

        $objPHPExcel->getActiveSheet()->fromArray($out, '', 'A2');

        //        $objPHPExcel->getActiveSheet()
        //            ->getStyle('G2')
        //            ->getNumberFormat()
        //            ->setFormatCode(
        //                PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
        //            );
        //        $objPHPExcel->getActiveSheet()
        //            ->getStyle('E2')
        //            ->getNumberFormat()
        //            ->setFormatCode(
        //                PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
        //            );

        // Stile
        /*$objPHPExcel->getActiveSheet()->getStyle('E2')->applyFromArray(
        array(
        'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => '00ff00')
        )
        )
        );
        $objPHPExcel->getActiveSheet()->getStyle('F2')->applyFromArray(
        array(
        'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => 'FF0000')
        )
        )
        );*/
        // Setto le larghezze
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"prima_nota.xlsx\"");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->setPreCalculateFormulas(true);

        $objWriter->save('php://output');
    }

    public function regeneratePdf($documento_id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        if ($this->input->get('file_tpl')) {
            $pdfFile = $this->layout->generate_pdf($this->input->get('file_tpl'), "portrait", "", ['documento_id' => $documento_id], 'contabilita');
        //debug($pdfFile, true);
        } else {
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
                $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
            }
        }

        if (file_exists($pdfFile)) {
            $contents = file_get_contents($pdfFile, true);
            $pdf_b64 = base64_encode($contents);
            $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file' => $pdf_b64]);
        }
    }

    public function regenerateXml($documento_id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        $this->docs->generate_xml($documento);
    }

    public function genera_cmr($documento_id = null)
    {
        if (empty($documento_id)) {
            die('Documento non esistente');
        }

        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        unset($documento['documenti_contabilita_template_pdf_html']);

        $pdfFile = $this->layout->generate_pdf("documento_cmr", "portrait", "", ['doc' => $documento], 'contabilita');

        $contents = file_get_contents($pdfFile, true);
        $pdf_b64 = base64_encode($contents);

        header('Content-Type: application/pdf');
        header('Content-disposition: inline; filename="documento_cmr_' . time() . '.pdf"');

        echo base64_decode($pdf_b64);
    }

    public function imposta_filtro_anno($anno)
    {
        $field_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'documenti_contabilita_data_emissione'")->row()->fields_id;

        $filtro_fatture = (array) @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];

        $filtro_fatture[$field_id] = [
            'value' => '01/01/' . $anno . ' - 31/12/' . $anno,
            'field_id' => $field_id,
            'operator' => 'eq',
        ];

        if (array_key_exists('0', $filtro_fatture)) {
            unset($filtro_fatture[0]);
        }

        $filtro = $this->session->userdata(SESS_WHERE_DATA);
        $filtro['filtro_elenchi_documenti_contabilita'] = $filtro_fatture;
        $this->session->set_userdata(SESS_WHERE_DATA, $filtro);

        redirect('main/layout/elenco_documenti');

        //debug($anno, true);
    }

    public function export_dhl()
    {
        $this->load->config('geography');
        $nazioni_map = array_flip($this->config->item('nazioni'));

        $ids = json_decode($this->input->post('ids_dhl'));

        $fatture = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_id IN (' . implode(',', $ids) . ')']);

        //debug($fatture, true);

        $this->load->helper('download');

        $dest_folder = FCPATH . "uploads";

        $filename = "SpedizioniFattDHL_del_" . date('dm') . ".csv";

        $destfile = "$dest_folder/$filename";

        if (file_exists($destfile)) {
            unlink($destfile);
        }

        $file = fopen($destfile, 'w');

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');

        $ha_ragione_sociale = $settings['documenti_contabilita_settings_company_name'];
        $ha_indirizzo = $settings['documenti_contabilita_settings_company_address'];
        $ha_cap = $settings['documenti_contabilita_settings_company_zipcode'];
        $ha_citta = $settings['documenti_contabilita_settings_company_city'];
        $ha_nazione = $settings['documenti_contabilita_settings_company_country'];
        $ha_nazione = (strlen($ha_nazione) > 2) ? $nazioni_map[$ha_nazione] : $ha_nazione;
        $ha_telefono = "";

        $csv = [];

        $csv_head = [
            'sender_reference',
            'sender_company',
            'sender_address1',
            'sender_zip',
            'sender_city',
            'sender_country_cd',
            'sender_cd',
            'sender_account_num',
            'receiver_company',
            'receiver_attention',
            'receiver_address_1',
            'receiver_zip',
            'receiver_city',
            'receiver_country_cd',
            'Local_product_cd',
            'shipment_pieces',
            'shipment_weight',
            'contents1',
            'pre_alert_email',
            'rcvr_always_send_prealert_flag',
            'Advisory_Attached_flag',
            'receiver_cd',
            'Services', // 0 o 1 se contrassegno
            'KB', 'COD', '', '', '', '', // valori fissi
            'COD_value',
            'COD_currency',
            'COD_payment_type',
            'receiver_phone',
            "\r\n",
        ];

        $csv_head_str = implode(';', $csv_head);

        fwrite($file, $csv_head_str);

        foreach ($fatture as $key => $fattura) {
            $sped = $this->apilib->getById('clienti_indirizzi_spedizione', $fattura['documenti_contabilita_extra_param']);

            $dest = json_decode($fattura['documenti_contabilita_destinatario'], true);

            $ragione_sociale = $dest['ragione_sociale'];
            $email = (!empty($fattura['clienti_email'])) ? $fattura['clienti_email'] : '';
            $telefono = (!empty($fattura['clienti_telefono'])) ? $fattura['clienti_telefono'] : '';
            $codice = (!empty($fattura['clienti_codice'])) ? $fattura['clienti_codice'] : '';

            $indirizzo = (!empty($sped)) ? $sped['clienti_indirizzi_spedizione_indirizzo'] : $dest['indirizzo'];
            $citta = (!empty($sped)) ? $sped['clienti_indirizzi_spedizione_citta'] : $dest['citta'];
            $cap = (!empty($sped)) ? $sped['clienti_indirizzi_spedizione_cap'] : $dest['cap'];
            $nazione = (!empty($sped)) ? ucfirst(strtolower($sped['clienti_indirizzi_spedizione_nazione'])) : ucfirst(strtolower($dest['nazione']));

            $nazione = (strlen($nazione) > 2) ? $nazioni_map[$nazione] : $nazione;

            $numdoc = $fattura['documenti_contabilita_numero'];
            $totale = number_format($fattura['documenti_contabilita_totale'], 2, ',', '');
            $valuta = $fattura['documenti_contabilita_valuta'];

            $n_colli = (!empty($fattura['documenti_contabilita_n_colli'])) ? $fattura['documenti_contabilita_n_colli'] : '';
            $peso = (!empty($fattura['documenti_contabilita_peso'])) ? $fattura['documenti_contabilita_peso'] : '0.01';
            $contrassegno = ($fattura['documenti_contabilita_metodo_pagamento'] == 'contrassegno') ? '1' : '0';
            $contrassegno_kb = ($fattura['documenti_contabilita_metodo_pagamento'] == 'contrassegno') ? 'KB' : '0';
            //$csv[$key]

            $csv_arr = [
                $numdoc, //A numero documento
                $ha_ragione_sociale, //B ragione sociale
                $ha_indirizzo, //C indirizzo mittente
                $ha_cap, //D cap mittente
                $ha_citta, //E citta mittente
                $ha_nazione, //F nazione mittente
                $settings['documenti_contabilita_settings_dhl_code'], //G codice dhl
                $settings['documenti_contabilita_settings_dhl_code'], //H codice dhl
                $ragione_sociale, //I ragione sociale destinatario
                $ragione_sociale, //J attenzione destinatario
                $indirizzo, //K indirizzo destinatario
                $cap, //L cap destinatario
                $citta, //M citta destinatario
                $nazione, //N nazione destinatario
                "N", //O codice prodotto dhl
                number_format($n_colli, 0), //P numero colli
                number_format($peso, 2, ',', ''), //Q peso spedizione
                "Generico", //R descrizione contenuto
                $email,
                "1", //S preavviso mail
                "1", //T attivazione preavviso
                $codice, //U codice cliente
                $contrassegno, //V se contrassegno, 1, altrimenti 0
                $contrassegno_kb,
                'COD', '', '', '', '',
                $totale, //W valore spedizione
                $valuta, //X valuta spedizione
                'K', //Y tipo di pagamento contrassegno (?)
                preg_replace("/[^0-9]/", "", $telefono), //Z telefono destinatario
                "\r\n",
            ];
            $csv_str = implode(';', $csv_arr);
            fwrite($file, $csv_str);
        }

        /*$csv = $this->array_to_csv($csv ,';', '"');

        fwrite($file, $csv);*/
        fclose($file);

        force_download($filename, file_get_contents($destfile));
        unlink($destfile);
    }

    public function getProducts($doc_id)
    {
        $articoli = $this->apilib->search('documenti_contabilita_articoli', [
            'documenti_contabilita_articoli_documento' => $doc_id,
        ]);

        e_json($articoli);
    }
    public function listDocumenti($options_html = false)
    {
        $mail_data = $this->input->post();
        $where = [];
        if ($tipo = $mail_data['tipo']) {
            $where['documenti_contabilita_tipo'] = $tipo;
        }

        $documenti = $this->apilib->search('documenti_contabilita', $where, 100, null, 'documenti_contabilita_data_emissione DESC');
        if ($options_html) {
            foreach ($documenti as $documento) {
                ?>
                <option data-tipo_documento="<?php echo $documento['documenti_contabilita_tipo']; ?>" data-data_documento="<?php echo dateFormat($documento['documenti_contabilita_data_emissione'], 'd/m/Y'); ?>" data-rif="<?php echo $documento['documenti_contabilita_numero']; ?><?php if ($documento['documenti_contabilita_serie']): ?>/<?php echo $documento['documenti_contabilita_serie']; ?><?php endif;?>" value="<?php echo $documento['documenti_contabilita_id']; ?>" <?php if (!empty($movimento['movimenti_documento_id']) && $movimento['movimenti_documento_id'] == $documento['documenti_contabilita_id']): ?> selected="selected" <?php endif;?>>
                    <?php echo $documento['documenti_contabilita_numero']; ?><?php if ($documento['documenti_contabilita_serie']): ?>/<?php echo $documento['documenti_contabilita_serie']; ?><?php endif;?> - <?php echo json_decode($documento['documenti_contabilita_destinatario'], true)['ragione_sociale']; ?>
                </option>
<?php
            }
        } else {
            e_json($documenti);
        }
    }

    public function exportOrdiniFornitori()
    {
        $ids = json_decode($this->input->post('ids'));

        //debug($ids, true);

        $_articoli = $this->apilib->search('documenti_contabilita_articoli', [
            'documenti_contabilita_id IN (' . implode(',', $ids) . ')',
        ], 'documenti_contabilita_numero');
        $ordini_fornitori = [];
        foreach ($_articoli as $articolo) {
            if (!$articolo['documenti_contabilita_supplier_id']) {
                die("Ordine fornitore n. '{$articolo['documenti_contabilita_numero']}' non ha il fornitore associato.");
            }

            $supplier = $this->apilib->view('suppliers', $articolo['documenti_contabilita_supplier_id']);
            $ordini_fornitori[$articolo['documenti_contabilita_supplier_id']][$articolo['documenti_contabilita_articoli_documento']][] = $articolo + $supplier;
        }

        //debug($ordini_fornitori, true);
        $this->load->helper('download');
        $this->load->library('zip');
        $dest_folder = FCPATH . "uploads";

        $destination_file = "{$dest_folder}/ordini.zip";

        //die('test');
        //Ci aggiungo il json e la versione, poi rizippo il pacchetto...
        $zip = new ZipArchive();

        if ($zip->open($destination_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            exit("cannot open <$destination_file>\n");
        }
        $columns = [
            'customers_company' => 'Fornitore',
            'documenti_contabilita_numero' => 'Num. doc.',
            'documenti_contabilita_data_emissione' => 'Data',
            'documenti_contabilita_totale' => 'Totale doc.',
            'documenti_contabilita_imponibile' => 'Imponibile doc.',
            'documenti_contabilita_competenze' => 'Competenze doc.',
            'documenti_contabilita_iva' => 'Iva doc.',
            'documenti_contabilita_articoli_name' => 'Nome art.',
            'documenti_contabilita_articoli_codice' => 'Codice',
            'documenti_contabilita_articoli_codice_ean' => 'Ean',
            'documenti_contabilita_articoli_codice_asin' => 'Asin',
            'documenti_contabilita_articoli_descrizione' => 'Descrizione',
            'documenti_contabilita_articoli_prezzo' => 'Prezzo unit.',
            'documenti_contabilita_articoli_sconto' => 'Sconto',
            'documenti_contabilita_articoli_iva' => 'Iva art.',
            'documenti_contabilita_articoli_importo_totale' => 'Totale art.',
            'documenti_contabilita_articoli_imponibile' => 'Imponibile art.',
            'documenti_contabilita_articoli_quantita' => 'Qty',
            'documenti_contabilita_note_interne' => 'Note ordine',
        ];
        foreach ($ordini_fornitori as $supplier_id => $ordini) {
            $supplier = $this->apilib->view('suppliers', $supplier_id);
            $supplier_code = $supplier['customers_code'] ?: $supplier_id;
            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->getActiveSheet()->fromArray($columns, '', 'A1');

            $xls_rows = [];
            foreach ($ordini as $ordine_id => $articoli) {
                foreach ($articoli as $key => $articolo) {
                    //debug($articolo, true);
                    foreach ($columns as $column => $label) {
                        $xls_rows[$key][$column] = $articolo[$column];
                    }
                }
            }

            $objPHPExcel->getActiveSheet()->fromArray($xls_rows, '', 'A2');

            $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
            $objWriter->setPreCalculateFormulas(true);
            $objWriter->save("{$supplier_code}.xlsx");
            $zip->addFromString("{$supplier_code}.xlsx", file_get_contents("{$supplier_code}.xlsx"));

            unlink("{$supplier_code}.xlsx");
        }

        $zip->close();

        force_download('ordini_fornitore.zip', file_get_contents($destination_file));

        unlink($destination_file);
    }

    public function bulk_invio_mail()
    {
        $post = $this->input->post();

        $ids = explode(',', $post['documenti_contabilita_mail_documento_id']);

        $this->load->model('contabilita/docs');

        // invio email
        $this->load->library('email');
        $config['charset'] = 'utf-8';
        $config['wordwrap'] = true;
        $config['mailtype'] = 'html';

        $this->email->initialize($config);

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');

        foreach ($ids as $documento_id) {
            $this->email->clear(true);

            $mail_data = $post;

            $documento = $this->apilib->view('documenti_contabilita', $documento_id);

            $this->email->from($mail_data['documenti_contabilita_mail_mittente'], $mail_data['documenti_contabilita_mail_mittente_nome']);

            $documento_numero = $documento['documenti_contabilita_numero'];

            // SE fattura elettronica allego anche la preview xml
            if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                // if ($mail_data['documenti_contabilita_mail_template']) {
                //     $allegato = base64_decode($documento['documenti_contabilita_file_preview_xml']);
                //     $this->email->attach($allegato, 'attachment', 'Fattura_elettronica_' . $documento_numero . '.pdf', 'application/pdf');
                // }

                // Allegato il pdf standard
                $allegato = base64_decode($documento['documenti_contabilita_file_preview']);
                $this->email->attach($allegato, 'attachment', 'Documento_' . $documento_numero . '.pdf', 'application/pdf');

            // Allego l'xml
                /*$allegato = base64_decode($documento['documenti_contabilita_file']);
            $nomefile = "IT" . $settings['documenti_contabilita_settings_company_vat_number'] . "_00001.xml";
            $this->email->attach($allegato, 'attachment', $nomefile, 'application/xml');*/
            } else {
                // Allegato il pdf standard
                $allegato = base64_decode($documento['documenti_contabilita_file']);
                $this->email->attach($allegato, 'attachment', 'Documento_' . $documento_numero . '.pdf', 'application/pdf');
            }

            if ($documento['documenti_contabilita_serie']) {
                $documento_numero = $documento_numero . '/' . $documento['documenti_contabilita_serie'];
            }

            // Replace
            $mail_data['documenti_contabilita_mail_oggetto'] = str_replace('{numero_fattura}', $documento_numero, $mail_data['documenti_contabilita_mail_oggetto']);
            $mail_data['documenti_contabilita_mail_oggetto'] = str_replace('{data_emissione}', date('d-m-Y', strtotime($documento['documenti_contabilita_data_emissione'])), $mail_data['documenti_contabilita_mail_oggetto']);

            $mail_data['documenti_contabilita_mail_testo'] = str_replace('{numero_fattura}', $documento_numero, $mail_data['documenti_contabilita_mail_testo']);
            $mail_data['documenti_contabilita_mail_testo'] = str_replace('{data_emissione}', date('d-m-Y', strtotime($documento['documenti_contabilita_data_emissione'])), $mail_data['documenti_contabilita_mail_testo']);

            $mail_data['documenti_contabilita_mail_documento_id'] = $documento_id;

            if (empty($mail_data['documenti_contabilita_mail_destinatario'])) {
                if ($documento['documenti_contabilita_customer_id']) {
                    $mappature = $this->docs->getMappature();
                    extract($mappature);

                    $destinatario = $this->apilib->view($entita_clienti, $documento['documenti_contabilita_customer_id']);

                    if (!empty($destinatario[$clienti_email])) {
                        $mail_data['documenti_contabilita_mail_destinatario'] = $destinatario[$clienti_email];
                    }
                }
            }

            if (!empty($mail_data['documenti_contabilita_mail_destinatario'])) {
                $this->email->to($mail_data['documenti_contabilita_mail_destinatario']);

                $this->email->subject($mail_data['documenti_contabilita_mail_oggetto']);

                $this->email->message($mail_data['documenti_contabilita_mail_testo'] ?: ' ');

                $mail_data['documenti_contabilita_mail_allegati'] = json_encode(array(base64_encode($allegato)));

                $mail_data['documenti_contabilita_mail_creation_date'] = date('Y-m-d H:i:s');

                // ho usato $this->db perchè sennò con apilib entrava nel post-process pre-insert documenti_contabilita_mail "Invio email contabilita" e avrebbe fatto casini
                $this->db->insert('documenti_contabilita_mail', $mail_data);

                // Send and return the result
                $return = $this->email->send();
                if (empty($return)) {
                    throw new ApiException("Invio mail fallito: " . $this->email->print_debugger());
                    //    exit();
                }

                //die('esito: ' . $return);
            }
        }
        echo json_encode(['status' => 2, 'txt' => 'ok']);
    }

    public function associa_movimento_scadenza($flusso_cassa_id)
    {
        $ids = json_decode($this->input->post('scadenze_ids'));

        $flusso_cassa = $this->apilib->view('flussi_cassa', $flusso_cassa_id);
        $data = $this->apilib->edit('flussi_cassa', $flusso_cassa_id, [
            'flussi_cassa_scadenze_collegate' => $ids,
        ]);
        //debug($this->apilib->view('flussi_cassa', $flusso_cassa_id), true);
        redirect('main/layout/dettaglio-flusso-cassa/' . $data['flussi_cassa_azienda']);
    }

    public function associa_movimento_scadenza_uscita($flusso_cassa_id)
    {
        $ids = json_decode($this->input->post('scadenze_uscita_ids'));

        //debug($ids, true);

        $flusso_cassa = $this->apilib->view('flussi_cassa', $flusso_cassa_id);
        $data = $this->apilib->edit('flussi_cassa', $flusso_cassa_id, [
            'flussi_cassa_spese_scadenze_collegate' => $ids,
        ]);
        //debug($this->apilib->view('flussi_cassa', $flusso_cassa_id), true);
        redirect('main/layout/dettaglio-flusso-cassa/' . $data['flussi_cassa_azienda']);
    }
}
