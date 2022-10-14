<?php
$this->load->model('contabilita/prima_nota');
$ultima_stampa_definitiva = $this->apilib->searchFirst('contabilita_stampe_definitive', [], 0, 'contabilita_stampe_definitive_id');
//debug($ultima_stampa_definitiva,true);

//TODO: indagare perchè mettendo i filtri non va più...

$liquidazione_iva_data = $this->prima_nota->getIvaData(
// [
//     "prime_note_stampa_definitiva_acquisti = '{$ultima_stampa_definitiva['contabilita_stampe_definitive_id']}' OR prime_note_stampa_definitiva_vendite = '{$ultima_stampa_definitiva['contabilita_stampe_definitive_id']}'
//     ",
// ]

);
//debug($liquidazione_iva_data['vendite'], true);


unset($liquidazione_iva_data['acquisti']['primeNoteDataGroupSezionale']);
unset($liquidazione_iva_data['vendite']['primeNoteDataGroupSezionale']);

$totale_imposta_acquisti = $liquidazione_iva_data['acquisti']['totale_italia_imposta']+$liquidazione_iva_data['acquisti']['totale_indetraibile_imposta']+$liquidazione_iva_data['acquisti']['totale_intra_imposta'];
$totale_imposta_acquisti_indetraibile = $liquidazione_iva_data['acquisti']['totale_indetraibile_imposta'];
$totale_imoposta_acquisti_detraibile = $totale_imposta_acquisti - $totale_imposta_acquisti_indetraibile;

$totale_imposta_vendite = $liquidazione_iva_data['vendite']['totale_italia_imposta']+$liquidazione_iva_data['vendite']['totale_intra_imposta'];

$differenza = $totale_imposta_vendite-$totale_imoposta_acquisti_detraibile;

//TODO: prendere dal conto speciale Erario c.to IVA, l'importo (sia negativo che positivo) e sottrarlo da quello che devo

//Cerco sottoconto compensazione iva
$sotto_conto_comp_iva = $this->apilib->searchFirst('documenti_contabilita_sottoconti', [
    'documenti_contabilita_sottoconti_compensazione_iva' => DB_BOOL_TRUE
]);
function reverseDate($data) {
    $data = explode('/', $data);
    return "{$data[2]}-{$data[1]}-{$data[0]}";
}
$credito_compensabile_portato_in_detrazione_avere = $credito_compensabile_portato_in_detrazione_dare = 0;
$filtri = $liquidazione_iva_data['acquisti']['filtri'];
if ($sotto_conto_comp_iva) {
    foreach ($filtri as $filtro) {
        if ($filtro['label'] == 'Data registrazione') {
            $date_reg_expl = explode(' - ',$filtro['value']);
            
            $data_reg_da = reverseDate($date_reg_expl[0]);
            $data_reg_a = reverseDate($date_reg_expl[1]);
        }
        if ($filtro['label'] == 'Data documento') {
            $date_doc_expl = explode(' - ',$filtro['value']);
            $data_doc_da = reverseDate($date_doc_expl[0]);
            $data_doc_a = reverseDate($date_doc_expl[1]);
        }
        if ($filtro['label'] == 'Azienda') {
                
            $azienda = $filtro['raw_data_value'];
            
        }
    }
    
    
    $registrazione_iva_compensazione = $this->apilib->searchFirst('prime_note_registrazioni', [
        "(prime_note_registrazioni_sottoconto_dare = '{$sotto_conto_comp_iva['documenti_contabilita_sottoconti_id']}' OR prime_note_registrazioni_sottoconto_avere = '{$sotto_conto_comp_iva['documenti_contabilita_sottoconti_id']}')",
        'prime_note_data_registrazione >=' => $data_reg_da,
        'prime_note_data_registrazione <=' => $data_reg_a,
// 'prime_note_scadenza >=' => $data_doc_da,
// 'prime_note_scadenza <=' => $data_doc_a,

        'prime_note_azienda' => $azienda
    ],0,'prime_note_data_registrazione', 'DESC');
    
    if ($registrazione_iva_compensazione) {
        
        if ($registrazione_iva_compensazione['prime_note_registrazioni_importo_dare'] > 0) { //ATTENZIONE: è corretto che qui si inverta dare/avere (logiche italiane)
            $credito_compensabile_portato_in_detrazione_avere = $registrazione_iva_compensazione['prime_note_registrazioni_importo_dare'];//'xxx';
        } else {
            
            $credito_compensabile_portato_in_detrazione_dare = $registrazione_iva_compensazione['prime_note_registrazioni_importo_avere'];
        }
        
    }
}


