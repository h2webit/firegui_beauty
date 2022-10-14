<?php
$errori = $warnings_spese = [];
$where_condiviso = [
    "YEAR(prime_note_data_registrazione) = 2022",
    "prime_note_modello <> 1",
];
$where_condiviso_str = implode(' AND ', $where_condiviso);
$progressivi_doppi = array_key_value_map($this->db->query("
        SELECT *, COUNT(prime_note_progressivo_annuo) as duplicates

            FROM (SELECT * FROM prime_note WHERE $where_condiviso_str) prime_note

            GROUP BY prime_note_progressivo_annuo
            
            HAVING COUNT(prime_note_progressivo_annuo) > 1
            
            ")->result_array(), 'prime_note_progressivo_annuo', 'duplicates');

foreach ($progressivi_doppi as $progressivo => $count) {
    $errori[$count['prime_note_id']] = "Trovate $count registrazioni con progr. annuo '$progressivo'";
}

$protocolli_iva_doppi = array_key_value_map($this->db->query("
        SELECT *, COUNT(conc) as duplicates

            FROM (SELECT *,CONCAT(prime_note_protocollo,' - sez.', sezionali_iva_sezionale) as conc FROM prime_note LEFT JOIN sezionali_iva ON (sezionali_iva_id = prime_note_sezionale) WHERE prime_note_sezionale IS NOT NULL AND prime_note_protocollo IS NOT NULL AND $where_condiviso_str) prime_note

            GROUP BY conc

            HAVING COUNT(conc) > 1

            ")->result_array(), 'conc', 'duplicates');

foreach ($protocolli_iva_doppi as $protocollo => $count) {
    $errori[$count['prime_note_id']] = "Trovate $count registrazioni con protocollo iva '$protocollo'";
}

$protocollo_mancante = $this->db->query("
        SELECT prime_note_progressivo_annuo,prime_note_id
        FROM prime_note 
        WHERE 
        prime_note_modello <> 1 
        AND prime_note_id IN (
                SELECT prime_note_righe_iva_prima_nota FROM prime_note_righe_iva
            ) 
            AND (prime_note_protocollo IS NULL ) ")
    ->result_array();

foreach ($protocollo_mancante as $key => $prima_nota) {
    $errori[$prima_nota['prime_note_id']] = "Progr. anno '{$prima_nota['prime_note_progressivo_annuo']}' privo di protocollo iva.";
}

$protocolli_iva_anomali = $this->db->query("
   SELECT MAX(p1.prime_note_protocollo) as m,p1.prime_note_sezionale
   FROM prime_note p1
   WHERE 
    prime_note_modello <> '1'
    AND prime_note_sezionale IS NOT NULL AND prime_note_sezionale <> ''
    AND prime_note_sezionale IN (SELECT sezionali_iva_id FROM sezionali_iva WHERE sezionali_iva_tipo = 2)
   GROUP BY p1.prime_note_sezionale
   HAVING m > COUNT(p1.prime_note_protocollo) 
")->result_array();

foreach ($protocolli_iva_anomali as $key => $prot_iva_anomalo) {
    $prime_note = $this->apilib->search('prime_note', [
        'prime_note_sezionale' => $prot_iva_anomalo['prime_note_sezionale'],
        'prime_note_modello <> ' => 1
    ], 0, null, 'prime_note_protocollo');
    $protocolli = array_key_map_data($prime_note, 'prime_note_id');
    $prot_expected = 0;
    foreach ($protocolli as $prima_nota_id => $prima_nota) {
        $protocollo = $prima_nota['prime_note_protocollo'];
        $progressivo_annuo = $prima_nota['prime_note_progressivo_annuo'];
        $prot_expected++;
        if ($protocollo != $prot_expected) {
            if ($this->input->get('fix_prot_iva')) {
                //Correggo
                //debug("Correggo da '$protocollo' a '$prot_expected'...");
                //$this->apilib->edit('prime_note', $prima_nota_id, ['prime_note_protocollo' => $prot_expected]);
                // redirect(base_url('main/layout/prima-nota'));
                // exit;
            } else {
                $link = ''; //'<a href="?fix_prot_iva=1">Clicca qui per correggere da \'' . $protocollo . '\' a \'' . $prot_expected . '\' e successivi...</a>';
                $errori[$prima_nota_id] = "Prot. '{$protocollo}' errato per progr. annuo '{$progressivo_annuo}' sez. '{$prima_nota['sezionali_iva_sezionale']}'. Atteso '{$prot_expected}'. {$link}";
                break;
            }
        }
    }
}

$sottoconti_mancanti = $this->db->query("
        SELECT *
        FROM prime_note_registrazioni
        LEFT JOIN prime_note ON (prime_note_id = prime_note_registrazioni_prima_nota) 
        WHERE prime_note_modello <> 1
            AND (prime_note_registrazioni_sottoconto_dare IS NULL OR prime_note_registrazioni_sottoconto_dare = '') AND (prime_note_registrazioni_sottoconto_avere IS NULL OR prime_note_registrazioni_sottoconto_avere = '')")
    ->result_array();

foreach ($sottoconti_mancanti as $key => $registrazione) {
    $errori[$registrazione['prime_note_registrazioni_prima_nota']] = "Sottoconto mancante per la prima nota '{$registrazione['prime_note_progressivo_annuo']}' (riga '{$registrazione['prime_note_registrazioni_numero_riga']}').";
}

$righe_iva_senza_imponibile = $this->db->query("
    SELECT * 
    FROM prime_note_righe_iva 
    LEFT JOIN prime_note ON (prime_note_id = prime_note_righe_iva_prima_nota) 
    WHERE 
        (prime_note_righe_iva_imponibile IS NULL OR prime_note_righe_iva_imponibile = '' OR prime_note_righe_iva_imponibile = 0) 
        AND prime_note_righe_iva_prima_nota NOT IN (
	        SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1
        )
")->result_array();
foreach ($righe_iva_senza_imponibile as $key => $registrazione) {
    //$errori[$registrazione['prime_note_righe_iva_prima_nota']] = "Imponibile riga iva non calcolato per la prima nota '{$registrazione['prime_note_progressivo_annuo']}'.";
}

$note_di_credito_importo_positivo_iva = $this->db->query("
    SELECT * 
    FROM prime_note_righe_iva 
    LEFT JOIN prime_note ON (prime_note_id = prime_note_righe_iva_prima_nota) 
    LEFT JOIN prime_note_causali ON (prime_note_causale = prime_note_causali_id)
    LEFT JOIN prime_note_mappature ON (prime_note_causali_mappatura = prime_note_mappature_id)
    LEFT JOIN prime_note_mappature_tipo ON (prime_note_mappature_chiave = prime_note_mappature_tipo_id)
    WHERE 
        prime_note_mappature_tipo_identifier IN ('modello_nota_di_credito_vendita_ita', 'modello_nota_di_credito_acquisto_ita','modello_nota_di_credito_acquisto_ita')
        AND (prime_note_righe_iva_imponibile > 0 OR prime_note_righe_iva_importo_iva > 0)
        AND prime_note_righe_iva_prima_nota NOT IN (
	        SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1
        )
")->result_array();

foreach ($note_di_credito_importo_positivo_iva as $key => $registrazione) {
    $errori[$registrazione['prime_note_righe_iva_prima_nota']] = "Imponibile riga iva positivo per la prima nota '{$registrazione['prime_note_progressivo_annuo']}', di tipo nota di credito.";
}

$registro_iva_inconsistente = $this->db->query("
    SELECT prime_note_righe_iva_id,CAST(prime_note_righe_iva_imponibile / 100 * prime_note_righe_iva_indetraibilie_perc AS FLOAT) as foo1,
    CAST(prime_note_righe_iva_importo_iva / 100 * prime_note_righe_iva_indetraibilie_perc AS FLOAT) as foo2,
    prime_note_righe_iva_prima_nota,
    prime_note_progressivo_annuo
    FROM prime_note_righe_iva 
    LEFT JOIN prime_note ON (prime_note_id = prime_note_righe_iva_prima_nota) 
    LEFT JOIN prime_note_causali ON (prime_note_causale = prime_note_causali_id)
    LEFT JOIN prime_note_mappature ON (prime_note_causali_mappatura = prime_note_mappature_id)
    LEFT JOIN prime_note_mappature_tipo ON (prime_note_mappature_chiave = prime_note_mappature_tipo_id)
    WHERE 
        (
            ROUND(CAST(prime_note_righe_iva_imponibile / 100 * prime_note_righe_iva_indetraibilie_perc AS FLOAT),2) <> ROUND(CAST(prime_note_righe_iva_imponibile_indet AS FLOAT),2)
            OR 
            ROUND(CAST(prime_note_righe_iva_importo_iva / 100 * prime_note_righe_iva_indetraibilie_perc AS FLOAT),2) <> ROUND(CAST(prime_note_righe_iva_iva_valore_indet AS FLOAT),2)
        )
        AND prime_note_righe_iva_prima_nota NOT IN (
	        SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1
        )
")->result_array();
//debug($registro_iva_inconsistente,true);
foreach ($registro_iva_inconsistente as $key => $registrazione) {
    
    $errori[$registrazione['prime_note_righe_iva_prima_nota']] = "Registro iva anomalo nella registrazione '{$registrazione['prime_note_progressivo_annuo']}'. Probabile errore di calcolo sull'indetraibilità";
}

/*
debug($registrazione['prime_note_righe_iva_imponibile']);
debug($registrazione['prime_note_righe_iva_imponibile_indet']);

debug($registrazione['prime_note_righe_iva_importo_iva']);
debug($registrazione['prime_note_righe_iva_iva_valore_indet']);
*/
foreach (['mastri','conti','sottoconti'] as $cosa) {
    if ($cosa == 'mastri') {
        $campo = 'codice';
        $where_add = '';
    } else {
        $campo = 'codice_completo';
        $where_add = " AND documenti_contabilita_{$cosa}_blocco <> 1";
    }
$conti_doppi = $this->db->query("
    SELECT t1.*
    FROM documenti_contabilita_$cosa AS t1
    INNER JOIN(
        SELECT documenti_contabilita_{$cosa}_{$campo}
        FROM documenti_contabilita_$cosa
        WHERE 1 $where_add 
        GROUP BY documenti_contabilita_{$cosa}_{$campo}
        HAVING COUNT(documenti_contabilita_{$cosa}_{$campo}) > 1
    ) temp ON (t1.documenti_contabilita_{$cosa}_{$campo} = temp.documenti_contabilita_{$cosa}_{$campo})
    ORDER BY documenti_contabilita_{$cosa}_{$campo}
")->result_array();
foreach ($conti_doppi as $key => $conto) {
    $errori["generic_{$conto["documenti_contabilita_{$cosa}_id"]}"] = "Codice '{$conto["documenti_contabilita_{$cosa}_{$campo}"]}' è duplicato nei $cosa.";
}
    }




//Warning SPESE
// $spese_prive_di_registrazione = array_key_value_map($this->db->query("
//        SELECT * FROM spese WHERE spese_id NOT IN (SELECT prime_note_spesa FROM prime_note WHERE prime_note_spesa IS NOT NULL) AND spese_data_emissione > DATE_SUB(NOW(), INTERVAL 30 DAY)")->result_array(), 'prime_note_progressivo_annuo', 'duplicates');

// foreach ($spese_prive_di_registrazione as $progressivo => $count) {
//     $errori[$count['prime_note_id']] = "Trovate $count registrazioni con progr. annuo '$progressivo'";
// }



?>
<div class="row">
<?php if ($errori) : ?>
    <div class="col-md-6 callout callout-danger Metronic-alerts alert alert-info">
        <h4>Attenzione!</h4>

        <p>

            Sono stati rilevati i seguenti problemi:

        <ul>
            <?php foreach ($errori as $prima_nota_id => $errore) : ?>
                <li>
                

                
                    <?php echo $errore; ?>
                    <?php
                if (is_numeric($prima_nota_id)) :
                    $regs = $this->prima_nota->getPrimeNoteData(['prime_note_id' => $prima_nota_id], 1, null, 0, false, true);
                    $reg = array_pop($regs);
?>
                
                    <button class="btn btn-edit-primanota bg-yellow js-action_button btn-grid-action-s" onclick="javascript:initPrimanotaForm(JSON.parse(atob('<?php echo base64_encode(json_encode($reg)); ?>')),false,true)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <br /> Si invita a correggere queste registrazioni.
        </p>
    </div>
<?php endif; ?>

<?php if ($warnings_spese) : ?>
    <div class="col-md-3  callout callout-warning Metronic-alerts alert alert-info">
        <h4>Attenzione!</h4>

        <p>

            Sono stati rilevati i seguenti problemi:

        <ul>
            <?php foreach ($warnings_spese as $prima_nota_id => $errore) : ?>
                <li>
                

                
                    <?php echo $errore; ?>
                    <?php
                if (is_numeric($prima_nota_id)) :
                    $regs = $this->prima_nota->getPrimeNoteData(['prime_note_id' => $prima_nota_id], 1, null, 0, false, true);
                    $reg = array_pop($regs);
?>
                
                    <button class="btn btn-edit-primanota bg-yellow js-action_button btn-grid-action-s" onclick="javascript:initPrimanotaForm(JSON.parse(atob('<?php echo base64_encode(json_encode($reg)); ?>')),false,true)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <br /> Si invita a correggere queste registrazioni.
        </p>
    </div>
<?php endif; ?>

                </div>