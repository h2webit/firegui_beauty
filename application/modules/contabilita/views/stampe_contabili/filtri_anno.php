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
.btn_filter_custom {
    background-color: #f1f5f9;
    border-radius: 4px;
    color: #000000;
    transition: background .15s ease-in;
}

.btn_filter_custom:hover {
    background: #cbd5e1;
}

.btn_filter_custom_active {
    background-color: #0ea5e9;
    color: #ffffff;
    transition: background .15s ease-in;
}

.btn_filter_custom_active:hover {
    background-color: #0284c7;
    color: #ffffff;
}


.btn_year {
    width: 100%;
    margin-bottom: 8px;
    font-size: 16px;
    font-weight: 500;
    /*padding: 8px 40px;*/
}

.btn_quarter,
.btn_month {
    width: 100%;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
}


.filter_year_container,
.filter_trimestre_container,
.filter_month_container {
    width: 100%;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    overflow-y: scroll;
    margin-bottom: 16px;
}

.btn_filter_custom {
    width: 150px;
    margin-right: 16px;
}
</style>

<!-- Annuale -->
<div class="row">
    <!--     <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2024</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2023</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year btn_filter_custom_active">2022</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2021</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2020</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2019</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2018</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2017</a>
    </div>
    <div class="col-md-4 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2016</a>
    </div> -->
    <div class="col-sm-12">
        <div class="filter_year_container">
            <?php for ($anno = date('Y'); $anno >= date('Y') - 12; $anno--): ?>
            <a href="#" class="btn btn-sm btn_filter_custom btn_year<?php if ($anno == $this->input->get('anno')): ?> btn_filter_custom_active<?php endif;?>"><?php echo $anno; ?></a>
            <?php endfor;?>
        </div>
    </div>
    <!--     <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year btn_filter_custom_active">2022</a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2021</a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2020</a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2019</a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2018</a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2017</a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_year">2016</a>
    </div> -->
</div>


<!-- <div class="row">
    <div class="col-sm-12">
        <hr>
    </div>
</div> -->
<?php if ($settings['documenti_contabilita_settings_liquidazione_iva'] == 2): ?>
<!-- Trimestrale -->
<div class="row">
    <div class="col-sm-12">
        <div class="filter_trimestre_container">
            <a href="#" class="btn btn-sm text-uppercase btn_filter_custom btn_quarter<?php if (1 == $this->input->get('trimestre')): ?> btn_filter_custom_active<?php endif;?>" data-trimestre="1"><?php e('1° trim.');?></a>
            <a href="#" class="btn btn-sm text-uppercase btn_filter_custom btn_quarter<?php if (2 == $this->input->get('trimestre')): ?> btn_filter_custom_active<?php endif;?>" data-trimestre="2"><?php e('2° trim.');?></a>
            <a href="#" class="btn btn-sm text-uppercase btn_filter_custom btn_quarter<?php if (3 == $this->input->get('trimestre')): ?> btn_filter_custom_active<?php endif;?>" data-trimestre="3"><?php e('3° trim');?></a>
            <a href="#" class="btn btn-sm text-uppercase btn_filter_custom btn_quarter<?php if (4 == $this->input->get('trimestre')): ?> btn_filter_custom_active<?php endif;?>" data-trimestre="4"><?php e('4° trim');?></a>
        </div>
    </div>
    <!--     <div class="col-md-3 text-center text-uppercase">
        <a href="#" class="btn btn-sm btn_filter_custom btn_quarter"><?php e('1° trim.');?></a>
    </div>
    <div class="col-md-3 text-center text-uppercase">
        <a href="#" class="btn btn-sm btn_filter_custom btn_quarter btn_filter_custom_active"><?php e('2° trim.');?></a>
    </div>
    <div class="col-md-3 text-center text-uppercase">
        <a href="#" class="btn btn-sm btn_filter_custom btn_quarter"><?php e('3° trim');?></a>
    </div>
    <div class="col-md-3 text-center text-uppercase">
        <a href="#" class="btn btn-sm btn_filter_custom btn_quarter"><?php e('4° trim');?></a>
    </div> -->
</div>
<?php endif;?>
<?php if ($settings['documenti_contabilita_settings_liquidazione_iva'] == 1): ?>
<!-- Mensile -->
<div class="row">
    <div class="col-sm-12">
        <div class="filter_month_container">
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (1 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="1">Gennaio</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (2 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="2">Febbraio</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (3 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="3">Marzo</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (4 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="4">Aprile</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (5 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="5">Maggio</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (6 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="6">Giugno</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (7 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="7">Luglio</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (8 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="8">Agosto</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (9 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="9">Settembre</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (10 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="10">Ottobre</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (11 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="11">Novembre</a>
            <a href="#" class="btn btn-sm btn_filter_custom btn_month<?php if (12 == $this->input->get('mese')): ?> btn_filter_custom_active<?php endif;?>" data-mese="12">Dicembre</a>
        </div>
    </div>
    <!--     <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('January');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('February');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('March');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('April');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('May');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month btn_filter_custom_active"><?php e('June');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('July');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('August');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('September');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('October');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('November');?></a>
    </div>
    <div class="col-md-3 text-center">
        <a href="#" class="btn btn-sm btn_filter_custom btn_month"><?php e('December');?></a>
    </div> -->
</div>
<?php endif;?>