if ($credito_compensabile_portato_in_detrazione_avere) {
    $totale_iva_da_versare_parziale = $differenza+$credito_compensabile_portato_in_detrazione_avere;
} else {
    $totale_iva_da_versare_parziale = $differenza-$credito_compensabile_portato_in_detrazione_dare;
}



//Interessi trimestrali 1% su {parziale}
$interessi_trimestrali = $totale_iva_da_versare_parziale/100;
//
$totale_da_versare = $totale_iva_da_versare_parziale + $interessi_trimestrali;

//TODO: inserire il filtro data


?>
<div style="margin-bottom:30px">

<?php foreach ($filtri as $filtro) : ?>
    <p><strong><?php echo $filtro['label']; ?></strong>: <?php echo $filtro['value']; ?></p>
<?php endforeach; ?>

</div>
<table class="table">
    <thead>
        <tr>
            <th>
                Descrizione
            </th>
            <th>
                Dare
            </th>
            <th>
                Avere
            </th>
            
        </tr>

    </thead>

    <tbody>
        <tr>
            <td>IVA VENDITE</td>
            <td><?php e_money($totale_imposta_vendite); ?></td>
            <td></td>

        </tr>
        <tr>
            <td>IVA ESIGIBILE PER IL PERIODO</td>
            <td></td>
            <td><?php e_money($totale_imposta_vendite); ?></td>

        </tr>
        <tr>
            <td>IVA ACQUISTI</td>
            <td></td>
            <td><?php e_money($totale_imposta_acquisti); ?></td>

        </tr>
        <tr>
            <td>di cui IVA indetraibile</td>
            <td><?php e_money($totale_imposta_acquisti_indetraibile); ?></td>
            <td></td>

        </tr>
        <tr>
            <td>IVA DETRAIBILE PER IL PERIODO</td>
            <td></td>
            <td><?php e_money($totale_imoposta_acquisti_detraibile); ?></td>
        </tr>
        <tr>
            <td>IVA a debito/credito per il periodo</td>
            <td></td>
            <td><?php e_money($differenza); ?></td>
        </tr>
        <tr>
            <td>TOTALE IVA PER IL PERIODO</td>
            <td></td>
            <td><?php e_money($differenza); ?></td>
        </tr>
        <?php if ($credito_compensabile_portato_in_detrazione_dare || $credito_compensabile_portato_in_detrazione_avere): ?>
        <tr>
            <td>Credito compensabile portato in detrazione</td>
            
            <td><?php if ($credito_compensabile_portato_in_detrazione_dare > 0) {e_money($credito_compensabile_portato_in_detrazione_dare);} ?></td>
            <td><?php if ($credito_compensabile_portato_in_detrazione_avere > 0) {e_money($credito_compensabile_portato_in_detrazione_avere);} ?></td>
            
        </tr>
        <?php endif; ?>
        <tr>
            <td>TOTALE IVA A DEBITO</td>
            <td></td>
            <td><?php e_money($totale_iva_da_versare_parziale); ?></td>
        </tr>
        <tr>
            <td>TOTALE IVA DOVUTA</td>
            <td></td>
            <td><?php e_money($totale_iva_da_versare_parziale); ?></td>
        </tr>
        <tr>
            <td>Interessi trimestrali 1%</td>
            <td></td>
            <td><?php e_money($interessi_trimestrali); ?></td>
        </tr>
        <tr>
            <td>TOTALE IVA PERDIODICA DA VERSARE</td>
            <td></td>
            <td><?php e_money($totale_da_versare); ?></td>
        </tr>
    </tbody>

</table>
