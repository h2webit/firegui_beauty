<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">

    <title>Layout PDF</title>

    <!-- CDN Stylesheets -->
    <link rel="stylesheet" href="<?php echo base_url("template/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css"); ?>" />

    <!-- Custom Stylesheet -->
    <style>
        .table>tbody>tr>td,
        .table>tbody>tr>th {
            border: none;
        }

        body {
            font-size: 1.5em;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="box">
                    <div class="box-body">
                        <?php echo $html; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>