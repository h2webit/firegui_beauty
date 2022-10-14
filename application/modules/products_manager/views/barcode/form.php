<?php
$product = $this->apilib->view('fw_products', $value_id);

$generator = new Picqer\Barcode\BarcodeGeneratorPNG();

$type = $generator::TYPE_EAN_13;

$barcodes = json_decode($product['fw_products_barcode'], true);

if (!is_array($barcodes)) {
    $barcodes = [$barcodes];
}

if (!empty($barcodes)):
?>

<div class="js_barcode_grid text-center">
    <table class="table table-condensed">
        <tbody>
            <?php foreach ($barcodes as $barcode) : ?>
                <tr>
                    <td><?php echo $barcode; ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn-view" data-barcode="<?php echo $barcode; ?>" data-barcode_b64="<?php echo base64_encode($barcode); ?>" data-barcode_img="data:image/png;base64,<?php echo base64_encode($generator->getBarcode($barcode, $type)); ?>">
                            <i class="fas fa-eye fa-fw"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>
    <div class="callout callout-info"><?php e('No barcode provided.') ?></div>;
<?php endif; ?>
    
    <div class="text-center js_barcode_container hide" data-type="<?php echo $type; ?>" data-value="" data-url="products_manager/productsmanager/print_barcode/">
        <div>
            <input type="hidden" name="product_name" value="<?php echo base64_encode(character_limiter($product['fw_products_name'], 15)); ?>">
            <strong class="product_name"><?php echo character_limiter($product['fw_products_name'], 15); ?></strong>
        </div>
        
        <div>
            <img class="barcode_img" /><br />
        </div>
        <div>
            <small class="barcode_label"></small>
        </div>
        
        <div class="row">
            <div class="col-md-12 ">
                Misure:
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>w:</label>
                    <input class="form_control" type="text" name="w" size="3" /> mm
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>h:</label>
                    <input class="form_control" type="text" name="h" size="3" /> mm
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>left:</label>
                    <input class="form_control" type="text" name="left" size="3" /> mm
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>top:</label>
                    <input class="form_control" type="text" name="top" size="3" /> mm
                </div>
            </div>
            
            <a class="btn btn-info js_print" href="javascript:void(0);"><?php e('Print'); ?></a>
        </div>
    </div>
</div>

<script>
    $(function () {
        // 'use strict';
        
        $('.btn-view').on('click', function () {
            var barcode_ct = $('.js_barcode_container');
            
            barcode_ct.find('img.barcode_img').prop('src', '');
            barcode_ct.find('small.barcode_label').text('');
            barcode_ct.prop('data-value', '');
            
            var btn = $(this);
            
            var barcode = btn.data('barcode');
            var barcode_b64 = btn.data('barcode_b64');
            var barcode_img = btn.data('barcode_img');
            
            
            barcode_ct.find('img.barcode_img').prop('src', barcode_img);
            barcode_ct.find('small.barcode_label').text(barcode);
            barcode_ct.attr('data-value', barcode_b64);
            
            barcode_ct.removeClass('hide');
            
            $('.js_print').on('click', function () {
                var container = $(this).closest('.js_barcode_container');
                var w = $('[name="w"]', container).val();
                var h = $('[name="h"]', container).val();
                var left = $('[name="left"]', container).val();
                var top = $('[name="top"]', container).val();
                var type = container.data('type');
                var value = container.attr('data-value');
                var product_name = $('[name="product_name"]').val();
                var pars = '&w=' + w + '&h=' + h + '&left=' + left + '&top=' + top + '&product_name='+product_name;
                var url = '<?php echo base_url(); ?>' + container.data('url') + type + '/?val=' + value + pars;
                window.open(url, '_blank');
            });
        });
    });
</script>