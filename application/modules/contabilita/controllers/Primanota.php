<?php

class Primanota extends MX_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('contabilita/docs');
        $this->load->model('contabilita/prima_nota');

        $this->settings = $this->db->get('settings')->row_array();
        $this->contabilita_settings = $this->apilib->searchFirst('documenti_contabilita_settings');
    }

    public function salva()
    {
        $input = $this->input->post();
        if (!$input['prime_note_numero_documento']) {
            $input['prime_note_numero_documento'] = $input['_prime_note_numero_documento'];
        }
        unset($input['_prime_note_numero_documento']);

        unset($input[get_csrf()['name']]);

        $is_edit = false;
        $is_modello = false;
        if (!empty($input['prime_note_id'])) {

            $is_edit = true;

            //Trick per verificare se sto modificando un modello (devo gestirlo in modo diverso)
            $prima_nota_data = $this->apilib->view('prime_note', $input['prime_note_id']);
            $modello_data = $this->apilib->searchFirst('prime_note_modelli', ['prime_note_modelli_prima_nota' => $input['prime_note_id']]);

            if ($prima_nota_data['prime_note_modello'] || $modello_data) {
                $is_modello = true;
            }
        } else {
            $is_modello = (!empty($input['prime_note_modello']));
        }

        // per inserire un field required, usare la formula field_name => descrizione (quella che c'è nel form)
        $required_fields = [
            'prime_note_azienda' => 'Azienda',
            'prime_note_progressivo_annuo' => 'Progr. Anno',
            'prime_note_progressivo_giornaliero' => 'Progr. Giorno',
            'prime_note_data_registrazione' => 'Data reg.',
            'prime_note_causale' => 'Causale',
            'prime_note_scadenza' => 'Data doc.',
        ];

        foreach ($input as $field => $value) {
            if (array_key_exists($field, $required_fields) && empty($value)) {
                $field_name = $required_fields[$field] ?? ucfirst(str_ireplace(['_'], [' '], $field));

                e_json(['status' => 0, 'txt' => "Il campo <b>{$field_name}</b> è obbligatorio"]);
                exit;
            }
        }

        $causale = $this->apilib->view('prime_note_causali', $input['prime_note_causale']);

        if ($causale['prime_note_causali_registro_iva'] == DB_BOOL_TRUE) {
            $sum_avere = array_sum(array_column($input['registrazioni'], 'prime_note_registrazioni_importo_avere'));
            $sum_registro_imponibile = array_sum(array_column($input['dettaglio_iva'], 'prime_note_righe_iva_imponibile'));
            $sum_registro_iva = array_sum(array_column($input['dettaglio_iva'], 'prime_note_righe_iva_importo_iva'));

            $sum_registro = ($sum_registro_imponibile + $sum_registro_iva);

            //Casto a stringa number format perchè php è cattivo: (float)168.92 secondo lui è diverso da (float)168.92
            $sum_avere = number_format(abs($sum_avere), 2);
            $sum_registro = number_format(abs($sum_registro), 2);
            // debug($sum_avere);
            // debug($sum_registro);
            if ($sum_avere != $sum_registro) {

                e_json(['status' => 0, 'txt' => "Rilevata squadratura nel registro iva, si prega di controllare"]);
                exit;
            }
        } else {
            unset($input['dettaglio_iva']);
        }

        // check progressivo anno
        if ($is_edit) {
            $this->db->where("prime_note_id <> '{$input['prime_note_id']}'", null, false);
        }

        $progr_anno = $this->db->where("YEAR(STR_TO_DATE('{$input['prime_note_data_registrazione']}','%d/%m/%Y')) = YEAR(prime_note_data_registrazione)", null, false)->where("prime_note_modello <> '1'", null, false)->where('prime_note_progressivo_annuo', $input['prime_note_progressivo_annuo'])->get('prime_note')->row_array();

        if (!$is_modello && !empty($progr_anno)) {
            e_json(['status' => 0, 'txt' => "Esiste già una prima nota in questo anno per questo progressivo annuale"]);
            exit;
        }

        // check progressivo giorno
        if ($is_edit) {
            $this->db->where("prime_note_id <> '{$input['prime_note_id']}'", null, false);
        }
        $progr_anno = $this->db->where("STR_TO_DATE('{$input['prime_note_data_registrazione']}','%d/%m/%Y') = DATE_FORMAT(prime_note_data_registrazione, '%Y-%m-%d')", null, false)->where("prime_note_modello <> '1'", null, false)->where('prime_note_progressivo_annuo', $input['prime_note_progressivo_annuo'])->get('prime_note')->row_array();

        if (!$is_modello && !empty($progr_anno)) {
            e_json(['status' => 0, 'txt' => "Esiste già una prima nota in questa giornata per questo progressivo giornaliero"]);
            exit;
        }

        // check data_reg non deve essere maggiore di oggi

        if (!$is_modello && (DateTime::createFromFormat('d/m/Y', $input['prime_note_data_registrazione']))->format('Y-m-d') > date('Y-m-d')) {
            e_json(['status' => 0, 'txt' => "La <b>Data reg.</b> non può essere nel futuro"]);
            exit;
        }

        // formato data registrazione
        if (!$is_modello && !(DateTime::createFromFormat('d/m/Y', $input['prime_note_data_registrazione']))) {
            e_json(['status' => 0, 'txt' => "Il formato <b>Data reg.</b> non è valida."]);
            exit;
        }

        // check sezionale e protocollo
        if ($is_edit) {
            $this->db->where("prime_note_id <> '{$input['prime_note_id']}'", null, false);
        }
        $sez_prot = $this->db->where("prime_note_modello <> '1'", null, false)->where("YEAR(STR_TO_DATE('{$input['prime_note_data_registrazione']}','%d/%m/%Y')) = YEAR(prime_note_data_registrazione)", null, false)->where(['prime_note_sezionale' => $input['prime_note_sezionale'], 'prime_note_protocollo' => $input['prime_note_protocollo']])->get('prime_note')->row_array();

        if (!$is_modello && !empty($sez_prot)) {
            e_json(['status' => 0, 'txt' => "Esiste già un'associazione tra il sezionale ed il protocollo iva selezionati per l'anno selezionato"]);
            exit;
        }

        $prima_nota = $input;

        if ($is_modello) {
            $prima_nota['prime_note_modello'] = DB_BOOL_TRUE;
        }

        $prima_nota['prime_note_json_data'] = json_encode($this->input->post());

        //Commentato tutto... le righe di iva vanno salvate per fare le varie estrapolazioni (libri giornale, riepiloghi iva, ecc...)
        // foreach ($prima_nota['dettaglio_iva'] as $key => $riga_dettaglio_iva) {
        //     if (!$riga_dettaglio_iva['iva_codice']) {
        //         unset($prima_nota['dettaglio_iva'][$key]);
        //     }
        // }
        // $prima_nota['dettaglio_iva'] = json_encode($prima_nota['dettaglio_iva']);
        // unset($prima_nota['dettaglio_iva']);

        unset($prima_nota['registrazioni']);
        unset($prima_nota['dettaglio_iva']);

        $registrazioni = $input['registrazioni'];
        $dettaglio_iva = $input['dettaglio_iva'] ?? [];

        if (!$is_modello && count($registrazioni) < 2) {
            e_json(['status' => 0, 'txt' => 'Il salvataggio della prima nota richiede almeno n° 2 righe registrazioni']);
            exit;
        }

        if (empty($prima_nota['prime_note_causale'])) {
            e_json(['status' => 0, 'txt' => 'Il campo Causale è obbligatorio']);
            exit;
        }

        if (!$is_modello && !$input['prime_note_scadenza']) {
            e_json(['status' => 0, 'txt' => 'Data documento obbligatoria']);
            exit;
        }

        $data_registrazione_ts = DateTime::createFromFormat("d/m/Y", $input['prime_note_data_registrazione'])->getTimestamp();
        if ($input['prime_note_scadenza']) {
            $data_documento_ts = DateTime::createFromFormat("d/m/Y", $input['prime_note_scadenza'])->getTimestamp();
        } else {
            $data_documento_ts = null;
        }

        if (!(empty($input['prime_note_scadenza'])) && $data_registrazione_ts < $data_documento_ts) {
            e_json(['status' => 0, 'txt' => 'La data di registrazione non può essere minore della data del documento!']);
            exit;
        }

        $tot_dare = $tot_avere = 0;

        $riga = 1;

        //debug($registrazioni, true);

        foreach ($registrazioni as $key => $registrazione) {
            /* EMPTY DARE/AVERE */

            //debug($registrazione);
            if (empty($registrazione['prime_note_registrazioni_codice_dare_testuale']) && empty($registrazione['prime_note_registrazioni_codice_avere_testuale'])) { // SE SOTTOCONTO DARE / AVERE VUOTI, DO ERRORE
                if (count($registrazioni) < 2) {
                    e_json(['status' => 0, 'txt' => 'Sottoconto non compilato alla riga ' . $riga]);
                    exit;
                } else {
                    unset($registrazioni[$key]);
                    continue;
                }
            }

            /* NOT EMPTY DARE/AVERE */

            if (!empty($registrazione['prime_note_registrazioni_codice_dare_testuale']) && !empty($registrazione['prime_note_registrazioni_codice_avere_testuale'])) { // SE COMPILATI SIA SOTTOCONTO DARE E SOTTOCONTO AVERE DO ERRORE
                e_json(['status' => 0, 'txt' => 'Errore: Sottoconto compilato sia su dare che su avere alla riga ' . $riga]);
                exit;
            }

            /* CHECK IMPORTI */
            if (empty($registrazione['prime_note_registrazioni_importo_dare']) && empty($registrazione['prime_note_registrazioni_importo_avere'])) { // SE NON COMPILATI SIA IMPORTO DARE CHE IMPORTO AVERE, DO ERRORE
                // e_json(['status' => 0, 'txt' => 'Importo non compilato alla riga ' . $riga]);
                // exit;
            }

            if (!empty($registrazione['prime_note_registrazioni_importo_dare']) && !empty($registrazione['prime_note_registrazioni_importo_avere'])) { // SE COMPILATI SIA IMPORTO DARE CHE IMPORTO AVERE, DO ERRORE
                e_json(['status' => 0, 'txt' => 'Errore: Importo compilato sia su dare che su avere alla riga ' . $riga]);
                exit;
            }

            /* ALTRI CHECKS */

            if (empty($registrazione['prime_note_registrazioni_numero_riga'])) {
                e_json(['status' => 0, 'txt' => 'Errore: Numero riga non compilato alla riga ' . $riga]);
                exit;
            }
            //debug($registrazione['prime_note_registrazioni_importo_dare']);
            $tot_dare += $registrazione['prime_note_registrazioni_importo_dare'];
            //debug($tot_dare);
            $tot_avere += $registrazione['prime_note_registrazioni_importo_avere'];

            $riga++;
        }

        // var_dump($tot_dare);
        // var_dump($tot_avere);
        // var_dump($tot_avere - $tot_dare);

        $tot_dare_txt = number_format($tot_dare, 2);
        $tot_avere_txt = number_format($tot_avere, 2);

        if ($tot_avere_txt != $tot_dare_txt) {
            e_json(['status' => 0, 'txt' => 'Il totale di "importo dare" (' . $tot_dare_txt . ') non è uguale al totale di "importo avere" (' . $tot_avere_txt . ')']);
            exit;
        }
        if (empty($prima_nota['prime_note_id'])) {
            //Verifico che nel frattempo non sia stata salvata una prima nota con lo stesso progressivo
            $progressivo_annuo = $input['prime_note_progressivo_annuo'];
            $progressivo_giorno = $input['prime_note_progressivo_giornaliero'];
            $anno = explode('/', $input['prime_note_data_registrazione'])[2];
            $progr_annuo_atteso = $this->prima_nota->getProgressivoAnno($anno, $input['prime_note_azienda']);
            $progr_giorno_atteso = $this->prima_nota->getProgressivoGiorno($input['prime_note_data_registrazione'], $input['prime_note_azienda']);

            if (!$is_modello && ($progressivo_annuo != $progr_annuo_atteso || $progressivo_giorno != $progr_giorno_atteso)) {
                e_json([
                    'status' => 9, //9 permette di eseguire un codice javascript custom
                    'txt' => 'getProgressivoPrimanotaAnno(); getProgressivoPrimanotaGiorno();alert(\'Esiste già un progressivo anno/giorno con questo numero. I progressivi sono stati ricalcolati automaticamente. Cliccare nuovamente salva per procedere...\');',
                ]);
                exit;
            }
        }

        if ($dettaglio_iva) {
            $iva_trovate = [];
            foreach ($dettaglio_iva as $iva) {
                if (in_array($iva['prime_note_righe_iva_iva'], $iva_trovate)) {
                    e_json([
                        'status' => 0, //9 permette di eseguire un codice javascript custom
                        'txt' => 'Controllare lo specchietto iva e accorpare le aliquote uguali!',
                    ]);
                    exit;
                } else {
                    $iva_trovate[] = $iva['prime_note_righe_iva_iva'];
                }
            }
        }

        try {

            if ($prima_nota['prime_note_id']) {
                //debug('BUG TROVATO!', true);
            }
            //debug($registrazioni,true);
            $this->prima_nota->salvaPrimaNota($prima_nota, $registrazioni, $dettaglio_iva);
        } catch (Exception $e) {
            e_json(['status' => 0, 'txt' => t($e->getMessage())]);
            exit;
        }

        //Se la causale selezionata necessita di sucessiva registrazione, procedo a fare redirect alla registrazione della sucessiva prima nota (non automatica, ma solo precompilata e da completare)

        if ($causale['prime_note_causali_next_modello'] && !$is_modello) {

            $parametri = [
                //TODO: per compilare in automatico le righe (occhio che questa cosa non vale solo per il reverse ma anche per altri tipi di registrazione)

            ];

            e_json(['status' => '1', 'txt' => base_url('main/layout/prima-nota?modello=' . $causale['prime_note_causali_next_modello'] . '&spesa_id=' . $prima_nota['prime_note_spesa'])]);
        } else {

            if ($causale['prime_note_causali_codice'] == 'PGR') {
                echo json_encode(array('status' => 9, 'txt' => "
                //let text;
                //    if (confirm('Vuoi procedere anche con la registrazione in prima nota?') == true) {
                //        location.href='" . base_url("main/layout/prima-nota?modello={$spesa['spese_modello_prima_nota']}&spesa_id={$spesa_id}") . "';
                //    } else {
                        location.href='" . base_url('main/layout/nuova_spesa') . "';
                //    }
                "));
            } else {
                if ($modello_get = $this->input->get('modello')) {
                    e_json(['status' => '1', 'txt' => base_url('main/layout/prima-nota?modello=' . $modello_get)]);
                } else {
                    e_json(['status' => '1', 'txt' => base_url('main/layout/prima-nota')]);
                }
            }

        }
    }

    public function getProgressivoAnno()
    {
        $post = $this->input->post();

        $data = $post['date'] ?? date('d/m/Y');
        $azienda_id = $post['azienda'];
        $anno = (DateTime::createFromFormat('d/m/Y', $data))->format('Y');

        $progressivo = $this->prima_nota->getProgressivoAnno($anno, $azienda_id);

        e_json([
            'status' => 1,
            'txt' => $progressivo,
        ]);
    }
    public function getProtocolloIva()
    {
        $post = $this->input->post();

        $data = $post['date'] ?? date('d/m/Y');
        $data = (DateTime::createFromFormat('d/m/Y', $data))->format('Y');
        $azienda_id = $post['azienda'];
        $sezionale_id = $post['sezionale'];
        $documento_id = $post['documento'];
        $protocollo = $this->prima_nota->getProtocolloIva($data, $azienda_id, $sezionale_id, $documento_id);

        e_json([
            'status' => 1,
            'txt' => $protocollo,
        ]);
    }

    public function getProgressivoGiorno()
    {
        $post = $this->input->post();

        $data = $post['date'] ?? date('d/m/Y');
        $azienda_id = $post['azienda'];

        //$data = (DateTime::createFromFormat('d/m/Y', $data))->format('Y-m-d');

        $progressivo = $this->prima_nota->getProgressivoGiorno($data, $azienda_id);

        e_json([
            'status' => 1,
            'txt' => $progressivo,
        ]);
    }

    public function getCausale($causale_id = null)
    {
        if (!$causale_id) {
            e_json(['status' => '0', 'txt' => 'Errore: causale non dichiarata']);
            exit;
        }

        $causale = $this->db->where(['prime_note_causale_id' => $causale_id])->get('prime_note_causale')->row_array();

        // SEZIONE MASTRI
        $mastro_id = $conto_id = $sottoconto_id = null;
        $mastro = [];
        $conto = [];
        $sottoconto = [];

        if (!empty($causale['prime_note_causale_mastro_dare'])) {
            $mastro_id = $causale['prime_note_causale_mastro_dare'];
        } else if (!empty($causale['prime_note_causale_mastro_avere'])) {
            $mastro_id = $causale['prime_note_causale_mastro_avere'];
        }

        if ($mastro_id) {
            $mastro = $this->db->where('documenti_contabilita_mastri_id', $mastro_id)->get('documenti_contabilita_mastri')->row_array();
        }

        // SEZIONE CONTO
        if (!empty($causale['prime_note_causale_conto_dare'])) {
            $conto_id = $causale['prime_note_causale_conto_dare'];
        } else if (!empty($causale['prime_note_causale_conto_avere'])) {
            $conto_id = $causale['prime_note_causale_conto_avere'];
        }

        if ($conto_id) {
            $conto = $this->db->where('documenti_contabilita_conti_id', $conto_id)->get('documenti_contabilita_conti')->row_array();
        }

        // SEZIONE SOTTOCONTO
        if (!empty($causale['prime_note_causale_sottoconto_dare'])) {
            $sottoconto_id = $causale['prime_note_causale_sottoconto_dare'];
        } else if (!empty($causale['prime_note_causale_sottoconto_avere'])) {
            $sottoconto_id = $causale['prime_note_causale_sottoconto_avere'];
        }

        if ($mastro_id) {
            $sottoconto = $this->db->where('documenti_contabilita_sottoconti_id', $sottoconto_id)->get('documenti_contabilita_sottoconti')->row_array();
        }

        $causale['mastro'] = $mastro;
        $causale['conto'] = $conto;
        $causale['sottoconto'] = $sottoconto;

        if (empty($causale)) {
            e_json(['status' => '0', 'txt' => 'Errore: causale non trovata']);
            exit;
        }

        e_json(['status' => 1, 'txt' => $causale]);
    }

    public function autocompleteDocumento()
    {
        $q = strtoupper($this->input->post('q'));
        $Y = date('Y');
        $prevY = $Y - 1;

        $documenti = $this->db
            ->select('documenti_contabilita.*,customers.*,
                m.documenti_contabilita_mastri_codice as mastro,
                c.documenti_contabilita_conti_codice as conto,
                s.documenti_contabilita_sottoconti_codice as sottoconto,

                cpm.documenti_contabilita_mastri_codice as contropartita_mastro,
                cpc.documenti_contabilita_conti_codice as contropartita_conto,
                cps.documenti_contabilita_sottoconti_codice as contropartita_sottoconto,

                prime_note.prime_note_id

                ')
            ->where(
                "(
                    documenti_contabilita_numero LIKE '%{$q}%'
                    OR documenti_contabilita_numero LIKE '%{$q}%'
                    OR documenti_contabilita_destinatario LIKE '%{$q}%'
                    OR UPPER(CONCAT(documenti_contabilita_numero,'/',documenti_contabilita_serie)) = '{$q}'
                )
                AND YEAR(documenti_contabilita_data_emissione) IN ($Y,{$prevY})",
                null,
                false
            )

            ->where_in('documenti_contabilita_tipo', [1, 4, 11, 12])
            ->join('customers', 'customers_id = documenti_contabilita_customer_id', 'LEFT')
            ->join('documenti_contabilita_mastri as m', 'm.documenti_contabilita_mastri_id = customers_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as c', 'c.documenti_contabilita_conti_id = customers_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as s', 's.documenti_contabilita_sottoconti_id = customers_sottoconto', 'LEFT')
            ->join('documenti_contabilita_serie', 'documenti_contabilita.documenti_contabilita_serie = documenti_contabilita_serie_id', 'LEFT')
            ->join('documenti_contabilita_mastri as cpm', 'cpm.documenti_contabilita_mastri_id = customers_contropartita_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as cpc', 'cpc.documenti_contabilita_conti_id = customers_contropartita_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as cps', 'cps.documenti_contabilita_sottoconti_id = customers_contropartita_sottoconto', 'LEFT')

            ->join('prime_note', 'prime_note.prime_note_documento = documenti_contabilita_id', 'left')

            ->limit(100)
            ->group_by('documenti_contabilita_id')
            ->order_by('documenti_contabilita_data_emissione DESC')
            ->get('documenti_contabilita')
            ->result_array();

        //die($this->db->last_query());

        $response = ['status' => 1, 'txt' => $documenti];

        die(json_encode($response));
    }
    public function getContoFromTestuale($identifier)
    {
        $conto_testuale = $this->input->post('conto');
        //debug($conto_testuale, true);
        $conto = $this->apilib->searchFirst('documenti_contabilita_sottoconti', [
            'documenti_contabilita_sottoconti_codice_completo' => $conto_testuale,
            '(documenti_contabilita_sottoconti_blocco = 0 OR documenti_contabilita_sottoconti_blocco IS NULL)',

        ]);
        if ($conto) {
            $response = ['status' => 1, 'conto' => $conto, 'id' => $identifier];
        } else {
            $response = ['status' => 0];
        }
        e_json($response);
    }
    public function autocompleteSpesa()
    {
        $q = $this->input->post('q');
        $Y = date('Y');
        $prevY = $Y - 1;
        // $documenti = $this->apilib->search('spese', [
        //     "(spese_numero LIKE '%{$q}%' OR spese_fornitore LIKE '%{$q}%')",
        //     "YEAR(spese_data_emissione) IN ($Y,{$prevY})"
        // ], 10, 0, 'spese_data_emissione', 'ASC', 3);

        $documenti = $this->db
            ->select('spese.*,customers.*,
                m.documenti_contabilita_mastri_codice as mastro,
                c.documenti_contabilita_conti_codice as conto,
                s.documenti_contabilita_sottoconti_codice as sottoconto,

                cpm.documenti_contabilita_mastri_codice as contropartita_mastro,
                cpc.documenti_contabilita_conti_codice as contropartita_conto,
                cps.documenti_contabilita_sottoconti_codice as contropartita_sottoconto,

                prime_note.prime_note_id
                ')
            ->where(
                "(
                    spese_numero LIKE '%{$q}%'
                    OR spese_fornitore LIKE '%{$q}%'
                ) AND YEAR(spese_data_emissione) IN ($Y,{$prevY})",
                null,
                false
            )

            ->join('customers', 'customers_id = spese_customer_id', 'LEFT')

            ->join('documenti_contabilita_mastri as m', 'm.documenti_contabilita_mastri_id = customers_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as c', 'c.documenti_contabilita_conti_id = customers_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as s', 's.documenti_contabilita_sottoconti_id = customers_sottoconto', 'LEFT')

            ->join('documenti_contabilita_mastri as cpm', 'cpm.documenti_contabilita_mastri_id = customers_contropartita_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as cpc', 'cpc.documenti_contabilita_conti_id = customers_contropartita_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as cps', 'cps.documenti_contabilita_sottoconti_id = customers_contropartita_sottoconto', 'LEFT')
            ->join('prime_note', 'prime_note.prime_note_spesa = spese_id', 'left')
            ->limit(10)
            ->order_by('spese_data_emissione DESC')
            ->get('spese')
            ->result_array();

        $response = ['status' => 1, 'txt' => $documenti];

        die(json_encode($response));
    }

    public function getPrimaNotaRighe($causale_id, $is_fattura, $doc_id = false, $modello_selezionato = false)
    {

        if ($is_fattura) {
            $documento = $this->apilib->view('documenti_contabilita', $doc_id);
            $check_registrazione_presente = $this->apilib->searchFirst('prime_note', [
                'CAST(prime_note_scadenza as DATE) = ' => substr($documento['documenti_contabilita_data_emissione'], 0, 10),
                "prime_note_id IN (SELECT prime_note_registrazioni_prima_nota FROM prime_note_registrazioni WHERE prime_note_registrazioni_importo_dare = '{$documento['documenti_contabilita_totale']}' OR prime_note_registrazioni_importo_avere = '{$documento['documenti_contabilita_totale']}')",
            ]);
            //debug($check_registrazione_presente, true);
        } else {
            $spesa = $this->apilib->view('spese', $doc_id);
            $check_registrazione_presente = $this->apilib->searchFirst('prime_note', [
                'CAST(prime_note_scadenza as DATE) = ' => substr($spesa['spese_data_emissione'], 0, 10),
                "prime_note_id IN (SELECT prime_note_registrazioni_prima_nota FROM prime_note_registrazioni WHERE prime_note_registrazioni_importo_dare = '{$spesa['spese_totale']}' OR prime_note_registrazioni_importo_avere = '{$spesa['spese_totale']}')",
            ]);
        }

        $righe = $this->prima_nota->getPrimaNotaRighe($causale_id, $is_fattura, $doc_id, null, $modello_selezionato);

        $righe_iva = $this->prima_nota->getPrimaNotaRigheIva($is_fattura, $doc_id);
        //debug('TODO: attenzione che la struttura è completamente cambiata... ora ha i nomi dei campi della tabella e non imponibile, totale,iva, perc, ec...', true);

        if ($check_registrazione_presente) {
            $warning_message = 'Per questo documento è già presente una registrazione prima nota con lo stesso importo e data';
        } else {
            $warning_message = false;
        }

        e_json(['righe' => $righe, 'righe_iva' => $righe_iva, 'warning_message' => $warning_message], true);
    }

    public function autocompleteSottoconto($dareavere = 'dare')
    {
        $query = addslashes($this->input->post('search'));

        $conti_selezionati = $this->input->post('conti_selezionati');

        $includi_clienti = $includi_fornitori = true;

        $conto_clienti = $this->apilib->searchFirst('documenti_contabilita_conti', ['documenti_contabilita_conti_clienti' => 1]);
        $conto_clienti_codice_completo = $conto_clienti['documenti_contabilita_conti_codice_completo'];

        $conto_fornitori = $this->apilib->searchFirst('documenti_contabilita_conti', ['documenti_contabilita_conti_fornitori' => 1]);
        $conto_fornitori_codice_completo = $conto_fornitori['documenti_contabilita_conti_codice_completo'];

        //Verifico se tra i conti già selezionati in questa prima nota ho già scelto un cliente o un fornitore, in tal caso li escludo dalla ricerca (non si sceglie mai due volte un fornitore in una registrazione)
        foreach ($conti_selezionati as $codice) {
            if (stripos($codice, $conto_clienti_codice_completo) === 0) {
                $includi_clienti = false;
            }
            if (stripos($codice, $conto_fornitori_codice_completo) === 0) {
                $includi_fornitori = false;
            }

        }

        $query = strtolower($query);

        // if ($dareavere == 'dare') {
        //     $where = 'documenti_contabilita_mastri_natura IN (1,5)'; //Attività e Ricavi
        // } else {
        //     $where = 'documenti_contabilita_mastri_natura IN (2,4)'; //Passività e Costi
        // }

        $where = [];
        foreach (explode(' ', $query) as $chunk) {
            $where[] = "(
                documenti_contabilita_sottoconti_codice_completo LIKE '{$chunk}%'
                OR
                documenti_contabilita_sottoconti_codice_completo LIKE '%{$chunk}'
                OR
                LOWER(documenti_contabilita_sottoconti_descrizione) LIKE '%{$chunk}%'
                OR
                LOWER(documenti_contabilita_conti_descrizione) LIKE '%{$chunk}%'
                OR
                LOWER(documenti_contabilita_mastri_descrizione) LIKE '%{$chunk}%'
             )
            ";
        }
        $where_add = [];
        if (!$includi_clienti) {
            $where_add[] = "documenti_contabilita_sottoconti_codice_completo NOT LIKE '{$conto_clienti_codice_completo}%'";
        }
        if (!$includi_fornitori) {
            $where_add[] = "documenti_contabilita_sottoconti_codice_completo NOT LIKE '{$conto_fornitori_codice_completo}%'";
        }
        //debug($where + $where_add);
        //Prima cerco senza clienti/fornitori doppi
        $items = $this->apilib->search('documenti_contabilita_sottoconti', array_merge($where, $where_add), 10, 0, 'documenti_contabilita_sottoconti_codice_completo', 'asc', 3);
        if (empty($items)) { //Ma se proprio proprio non trovo, allora vuole dire che forzatamente sto cercando un fornitore/client e allora bypasso quel filtro
            $items = $this->apilib->search('documenti_contabilita_sottoconti', $where, 10, 0, 'documenti_contabilita_sottoconti_codice_completo', 'asc', 3);

        }
        if (!empty($items)) {
            die(json_encode([
                'status' => 1,
                'data' => $items,
            ]));
        } else {
            die(json_encode([
                'status' => 0,
                'data' => [],
            ]));
        }
    }

    public function testCreaPrimaNotaDaFattura($documento_id, $limit = null, $offset = 0, $js = true)
    {
        echo_flush('Crea da fattura');
        if ($documento_id == 'all') {

            if ($offset == 0) {
                $this->db->query("DELETE FROM prime_note WHERE prime_note_documento IS NOT NULL");
                $this->apilib->clearCache();
            }

            $year = date('Y');
            $documenti = $this->apilib->search('documenti_contabilita', [
                "YEAR(documenti_contabilita_data_emissione) = '$year'",
                'documenti_contabilita_tipo' => 1,
                'documenti_contabilita_id NOT IN (SELECT prime_note_documento FROM prime_note WHERE prime_note_documento IS NOT NULL)',
            ], $limit, $offset, 'documenti_contabilita_numero ASC, documenti_contabilita_data_emissione ASC');

            foreach ($documenti as $documento) {
                $return = $this->prima_nota->creaPrimaNotaDaFattura($documento['documenti_contabilita_id']);
                echo_flush(' .');

                //die('blocco');
            }
            if ($documenti && $limit) {
                $offset += $limit;
                echo ("<script>location.href='" . base_url() . "contabilita/primanota/testCreaPrimaNotaDaFattura/all/$limit/$offset';</script>");
            }
        } else {
            $return = $this->prima_nota->creaPrimaNotaDaFattura($documento_id);
        }

        //debug($return, true);
    }

    public function testCreaPrimaNotaDaSpesa($spesa_id, $limit = null, $offset = 0, $js = true)
    {
        echo_flush('Crea da spesa');
        if ($spesa_id == 'all') {
            if ($offset == 0) {
                $this->db->query("DELETE FROM prime_note WHERE prime_note_spesa IS NOT NULL AND prime_note_modello <> 1");
                $this->apilib->clearCache();
            }
            $year = date('Y');
            $spese = $this->apilib->search('spese', [
                "YEAR(spese_data_emissione) = '$year'",
            ], $limit, $offset, 'spese_data_emissione ASC');
            foreach ($spese as $spesa) {
                $return = $this->prima_nota->creaPrimaNotaDaSpesa($spesa['spese_id']);
                echo_flush(' .');
            }
            if ($spese && $limit) {
                $offset += $limit;
                echo ("<script>location.href='" . base_url() . "contabilita/primanota/testCreaPrimaNotaDaSpesa/all/$limit/$offset';</script>");
            }
        } else {
            $return = $this->prima_nota->creaPrimaNotaDaSpesa($spesa_id);
        }

        //debug($return, true);
    }

    public function generaPrimeNote($day = 1)
    {
        $year = date('Y');
        $date = DateTime::createFromFormat('z Y', strval($day) . ' ' . strval($year))->format('Y-m-d');
        echo_flush("Generazione prime note per il giorno {$date}<br />", 1);
        if ($day == 1) {
            $this->db->query("DELETE FROM prime_note WHERE prime_note_modello <> 1");
            $this->apilib->clearCache();
        }

        $documenti = $this->apilib->search('documenti_contabilita', [
            "CAST(documenti_contabilita_data_emissione AS DATE) = '$date'",
            'documenti_contabilita_tipo' => [1, 4, 11, 12],
            'documenti_contabilita_id NOT IN (SELECT prime_note_documento FROM prime_note WHERE prime_note_documento IS NOT NULL)',
        ], null, 0, 'documenti_contabilita_numero ASC, documenti_contabilita_data_emissione ASC');
        echo_flush("Trovati " . count($documenti) . " documenti di vendita<br />", 1);

        $spese = $this->apilib->search('spese', [
            "CAST(spese_data_emissione AS DATE) = '$date'",
        ], null, 0, 'spese_data_emissione ASC');
        echo_flush("Trovati " . count($spese) . " documenti di acquisto<br />", 1);

        foreach ($documenti as $documento) {
            $return = $this->prima_nota->creaPrimaNotaDaFattura($documento['documenti_contabilita_id']);
            echo_flush(' +');

            //die('blocco');
        }
        foreach ($spese as $spesa) {
            $return = $this->prima_nota->creaPrimaNotaDaSpesa($spesa['spese_id']);
            echo_flush(' -');
        }

        if ($day <= 364) {
            $day++;
            echo ("<script>location.href='" . base_url() . "contabilita/primanota/generaPrimeNote/$day';</script>");
        }
    }

    public function stampa_conto($conto_id)
    {
        //$customer = $this->apilib->view('customers', $customer_id);
        $content = $this->load->view('pdf/stampa_conto', [
            'conto_id' => $conto_id,
        ], true);

        $pdf = $this->layout->generate_pdf($content, "portrait", "", 'contabilita', false, true);
        $fp = fopen(
            $pdf,
            'rb'
        );
        //die(file_get_contents($pdf));
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdf));
        fpassthru($fp);
        unlink($pdf);
    }

    public function stampa_mastro($mastro_id)
    {
        //$customer = $this->apilib->view('customers', $customer_id);
        $content = $this->load->view('pdf/stampa_mastro', [
            'mastro_id' => $mastro_id,
        ], true);

        $pdf = $this->layout->generate_pdf($content, "portrait", "", 'contabilita', false, true);
        $fp = fopen(
            $pdf,
            'rb'
        );
        //die(file_get_contents($pdf));
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdf));
        fpassthru($fp);
        unlink($pdf);
    }
    public function stampa_sottoconto($sottoconto_id)
    {
        //$customer = $this->apilib->view('customers', $customer_id);
        $content = $this->load->view('pdf/stampa_sottoconto', [
            'sottoconto_id' => $sottoconto_id,
        ], true);

        $pdf = $this->layout->generate_pdf($content, "portrait", "", 'contabilita', false, true);
        $fp = fopen(
            $pdf,
            'rb'
        );
        //die(file_get_contents($pdf));
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdf));
        fpassthru($fp);
        unlink($pdf);
    }

    public function estratto_conto_cliente($customer_id = null)
    {
        //$customer = $this->apilib->view('customers', $customer_id);
        $content = $this->load->view('pdf/stampa_sottoconto', [
            'customer_id' => $customer_id,
        ], true);

        $pdf = $this->layout->generate_pdf($content, "portrait", "", 'contabilita', false, true);
        $fp = fopen(
            $pdf,
            'rb'
        );
        //die(file_get_contents($pdf));
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdf));
        fpassthru($fp);
        unlink($pdf);
    }

    public function generaSottoconti($customer_id, $limit = null, $offset = 0)
    {
        $this->load->model('contabilita/customer', 'contab_cust');

        if ($customer_id == 'all') {
            if ($offset == 0) {
                $this->db->query("DELETE FROM documenti_contabilita_sottoconti WHERE documenti_contabilita_sottoconti_id IN (SELECT customers_sottoconto FROM customers where customers_sottoconto IS NOT NULL) OR documenti_contabilita_sottoconti_conto IN (SELECT documenti_contabilita_conti_id FROM documenti_contabilita_conti WHERE documenti_contabilita_conti_clienti = 1 OR documenti_contabilita_conti_fornitori = 1)");
                $this->db->query("UPDATE customers SET customers_sottoconto = null");
                $this->apilib->clearCache();
                //die();
            }

            $customers = $this->apilib->search('customers', [], $limit, $offset, 'customers_id ASC');
            foreach ($customers as $customer) {
                $this->contab_cust->generaSottoconto($customer);
                echo_flush(' .');
            }
            if ($customers && $limit) {
                $offset += $limit;
                echo ("<script>location.href='" . base_url() . "contabilita/primanota/generaSottoconti/all/$limit/$offset';</script>");
            }
        } else {
            // $this->db->query("DELETE FROM documenti_contabilita_sottoconti WHERE documenti_contabilita_sottoconti_id IN (SELECT customers_sottoconto FROM customers where customers_sottoconto IS NOT NULL) OR documenti_contabilita_sottoconti_conto IN (SELECT documenti_contabilita_conti_id FROM documenti_contabilita_conti WHERE documenti_contabilita_conti_clienti = 1 OR documenti_contabilita_conti_fornitori = 1)");
            // $this->db->query("UPDATE customers SET customers_sottoconto = null");
            // $this->apilib->clearCache();
            $return =
            $this->contab_cust->generaSottoconto($this->apilib->view('customers', $customer_id));
            echo_flush(' .');
        }
    }

    public function ricalcolaProgressiviIvaAcquisti($anno, $mese_da = 1)
    {
        //debug($this->apilib->view('prime_note', 6695),true);
        $prime_note_acquisti = $this->db->query("
            SELECT * FROM prime_note
            LEFT JOIN sezionali_iva ON (prime_note_sezionale = sezionali_iva_id)
            LEFT JOIN prime_note_tipo ON (sezionali_iva_tipo =  	prime_note_tipo_id)
            LEFT JOIN sezionali_iva_origine ON (sezionali_iva_origine_id = sezionali_iva_origine)

            WHERE
                prime_note_modello <> 1
                AND YEAR(prime_note_data_registrazione) = '$anno'
                AND MONTH(prime_note_data_registrazione) >= '$mese_da'
                AND (prime_note_tipo_value = 'Acquisti' OR prime_note_tipo_value = 'Vendite reverse')
            ORDER BY prime_note_sezionale, prime_note_data_registrazione
        ")->result_array();

        $prime_note = $this->db->query("
            SELECT * FROM prime_note

            WHERE
                prime_note_modello <> 1
                AND YEAR(prime_note_data_registrazione) = '$anno'
                AND MONTH(prime_note_data_registrazione) >= '$mese_da'
            ORDER BY prime_note_data_registrazione
        ")->result_array();
//debug($prime_note_acquisti, true);

        $c = 0;
        $count = count($prime_note_acquisti) + count($prime_note);
        $last_sezionale = $last_giorno = $protocollo_iva = false;

        //debug($prime_note,true);

        foreach ($prime_note_acquisti as $prima_nota) {
            $c++;

            //Se cambia il sezionale, riparto col protocollo iva
            if ($last_sezionale != $prima_nota['prime_note_sezionale']) {
                $last_sezionale = $prima_nota['prime_note_sezionale'];
                $protocollo_iva = 1;
            }

            $this->db
                ->where('prime_note_id', $prima_nota['prime_note_id'])
                ->update('prime_note', [
                    'prime_note_protocollo' => $protocollo_iva,
                ]);
            //debug("Aggiorno prima nota '{$prima_nota['prime_note_id']}' con protocollo '{$protocollo_iva}'");

            $protocollo_iva++;

            progress($c, $count);

        }

        $progressivo_giorno = false;
        $progressivo_annuo = 1;
        //Fatto questo devo comunque ricalcolare tutti i progressivi annui e giornalieri
        foreach ($prime_note as $prima_nota) {
            $c++;

            //Se cambia la data, riparto col progressivo giorno
            if ($last_giorno != substr($prima_nota['prime_note_data_registrazione'], 0, 10)) {
                $last_giorno = substr($prima_nota['prime_note_data_registrazione'], 0, 10);
                $progressivo_giorno = 1;
            }

            $this->db
                ->where('prime_note_id', $prima_nota['prime_note_id'])
                ->update('prime_note', [
                    'prime_note_progressivo_annuo' => $progressivo_annuo,
                    'prime_note_progressivo_giornaliero' => $progressivo_giorno,
                ]);
            //debug("Aggiorno prima nota '{$prima_nota['prime_note_id']}' con progressivo giorno '{$progressivo_giorno}' e progressivo anno '{$progressivo_annuo}'");

            $progressivo_giorno++;
            $progressivo_annuo++;
            progress($c, $count);

        }

        $this->apilib->clearCache();

    }

    public function generaRegistroIvaVenditeDefinitivo($anno, $trimestre, $mese = null)
    {

        //per prima cosa verifico che nei filtri siano stati selezionati periodo (e derivo l'anno), azienda e nient'altro
        $filtri = @$this->session->userdata(SESS_WHERE_DATA)['filter_stampe_contabili'];
        $azienda = false;
        $filtri_previsti = [
            'prime_note_data_registrazione',
            'prime_note_azienda',
            'prime_note_scadenza', //Sarebbe la data documento

        ];

        foreach ($filtri as $filtro) {
            $field_id = $filtro['field_id'];
            $value = $filtro['value'];
            if ($value == '-1') {
                continue;
            }
            $field_data = $this->db->query("SELECT * FROM fields LEFT JOIN fields_draw ON (fields_draw_fields_id = fields_id) WHERE fields_id = '$field_id'")->row_array();
            $field_name = $field_data['fields_name'];
            if (!in_array($field_name, $filtri_previsti)) {
                die("Svuotare il filtro '{$field_data['fields_draw_label']}' in quanto non previsto per le stampe definitive!");
            } else {
                if ($field_name == 'prime_note_azienda' && $value > 0) {
                    $azienda = $value;
                }

            }
        }
        if (!$azienda) {
            die('Impostare correttamente il filtro azienda!');
        }
        $impostazioni = $this->apilib->view('documenti_contabilita_settings', $azienda);
        if ($impostazioni['documenti_contabilita_settings_liquidazione_iva'] == 1) { //Liquidazione mensile
            $mese = $trimestre;
            $trimestre = null;
        } else { //trimestrale
        }

        $value_id = null;
        $layout_id = $this->layout->getLayoutByIdentifier('registro-iva-vendite');
        $layout = $this->layout->getLayout($layout_id);
        $dati = $this->datab->build_layout($layout_id, $value_id);

        $data = $this->prima_nota->getIvaData([
            'sezionali_iva_tipo = 1',
        ], true);

        //extract($data['vendite']);

        if (file_exists(FCPATH . "application/views/custom/layout/pdf.php")) {
            $view_content = $this->load->view("custom/layout/pdf", array('dati' => $dati, 'value_id' => $value_id), true);
        } else {
            $view_content = $this->load->view("layout/pdf", array('dati' => $dati, 'value_id' => $value_id), true);
        }

        $pdfFile = $this->layout->generate_pdf($view_content, "portrait", "", [], false, true);

        $contents = file_get_contents($pdfFile, true);
        $pdf_b64 = base64_encode($contents);

        $file_name = "{$anno}-{$mese}{$trimestre}-{$azienda}-iva-vendite.";

        if (!is_dir(FCPATH . "registri_contabili/$anno/")) {
            mkdir(FCPATH . "registri_contabili/$anno/", DIR_WRITE_MODE, true);
        }
        $fp = fopen(FCPATH . "registri_contabili/$anno/{$file_name}pdf", 'w+');
        fwrite($fp, $contents);

        $fpjson = fopen(FCPATH . "registri_contabili/$anno/{$file_name}json", 'w+');
        fwrite($fpjson, json_encode($data['vendite'], JSON_PRETTY_PRINT));

        //Se esiste un definitivo per questo periodo, aggiorno, altrimenti creo
        $exists = $this->apilib->searchFirst('contabilita_stampe_definitive', [
            'contabilita_stampe_definitive_anno' => $anno,
            'contabilita_stampe_definitive_azienda' => $azienda,
            'contabilita_stampe_definitive_mese' => $mese,
            'contabilita_stampe_definitive_trimestre' => $trimestre,
        ]);
        if ($exists) {
            $stampa_definitiva_id = $exists['contabilita_stampe_definitive_id'];
            $this->apilib->edit('contabilita_stampe_definitive', $stampa_definitiva_id, [
                'contabilita_stampe_definitive_iva_vendite_pdf' => "../registri_contabili/$anno/{$file_name}pdf",
                'contabilita_stampe_definitive_iva_vendite_pdf_b64' => $pdf_b64,
            ]);
        } else {
            $stampa_definitiva_id = $this->apilib->create('contabilita_stampe_definitive', [
                'contabilita_stampe_definitive_anno' => $anno,
                'contabilita_stampe_definitive_azienda' => $azienda,
                'contabilita_stampe_definitive_mese' => $mese,
                'contabilita_stampe_definitive_trimestre' => $trimestre,
                //'contabilita_stampe_definitive_raw_data_vendite' => json_encode($data['vendite']),
                'contabilita_stampe_definitive_iva_vendite_pdf' => "../registri_contabili/$anno/{$file_name}pdf",
                'contabilita_stampe_definitive_iva_vendite_pdf_b64' => $pdf_b64,
            ], false);
        }

        //TODO: associo le registrazioni prima nota a questa stampa (così da bloccarle)
        $sez = 0;
        foreach ($data['vendite']['primeNoteDataGroupSezionale'] as $sezionale => $prime_note) {
            $sez++;

            $c = 0;
            $total = count($prime_note);
            foreach ($prime_note as $prima_nota) {
                $c++;

                $this->db
                    ->where('prime_note_id', $prima_nota['prime_note_id'])
                    ->update('prime_note', [
                        'prime_note_stampa_definitiva_vendite' => $stampa_definitiva_id,
                    ]);
                progress($c, $total, "Blocco registrazioni sezionale '$sezionale'");
            }

        }
        $this->apilib->clearCache();
        fclose($fp);
        fclose($fpjson);
        // header('Content-Type: application/pdf');
        // header('Content-disposition: inline; filename="' . $file_name . time() . '.pdf"');
        // $this->layout->setLayoutModule();
        // echo base64_decode($pdf_b64);

        echo "<script>location.href='" . base_url() . "main/layout/stampe-contabilita/?anno={$anno}&mese={$mese}&trimestre={$trimestre}';</script>";

    }

    public function generaRegistroIvaAcquistiDefinitivo($anno, $trimestre, $mese = null)
    {

        //per prima cosa verifico che nei filtri siano stati selezionati periodo (e derivo l'anno), azienda e nient'altro
        $filtri = @$this->session->userdata(SESS_WHERE_DATA)['filter_stampe_contabili'];
        $azienda = false;
        $filtri_previsti = [
            'prime_note_data_registrazione',
            'prime_note_azienda',
            'prime_note_scadenza', //Sarebbe la data documento

        ];

        foreach ($filtri as $filtro) {
            $field_id = $filtro['field_id'];
            $value = $filtro['value'];
            if ($value == '-1') {
                continue;
            }
            $field_data = $this->db->query("SELECT * FROM fields LEFT JOIN fields_draw ON (fields_draw_fields_id = fields_id) WHERE fields_id = '$field_id'")->row_array();
            $field_name = $field_data['fields_name'];
            if (!in_array($field_name, $filtri_previsti)) {
                die("Svuotare il filtro '{$field_data['fields_draw_label']}' in quanto non previsto per le stampe definitive!");
            } else {
                if ($field_name == 'prime_note_azienda' && $value > 0) {
                    $azienda = $value;
                }

            }
        }
        if (!$azienda) {
            die('Impostare correttamente il filtro azienda!');
        }
        $impostazioni = $this->apilib->view('documenti_contabilita_settings', $azienda);
        if ($impostazioni['documenti_contabilita_settings_liquidazione_iva'] == 1) { //Liquidazione mensile
            $mese = $trimestre;
            $trimestre = null;
        } else { //trimestrale
        }

        $value_id = null;
        $layout_id = $this->layout->getLayoutByIdentifier('registro-iva-acquisti');
        $layout = $this->layout->getLayout($layout_id);
        $dati = $this->datab->build_layout($layout_id, $value_id);

        $data = $this->prima_nota->getIvaData([
            'sezionali_iva_tipo = 2',
        ], true);

        //extract($data['vendite']);

        if (file_exists(FCPATH . "application/views/custom/layout/pdf.php")) {
            $view_content = $this->load->view("custom/layout/pdf", array('dati' => $dati, 'value_id' => $value_id), true);
        } else {
            $view_content = $this->load->view("layout/pdf", array('dati' => $dati, 'value_id' => $value_id), true);
        }

        $pdfFile = $this->layout->generate_pdf($view_content, "portrait", "", [], false, true);

        $contents = file_get_contents($pdfFile, true);

        //die($view_content);

        $pdf_b64 = base64_encode($contents);

        $file_name = "{$anno}-{$mese}{$trimestre}-{$azienda}-iva-acquisti.";

        if (!is_dir(FCPATH . "registri_contabili/$anno/")) {
            mkdir(FCPATH . "registri_contabili/$anno/", DIR_WRITE_MODE, true);
        }
        $fp = fopen(FCPATH . "registri_contabili/$anno/{$file_name}pdf", 'w+');
        fwrite($fp, $contents);

        $fpjson = fopen(FCPATH . "registri_contabili/$anno/{$file_name}json", 'w+');
        fwrite($fpjson, json_encode($data['vendite'], JSON_PRETTY_PRINT));

        //Se esiste un definitivo per questo periodo, aggiorno, altrimenti creo
        $exists = $this->apilib->searchFirst('contabilita_stampe_definitive', [
            'contabilita_stampe_definitive_anno' => $anno,
            'contabilita_stampe_definitive_azienda' => $azienda,
            'contabilita_stampe_definitive_mese' => $mese,
            'contabilita_stampe_definitive_trimestre' => $trimestre,
        ]);
        if ($exists) {
            $stampa_definitiva_id = $exists['contabilita_stampe_definitive_id'];
            $this->apilib->edit('contabilita_stampe_definitive', $stampa_definitiva_id, [
                'contabilita_stampe_definitive_iva_acquisti_pdf' => "../registri_contabili/$anno/{$file_name}pdf",
                'contabilita_stampe_definitive_iva_acquisti_pdf_b64' => $pdf_b64,
            ]);
        } else {
            $stampa_definitiva_id = $this->apilib->create('contabilita_stampe_definitive', [
                'contabilita_stampe_definitive_anno' => $anno,
                'contabilita_stampe_definitive_azienda' => $azienda,
                'contabilita_stampe_definitive_mese' => $mese,
                'contabilita_stampe_definitive_trimestre' => $trimestre,
                //'contabilita_stampe_definitive_raw_data_vendite' => json_encode($data['vendite']),
                'contabilita_stampe_definitive_iva_acquisti_pdf' => "../registri_contabili/$anno/{$file_name}pdf",
                'contabilita_stampe_definitive_iva_acquisti_pdf_b64' => $pdf_b64,
            ], false);
        }

        //TODO: associo le registrazioni prima nota a questa stampa (così da bloccarle)
        $sez = 0;
        foreach ($data['acquisti']['primeNoteDataGroupSezionale'] as $sezionale => $prime_note) {
            $sez++;

            $c = 0;
            $total = count($prime_note);
            foreach ($prime_note as $prima_nota) {
                $c++;

                $this->db
                    ->where('prime_note_id', $prima_nota['prime_note_id'])
                    ->update('prime_note', [
                        'prime_note_stampa_definitiva_acquisti' => $stampa_definitiva_id,
                    ]);
                progress($c, $total, "Blocco registrazioni sezionale '$sezionale'");
            }

        }
        $this->apilib->clearCache();
        fclose($fp);
        fclose($fpjson);
        // header('Content-Type: application/pdf');
        // header('Content-disposition: inline; filename="' . $file_name . time() . '.pdf"');
        // $this->layout->setLayoutModule();
        // echo base64_decode($pdf_b64);

        echo "<script>location.href='" . base_url() . "main/layout/stampe-contabilita/?anno={$anno}&mese={$mese}&trimestre={$trimestre}';</script>";

    }

    public function generaLiquidazioneIvaDefinitiva($anno, $trimestre, $mese = null)
    {
        $prima_nota_liquidazione_exists = $this->apilib->searchFirst('prime_note', [
            'prime_note_causali_tipo' => '7',
            //'prime_note_stampa_definitiva IS NULL',
            'prime_note_modello <> 1',

        ], 0, 'prime_note_id DESC');
        if (!$prima_nota_liquidazione_exists) {
            die('Non trovo la registrazione in prima nota della liquidazione iva.');
        }

        $filtri = @$this->session->userdata(SESS_WHERE_DATA)['filter_stampe_contabili'];
        $azienda = false;
        $filtri_previsti = [
            'prime_note_data_registrazione',
            'prime_note_azienda',
            'prime_note_scadenza', //Sarebbe la data documento

        ];

        foreach ($filtri as $filtro) {
            $field_id = $filtro['field_id'];
            $value = $filtro['value'];
            if ($value == '-1') {
                continue;
            }
            $field_data = $this->db->query("SELECT * FROM fields LEFT JOIN fields_draw ON (fields_draw_fields_id = fields_id) WHERE fields_id = '$field_id'")->row_array();
            $field_name = $field_data['fields_name'];
            if (!in_array($field_name, $filtri_previsti)) {
                die("Svuotare il filtro '{$field_data['fields_draw_label']}' in quanto non previsto per le stampe definitive!");
            } else {
                if ($field_name == 'prime_note_azienda' && $value > 0) {
                    $azienda = $value;
                }

            }
        }
        if (!$azienda) {
            die('Impostare correttamente il filtro azienda!');
        }
        $impostazioni = $this->apilib->view('documenti_contabilita_settings', $azienda);
        if ($impostazioni['documenti_contabilita_settings_liquidazione_iva'] == 1) { //Liquidazione mensile
            $mese = $trimestre;
            $trimestre = null;
        } else { //trimestrale
        }

        $value_id = null;
        $layout_id = $this->layout->getLayoutByIdentifier('liquidazione-iva');
        $layout = $this->layout->getLayout($layout_id);
        $dati = $this->datab->build_layout($layout_id, $value_id);

        if (file_exists(FCPATH . "application/views/custom/layout/pdf.php")) {
            $view_content = $this->load->view("custom/layout/pdf", array('dati' => $dati, 'value_id' => $value_id), true);
        } else {
            $view_content = $this->load->view("layout/pdf", array('dati' => $dati, 'value_id' => $value_id), true);
        }

        $pdfFile = $this->layout->generate_pdf($view_content, "portrait", "", [], false, true);

        $contents = file_get_contents($pdfFile, true);

        //die($view_content);

        $pdf_b64 = base64_encode($contents);

        $file_name = "{$anno}-{$mese}{$trimestre}-{$azienda}-liquidazione-iva.";

        if (!is_dir(FCPATH . "registri_contabili/$anno/")) {
            mkdir(FCPATH . "registri_contabili/$anno/", DIR_WRITE_MODE, true);
        }
        $fp = fopen(FCPATH . "registri_contabili/$anno/{$file_name}pdf", 'w+');
        fwrite($fp, $contents);

        $fpjson = fopen(FCPATH . "registri_contabili/$anno/{$file_name}json", 'w+');
        fwrite($fpjson, json_encode($data['vendite'], JSON_PRETTY_PRINT));

        //Se esiste un definitivo per questo periodo, aggiorno, altrimenti creo
        $exists = $this->apilib->searchFirst('contabilita_stampe_definitive', [
            'contabilita_stampe_definitive_anno' => $anno,
            'contabilita_stampe_definitive_azienda' => $azienda,
            'contabilita_stampe_definitive_mese' => $mese,
            'contabilita_stampe_definitive_trimestre' => $trimestre,
        ]);
        if ($exists) {
            $this->apilib->edit('contabilita_stampe_definitive', $exists['contabilita_stampe_definitive_id'], [
                'contabilita_stampe_definitive_liquidazione_iva_pdf' => "../registri_contabili/$anno/{$file_name}pdf",
                'contabilita_stampe_definitive_liquidazione_iva_pdf_b64' => $pdf_b64,
            ]);
        } else {
            $stampa_definitiva_id = $this->apilib->create('contabilita_stampe_definitive', [
                'contabilita_stampe_definitive_anno' => $anno,
                'contabilita_stampe_definitive_azienda' => $azienda,
                'contabilita_stampe_definitive_mese' => $mese,
                'contabilita_stampe_definitive_trimestre' => $trimestre,
                //'contabilita_stampe_definitive_raw_data_vendite' => json_encode($data['vendite']),
                'contabilita_stampe_definitive_liquidazione_iva_pdf' => "../registri_contabili/$anno/{$file_name}pdf",
                'contabilita_stampe_definitive_liquidazione_iva_pdf_b64' => $pdf_b64,
            ], false);
        }

        //$this->apilib->clearCache();
        fclose($fp);
        fclose($fpjson);
        // header('Content-Type: application/pdf');
        // header('Content-disposition: inline; filename="' . $file_name . time() . '.pdf"');
        // $this->layout->setLayoutModule();
        // echo base64_decode($pdf_b64);

        echo "<script>location.href='" . base_url() . "main/layout/stampe-contabilita/?anno={$anno}&mese={$mese}&trimestre={$trimestre}';</script>";
    }
    public function download_zip($riga_id)
    {

        $doc = $this->db->query("SELECT * FROM contabilita_stampe_definitive WHERE contabilita_stampe_definitive_id = '{$riga_id}'")->row();
        //dd($dettagli_riga);
        $zip = new ZipArchive;

        $filename = 'contabilita_stampe_definitive' . $riga_id . '.zip';

        if (!is_dir(FCPATH . "registri_contabili/zip/")) {
            mkdir(FCPATH . "registri_contabili/zip/", DIR_WRITE_MODE, true);
        }
        $destination_file = FCPATH . 'registri_contabili/zip/' . $filename;
        if (!file_exists($destination_file)) {
            @touch($destination_file);
        } else {
            unlink($destination_file);
            @touch($destination_file);
        }

        if ($zip->open($destination_file) === true) {
            $zip->addEmptyDir('contabilita_stampe_definitive');

            if (!empty($doc->contabilita_stampe_definitive_iva_vendite_pdf)) {
                //tolgo i primi 3 caratteri ../
                $nome_pdf = substr($doc->contabilita_stampe_definitive_iva_vendite_pdf, 3);
                $contents = file_get_contents(FCPATH . $nome_pdf);
                if ($contents === false) {
                    return false;
                }
                $zip->addFromString('contabilita_stampe_definitive/vendite.pdf', $contents);
            }
            if (!empty($doc->contabilita_stampe_definitive_iva_acquisti_pdf)) {
                //tolgo i primi 3 caratteri ../
                $nome_pdf = substr($doc->contabilita_stampe_definitive_iva_acquisti_pdf, 3);
                $contents = file_get_contents(FCPATH . $nome_pdf);
                if ($contents === false) {
                    return false;
                }
                $zip->addFromString('contabilita_stampe_definitive/acquisti.pdf', $contents);
            }
            if (!empty($doc->contabilita_stampe_definitive_liquidazione_iva_pdf)) {
                //tolgo i primi 3 caratteri ../
                $nome_pdf = substr($doc->contabilita_stampe_definitive_liquidazione_iva_pdf, 3);
                $contents = file_get_contents(FCPATH . $nome_pdf);
                if ($contents === false) {
                    return false;
                }
                $zip->addFromString('contabilita_stampe_definitive/liquidazione_iva.pdf', $contents);
            }
            if (!empty($doc->contabilita_stampe_definitive_libro_giornale_pdf)) {
                //tolgo i primi 3 caratteri ../
                $nome_pdf = substr($doc->contabilita_stampe_definitive_libro_giornale_pdf, 3);
                $contents = file_get_contents(FCPATH . $nome_pdf);
                if ($contents === false) {
                    return false;
                }
                $zip->addFromString('contabilita_stampe_definitive/libro_giornale.pdf', $contents);
            }

            $zip->close();
            if (headers_sent()) {
                echo 'HTTP header already sent';
            } else {
                if (!is_file($destination_file)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                    echo 'File not found';
                } else if (!is_readable($destination_file)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
                    echo 'File not readable';
                } else {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
                    header("Content-Type: application/zip");
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-Length: " . filesize($destination_file));
                    header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
                    readfile($destination_file);
                    unlink($destination_file);
                }
            }
        } else {
            die('Errore nello generare lo zip');
        }

    }
}
