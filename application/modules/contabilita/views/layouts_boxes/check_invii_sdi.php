<?php
$settings = $this->apilib->searchFirst('documenti_contabilita_settings');
if ($settings['documenti_contabilita_settings_invio_sdi_attivo'] != DB_BOOL_TRUE) {
    return;
}

$where = [];
// Solo quelle che non sono state accettate o che non sono ancora state inviate
$where[] = '(documenti_contabilita_stato_invio_sdi NOT IN (7, 11, 10) OR documenti_contabilita_stato_invio_sdi IS NULL)';

$where[] = 'documenti_contabilita_tipo IN (1,4,11,12)'; // Solo fatture o note di credito
$where[] = "documenti_contabilita_formato_elettronico = '" . DB_BOOL_TRUE . "'";
if ($this->db->dbdriver != 'postgre') {
    $where[] = 'DATEDIFF(NOW(), documenti_contabilita_data_emissione) > 7';
} else {
    $where[] = "documenti_contabilita_data_emissione < now() - '7 days'::interval";
    // Teoricamente prima del 2019 non ci dovevano essere fatture in formato elettronico, quindi questo filtro non serve.
    // Rettifica: in realtà abbiamo dei refusi dovuti a vecchie importazioni da OUT quindi lasciamo sto filtro, male non fa.
    //$where[] = "DATE_PART('YEAR', documenti_contabilita_data_emissione) >= 2019 ";
}
$fatture_non_valide = $this->db->join('documenti_contabilita_stato_invio_sdi', '(documenti_contabilita_stato_invio_sdi_id = documenti_contabilita_stato_invio_sdi)', 'LEFT')
    ->order_by('documenti_contabilita_data_emissione', 'ASC')
    ->limit(10)
    ->where(implode(' AND ', $where), null, false)
    ->get('documenti_contabilita')
    ->result_array();
$conteggio = $this->db->
// ->order_by('documenti_contabilita_data_emissione', 'DESC')
    where(implode(' AND ', $where), null, false)->count_all_results('documenti_contabilita');

// debug($fatture_non_inviate);
$fatture_numero = array_map(function ($item) {
    if (empty($item['documenti_contabilita_stato_invio_sdi_value'])) {
        $item['documenti_contabilita_stato_invio_sdi_value'] = 'Non inviato';
    }
    $item['documenti_contabilita_data_emissione'] = dateFormat($item['documenti_contabilita_data_emissione']);
    return "<li><a href=\"" . base_url("main/layout/contabilita_dettaglio_documento/{$item['documenti_contabilita_id']}") . "\">{$item['documenti_contabilita_numero']}/{$item['documenti_contabilita_serie']}</a> ({$item['documenti_contabilita_data_emissione']}): {$item['documenti_contabilita_stato_invio_sdi_value']}</li>";
}, $fatture_non_valide);

// debug($fatture_numero);

?>
<div class="callout callout-danger Metronic-alerts alert alert-info">
	<h4>Attenzione!</h4>

	<p>
		Hai <strong><?php echo $conteggio; ?></strong> fatture elettroniche
		che non risultano correttamente accettate o inviate allo SDI. <br />Di
		seguito alcune di queste (le più recenti):


	<ul>
    		<?php echo implode(' ', $fatture_numero); ?>
    	</ul>
	<br /> Si invita a controllarne lo stato e procedere al corretto invio.
	</p>
</div>