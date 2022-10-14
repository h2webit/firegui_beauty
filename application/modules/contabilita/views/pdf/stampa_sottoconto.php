<?php
ini_set('memory_limit', '8000M');
$this->load->model('contabilita/prima_nota');

$grid = $this->db->where('grids_append_class', 'grid_stampe_contabili')->get('grids')->row_array();
$where_grid = $this->datab->generate_where("grids", $grid['grids_id'], null);
if (!$where_grid) {
    $where_grid = '(1=1)';
}

if ($customer_id) {
    $customer = $this->apilib->view('customers', $customer_id);
    $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND
    (prime_note_registrazioni_sottoconto_dare = '{$customer['customers_sottoconto']}'
    OR
    prime_note_registrazioni_sottoconto_avere = '{$customer['customers_sottoconto']}')
";
    $titolo = $customer['customers_company'];
} else {
    $where_registrazioni = "prime_note_registrazioni_prima_nota NOT IN (SELECT prime_note_id FROM prime_note WHERE prime_note_modello = 1) AND
                (prime_note_registrazioni_sottoconto_dare = '{$sottoconto_id}'
                OR
                prime_note_registrazioni_sottoconto_avere = '{$sottoconto_id}')
            ";
    $sottoconto = $this->apilib->view('documenti_contabilita_sottoconti', $sottoconto_id);
    $titolo = $sottoconto['documenti_contabilita_sottoconti_codice_completo'] . ' - ' . $sottoconto['documenti_contabilita_sottoconti_descrizione'];
}

$where = [
    "prime_note_id IN (
        SELECT
            prime_note_registrazioni_prima_nota
        FROM
            prime_note_registrazioni
        WHERE
            prime_note_registrazioni_prima_nota IS NOT NULL AND
            ($where_grid) AND ($where_registrazioni)
    ) AND prime_note_modello <> 1",
];
$limit = 100;
$offset = 0;
$_primeNoteData = [];
while ($_primeNote = $this->prima_nota->getPrimeNoteData($where, $limit, 'prime_note_id ASC', $offset, false, false, $where_registrazioni)) {
    $offset += $limit;
    $_primeNoteData = array_merge($_primeNoteData, $_primeNote);
}

//Mi costruisco un array più comodo da ciclare dopo (con chiave il conto)
foreach ($_primeNoteData as $key => $data) {
    foreach ($data['registrazioni'] as $registrazione) {
        $conto = $registrazione['sottocontodare_descrizione'] ?: $registrazione['sottocontoavere_descrizione'];
        if (empty($primeNoteData[$conto]['registrazioni'])) {
            $primeNoteData[$conto]['registrazioni'] = [];
        }
        $primeNoteData[$conto]['registrazioni'][] = $registrazione;
    }
}
//debug($primeNoteData, true);
foreach ($primeNoteData as $conto => $data) {
    //debug($primeNoteData[$conto]['registrazioni']);
    usort($primeNoteData[$conto]['registrazioni'], function ($a, $b) {
        // debug($a);
        // debug($b);

        $scadenza_a = $a['prime_note_scadenza'];
        $scadenza_b = $b['prime_note_scadenza'];

        return ($scadenza_a < $scadenza_b) ? -1 : 1;
    });
    //debug($primeNoteData[$conto]['registrazioni'], true);

}

$this->load->view('contabilita/pdf/base_stampa', [
    'primeNoteData' => $primeNoteData,
    'type' => 'sottoconto',
]);
