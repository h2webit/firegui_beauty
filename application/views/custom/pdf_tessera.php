<?php
if (empty($value_id)) redirect(base_url(), 'refresh');

$socio = $this->apilib->view('customers', $value_id);

if (empty($socio) || empty($socio['customers_card'])) redirect(base_url(), 'refresh');


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tessera Socio</title>

    <!-- CORE LEVEL STYLES -->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css?v={$this->config->item('version')}"); ?>" />
</head>

<body>
    <div class="container-fluid">


        <div class="row" style="margin-top: 500px;">
            <div class="col-md-6 col-md-offset-3">
                    <div class="retro_tessera" style="position: relative">
                        <img src="<?php echo base_url("images/pdf/tessera.png"); ?>" alt="" class="img-responsive">
                        <p class="n_tessera text-uppercase" style="position: absolute; top: 24px; left: 50%; font-size: 18px"><?php echo $socio['customers_card']; ?></p>
                        <p class="associato text-uppercase" style="position: absolute; top: 78px; left: 34%; font-size: <?php echo (strlen(strtoupper($socio['customers_last_name']) . ' ' . strtoupper($socio['customers_last_name'])) >= 18) ? '15px' : '18px'; ?> !important"><?php echo strtoupper($socio['customers_name']), ' ', strtoupper($socio['customers_last_name']); ?></p>
                        </div>
                    </div>
            </div>
        </div>

    </div>
</body>

</html>