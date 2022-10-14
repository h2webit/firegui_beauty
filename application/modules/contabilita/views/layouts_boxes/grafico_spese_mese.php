<?php

$where_spese = ["1=1"]; //Prendo solo fatture e note di credito

//Verifico eventuali filtri impostati nel modulo contabilità:
$field_data_emissione_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'spese_data_emissione'")->row()->fields_id;
$field_categoria_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'spese_categoria'")->row()->fields_id;
$filtro_spese = (array) @$this->session->userdata(SESS_WHERE_DATA)['filtro_spese'];

$data_da = date('Y-01-01');
$data_a = date('Y-12-31');
foreach ($filtro_spese as $field_id => $filtro) {
    if ($filtro['value'] == -1 || !$filtro['value']) {
        continue;
    }
    switch ($filtro['field_id']) {
        case $field_data_emissione_id: //Filtro data

            $value_expl = explode(' - ', $filtro_spese[$field_id]['value']);
            $data_da = DateTime::createFromFormat('d/m/Y', $value_expl[0])->format('Y-m-d');
            $data_a = DateTime::createFromFormat('d/m/Y', $value_expl[1])->format('Y-m-d');

            $where_spese[] = "(spese_data_emissione >= '$data_da' AND spese_data_emissione <= '$data_a')";
            break;
        case $field_categoria_id: //Filtro data

            $where_spese[] = "spese_categoria = '{$filtro_spese[$field_id]['value']}'";
            break;

        default:
            //debug($filtro, true);
            break;
    }
}
$grid_id = $this->datab->get_grid_id_by_identifier('contabilita_spese');
$where_spese_str = $this->datab->generate_where("grids", $grid_id, $value_id);

if (!$where_spese_str) {
    $where_spese_str = '(1=1)';
}

//Mi preocostruisco la tabella fake di supporto per avere tutte le date
$query_all_days = "with recursive all_dates(dt) as (\r\n\r\n
    select '$data_da' dt\r\n
        union all \r\n
    select dt + interval 1 day from all_dates where dt + interval 1 day <= '$data_a'\r\n
) \r\n

";

$query_spese = $query_all_days . " SELECT
    coalesce(SUM(CASE WHEN spese_tipologia_fatturazione IN (4) THEN -(spese_totale-spese_iva) ELSE (spese_totale-spese_iva) END ),0) as x,
    extract(month FROM dt) as y,
    extract(year FROM dt) as anno
    FROM
        all_dates d
        left join spese spese on CAST(spese.spese_data_emissione AS DATE) = d.dt


    WHERE $where_spese_str
    GROUP BY extract(month FROM dt),extract(year FROM dt)
    ORDER BY extract(year FROM dt), extract(month from dt)";
$spese_mensile = $this->db->query($query_spese)->result_array();

//debug($this->db->last_query());

$values_spese = $categories = [];
foreach ($spese_mensile as $data) {
    $values_spese[] = number_format($data['x'], 2, '.', '');
    $meset = mese_testuale($data['y']);
    $categories[] = "{$meset} {$data['anno']}";
}

$series = [];

$series[] = [
    'name' => 'Spese',
    'data' => $values_spese,
];

//debug($fatturato_mensile);
//
?>



<div id="container_chartjs_14"></div>
<script>
    var seriescontainer_chartjs_14 = JSON.parse('<?php echo json_encode($series); ?>');
    console.log(seriescontainer_chartjs_14);
    var optionscontainer_chartjs_14 = {
        chart: {
            type: 'line',
            zoom: {
                type: 'x',
                enabled: true,
                autoScaleYaxis: true
            },
        },

        dataLabels: {
            enabled: false,
            enabledOnSeries: true,
            position: 'center',
            maxItems: 100,
            hideOverflowingLabels: true,
            orientation: 'vertical'
        },

        legend: {
            show: true
        },
        series: seriescontainer_chartjs_14,
        xaxis: {
            categories: JSON.parse('<?php echo json_encode($categories); ?>')

        },

        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(y) {

                    return y;

                }
            }
        }



    }

    var chartcontainer_chartjs_14 = new ApexCharts(document.querySelector("#container_chartjs_14"), optionscontainer_chartjs_14);

    chartcontainer_chartjs_14.render();
</script>