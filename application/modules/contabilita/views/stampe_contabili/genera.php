<?php $this->layout->addModuleJavascript('contabilita', 'stampe_contabili.js');?>
<?php
$filtri = @$this->session->userdata(SESS_WHERE_DATA)['filter_stampe_contabili'];
$filtri = (array) $filtri;
$azienda = false;
foreach ($filtri as $filtro) {
    $field_id = $filtro['field_id'];
    $value = $filtro['value'];
    if ($value == '-1') {
        continue;
    }
    $field_data = $this->db->query("SELECT * FROM fields LEFT JOIN fields_draw ON (fields_draw_fields_id = fields_id) WHERE fields_id = '$field_id'")->row_array();
    $field_name = $field_data['fields_name'];

    if ($field_name == 'prime_note_azienda') {
        $azienda = $value;
    }

}
if ($azienda) {
    $settings = $this->apilib->view('documenti_contabilita_settings', $azienda);
} else {
    $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
}
?>
<style>
.create_iva {
    width: 100%;
    cursor:pointer;
    border-radius: 4px;
    margin-bottom: 16px;
    padding: 16px 32px;
    display: inline-block;
    font-weight: 500;
    font-size: 18px;
    transition: background .15s ease-in;
}
.create_iva.disabled {
    background-color: #f1f5f9;
    cursor: none;
    color: #AAA;
}

.create_iva:hover {
    background: #cbd5e1;
}
</style>


<?php
$anno_corrente = date('Y');

if ($azienda && $this->input->get('anno') &&
    ($this->input->get('mese') || $this->input->get('trimestre'))
) {
    $filtro = [
        'contabilita_stampe_definitive_anno' => $this->input->get('anno'),
        'contabilita_stampe_definitive_azienda' => $azienda,
    ];
    if ($this->input->get('mese')) {
        $filtro['contabilita_stampe_definitive_mese'] = $this->input->get('mese');
    }
    if ($this->input->get('trimestre')) {
        $filtro['contabilita_stampe_definitive_trimestre'] = $this->input->get('trimestre');
    }
    $last_definitivo = $this->apilib->searchFirst('contabilita_stampe_definitive', $filtro);
//debug($last_definitivo);

    if (empty($last_definitivo)) {
        $iva_vendite = true;
        $iva_acquisti = true;
        $liquidazione = false;
        $libro_giornale = false;
    } else {
        $iva_vendite = false;
        $iva_acquisti = false;
        $liquidazione = false;
        $libro_giornale = false;
        if (!$last_definitivo['contabilita_stampe_definitive_iva_vendite_pdf']) {
            $iva_vendite = true;
        }
        if (!$last_definitivo['contabilita_stampe_definitive_iva_acquisti_pdf']) {
            $iva_acquisti = true;
        }

        if (!$last_definitivo['contabilita_stampe_definitive_liquidazione_iva_pdf'] && $last_definitivo['contabilita_stampe_definitive_iva_vendite_pdf'] && $last_definitivo['contabilita_stampe_definitive_iva_acquisti_pdf']) {
            //debug($this->apilib->view('prime_note', 7919),true);
            $prima_nota_liquidazione_exists = $this->apilib->searchFirst('prime_note', [
                'prime_note_causali_tipo' => '7',
//'prime_note_stampa_definitiva IS NULL',

                'prime_note_modello <> 1',

            ], 0, 'prime_note_id DESC');
            if ($prima_nota_liquidazione_exists) {
                $liquidazione = true;
            }

        }
        if (!$last_definitivo['contabilita_stampe_definitive_libro_giornale_pdf'] && $last_definitivo['contabilita_stampe_definitive_liquidazione_iva_pdf'] && ($this->input->get('trimestre') == 4 || $this->input->get('mese') == 12)) {
            $libro_giornale = true;
        }

        //debug($last_definitivo, true);
    }
} else {
    $iva_vendite = false;
    $iva_acquisti = false;
    $liquidazione = false;
    $libro_giornale = false;

}

?>

<div class="row">
    <div class="col-sm-3">
        <div class="create_iva text-uppercase text-center js_registro_iva_vendite_definitivo <?php if ($iva_vendite): ?>btn-success<?php else: ?>disabled<?php endif;?>">
            <span>genera registro <br />iva vendite</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="create_iva text-uppercase text-center js_registro_iva_acquisti_definitivo <?php if ($iva_acquisti): ?>btn-success<?php else: ?>disabled<?php endif;?>">
            <span>genera registro <br />iva acquisti</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="create_iva text-uppercase text-center js_liquidazione_iva_definitivo <?php if ($liquidazione): ?>btn-success<?php else: ?>disabled<?php endif;?>">
            <span>genera <br /> liquidazione iva</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="create_iva text-uppercase text-center js_libro_giornale_definitivo <?php if ($libro_giornale): ?>btn-success<?php else: ?>disabled<?php endif;?>">
            <span>genera <br /> libro giornale</span>
        </div>
    </div>
</div>

<div class="row hide">
    <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase js_registro_iva_vendite_definitivo">
            <span>genera registro <br />iva vendite</span>
        </div>
    </div>
    <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase js_registro_iva_acquisti_definitivo">
            <span>genera registro <br />iva acquisti</span>
        </div>
    </div>
    <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase js_liquidazione_iva_definitivo">
            <span>genera <br /> liquidazione iva</span>
        </div>
    </div>
    <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase js_libro_giornale_definitivo">
            <span>genera <br /> libro giornale</span>
        </div>
    </div>
    <!--     <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase">
            genera registro <br />iva vendite
        </div>
    </div>
    <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase">
            genera registro <br />iva acquisti
        </div>
    </div>
    <div class="col-sm-4 text-center">
        <div class="create_iva text-uppercase">
            genera <br /> liquidazione iva
        </div>
    </div> -->
</div>

