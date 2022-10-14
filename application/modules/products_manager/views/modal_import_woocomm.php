<div class="row">
    <div class="col-sm-12">
        <div class="input-group">
            <input type="text" class="form-control" id="product_id" placeholder="1234567890" name="product_id">
            <span class="input-group-btn">
                <button type="button" class="btn btn-primary btn_import_single"><?php e('Import this product'); ?></button>
            </span>
        </div>
    </div>
</div>
<h3 class="text-center">OR</h3>
<div class="row text-center">
    <div class="col-sm-12">
        <button type="button" class="btn btn-info btn-lg btn_import_all"><?php e('Import last 20 products'); ?></button>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-sm-12 alert_zone"></div>
    <div class="col-sm-12">
        <pre class="log_output" style="overflow-y: scroll; height:250px; display:none"></pre>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-sm-12">
        <h3><?php e('Help - Where is the Product Id?'); ?></h3>
        <p><?php e('You can find the <b>Product ID</b> by going on your WordPress admin area, <b>click on Products</b>, and you\'ll see list of all products.<br />If you hover with mouse (without click) on one product listed, you\'ll see a voice "<code>ID: *number*</code>"... This is your Product ID'); ?></p>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.btn_import_single').on('click', function(e) {
            var product_id = $('#product_id').val();
            var all_ok = true;

            if (product_id.length == 0 || !$.isNumeric(product_id)) {
                alert("Product ID not provided or is not numeric");
                all_ok = false;
                e.preventDefault();
                e.stopPropagation();
            }

            if (all_ok) {
                var this_btn = $(this);

                this_btn.prop('disabled', true);
                $('.btn_import_all').prop('disabled', true);

                if ($('.js_import_alert').length > 0) {
                    $('.js_import_alert').remove();
                }

                $.ajax({
                    url: base_url + 'products_manager/woocommerce/importproducts/1/' + product_id,
                    success: function(data) {
                        $('.alert_zone').after().append('<div class="callout callout-info js_import_alert">Import from Woocommerce started</div>');
                        $('.log_output').text(data).show();
                        this_btn.prop('disabled', false);
                        $('.btn_import_all').prop('disabled', false);
                    },
                    error: function() {
                        this_btn.prop('disabled', false);
                        $('.btn_import_all').prop('disabled', false);
                    }
                });
            }
        });

        $('.btn_import_all').on('click', function() {
            var this_btn = $(this);

            this_btn.prop('disabled', true);
            $('.btn_import_single').prop('disabled', true);

            if ($('.js_import_alert').length > 0) {
                $('.js_import_alert').remove();
            }

            $.ajax({
                url: base_url + 'products_manager/woocommerce/importproducts',
                success: function(data) {
                    $('.alert_zone').after().append('<div class="callout callout-info js_import_alert">Import from Woocommerce started</div>');
                    $('.log_output').text(data).show();
                    this_btn.prop('disabled', false);
                    $('.btn_import_single').prop('disabled', false);
                },
                error: function() {
                    this_btn.prop('disabled', false);
                    $('.btn_import_single').prop('disabled', false);
                }
            });
        });
    });
</script>