<?php

class Prima_nota extends CI_Model
{
    public function getPrimaNotaRighe($causale_id, $is_fattura, $doc_id = false, $prima_nota_id = null, $modello_selezionato = null)
    {

        $causale = $this->apilib->view('prime_note_causali', $causale_id);

        $righe = [];

        if ($doc_id) {
            if ($is_fattura) {
                $documento =
                $this->getDocumento($doc_id);
            } else {
                $spesa = $this->getSpesa($doc_id);
            }
        }

        //debug($modello_selezionato);

        if ($modello_selezionato) {
            $modello =
            $this->db->query("
                    SELECT * FROM prime_note WHERE
                    prime_note_id IN (
                        SELECT
                            prime_note_modelli_prima_nota
                        FROM
                            prime_note_modelli
                        WHERE
                            prime_note_modelli_id = '$modello_selezionato'
                           )
                            ")->row_array();
        } else {
            if ($doc_id) {
                if ($is_fattura) {

                    if ($documento['documenti_contabilita_tipo'] == 1) { //Fattura
                        $prime_note_mappature_tipo_identifier = 'FOO';
                        if (empty($documento['customers_country_id_countries_name']) || $documento['customers_country_id_countries_name'] == 'Italy') {
                            $prime_note_mappature_tipo_identifier = 'modello_fattura_vendita_italia';
                        } else {
                            $prime_note_mappature_tipo_identifier = 'modello_fattura_vendita_intra';
                        }

                        $modello = $this->db->query("SELECT * FROM prime_note WHERE
                    prime_note_id IN (
                        SELECT
                            prime_note_modelli_prima_nota
                        FROM
                            prime_note_modelli
                        WHERE
                            prime_note_modelli_tipo IN (
                                SELECT prime_note_mappature_id
                                FROM prime_note_mappature
                                LEFT JOIN prime_note_mappature_tipo ON ( prime_note_mappature_tipo_id = prime_note_mappature_chiave)
                                WHERE prime_note_mappature_tipo_identifier = '$prime_note_mappature_tipo_identifier'
                            )
                    )")->row_array();
                    } elseif ($documento['documenti_contabilita_tipo'] == 4) { //E' una nota di credito
                        $modello = $this->db->query("SELECT * FROM prime_note WHERE
                    prime_note_id IN (
                        SELECT
                            prime_note_modelli_prima_nota
                        FROM
                            prime_note_modelli
                        WHERE
                            prime_note_modelli_tipo IN (
                                SELECT prime_note_mappature_id
                                FROM prime_note_mappature
                                LEFT JOIN prime_note_mappature_tipo ON ( prime_note_mappature_tipo_id = prime_note_mappature_chiave)
                                WHERE prime_note_mappature_tipo_identifier = 'modello_nota_di_credito_vendita_ita'
                            )
                    )")->row_array();
                    }
                } else {

                    $prime_note_mappature_tipo_identifier = 'FOO';
                    if (in_array($spesa['spese_tipologia_fatturazione'], [4])) { //Nota di credito
                        $prime_note_mappature_tipo_identifier = 'modello_nota_di_credito_acquisto_ita';
                    } else {
                        $prime_note_mappature_tipo_identifier = 'modello_fattura_acquisto_italia';
                    }

                    $modello = $this->db->query("SELECT * FROM prime_note WHERE
                    prime_note_id IN (
                        SELECT
                            prime_note_modelli_prima_nota
                        FROM
                            prime_note_modelli
                        WHERE
                            prime_note_modelli_tipo IN (
                                SELECT prime_note_mappature_id
                                FROM prime_note_mappature
                                LEFT JOIN prime_note_mappature_tipo ON ( prime_note_mappature_tipo_id = prime_note_mappature_chiave)
                                WHERE prime_note_mappature_tipo_identifier = '$prime_note_mappature_tipo_identifier'
                            )
                    )")->row_array();
                }
            }
        }
        $modello_registrazioni = array_key_map_data($this->db->where('prime_note_registrazioni_prima_nota', $modello['prime_note_id'])->get('prime_note_registrazioni')->result_array(), 'prime_note_registrazioni_numero_riga');
        // debug($doc_id);
        // debug($is_fattura);
        // debug($prima_nota_id);
        // debug($modello_selezionato);
        // debug($modello_registrazioni);
        foreach ($modello_registrazioni as $numero_riga => $registrazione) {
            // debug($causale);
            //debug($conti[$causale['prime_note_causale_conto_dare']], true);
            $riga = $registrazione;
            $riga['prime_note_registrazioni_prima_nota'] = $prima_nota_id;
            $riga['prime_note_registrazioni_numero_riga'] = $numero_riga;
            unset($riga['prime_note_registrazioni_creation_date']);
            unset($riga['prime_note_registrazioni_modified_date']);
            unset($riga['prime_note_registrazioni_id']);
            $riga['prime_note_registrazioni_importo_dare'] = '';
            $riga['prime_note_registrazioni_importo_avere'] = '';

            if ($registrazione['prime_note_registrazioni_mastro_avere_codice']) {
                $dare_o_avere = 'avere';
            } else {
                $dare_o_avere = 'dare';
            }

            if ($doc_id) {

                if ($is_fattura) {

                    //$customer = $this->apilib->view('customers', $documento['documenti_contabilita_customer']);
                    if ($documento['documenti_contabilita_tipo'] == 4 || $causale['prime_note_causali_tipo'] == 3) { //Nota di credito o se è il pagamento di una fattura (si inverte tutto il dare con l'avere e viceversa)
                        if ($numero_riga == 1) { //Totale
                            $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_totale'];
                            if ($documento['sottoconto']) {
                                $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = $documento['sottoconto_codice_completo'];

                                $riga['prime_note_registrazioni_sottoconto_' . $dare_o_avere . '_descrizione'] = $documento['sottoconto_descrizione'];
                            }
                        } elseif ($numero_riga == 2) { //Iva

                            if ($causale['prime_note_causali_tipo'] == 3 || count($modello_registrazioni) == 2) { //I movimenti bancari hanno solo due righe
                                $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_totale'];
                            } else {
                                $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_iva'];
                            }
                        } elseif ($numero_riga == 3) { //Imponibile
                            $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_imponibile'];

                            if ($documento['contropartita_sottoconto']) {
                                $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = "{$documento['contropartita_mastro']}.{$documento['contropartita_conto']}.{$documento['contropartita_sottoconto']}";
                            }
                        }
                    } else {
                        if ($numero_riga == 1) { //Totale
                            $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_totale'];
                            if ($documento['sottoconto']) {
                                $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = $documento['sottoconto_codice_completo'];

                                $riga['prime_note_registrazioni_sottoconto_' . $dare_o_avere . '_descrizione'] = $documento['sottoconto_descrizione'];
                            }
                        } elseif ($numero_riga == 2) { //Iva

                            if (count($modello_registrazioni) == 2) { //I movimenti bancari hanno solo due righe
                                $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_totale'];
                            } else {
                                $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_iva'];
                            }
                        } elseif ($numero_riga == 3) { //Imponibile
                            $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $documento['documenti_contabilita_imponibile'];

                            if ($documento['contropartita_sottoconto']) {
                                $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = "{$documento['contropartita_mastro']}.{$documento['contropartita_conto']}.{$documento['contropartita_sottoconto']}";
                            }
                        }
                    }
                } else {

                    //non devo forzare io dove mettere (dare/avere) ma devo dedurlo dal modello!
                    //debug($registrazione);
                    // debug($spesa['spese_totale']);
                    // debug($spesa['mastro'], true);
                    //$articoli = $this->apilib->search('spese_articoli', ['spese_articoli_spesa' => $doc_id]);
                    $articoli = $this->db->query(
                        "SELECT * FROM `spese_articoli`
                            WHERE spese_articoli_spesa = '$doc_id'
                    ")->result_array();

                    //Se non c'è iva (come nel caso di intrastat), la forzo al 22 e ricalcolo tutto....
                    if (empty($articoli) && $spesa['spese_iva'] <= 0) {
                        $spesa['spese_imponibile'] = $spesa['spese_totale'];
                        $spesa['spese_iva'] = $spesa['spese_imponibile'] * 0.22;
                        $spesa['spese_totale'] = $spesa['spese_totale'] * 1.22;
                    }

                    //debug($spesa, true);

                    if ($numero_riga == 1) { //Totale
                        $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $spesa['spese_totale'];

                        //Sovrascrivo il sottoconto avere con quello del fornitore
                        if ($spesa['mastro']) {
                            $riga['prime_note_registrazioni_mastro_' . $dare_o_avere . '_codice'] = $spesa['mastro'];
                        }
                        if ($spesa['conto']) {
                            $riga['prime_note_registrazioni_conto_' . $dare_o_avere . '_codice'] = $spesa['conto'];
                        }
                        //Importo il sottoconto se e solo se il modello non ha già il sottoconto forzato
                        if ($spesa['sottoconto'] && !$registrazione['prime_note_registrazioni_sottoconto_' . $dare_o_avere]) {
                            $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = $spesa['sottoconto_codice_completo'];
                        }
                    } elseif ($numero_riga == 2) { //Iva

                        $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $spesa['spese_iva'];
                        //Sovrascrivo il sottoconto avere con quello del fornitore
                        // debug($spesa['mastro']);
                        // debug($registrazione['prime_note_registrazioni_mastro_' . $dare_o_avere . '_codice']);
                        if ($spesa['mastro'] == $registrazione['prime_note_registrazioni_mastro_' . $dare_o_avere . '_codice']) {
                            $riga['prime_note_registrazioni_conto_' . $dare_o_avere . '_codice'] = $spesa['conto'];
                            $riga['prime_note_registrazioni_sottoconto_' . $dare_o_avere . '_codice'] = $spesa['sottoconto'];
                            $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = $spesa['sottoconto_codice_completo'];
                        }
                    } elseif ($numero_riga == 3) { //Imponibile
                        $riga['prime_note_registrazioni_importo_' . $dare_o_avere] = $spesa['spese_imponibile'];

                        if ($spesa['contropartita_sottoconto'] && !$registrazione['prime_note_registrazioni_sottoconto_' . $dare_o_avere]) {
                            $riga['prime_note_registrazioni_codice_' . $dare_o_avere . '_testuale'] = $spesa['contropartita_sottoconto_codice_completo'];
                        }
                    }
                }
            }

            $righe[] = $riga;
        }

        return $righe;
    }

    public function getPrimaNotaRigheIva($is_fattura, $doc_id = false, $prima_nota_id = null)
    {
        $righe_iva = [];
        $numero_riga = 1;

        $aliquote_iva = array_key_value_map($this->apilib->search('iva'), 'iva_valore', 'iva_id');

        if ($doc_id) {
            if ($is_fattura) {
                $articoli = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $doc_id]);
                foreach ($articoli as $articolo) {

                    if ($articolo['documenti_contabilita_articoli_imponibile'] <= 0) {
                        $articolo['documenti_contabilita_articoli_imponibile'] = $articolo['documenti_contabilita_articoli_importo_totale'] - $articolo['documenti_contabilita_articoli_iva'];
                    }
                    if (empty($righe_iva[$articolo['iva_valore']])) {
                        //debug($articolo, true);
                        $righe_iva[$articolo['iva_valore']] = [
                            'prime_note_righe_iva_imponibile' => $articolo['documenti_contabilita_articoli_imponibile'],
                            'prime_note_righe_iva_totale' => $articolo['documenti_contabilita_articoli_importo_totale'],
                            'prime_note_righe_iva_importo_iva' => $articolo['documenti_contabilita_articoli_iva'],
                            'prime_note_righe_iva_iva' => $articolo['documenti_contabilita_articoli_iva_id'],
                            'prime_note_righe_iva_iva_valore' => $articolo['iva_valore'],
                            'prime_note_righe_iva_riga' => $numero_riga++,
                            'prime_note_righe_iva_indetraibilie_perc' => 0,
                            'prime_note_righe_iva_imponibile_indet' => 0,
                            'prime_note_righe_iva_iva_valore_indet' => 0,
                            'prime_note_righe_iva_natura' => (!empty($articolo['iva_codice'])) ? $articolo['iva_codice'] : '',

                        ];
                    } else {
                        $righe_iva[$articolo['iva_valore']]['prime_note_righe_iva_imponibile'] += (float) $articolo['documenti_contabilita_articoli_imponibile'];
                        $righe_iva[$articolo['iva_valore']]['prime_note_righe_iva_totale'] += $articolo['documenti_contabilita_articoli_importo_totale'];
                        $righe_iva[$articolo['iva_valore']]['prime_note_righe_iva_importo_iva'] += $articolo['documenti_contabilita_articoli_iva'];
                    }
                }
            } else {
                $spesa = $this->apilib->view('spese', $doc_id);

                //debug($articoli, true);
                if ($spesa['spese_importata_da_xml']) {

                    $sdi_xml_json = json_decode($spesa['spese_json'], true);
                    $righe = $sdi_xml_json['FatturaElettronicaBody']['DatiBeniServizi']['DatiRiepilogo'];
                    //debug($sdi_xml_json, true);
                    if (is_array($righe[0])) {
                        foreach ($righe as $riga) {
                            // debug($aliquote_iva);
                            //debug($riga, true);
                            $righe_iva[$riga['AliquotaIVA']] = [
                                'prime_note_righe_iva_imponibile' => $riga['ImponibileImporto'],
                                'prime_note_righe_iva_totale' => $riga['ImponibileImporto'] + $riga['Imposta'],
                                'prime_note_righe_iva_importo_iva' => $riga['Imposta'],
                                'prime_note_righe_iva_riga' => $numero_riga++,
                                'prime_note_righe_iva_iva' => $aliquote_iva[$riga['AliquotaIVA']],
                                'prime_note_righe_iva_iva_valore' => $riga['AliquotaIVA'],
                                'prime_note_righe_iva_indetraibilie_perc' => 0,
                                'prime_note_righe_iva_imponibile_indet' => 0,
                                'prime_note_righe_iva_iva_valore_indet' => 0,
                                'prime_note_righe_iva_natura' => (!empty($riga['Natura'])) ? $riga['Natura'] : '',
                            ];
                        }
                    } else {

                        $riga = $righe;

                        // debug($aliquote_iva);
                        // debug($riga, true);

                        $righe_iva[$riga['AliquotaIVA']] = [
                            'prime_note_righe_iva_imponibile' => $riga['ImponibileImporto'],
                            'prime_note_righe_iva_totale' => $riga['ImponibileImporto'] + $riga['Imposta'],
                            'prime_note_righe_iva_importo_iva' => $riga['Imposta'],
                            'prime_note_righe_iva_riga' => $numero_riga++,
                            'prime_note_righe_iva_iva' => $aliquote_iva[$riga['AliquotaIVA']],
                            'prime_note_righe_iva_iva_valore' => $riga['AliquotaIVA'],
                            'prime_note_righe_iva_indetraibilie_perc' => 0,
                            'prime_note_righe_iva_imponibile_indet' => 0,
                            'prime_note_righe_iva_imponibile' => $riga['ImponibileImporto'],
                            'prime_note_righe_iva_iva_valore_indet' => 0,
                            'prime_note_righe_iva_natura' => (!empty($riga['Natura'])) ? $riga['Natura'] : '',
                        ];
                    }
                } else {

                    //$articoli = $this->apilib->search('spese_articoli', ['spese_articoli_spesa' => $doc_id]);

                    $articoli = $this->db->query(
                        "SELECT * FROM `spese_articoli`
                            WHERE spese_articoli_spesa = '$doc_id'
                    ")->result_array();

                    if ($articoli) {
                        foreach ($articoli as $articolo) {

                            if (empty($righe_iva[$articolo['spese_articoli_iva_perc']])) {
                                //debug($articolo, true);
                                $righe_iva[$articolo['spese_articoli_iva_perc']] = [
                                    'imponibile' => $articolo['spese_articoli_imponibile'],
                                    'totale' => $articolo['spese_articoli_importo_totale'],
                                    'iva' => $articolo['spese_articoli_iva_perc'],
                                    'numero_riga' => $numero_riga++,
                                    'iva_id' => null,
                                    //'percentuale_indetraibilita' => null,
                                ];
                            } else {
                                $righe_iva[$articolo['spese_articoli_iva_perc']]['imponibile'] += $articolo['spese_articoli_imponibile'];
                                $righe_iva[$articolo['spese_articoli_iva_perc']]['totale'] += $articolo['spese_articoli_importo_totale'];
                                $righe_iva[$articolo['spese_articoli_iva_perc']]['iva'] += $articolo['spese_articoli_iva'];
                            }
                        }
                    } else {
                        //Se arrivo qui vuol dire che è una spesa inserita a mano solo con i totali e non con "registra i singoli articoli"

                        //Se non c'è iva (come nel caso di intrastat), la forzo al 22 e ricalcolo tutto....
                        if ($spesa['spese_iva'] <= 0) {
                            $spesa['spese_imponibile'] = $spesa['spese_totale'];
                            $spesa['spese_iva'] = $spesa['spese_imponibile'] * 0.22;
                            $spesa['spese_totale'] = $spesa['spese_totale'] * 1.22;
                        }

                        $righe_iva['22.00'] = [
                            'prime_note_righe_iva_imponibile' => $spesa['spese_imponibile'],
                            'prime_note_righe_iva_totale' => $spesa['spese_iva'],
                            'prime_note_righe_iva_importo_iva' => $spesa['spese_iva'],
                            'prime_note_righe_iva_riga' => $numero_riga++,
                            'prime_note_righe_iva_iva' => $aliquote_iva['22.00'],
                            'prime_note_righe_iva_iva_valore' => 22.00,
                            'prime_note_righe_iva_indetraibilie_perc' => 0,
                            'prime_note_righe_iva_imponibile_indet' => 0,
                            'prime_note_righe_iva_imponibile' => $spesa['spese_imponibile'],
                            'prime_note_righe_iva_iva_valore_indet' => 0,
                            'prime_note_righe_iva_natura' => '',
                        ];
                    }
                }
            }
        }

        return $righe_iva;
    }

    public function getDocumento($documento_id)
    {
        $documento = $this->db
            ->select('documenti_contabilita.*,customers.*,
                m.documenti_contabilita_mastri_codice as mastro,
                c.documenti_contabilita_conti_codice as conto,
                s.documenti_contabilita_sottoconti_codice as sottoconto,
                s.documenti_contabilita_sottoconti_codice_completo as sottoconto_codice_completo,
                s.documenti_contabilita_sottoconti_descrizione as sottoconto_descrizione,

                cpm.documenti_contabilita_mastri_codice as contropartita_mastro,
                cpc.documenti_contabilita_conti_codice as contropartita_conto,
                cps.documenti_contabilita_sottoconti_codice as contropartita_sottoconto,
                cps.documenti_contabilita_sottoconti_codice_completo as contropartita_sottoconto_codice_completo,



                ')
            ->where('documenti_contabilita_id', $documento_id)

            ->join('customers', 'customers_id = documenti_contabilita_customer_id', 'LEFT')
            ->join('documenti_contabilita_mastri as m', 'm.documenti_contabilita_mastri_id = customers_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as c', 'c.documenti_contabilita_conti_id = customers_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as s', 's.documenti_contabilita_sottoconti_id = customers_sottoconto', 'LEFT')

            ->join('documenti_contabilita_mastri as cpm', 'cpm.documenti_contabilita_mastri_id = customers_contropartita_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as cpc', 'cpc.documenti_contabilita_conti_id = customers_contropartita_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as cps', 'cps.documenti_contabilita_sottoconti_id = customers_contropartita_sottoconto', 'LEFT')

            ->get('documenti_contabilita')
            ->row_array();
        return $documento;
    }
    public function getSpesa($spesa_id)
    {
        $spesa = $this->db
            ->select('spese.*,customers.*,
                m.documenti_contabilita_mastri_codice as mastro,
                c.documenti_contabilita_conti_codice as conto,
                s.documenti_contabilita_sottoconti_codice as sottoconto,
                s.documenti_contabilita_sottoconti_codice_completo as sottoconto_codice_completo,

                cpm.documenti_contabilita_mastri_codice as contropartita_mastro,
                cpc.documenti_contabilita_conti_codice as contropartita_conto,
                cps.documenti_contabilita_sottoconti_codice as contropartita_sottoconto,
                cps.documenti_contabilita_sottoconti_codice_completo as contropartita_sottoconto_codice_completo,

                ')
            ->where('spese_id', $spesa_id)

            ->join('customers', 'customers_id = spese_customer_id', 'LEFT')
            ->join('documenti_contabilita_mastri as m', 'm.documenti_contabilita_mastri_id = customers_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as c', 'c.documenti_contabilita_conti_id = customers_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as s', 's.documenti_contabilita_sottoconti_id = customers_sottoconto', 'LEFT')

            ->join('documenti_contabilita_mastri as cpm', 'cpm.documenti_contabilita_mastri_id = customers_contropartita_mastro', 'LEFT')
            ->join('documenti_contabilita_conti as cpc', 'cpc.documenti_contabilita_conti_id = customers_contropartita_conto', 'LEFT')
            ->join('documenti_contabilita_sottoconti as cps', 'cps.documenti_contabilita_sottoconti_id = customers_contropartita_sottoconto', 'LEFT')

            ->get('spese')
            ->row_array();
        return $spesa;
    }

    public function getCodiceCompleto($registrazione, $what = 'avere', $separator = '.', $append_description = false, $desc_separator = '<br />')
    {

        //debug($registrazione, true);
        $codice_completo = '';
        $mastro = $registrazione['prime_note_registrazioni_mastro_' . $what . '_codice'];

        if ($mastro) {
            $codice_completo = $mastro;
            $conto = $registrazione['prime_note_registrazioni_conto_' . $what . '_codice'];
            // debug($conto);
            // debug($what);
            // debug($registrazione, true);

            if ($conto) {
                $codice_completo .= $separator . $conto;
                $sottoconto = $registrazione['prime_note_registrazioni_sottoconto_' . $what . '_codice'];
                if ($sottoconto) {
                    $codice_completo .= $separator . $sottoconto;
                } else {
                }
            } else {
            }
        } else {
        }
        if ($append_description && $codice_completo) {
            //debug($registrazione, true);
            $codice_completo .= $desc_separator . $registrazione["sottoconto{$what}_descrizione"];
        }

        return $codice_completo;
    }

    public function getCodiceCompletoFromSottoconto($sottoconto_id)
    {
        $sottoconto = $this->apilib->view('documenti_contabilita_sottoconti', $sottoconto_id);
        debug($sottoconto, true);
    }

    public function getPrimeNoteData($where = [], $limit = 10, $order_by = null, $offset = 0, $use_apilib = true, $includi_righe_iva = false, $where_registrazioni = '', $progress = false)
    {
        if ($order_by == null) {
            $order_by = 'prime_note_protocollo, prime_note_data_registrazione ASC, prime_note_id DESC';
        }
        $prime_note = [];

        if ($use_apilib) {
            $_prime_note = $this->apilib->search('prime_note', $where, $limit, $offset, $order_by);
        } else {
            $_prime_note = $this->apilib->search('prime_note', $where, $limit, $offset, $order_by);
            /*if (is_array($where)) {
        $where = implode(' AND ', $where);
        }
        $_prime_note = $this->db
        ->select('prime_note.*,customers.*,
        m.documenti_contabilita_mastri_codice as mastro,
        c.documenti_contabilita_conti_codice as conto,
        s.documenti_contabilita_sottoconti_codice as sottoconto,

        cpm.documenti_contabilita_mastri_codice as contropartita_mastro,
        cpc.documenti_contabilita_conti_codice as contropartita_conto,
        cps.documenti_contabilita_sottoconti_codice as contropartita_sottoconto

        ')
        ->where(
        $where,
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

        ->limit($limit)
        ->offeset($offset)
        ->order_by('spese_data_emissione DESC')
        ->get('prime_note')
        ->result_array();*/
        }

        $prime_note_ids = array_unique(array_map(function ($row) {
            return $row['prime_note_id'];
        }, $_prime_note));

        if ($_prime_note) {
            if ($use_apilib) {
                $_prime_note_registrazioni = $this->apilib->search('prime_note_registrazioni', array_merge([
                    'prime_note_registrazioni_prima_nota' => $prime_note_ids,
                ], [$where_registrazioni]));

                if ($includi_righe_iva) {
                    $_prime_note_registrazioni_iva = $this->apilib->search('prime_note_righe_iva', [
                        'prime_note_righe_iva_prima_nota' => $prime_note_ids,
                    ]);
                }
            } else {
                if ($where_registrazioni) {
                    $this->db->where($where_registrazioni, null, false);
                }

                //debug($where_registrazioni, true);
                $_prime_note_registrazioni = $this->db
                    ->select('prime_note_registrazioni.*,customers.*,
                        md.documenti_contabilita_mastri_codice as mastrodare,

                        cd.documenti_contabilita_conti_codice as contodare,
                        sd.documenti_contabilita_sottoconti_codice as sottocontodare,
                        sd.documenti_contabilita_sottoconti_descrizione as sottocontodare_descrizione,

                        ma.documenti_contabilita_mastri_codice as mastroavere,

                        ca.documenti_contabilita_conti_codice as contoavere,
                        sa.documenti_contabilita_sottoconti_codice as sottocontoavere,
                        sa.documenti_contabilita_sottoconti_descrizione as sottocontoavere_descrizione,

                        pn.*,
                        pnc.*


                ')
                    ->where_in('prime_note_registrazioni_prima_nota', $prime_note_ids)

                    ->join('customers', '(
                        (
                            customers_sottoconto = prime_note_registrazioni.prime_note_registrazioni_sottoconto_dare
                            AND
                            prime_note_registrazioni.prime_note_registrazioni_sottoconto_dare IS NOT NULL
                            AND
                            prime_note_registrazioni.prime_note_registrazioni_sottoconto_dare <> \'\'
                        )
                        OR
                        (
                            customers_sottoconto = prime_note_registrazioni.prime_note_registrazioni_sottoconto_avere
                            AND
                            prime_note_registrazioni.prime_note_registrazioni_sottoconto_avere IS NOT NULL
                            AND
                            prime_note_registrazioni.prime_note_registrazioni_sottoconto_avere <> \'\'
                        )', 'LEFT')
                    ->join('documenti_contabilita_mastri as md', 'md.documenti_contabilita_mastri_id = prime_note_registrazioni_mastro_dare', 'LEFT')
                    ->join('documenti_contabilita_conti as cd', 'cd.documenti_contabilita_conti_id = prime_note_registrazioni_conto_dare', 'LEFT')
                    ->join('documenti_contabilita_sottoconti as sd', 'sd.documenti_contabilita_sottoconti_id = prime_note_registrazioni_sottoconto_dare', 'LEFT')

                    ->join('documenti_contabilita_mastri as ma', 'ma.documenti_contabilita_mastri_id = prime_note_registrazioni_mastro_avere', 'LEFT')
                    ->join('documenti_contabilita_conti as ca', 'ca.documenti_contabilita_conti_id = prime_note_registrazioni_conto_avere', 'LEFT')
                    ->join('documenti_contabilita_sottoconti as sa', 'sa.documenti_contabilita_sottoconti_id = prime_note_registrazioni_sottoconto_avere', 'LEFT')
                    ->join('prime_note as pn', 'prime_note_id = prime_note_registrazioni_prima_nota')
                    ->join('prime_note_causali as pnc', 'prime_note_causale = prime_note_causali_id')
                    ->group_by('prime_note_registrazioni_id')
                    ->order_by('prime_note_registrazioni_id ASC')
                    ->get('prime_note_registrazioni')
                    ->result_array();

                //debug($_prime_note_registrazioni, true);

                if ($includi_righe_iva) {
                    $_prime_note_registrazioni_iva = $this->db

                        ->where_in('prime_note_righe_iva_prima_nota', $prime_note_ids)
                        ->join('prime_note', 'prime_note_id = prime_note_righe_iva_prima_nota', 'LEFT')
                        ->join('documenti_contabilita', 'documenti_contabilita_id = prime_note_documento', 'LEFT')
                        ->join('spese', 'spese_id = prime_note_spesa', 'LEFT')
                        ->join('iva', 'prime_note_righe_iva_iva = iva_id', 'LEFT')
                        ->order_by('prime_note_righe_iva_id ASC')

                        ->join('sezionali_iva', 'sezionali_iva_id = prime_note_sezionale')
                        ->get('prime_note_righe_iva')
                        ->result_array();
                }
            }

            foreach ($_prime_note as $prima_nota) {
                $prime_note[$prima_nota['prime_note_id']] = $prima_nota;

                $prime_note[$prima_nota['prime_note_id']]['registrazioni'] = [];

                if ($includi_righe_iva) {
                    $prime_note[$prima_nota['prime_note_id']]['registrazioni_iva'] = [];
                }
            }
            foreach ($_prime_note_registrazioni as $registrazione) {
                $prime_note[$registrazione['prime_note_registrazioni_prima_nota']]['registrazioni'][] = $registrazione;
            }
            if ($includi_righe_iva) {
                foreach ($_prime_note_registrazioni_iva as $registrazione) {
                    $prime_note[$registrazione['prime_note_righe_iva_prima_nota']]['registrazioni_iva'][] = $registrazione;
                }
            }
        }
        return $prime_note;
    }
    public function getProgressivoAnno($anno, $azienda_id)
    {

        $ultimo_progressivo = $this->db->query("
                SELECT prime_note_progressivo_annuo FROM prime_note
                WHERE
                    YEAR(prime_note_data_registrazione) = '{$anno}'
                    AND prime_note_azienda = '$azienda_id'
                    AND prime_note_modello <> 1

                ORDER BY prime_note_progressivo_annuo DESC
                LIMIT 1
            ")->row()->prime_note_progressivo_annuo;

        if (empty($ultimo_progressivo)) {
            $progressivo = 1;
        } else {
            $progressivo = $ultimo_progressivo + 1;
        }

        return $progressivo;
    }
    public function getProgressivoGiorno($data, $azienda_id)
    {
        if (stripos($data, '/')) {
            $data = (DateTime::createFromFormat('d/m/Y', $data))->format('Y-m-d');
        }

        $ultimo_progressivo = $this->db->query("
            SELECT prime_note_progressivo_giornaliero FROM prime_note
            WHERE
                DATE(prime_note_data_registrazione) = DATE('{$data}')
                AND prime_note_azienda = '$azienda_id'
                AND prime_note_modello <> 1
            ORDER BY prime_note_progressivo_giornaliero DESC
            LIMIT 1
        ")->row()->prime_note_progressivo_giornaliero;
        //debug($data, true);
        if (empty($ultimo_progressivo)) {
            $progressivo = 1;
        } else {
            $progressivo = $ultimo_progressivo + 1;
        }
        return $progressivo;
    }
    public function getProtocolloIva($data, $azienda_id, $sezionale_id, $documento_id = null)
    {
        if ($documento_id) {
            $progressivo = $this->apilib->view('documenti_contabilita', $documento_id)['documenti_contabilita_numero'];
        } else {
            $ultimo_protocollo = $this->db->query("
                SELECT * FROM prime_note
                WHERE
                    YEAR(prime_note_data_registrazione) = '{$data}'
                    AND
                    prime_note_sezionale = '$sezionale_id'
                    AND
                    prime_note_azienda = '$azienda_id'
                    AND prime_note_modello <> 1
                ORDER BY prime_note_protocollo DESC, prime_note_progressivo_annuo DESC
                LIMIT 1
            ")->row();
            //debug($ultimo_protocollo, true);
            if (empty($ultimo_protocollo->prime_note_protocollo)) {
                $progressivo = 1;
            } else {
                $progressivo = $ultimo_protocollo->prime_note_protocollo + 1;
            }
        }

        return $progressivo;
    }

    public function salvaRegistrazioniPrimaNota($registrazioni, $prima_nota_id)
    {
        if ($prima_nota_id == 6522) {
            //die('SE VEDI QUESTO ERRORE, ARIANNA, NON FARE NIENTE E CHIAMAMI!!!!!');
        }
        $this->db->query("DELETE FROM prime_note_registrazioni WHERE prime_note_registrazioni_prima_nota = '{$prima_nota_id}'");
        $return = [];
        foreach ($registrazioni as $key => $registrazione) {

            //Mi arrivano solo i codici testuali. Per ottimizzazione db, voglio salvarmi l'id dei vari mastri/conti/sottoconti e non solo il codice (per fare where ottimizzati, join, ecc... per questo motivo qui faccio un po' di cose...)
            //Per prima cosa devo capire se parliamo di un dare o di un avere. La cosa più semplice è basarsi sull'importo:
            if ($registrazione['prime_note_registrazioni_codice_dare_testuale']) {

                $codice_completo = $registrazione['prime_note_registrazioni_codice_dare_testuale'];

                $codice_exploded = explode('.', $codice_completo);

                $mastro_codice = $codice_exploded[0];
                $conto_codice = $codice_exploded[1];
                $sottoconto_codice = $codice_exploded[2];

                $mastro = $this->apilib->searchFirst('documenti_contabilita_mastri', [
                    'documenti_contabilita_mastri_codice' => $mastro_codice,

                ]);
                $mastro_id = $mastro['documenti_contabilita_mastri_id'];
                $registrazione['prime_note_registrazioni_mastro_dare'] = $mastro_id;
                $registrazione['prime_note_registrazioni_mastro_dare_codice'] = $mastro['documenti_contabilita_mastri_codice'];

                $conto = $this->apilib->searchFirst('documenti_contabilita_conti', [
                    'documenti_contabilita_conti_codice' => $conto_codice,
                    'documenti_contabilita_conti_mastro' => $mastro_id,
                    '(documenti_contabilita_conti_blocco = 0 OR documenti_contabilita_conti_blocco IS NULL)',
                ]);
                $conto_id = $conto['documenti_contabilita_conti_id'];
                $registrazione['prime_note_registrazioni_conto_dare'] = $conto_id;

                if (!$conto) {
                    debug($registrazione, true);
                }

                $registrazione['prime_note_registrazioni_conto_dare_codice'] = $conto['documenti_contabilita_conti_codice'];
                if ($sottoconto_codice) {
                    $sottoconto = $this->apilib->searchFirst('documenti_contabilita_sottoconti', [
                        'documenti_contabilita_sottoconti_codice' => $sottoconto_codice,
                        'documenti_contabilita_sottoconti_conto' => $conto_id,
                        '(documenti_contabilita_sottoconti_blocco = 0 OR documenti_contabilita_sottoconti_blocco IS NULL)',
                    ]);

                    if (!$sottoconto) {
                        //TODO: capire come gestire, perchè non posso fare throw new ApiException altrimenti rimane una prima nota senza registrazioni!
                        //throw new ApiException("Impossibile trovare il sottoconto '{$sottoconto_codice}' corretto: controllare che il codice conto sia univoco!");
                    }

                    $sottoconto_id = $sottoconto['documenti_contabilita_sottoconti_id'];
                    $registrazione['prime_note_registrazioni_sottoconto_dare'] = $sottoconto_id;
                    $registrazione['prime_note_registrazioni_sottoconto_dare_codice'] = $sottoconto['documenti_contabilita_sottoconti_codice'];

                    if ($key == 8) {
                        //debug($registrazione,true);
                    }

                    //Ricostruisco il conto dare testuale, ora che ho tutto
                    if (!$registrazione['prime_note_registrazioni_codice_dare_testuale']) {
                        $registrazione['prime_note_registrazioni_codice_dare_testuale'] = $this->getCodiceCompleto($registrazione, 'dare');
                    }
                }
                //Posso quindi unsettare tutto ciò che è "avere"
                foreach ($registrazione as $field => $val) {
                    if (stripos($field, 'avere') !== false) {
                        unset($registrazione[$field]);
                    }
                }
            } elseif ($registrazione['prime_note_registrazioni_codice_avere_testuale']) {
                $codice_completo = $registrazione['prime_note_registrazioni_codice_avere_testuale'];

                $codice_exploded = explode('.', $codice_completo);

                $mastro_codice = $codice_exploded[0];
                $conto_codice = $codice_exploded[1];
                $sottoconto_codice = $codice_exploded[2];

                $mastro = $this->apilib->searchFirst('documenti_contabilita_mastri', ['documenti_contabilita_mastri_codice' => $mastro_codice]);
                $mastro_id = $mastro['documenti_contabilita_mastri_id'];
                $registrazione['prime_note_registrazioni_mastro_avere'] = $mastro_id;
                $registrazione['prime_note_registrazioni_mastro_avere_codice'] = $mastro['documenti_contabilita_mastri_codice'];

                $conto = $this->apilib->searchFirst('documenti_contabilita_conti', [
                    'documenti_contabilita_conti_codice' => $conto_codice,
                    'documenti_contabilita_conti_mastro' => $mastro_id,
                    '(documenti_contabilita_conti_blocco = 0 OR documenti_contabilita_conti_blocco IS NULL)',
                ]);

                if (!$conto) {
                    debug($registrazione, true);
                }

                $conto_id = $conto['documenti_contabilita_conti_id'];
                $registrazione['prime_note_registrazioni_conto_avere'] = $conto_id;
                $registrazione['prime_note_registrazioni_conto_avere_codice'] = $conto['documenti_contabilita_conti_codice'];
                if ($sottoconto_codice) {
                    $sottoconto = $this->apilib->searchFirst('documenti_contabilita_sottoconti', [
                        'documenti_contabilita_sottoconti_codice' => $sottoconto_codice,
                        'documenti_contabilita_sottoconti_conto' => $conto_id,
                        '(documenti_contabilita_sottoconti_blocco = 0 OR documenti_contabilita_sottoconti_blocco IS NULL)',
                    ]);
                    if (!$sottoconto) {
                        debug($registrazione, true);
                    }
                    //debug($sottoconto, true);

                    $sottoconto_id = $sottoconto['documenti_contabilita_sottoconti_id'];
                    $registrazione['prime_note_registrazioni_sottoconto_avere'] = $sottoconto_id;
                    $registrazione['prime_note_registrazioni_sottoconto_avere_codice'] = $sottoconto['documenti_contabilita_sottoconti_codice'];
                }

                //Ricostruisco il conto avere testuale, ora che ho tutto
                if (!$registrazione['prime_note_registrazioni_codice_avere_testuale']) {
                    $registrazione['prime_note_registrazioni_codice_avere_testuale'] = $this->getCodiceCompleto($registrazione, 'avere');
                }

                //Posso quindi unsettare tutto ciò che è "dare"
                foreach ($registrazione as $field => $val) {
                    if (stripos($field, 'dare') !== false) {
                        unset($registrazione[$field]);
                    }
                }

                //debug($registrazione, true);
            } else {
                //Probabilmente è arrivata una riga vuota. Skippo passando alla riga sucessiva.
                continue;
            }

            //debug($registrazione, true);
            //if ($registrazione['prime_note_registrazioni_importo_dare'] > 0 || $registrazione['prime_note_registrazioni_importo_avere'] > 0) {
            $registrazione['prime_note_registrazioni_prima_nota'] = $prima_nota_id;

            try {
                if ($key == 1) {
                    //debug($registrazione,true);
                }

                $this->apilib->create('prime_note_registrazioni', $registrazione);
                $return[] = $registrazione;
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => $e->getMessage()]);
                exit;
            }
            //}
        }
        return $return;
    }
    public function salvaRegistrazioniIvaPrimaNota($righe_iva, $prima_nota_id)
    {

        $this->db->query("DELETE FROM prime_note_righe_iva WHERE prime_note_righe_iva_prima_nota = '{$prima_nota_id}'");
        $return = [];
        foreach ($righe_iva as $key => $valore) {
            //Controllo su imponibile: le vecchie righe articoli nelle fatture, per un bug hanno tutte un imponibile a 0

            if (abs($valore['prime_note_righe_iva_imponibile']) <= 0) {
                $righe_iva[$key]['prime_note_righe_iva_imponibile'] = $valore['prime_note_righe_iva_totale'] - $valore['prime_note_righe_iva_importo_iva'];
            }
            $righe_iva[$key]['prime_note_righe_iva_prima_nota'] = $prima_nota_id;
        }

        foreach ($righe_iva as $riga) {
            $return[] = $this->apilib->create('prime_note_righe_iva', $riga);
        }

        return $return;
    }
    public function creaPrimaNotaDaFattura($documento_id, $modello_selezionato)
    {
        $today = date('Y-m-d');
        $today_anno = date('Y');
        $azienda_id = $documento['documenti_contabilita_azienda'];

        $modello_documento = $this->db->query("SELECT * FROM prime_note WHERE
            prime_note_id IN (
                SELECT
                    prime_note_modelli_prima_nota
                FROM
                    prime_note_modelli
                WHERE
                    prime_note_modelli_tipo IN (
                        SELECT prime_note_mappature_id
                        FROM prime_note_mappature
                        LEFT JOIN prime_note_mappature_tipo ON ( prime_note_mappature_tipo_id = prime_note_mappature_chiave)
                        WHERE prime_note_mappature_tipo_identifier = 'modello_fattura_vendita_italia'
                    )
            )")->row_array();

        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        //$articoli = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);

        //debug($prima_nota_esempio);

        $prima_nota = $modello_documento;
        unset($prima_nota['prime_note_id']);
        unset($prima_nota['prime_note_creation_date']);
        unset($prima_nota['prime_note_modified_date']);
        $prima_nota['prime_note_modello'] = 0;
        $prima_nota['prime_note_documento'] = $documento_id;
        $prima_nota['prime_note_data_registrazione'] = dateFormat($documento['documenti_contabilita_data_emissione']) . ' 18:00:00';
        $prima_nota['prime_note_progressivo_giornaliero'] = $this->getProgressivoGiorno($today, $azienda_id);
        $prima_nota['prime_note_progressivo_annuo'] = $this->getProgressivoAnno($today_anno, $azienda_id);
        $prima_nota['prime_note_protocollo'] = $documento['documenti_contabilita_numero']; //$this->getProtocolloIva($today, $azienda_id, $prima_nota['prime_note_sezionale']);
        $prima_nota['prime_note_scadenza'] = dateFormat($documento['documenti_contabilita_data_emissione']);
        $prima_nota['prime_note_numero_documento'] = $documento['documenti_contabilita_numero'];
        $prima_nota['prime_note_json_data'] = json_encode($prima_nota);

        if ($prima_nota['prime_note_protocollo'] != $documento['documenti_contabilita_numero']) {
            debug($documento);
            debug($prima_nota, true);
        }

        //debug($prima_nota, true);

        $prima_nota_id = $this->apilib->create('prime_note', $prima_nota, false);
        $righe = $this->getPrimaNotaRighe($prima_nota['prime_note_causale'], true, $documento_id, $modello_selezionato);
        foreach ($righe as $key => $riga) {
            $righe[$key]['prime_note_registrazioni_prima_nota'] = $prima_nota_id;
        }
        $registrazioni = $this->salvaRegistrazioniPrimaNota($righe, $prima_nota_id);

        $righe_iva = $this->getPrimaNotaRigheIva(true, $documento_id);
        foreach ($righe_iva as $key => $riga_iva) {
            $righe_iva[$key]['prime_note_righe_iva_prima_nota'] = $prima_nota_id;
        }

        //debug($righe_iva, true);

        $registrazioni_iva = $this->salvaRegistrazioniIvaPrimaNota($righe_iva, $prima_nota_id);

        return [
            'prima_nota' => $prima_nota,
            'prima_nota_registrazioni' => $registrazioni,
            'prima_nota_righe_iva' => $registrazioni_iva,
        ];
    }

    public function creaPrimaNotaDaSpesa($spesa_id, $modello_selezionato)
    {
        $today = date('Y-m-d');
        $today_anno = date('Y');
        $azienda_id = 33;

        $modello_spesa = $this->db->query("SELECT * FROM prime_note WHERE
            prime_note_id IN (
                SELECT
                    prime_note_modelli_prima_nota
                FROM
                    prime_note_modelli
                WHERE
                    prime_note_modelli_tipo IN (
                        SELECT prime_note_mappature_id
                        FROM prime_note_mappature
                        LEFT JOIN prime_note_mappature_tipo ON ( prime_note_mappature_tipo_id = prime_note_mappature_chiave)
                        WHERE prime_note_mappature_tipo_identifier = 'modello_fattura_acquisto_italia'
                    )
            )")->row_array();

        $spesa = $this->apilib->view('spese', $spesa_id);
        //$articoli = $this->apilib->search('spese_articoli', ['spese_articoli_spesa' => $spesa_id]);

        //debug($prima_nota_esempio);

        $prima_nota = $modello_spesa;
        unset($prima_nota['prime_note_id']);
        unset($prima_nota['prime_note_creation_date']);
        unset($prima_nota['prime_note_modified_date']);
        $prima_nota['prime_note_modello'] = 0;
        $prima_nota['prime_note_spesa'] = $spesa_id;
        $prima_nota['prime_note_data_registrazione'] = dateFormat($spesa['spese_data_emissione']) . ' 18:00:00';
        $prima_nota['prime_note_progressivo_giornaliero'] = $this->getProgressivoGiorno($today, $azienda_id);
        $prima_nota['prime_note_progressivo_annuo'] = $this->getProgressivoAnno($today_anno, $azienda_id);
        $prima_nota['prime_note_protocollo'] = $this->getProtocolloIva($today, $azienda_id, $prima_nota['prime_note_sezionale']);
        $prima_nota['prime_note_scadenza'] = dateFormat($spesa['spese_data_emissione']);
        $prima_nota['prime_note_numero_documento'] = $spesa['spese_numero'];
        $prima_nota['prime_note_json_data'] = json_encode($prima_nota);
        //debug($prima_nota, true);

        $prima_nota_id = $this->apilib->create('prime_note', $prima_nota, false);

        $righe = $this->getPrimaNotaRighe($prima_nota['prime_note_causale'], false, $spesa_id, $modello_selezionato);

        foreach ($righe as $key => $riga) {

            $righe[$key]['prime_note_registrazioni_prima_nota'] = $prima_nota_id;
        }
        $registrazioni = $this->salvaRegistrazioniPrimaNota($righe, $prima_nota_id);

        $righe_iva = $this->getPrimaNotaRigheIva(false, $spesa_id);
        //debug($righe_iva, true);
        foreach ($righe_iva as $key => $riga_iva) {
            $righe_iva[$key]['prime_note_righe_iva_prima_nota'] = $prima_nota_id;
        }

        //debug($righe_iva, true);

        $registrazioni_iva = $this->salvaRegistrazioniIvaPrimaNota($righe_iva, $prima_nota_id);

        return [
            'prima_nota' => $prima_nota,
            'prima_nota_registrazioni' => $registrazioni,
            'prima_nota_righe_iva' => $registrazioni_iva,
        ];
    }
    public function salvaPrimaNota($prima_nota, $registrazioni, $dettaglio_iva)
    {
        //debug($registrazioni, true);

        $nome_modello = $prima_nota['nome_modello'];
        unset($prima_nota['nome_modello']);
        $is_edit = false;
        if (!empty($prima_nota['prime_note_id'])) {
            //die('test');
            $prima_nota_id = $prima_nota['prime_note_id'];
            unset($prima_nota['prime_note_id']);
            $is_edit = true;

            $this->apilib->clearCache();

            $prima_nota_db = $this->apilib->edit('prime_note', $prima_nota_id, $prima_nota);
        } else {

            $prima_nota_db = $this->apilib->create('prime_note', $prima_nota);
            $prima_nota_id = $prima_nota_db['prime_note_id'];
        }

        //Se sto creando un modello lo creo e lo associo a questa prima nota
        if (!empty($prima_nota['prime_note_modello']) && $prima_nota['prime_note_modello'] == 1) {

            //debug($prima_nota, true);

            if (!$is_edit) {
                //Creo modello
                $modello = $this->apilib->create('prime_note_modelli', [
                    'prime_note_modelli_nome' => $nome_modello,
                    'prime_note_modelli_prima_nota' => $prima_nota_id,
                ]);
            } else {
                //Se sono in modifica potrei anche aver cambiato il nome al modello
                $modello = $this->apilib->searchFirst('prime_note_modelli', ['prime_note_modelli_prima_nota' => $prima_nota_id]);
                $modello = $this->apilib->edit(
                    'prime_note_modelli',
                    $modello['prime_note_modelli_id'],
                    [
                        'prime_note_modelli_nome' => ($nome_modello) ? $nome_modello : $modello['prime_note_modelli_nome'],
                        'prime_note_modelli_prima_nota' => $prima_nota_id,
                    ]
                );
            }
        }

        $this->salvaRegistrazioniPrimaNota($registrazioni, $prima_nota_id);
        //Unsetto la prima riga perchè è quella vuota che viene usata per clonare le altre in js
        // unset($dettaglio_iva['prime_note_righe_iva_riga'][0]);
        $this->salvaRegistrazioniIvaPrimaNota($dettaglio_iva, $prima_nota_id);

        //A questo punto verifico se sto facendo un'autofattura (reverse). Se si, creo le altre due registrazioni in automatico (in caso di edit ovviamente le cancello prima e poi le ricreo)
        //debug($prima_nota_db, true);
        // if ($prima_nota_db['prime_note_causale_causale_reverse_sucessiva']) {
        //     $this->generaPrimaNotaReverseCollegata($prima_nota_db);
        // }
    }
    public function generaPrimaNotaReverseCollegata($prima_nota_db)
    {
        //Recupero l'imponibile dalle righe iva, dove specificato il reverse

        $campi_entita_prime_note = array_map(function ($field) {
            return $field['fields_name'];
        }, $this->crmentity->getFields('prime_note'));
        $prima_nota_collegata = $prima_nota_db;
        unset($prima_nota_collegata['prime_note_id']);
        foreach ($prima_nota_collegata as $campo => $valore) {
            //Tengo solo i campi effettivamente dell'entità prime_note e non quelli joinati
            if (!in_array($campo, $campi_entita_prime_note)) {
                unset($prima_nota_collegata[$campo]);
            }
        }
        //Adesso correggo le varie numerazioni
        $prima_nota_collegata['prime_note_progressivo_annuo']++;
        $prima_nota_collegata['prime_note_progressivo_giornaliero']++;
        //$prima_nota_collegata['prime_note_protocollo']++; //Il protocollo non deve cambiare

        //La collego alla prima nota padre (mi serve dopo per poter editare/cancellare le figlie)
        $prima_nota_collegata['prime_note_ref_prima_nota'] = $prima_nota_db['prime_note_id'];

        //Cambio la causale
        //debug($prima_nota_db, true);
        $causale_sucessiva = $this->apilib->view('prime_note_causale', $prima_nota_db['prime_note_causale_causale_reverse_sucessiva']);

        $prima_nota_collegata['prime_note_causale'] = $causale_sucessiva['prime_note_causale_id'];

        //cancello eventuali figlie collegate al padre (es.: se sono in edit)
        $this->db->query("DELETE FROM prime_note WHERE prime_note_ref_prima_nota = '{$prima_nota_db['prime_note_id']}'");

        $autofattura = $this->apilib->create('prime_note', $prima_nota_collegata);

        $righe_iva_reverse = $this->apilib->search('prime_note_righe_iva', [
            'prime_note_righe_iva_prima_nota' => $prima_nota_db['prime_note_id'],
            'iva_reverse' => 1,
        ]);
        $registrazioni_padre =
        $this->apilib->search('prime_note_registrazioni', [
            'prime_note_registrazioni_prima_nota' => $prima_nota_db['prime_note_id'],

        ]);

        $sottoconto_riga_1 = $registrazioni_padre[0]['prime_note_registrazioni_sottoconto_avere'];
        $supplier = $this->apilib->searchFirst('customers', ['customers_sottoconto' => $sottoconto_riga_1]);

        //Verifico se ha un customer collegato per il reverse
        if ($supplier['customers_related_customer']) {
            $customer = $this->apilib->view('customers', $supplier['customers_related_customer']);

            if ($customer['customers_sottoconto']) {
                //Dovrebbe funzionare sempre, in alternativa sopra c'è una funzione get from sottoconto che dovrebbe estrarre il codice testuale...
                $codice_testuale = $customer['customers_sottoconto_documenti_contabilita_sottoconti_codice_completo'];
                $registrazioni = [];
                $imponibile = $iva = 0;

                foreach ($righe_iva_reverse as $riga_iva_reverse) {
                    $iva += $riga_iva_reverse['prime_note_righe_iva_importo_iva'];
                    $imponibile += $riga_iva_reverse['prime_note_righe_iva_imponibile'];
                }
                $totale = $imponibile + $iva;

                $mastri = array_key_map_data($this->db->get('documenti_contabilita_mastri')->result_array(), 'documenti_contabilita_mastri_id');

                $conti = array_key_map_data($this->db->get('documenti_contabilita_conti')->result_array(), 'documenti_contabilita_conti_id');

                $sottoconti = array_key_map_data($this->db->get('documenti_contabilita_sottoconti')->result_array(), 'documenti_contabilita_sottoconti_id');
                //TOTALE
                $registrazioni[] = [
                    'prime_note_registrazioni_numero_riga' => 1,
                    'prime_note_registrazioni_codice_dare_testuale' => $codice_testuale,
                    'prime_note_registrazioni_importo_dare' => $totale,

                ];

                $causale_iva = $this->apilib->view('prime_note_causale', $causale_sucessiva['prime_note_causale_causale_sucessiva']);

                //IVA
                $registrazioni[] = [
                    'prime_note_registrazioni_numero_riga' => 1,
                    'prime_note_registrazioni_codice_avere_testuale' => $causale_iva['documenti_contabilita_sottoconti_codice_completo'],
                    'prime_note_registrazioni_importo_avere' => $iva,
                ];

                $causale_imponibile = $this->apilib->view('prime_note_causale', $causale_iva['prime_note_causale_causale_sucessiva']);
                //debug($causale_imponibile, true);
                //IMPONIBILE
                $registrazioni[] = [
                    'prime_note_registrazioni_numero_riga' => 1,
                    'prime_note_registrazioni_codice_avere_testuale' => $causale_imponibile['documenti_contabilita_sottoconti_codice_completo'],
                    'prime_note_registrazioni_importo_avere' => $imponibile,
                ];
                $dettaglio_iva = $righe_iva_reverse;
                $campi_entita_prime_note_righe_iva = array_map(function ($field) {
                    return $field['fields_name'];
                }, $this->crmentity->getFields('prime_note_righe_iva'));

                foreach ($dettaglio_iva as $riga_id => $riga) {
                    foreach ($riga as $campo => $valore) {
                        //Tengo solo i campi effettivamente dell'entità prime_note e non quelli joinati
                        if (!in_array($campo, $campi_entita_prime_note_righe_iva)) {
                            unset($dettaglio_iva[$riga_id][$campo]);
                        }
                    }

                    unset($dettaglio_iva[$riga_id]['prime_note_righe_iva_id']);
                    unset($dettaglio_iva[$riga_id]['prime_note_righe_iva_prima_nota']);
                }

                $this->salvaRegistrazioniPrimaNota($registrazioni, $autofattura['prime_note_id']);
                $this->salvaRegistrazioniIvaPrimaNota($dettaglio_iva, $autofattura['prime_note_id']);

                $this->generaPrimaNotaReverseGirocontiCollegata($autofattura, $imponibile, $iva, $prima_nota_db);
            } else {
                $this->apilib->delete('prime_note', $autofattura['prime_note_id']);
                $this->apilib->delete('prime_note', $prima_nota_db['prime_note_id']);
                e_json(['status' => 0, 'txt' => 'Il cliente collegato a questo fornitore non ha sottoconto impostato, modificare l\'anagrafica e riprovare...']);
                exit;
            }
        } else {
            $this->apilib->delete('prime_note', $autofattura['prime_note_id']);
            $this->apilib->delete('prime_note', $prima_nota_db['prime_note_id']);
            e_json(['status' => 0, 'txt' => 'Il fornitore non ha un cliente associato, modificare l\'anagrafica e riprovare...']);
            exit;
        }
    }
    public function generaPrimaNotaReverseGirocontiCollegata($autofattura_db, $imponibile, $iva, $prima_nota_orig)
    {

        //Recupero l'imponibile dalle righe iva, dove specificato il reverse

        $campi_entita_prime_note = array_map(function ($field) {
            return $field['fields_name'];
        }, $this->crmentity->getFields('prime_note'));
        $giroconto = $autofattura_db;
        unset($giroconto['prime_note_id']);
        foreach ($giroconto as $campo => $valore) {
            //Tengo solo i campi effettivamente dell'entità prime_note e non quelli joinati
            if (!in_array($campo, $campi_entita_prime_note)) {
                unset($giroconto[$campo]);
            }
        }
        //Adesso correggo le varie numerazioni
        $giroconto['prime_note_progressivo_annuo']++;
        $giroconto['prime_note_progressivo_giornaliero']++;
        $giroconto['prime_note_protocollo']++;

        //La collego alla prima nota padre (mi serve dopo per poter editare/cancellare le figlie)
        $giroconto['prime_note_ref_prima_nota'] = $autofattura_db['prime_note_id'];

        //Cambio la causale
        //debug($autofattura_db, true);
        $causale_gcva1 = $this->apilib->view('prime_note_causale', $autofattura_db['prime_note_causale_causale_reverse_sucessiva']);

        $giroconto['prime_note_causale'] = $causale_gcva1['prime_note_causale_id'];

        //cancello eventuali figlie collegate al padre (es.: se sono in edit)
        $this->db->query("DELETE FROM prime_note WHERE prime_note_ref_prima_nota = '{$autofattura_db['prime_note_id']}'");

        $giroconto = $this->apilib->create('prime_note', $giroconto);

        $registrazioni_autofattura = $this->apilib->search('prime_note_registrazioni', ['prime_note_registrazioni_prima_nota' => $autofattura_db['prime_note_id']]);
        $registrazioni_originale = $this->apilib->search('prime_note_registrazioni', ['prime_note_registrazioni_prima_nota' => $prima_nota_orig['prime_note_id']]);
        $registrazioni = [];

        $totale = $imponibile + $iva;

        $registrazioni[] = [
            'prime_note_registrazioni_numero_riga' => 1,
            'prime_note_registrazioni_codice_avere_testuale' => $registrazioni_autofattura[0]['prime_note_registrazioni_codice_dare_testuale'],
            'prime_note_registrazioni_importo_avere' => $totale,

        ];

        $causale_iva = $this->apilib->view('prime_note_causale', $causale_gcva1['prime_note_causale_causale_sucessiva']);

        //IVA
        $registrazioni[] = [
            'prime_note_registrazioni_numero_riga' => 1,
            'prime_note_registrazioni_codice_dare_testuale' => $registrazioni_originale[0]['documenti_contabilita_sottoconti_codice_completo'],
            'prime_note_registrazioni_importo_dare' => $iva,
        ];

        $causale_imponibile = $this->apilib->view('prime_note_causale', $causale_iva['prime_note_causale_causale_sucessiva']);

        //IMPONIBILE
        $registrazioni[] = [
            'prime_note_registrazioni_numero_riga' => 1,
            'prime_note_registrazioni_codice_dare_testuale' => $registrazioni_autofattura[2]['prime_note_registrazioni_codice_avere_testuale'],
            'prime_note_registrazioni_importo_dare' => $imponibile,
        ];

        //debug($registrazioni, true);

        $this->salvaRegistrazioniPrimaNota($registrazioni, $giroconto['prime_note_id']);
    }

    public function getIvaData($where_arr = [], $progress = false)
    {
        $data = [];
        // Filtro tabella e dati prima nota
        $grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
        $where = [$this->datab->generate_where("grids", $grid['grids_id'], null)];
        $where['prime_note_modello'] = 0;

        if ($where_arr) {

            $where = array_merge($where, $where_arr);

        }

        //$where[] = "prime_note_spesa IS NOT NULL AND prime_note_spesa <> ''";
        $where_acquisti = $where_vendite = $where;
        $where_acquisti[] = "sezionali_iva_tipo = 2"; //Acquisto
        $where_vendite[] = "(sezionali_iva_tipo = 1 OR sezionali_iva_tipo = 9)"; //Vendite

//debug($where_vendite,true);

        $primeNoteData_acquisti = $this->getPrimeNoteData($where_acquisti, null, 'prime_note_scadenza', 0, false, true, '', $progress);

        $primeNoteData_vendite = $this->getPrimeNoteData($where_vendite, null, 'prime_note_scadenza', 0, false, true, '', $progress);

        // Dati filtri impostati
        $filters = $this->session->userdata(SESS_WHERE_DATA);

        // Costruisco uno specchietto di filtri autogenerati leggibile
        $filtri = array();

        if (!empty($filters["filter_stampe_contabili"])) {
            foreach ($filters["filter_stampe_contabili"] as $field) {
                if ($field['value'] == '-1') {
                    continue;
                }
                $filter_field = $this->datab->get_field($field["field_id"], true);
                // debug($filter_field);

                // Se ha una entità/support collegata
                if ($filter_field['fields_ref']) {
                    $entity_data = $this->crmentity->getEntityPreview($filter_field['fields_ref']);
                    $filtri[] = array(
                        "label" => $filter_field["fields_draw_label"],
                        "value" => $entity_data[$field['value']],
                        'raw_data_value' => $field['value'],
                    );
                } else {
                    $filtri[] = array("label" => $filter_field["fields_draw_label"], "value" => $field['value']);
                }
            }
        }

        $sez = 0;
        foreach (['acquisti', 'vendite'] as $tipo) {
            $totali = [];

            $totali_per_sezionale = [];

            $primeNoteDataGroupSezionale = [];
            $array_str = "primeNoteData_{$tipo}";
            foreach ($$array_str as $prima_nota) { //Attenzione che qui c'è un doppio $$

                $primeNoteDataGroupSezionale[$prima_nota['sezionali_iva_sezionale']][] = $prima_nota;
            }

            $imponibili = $imposte = 0;
            $total_sez = count($primeNoteDataGroupSezionale);
            foreach ($primeNoteDataGroupSezionale as $sezionale => $primeNoteData) {
                $sez++;
                if ($progress) {
                    progress($sez, $total_sez, 'Elaborazione sezionali');
                }
                $totale_sezionale_imponibile = $totale_sezionale_imposta = 0;
                $pn = 0;
                $totale_prime_note = count($primeNoteData);
                foreach ($primeNoteData as $prime_note_id => $prima_nota) {
                    $pn++;
                    if ($progress) {
                        progress($pn, $totale_prime_note, "Elaborazione registrazioni sezionale '$sezionale'");
                    }
                    foreach ($prima_nota["registrazioni_iva"] as $registrazione) {
                        // if (!empty($prima_nota['prime_note_causale_mastro_dare_documenti_contabilita_mastri_natura_value'])) {
                        //     if ($prima_nota['prime_note_causale_mastro_dare_documenti_contabilita_mastri_natura_value'] == 'Passività') {
                        //         $registrazione['prime_note_righe_iva_imponibile'] = -$registrazione['prime_note_righe_iva_imponibile'];
                        //         $registrazione['prime_note_righe_iva_importo_iva'] = -$registrazione['prime_note_righe_iva_importo_iva'];
                        //     }
                        // } elseif (!empty($prima_nota['prime_note_causale_mastro_avere_documenti_contabilita_mastri_natura_value'])) {
                        //     if ($prima_nota['prime_note_causale_mastro_avere_documenti_contabilita_mastri_natura_value'] == 'Attività') {
                        //         $registrazione['prime_note_righe_iva_imponibile'] = -$registrazione['prime_note_righe_iva_imponibile'];
                        //         $registrazione['prime_note_righe_iva_importo_iva'] = -$registrazione['prime_note_righe_iva_importo_iva'];
                        //     }
                        // }
                        if ($tipo == 'vendite') {
//debug($registrazione['prime_note_righe_iva_imponibile']);
                        }

                        $imponibili += $registrazione['prime_note_righe_iva_imponibile'];
                        $imposte += $registrazione['prime_note_righe_iva_importo_iva'];

                        $totale_sezionale_imponibile += $registrazione['prime_note_righe_iva_imponibile'];
                        $totale_sezionale_imposta += $registrazione['prime_note_righe_iva_importo_iva'];

                        if (empty($totali[$registrazione['iva_id']]['iva_id'])) {

                            $totali[$registrazione['iva_id']]['iva_id'] = $registrazione['iva_id'];
                            $totali[$registrazione['iva_id']]['iva_valore'] = $registrazione['iva_valore'];
                            $totali[$registrazione['iva_id']]['iva_descrizione'] = $registrazione['iva_descrizione'];
                            $totali[$registrazione['iva_id']]['iva_label'] = $registrazione['iva_label'];
                            $totali[$registrazione['iva_id']]['iva_percentuale_indetraibilita'] = $registrazione['iva_percentuale_indetraibilita'];

                        }
                        if (empty($totali[$registrazione['iva_id']]['intra']['imponibile'])) {
                            $totali[$registrazione['iva_id']]['intra']['imponibile'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['intra']['imposta'])) {
                            $totali[$registrazione['iva_id']]['intra']['imposta'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['extra']['imponibile'])) {
                            $totali[$registrazione['iva_id']]['extra']['imponibile'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['extra']['imposta'])) {
                            $totali[$registrazione['iva_id']]['extra']['imposta'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['italia']['imponibile'])) {
                            $totali[$registrazione['iva_id']]['italia']['imponibile'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['italia']['imposta'])) {
                            $totali[$registrazione['iva_id']]['italia']['imposta'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['reverse']['imponibile'])) {
                            $totali[$registrazione['iva_id']]['reverse']['imponibile'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['reverse']['imposta'])) {
                            $totali[$registrazione['iva_id']]['reverse']['imposta'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['indetraibile']['imponibile'])) {
                            $totali[$registrazione['iva_id']]['indetraibile']['imponibile'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['indetraibile']['imposta'])) {
                            $totali[$registrazione['iva_id']]['indetraibile']['imposta'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['split']['imponibile'])) {
                            $totali[$registrazione['iva_id']]['split']['imponibile'] = 0;
                        }

                        if (empty($totali[$registrazione['iva_id']]['split']['imposta'])) {
                            $totali[$registrazione['iva_id']]['split']['imposta'] = 0;
                        }

                        //Aggiunto totale per sezionale indetraibile
                        if (empty($totali_per_sezionale[$sezionale]['indetraibile']['imponibile'])) {
                            $totali_per_sezionale[$sezionale]['indetraibile']['imponibile'] = 0;
                        }

                        if (empty($totali_per_sezionale[$sezionale]['indetraibile']['imposta'])) {
                            $totali_per_sezionale[$sezionale]['indetraibile']['imposta'] = 0;
                        }

                        if (empty($totali_per_sezionale[$sezionale]['detraibile']['imponibile'])) {
                            $totali_per_sezionale[$sezionale]['detraibile']['imponibile'] = 0;
                        }

                        if (empty($totali_per_sezionale[$sezionale]['detraibile']['imposta'])) {
                            $totali_per_sezionale[$sezionale]['detraibile']['imposta'] = 0;
                        }

                        //debug($registrazione['prime_note_righe_iva_iva_valore_indet']);

                        if ($registrazione['iva_percentuale_indetraibilita'] > 0) { // Indetraibile

                            //TODO: Una volta corretta a maschera, non dovrebbe più riscontrare quest'anomalia!!!!!!
                            if ($registrazione['prime_note_righe_iva_iva_valore_indet'] <= 0 && $registrazione['prime_note_righe_iva_indetraibilie_perc'] > 0) {
                                // $registrazione['prime_note_righe_iva_imponibile_indet'] = $registrazione['prime_note_righe_iva_imponibile'];
                                // $registrazione['prime_note_righe_iva_iva_valore_indet'] = $registrazione['prime_note_righe_iva_importo_iva'];

                                //$registrazione['prime_note_righe_iva_imponibile_indet'] = ($registrazione['prime_note_righe_iva_imponibile']/100)*$registrazione['prime_note_righe_iva_indetraibilie_perc'];
                                //$registrazione['prime_note_righe_iva_iva_valore_indet'] = ($registrazione['prime_note_righe_iva_importo_iva']/100)*$registrazione['prime_note_righe_iva_indetraibilie_perc'];
                            }

                            $totali_per_sezionale[$sezionale]['indetraibile']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile_indet'];

                            $totali_per_sezionale[$sezionale]['indetraibile']['imposta'] += $registrazione['prime_note_righe_iva_iva_valore_indet'];

                            //Controllo... se c'è un indetraibilità, l'imponibile e il valore indetraibile dovranno essere sempre valorizzati. Avviso!
                            if (!($registrazione['prime_note_righe_iva_imponibile_indet'] > 0 && $registrazione['prime_note_righe_iva_iva_valore_indet'] > 0)) {
                                echo ("<br />La registrazione con progressivo annuo '{$registrazione['prime_note_progressivo_annuo']}' e protocollo '{$registrazione['prime_note_protocollo']}' ha un anomalia sull'indetraibilità dell'iva. Correggere e riprovare....<br />");
                            }

                            $totali[$registrazione['iva_id']]['indetraibile']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile_indet'];
                            $totali[$registrazione['iva_id']]['indetraibile']['imposta'] += $registrazione['prime_note_righe_iva_iva_valore_indet'];

                            $totali_per_sezionale[$sezionale]['detraibile']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'] - $registrazione['prime_note_righe_iva_imponibile_indet'];
                            $totali_per_sezionale[$sezionale]['detraibile']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'] - $registrazione['prime_note_righe_iva_iva_valore_indet'];

                            // Se l'iva indetraibile è < 100 devo sommare il restante alle imposte/imponibile della giusta colonna
                            if ($registrazione['iva_percentuale_indetraibilita'] < 100) {

                                if ($registrazione['sezionali_iva_origine'] == 3) { //INTRA
                                    $totali[$registrazione['iva_id']]['intra']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'] - $registrazione['prime_note_righe_iva_imponibile_indet'];
                                    $totali[$registrazione['iva_id']]['intra']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'] - $registrazione['prime_note_righe_iva_iva_valore_indet'];
                                } else if ($registrazione['sezionali_iva_origine'] == 4) { // EXTRA
                                    $totali[$registrazione['iva_id']]['extra']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'] - $registrazione['prime_note_righe_iva_imponibile_indet'];
                                    $totali[$registrazione['iva_id']]['extra']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'] - $registrazione['prime_note_righe_iva_iva_valore_indet'];
                                } else if ($registrazione['sezionali_iva_origine'] == 1) { // ITALIA
                                    $totali[$registrazione['iva_id']]['italia']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'] - $registrazione['prime_note_righe_iva_imponibile_indet'];
                                    $totali[$registrazione['iva_id']]['italia']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'] - $registrazione['prime_note_righe_iva_iva_valore_indet'];
                                } else {
                                    debug($registrazione, true);
                                }
                            }
// if ($registrazione['iva_id'] == 46) {

// debug($registrazione['prime_note_righe_iva_imponibile']);
// debug($registrazione['prime_note_righe_iva_imponibile_indet']);

// debug($registrazione['prime_note_righe_iva_importo_iva']);
// debug($registrazione['prime_note_righe_iva_iva_valore_indet']);

// debug($registrazione);

// debug('************************************************');

// }

                        } else {

                            $totali_per_sezionale[$sezionale]['detraibile']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'];
                            $totali_per_sezionale[$sezionale]['detraibile']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'];

                            // intra -- Provvisorio, per ora verifica ==1 cioè intra, ma ci sarà anche intra extra CEE e Intra CEE nelle origini dei sezionali?
                            if ($registrazione['sezionali_iva_origine'] == 1) { // ITALIA
                                $totali[$registrazione['iva_id']]['italia']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'];
                                $totali[$registrazione['iva_id']]['italia']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'];
                            } else if ($registrazione['sezionali_iva_origine'] == 3) { // INTRA
                                $totali[$registrazione['iva_id']]['intra']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'];
                                $totali[$registrazione['iva_id']]['intra']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'];
                            } else if ($registrazione['sezionali_iva_origine'] == 4) { // EXTRA
                                $totali[$registrazione['iva_id']]['extra']['imponibile'] += $registrazione['prime_note_righe_iva_imponibile'];
                                $totali[$registrazione['iva_id']]['extra']['imposta'] += $registrazione['prime_note_righe_iva_importo_iva'];
                            } else {
                                debug($registrazione, true);
                            }
                        }
                    }
                }

                $totali_per_sezionale[$sezionale]['imponibile'] = $totale_sezionale_imponibile;

                $totali_per_sezionale[$sezionale]['imposta'] = $totale_sezionale_imposta;

            }

            $totale_italia_imponibile = 0;
            $totale_italia_imposta = 0;
            $totale_indetraibile_imponibile = 0;
            $totale_indetraibile_imposta = 0;
            $totale_reverse_imponibile = 0;
            $totale_reverse_imposta = 0;
            $totale_intra_imponibile = 0;
            $totale_intra_imposta = 0;
            $totale_extra_imponibile = 0;
            $totale_extra_imposta = 0;
            $totale_split_imponibile = 0;
            $totale_split_imposta = 0;
            foreach ($totali as $totale) {

                $totale_italia_imponibile = $totale_italia_imponibile + $totale['italia']['imponibile'];
                $totale_italia_imposta = $totale_italia_imposta + $totale['italia']['imposta'];

                $totale_indetraibile_imponibile = $totale_indetraibile_imponibile + $totale['indetraibile']['imponibile'];
                $totale_indetraibile_imposta = $totale_indetraibile_imposta + $totale['indetraibile']['imposta'];

                $totale_reverse_imponibile = $totale_reverse_imponibile + $totale['reverse']['imponibile'];
                $totale_reverse_imposta = $totale_reverse_imposta + $totale['reverse']['imposta'];

                $totale_intra_imponibile = $totale_intra_imponibile + $totale['intra']['imponibile'];
                $totale_intra_imposta = $totale_intra_imposta + $totale['intra']['imposta'];

                $totale_extra_imponibile = $totale_extra_imponibile + $totale['extra']['imponibile'];
                $totale_extra_imposta = $totale_extra_imposta + $totale['extra']['imposta'];

                $totale_split_imposta = $totale_split_imposta + $totale['split']['imposta'];
                $totale_split_imponibile = $totale_split_imponibile + $totale['split']['imponibile'];
            }

            //debug($totali, true);

            $data[$tipo]['filtri'] = $filtri;
            //$data[$tipo]['primeNoteData'] = $primeNoteData;
            $data[$tipo]['primeNoteDataGroupSezionale'] = $primeNoteDataGroupSezionale;
            $data[$tipo]['imponibili'] = $imponibili;
            $data[$tipo]['imposte'] = $imposte;
            $data[$tipo]['totali'] = $totali;

            $data[$tipo]['totali_per_sezionale'] = $totali_per_sezionale;

            $data[$tipo]['totale_italia_imponibile'] = $totale_italia_imponibile;
            $data[$tipo]['totale_italia_imposta'] = $totale_italia_imposta;

            $data[$tipo]['totale_indetraibile_imponibile'] = $totale_indetraibile_imponibile;
            $data[$tipo]['totale_indetraibile_imposta'] = $totale_indetraibile_imposta;

            $data[$tipo]['totale_reverse_imponibile'] = $totale_reverse_imponibile;
            $data[$tipo]['totale_reverse_imposta'] = $totale_reverse_imposta;

            $data[$tipo]['totale_intra_imponibile'] = $totale_intra_imponibile;
            $data[$tipo]['totale_intra_imposta'] = $totale_intra_imposta;

            $data[$tipo]['totale_extra_imponibile'] = $totale_extra_imponibile;
            $data[$tipo]['totale_extra_imposta'] = $totale_extra_imposta;

            $data[$tipo]['totale_split_imposta'] = $totale_split_imposta;
            $data[$tipo]['totale_split_imponibile'] = $totale_split_imponibile;
        }

        return $data;
    }

    public function getPianoDeiConti($full = false, $nascondi_orfani = false, $conteggi = false)
    {

        $grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
        $where = $this->datab->generate_where("grids", $grid['grids_id'], null);
        if (!$where) {
            $where = '(1=1)';
        }

        $mastri_tipi = $this->apilib->search('documenti_contabilita_mastri_tipo');
        foreach ($mastri_tipi as $key => $mastro_tipo) {
            $mastri_tipi[$key]['mastri'] = $this->apilib->search('documenti_contabilita_mastri', [
                'documenti_contabilita_mastri_tipo' => $mastro_tipo['documenti_contabilita_mastri_tipo_id'],
                ($nascondi_orfani) ? "documenti_contabilita_mastri_id IN (SELECT COALESCE(prime_note_registrazioni_mastro_dare, prime_note_registrazioni_mastro_avere) FROM prime_note_registrazioni WHERE prime_note_registrazioni_mastro_dare IS NOT NULL OR prime_note_registrazioni_mastro_avere IS NOT NULL)" : '1=1',
            ]);
            foreach ($mastri_tipi[$key]['mastri'] as $mastro_key => $mastro) {
                $mastri_tipi[$key]['mastri'][$mastro_key]['conti'] = $this->apilib->search('documenti_contabilita_conti', [
                    'documenti_contabilita_conti_mastro' => $mastro['documenti_contabilita_mastri_id'],
                    ($nascondi_orfani) ? "documenti_contabilita_conti_id IN (SELECT COALESCE(prime_note_registrazioni_conto_dare, prime_note_registrazioni_conto_avere) FROM prime_note_registrazioni WHERE prime_note_registrazioni_conto_dare IS NOT NULL OR prime_note_registrazioni_conto_avere IS NOT NULL)" : '1=1',
                ]);
                if ($conteggi) {
                    $mastri_tipi[$key]['mastri'][$mastro_key]['totale'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare - prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE $where AND (prime_note_registrazioni_mastro_dare = '{$mastro['documenti_contabilita_mastri_id']}' OR prime_note_registrazioni_mastro_avere = '{$mastro['documenti_contabilita_mastri_id']}')")->row()->s;
                }

                foreach ($mastri_tipi[$key]['mastri'][$mastro_key]['conti'] as $conto_key => $conto) {
                    if ($conteggi) {
                        $mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['totale'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare - prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE $where AND (prime_note_registrazioni_conto_dare = '{$conto['documenti_contabilita_conti_id']}' OR prime_note_registrazioni_conto_avere = '{$conto['documenti_contabilita_conti_id']}')")->row()->s;
                    }
                    if ($full || (!$conto['documenti_contabilita_conti_clienti'] && !$conto['documenti_contabilita_conti_fornitori'])) {
                        $mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['sottoconti'] = $this->apilib->search('documenti_contabilita_sottoconti', [
                            'documenti_contabilita_sottoconti_conto' => $conto['documenti_contabilita_conti_id'],
                            ($nascondi_orfani) ? "documenti_contabilita_sottoconti_id IN (SELECT COALESCE(prime_note_registrazioni_sottoconto_dare, prime_note_registrazioni_sottoconto_avere) FROM prime_note_registrazioni WHERE prime_note_registrazioni_sottoconto_dare IS NOT NULL OR prime_note_registrazioni_sottoconto_avere IS NOT NULL)" : '1=1',
                        ]);

                        foreach ($mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['sottoconti'] as $sottoconto_key => $sottoconto) {
                            if ($conteggi) {
                                $mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['sottoconti'][$sottoconto_key]['totale'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare - prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE $where AND (prime_note_registrazioni_sottoconto_dare = '{$sottoconto['documenti_contabilita_sottoconti_id']}' OR prime_note_registrazioni_sottoconto_avere = '{$sottoconto['documenti_contabilita_sottoconti_id']}')")->row()->s;
                            }
                        }
                    } else {
                        //Escludo i sottoconti dei clienti/fornitori
                        $mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['sottoconti'] = [];
                    }
                }
                // switch ($mastro['documenti_contabilita_mastri_natura_value']) {
                //     case 'Attività': //Attività
                //         $alberatura = &$alberatura_attivita;
                //         $totale_attivo += $mastro['tot'];
                //         break;
                //     case 'Passività': //Attività
                //         $mastro['tot'] = -$mastro['tot'];
                //         $totale_passivo += $mastro['tot'];
                //         $alberatura = &$alberatura_passivita;
                //         break;
                //     case 'Costi': //Attività
                //         $alberatura = &$alberatura_costi;
                //         $totale_costi +=            $mastro['tot'];
                //         break;
                //     case 'Ricavi': //Attività
                //         $mastro['tot'] = -$mastro['tot'];
                //         $alberatura = &$alberatura_ricavi;
                //         $totale_ricavi +=            $mastro['tot'];
                //         break;
                //     default:
                //         debug($mastro, true);
                //         break;
                // }
                foreach ($mastri_tipi[$key]['mastri'][$mastro_key]['conti'] as $conto_key => $conto) {
                    if ($conteggi) {
                        $mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['totale'] = $this->db->query("SELECT SUM(prime_note_registrazioni_importo_dare-prime_note_registrazioni_importo_avere) as s FROM prime_note_registrazioni LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) WHERE $where AND (prime_note_registrazioni_conto_dare = '{$conto['documenti_contabilita_conti_id']}' OR prime_note_registrazioni_conto_avere = '{$conto['documenti_contabilita_conti_id']}')")->row()->s;
                    }
                    // switch ($mastro['documenti_contabilita_mastri_natura_value']) {
                    //     case 'Passività': //Attività
                    //         $conto['tot'] = -$conto['tot'];
                    //         break;
                    //     case 'Ricavi': //Attività
                    //         $conto['tot'] = -$conto['tot'];
                    //         break;
                    //     default:
                    //         //debug($mastro, true);
                    //         break;
                    // }

                    //Decommentare se vogliamo escludere i conti a 0
                    // if ($mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]['totale'] == 0) {
                    //     unset($mastri_tipi[$key]['mastri'][$mastro_key]['conti'][$conto_key]);
                    // }
                }
            }
        }
        return $mastri_tipi;
    }
}
