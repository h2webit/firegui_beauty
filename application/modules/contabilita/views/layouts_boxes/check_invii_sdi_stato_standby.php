<?php
$settings = $this->apilib->searchFirst('documenti_contabilita_settings');
if ($settings['documenti_contabilita_settings_invio_sdi_attivo'] != DB_BOOL_TRUE) {
    return;
}

$where = [];
// Solo fatture o note di credito
$where[] = 'documenti_contabilita_tipo IN (1,4,11,12)';
// Solo fatture elettroniche
$where[] = "documenti_contabilita_formato_elettronico = '" . DB_BOOL_TRUE . "'";

//Escludo quelle che sono consegnate 11, mancata consegna 10, errore nel processo di invio 4, scartata da sdi 6, non inviate 1 or null
//$where[] = '(documenti_contabilita_stato_invio_sdi NOT IN (11,10,4,6,1) AND documenti_contabilita_stato_invio_sdi IS NOT NULL)';

if ($this->db->dbdriver != 'postgre') {
    $filtro_data_2g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE DATEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date) < 1)";
    $filtro_data_3h = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE HOUR(TIMEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date)) < 1)";
} else {
    $filtro_data_2g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE documenti_contabilita_cambi_stato_creation_date > now() - '1 days'::interval)";
    $filtro_data_3h = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE documenti_contabilita_cambi_stato_creation_date > now() - '2 hours'::interval)";
    // Teoricamente prima del 2019 non ci dovevano essere fatture in formato elettronico, quindi questo filtro non serve.
    // Rettifica: in realtà abbiamo dei refusi dovuti a vecchie importazioni da OUT quindi lasciamo sto filtro, male non fa.
    //$where[] = "DATE_PART('YEAR', documenti_contabilita_data_emissione) >= 2019 ";
    $where[] = "DATE_PART('YEAR', documenti_contabilita_data_emissione) >= 2019 ";
}

//inviata al server centralizzato 8, invio in corso 2, elaborazione in corso 3 (in carico a noi)
//in attesa risposta dal sdi 5, accettata da sdi 7, in attesa di consegna 9
$where[] = "((documenti_contabilita_stato_invio_sdi IN (8,2,3) AND $filtro_data_3h)"// Prendo tutte le fatture che non ricevono un aggiornamento di stato da noi da più di 3h
. ' OR '
    . "(documenti_contabilita_stato_invio_sdi IN (5,7,9, 4,6) AND $filtro_data_2g))"; // Oppure tutte le fatture che non ricevono un aggiornamento di stato da SDI da più di 2g;

/*$fatture_non_valide = $this->db
->join('documenti_contabilita_stato_invio_sdi', '(documenti_contabilita_stato_invio_sdi_id = documenti_contabilita_stato_invio_sdi)', 'LEFT')
->join('documenti_contabilita_tipo', '(documenti_contabilita_tipo_id = documenti_contabilita_tipo)', 'LEFT')
->order_by('documenti_contabilita_data_emissione', 'ASC')
->limit(10)
->where(implode(' AND ', $where), null, false)
->get('documenti_contabilita')
->result_array();*/
//debug($where);
$fatture_non_valide = $this->apilib->search('documenti_contabilita', $where, 10, 0, 'documenti_contabilita_data_emissione', 'ASC');

$conteggio = $this->db->
    // ->order_by('documenti_contabilita_data_emissione', 'DESC')
    where(implode(' AND ', $where), null, false)->count_all_results('documenti_contabilita');

// debug($fatture_non_inviate);
$fatture_numero = array_map(function ($item) {
    if (empty($item['documenti_contabilita_stato_invio_sdi_value'])) {
        $item['documenti_contabilita_stato_invio_sdi_value'] = 'Non inviato';
    }
    $item['documenti_contabilita_data_emissione'] = dateFormat($item['documenti_contabilita_data_emissione']);
    return "<li><a target=\"_blank\" href=\"" . base_url("main/layout/contabilita_dettaglio_documento/{$item['documenti_contabilita_id']}") . "\">{$item['documenti_contabilita_tipo_value']}: {$item['documenti_contabilita_numero']}/{$item['documenti_contabilita_serie']}</a> ({$item['documenti_contabilita_data_emissione']}): {$item['documenti_contabilita_stato_invio_sdi_value']}</li>";
}, $fatture_non_valide);

// debug($fatture_numero);

?>
<?php if ($conteggio): ?>
    <div class="callout callout-danger Metronic-alerts alert alert-info">
        <h4>Attenzione!</h4>

        <p>
            Hai <strong><?php echo $conteggio; ?></strong> fatture elettroniche
            che non stanno ricevendo un cambio stato da troppo tempo. <br />Di
            seguito alcune di queste (le più recenti):
        <ul>
            <?php echo implode(' ', $fatture_numero); ?>
        </ul>
        <br />Se questo messaggio persiste, si invita a contattare l'assistenza.
        </p>
    </div>
<?php endif;?>