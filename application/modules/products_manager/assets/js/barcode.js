function calculateChecksum(ean) {
    ean = '' + ean;
    if (!ean || ean.length !== 12) throw new Error('Invalid EAN 13, should have 12 digits: ' + ean.length);
    const multiply = [1, 3];
    let total = 0;
    ean.split('').forEach((letter, index) => {
        total += parseInt(letter, 10) * multiply[index % 2];
    });
    const base10Superior = Math.ceil(total / 10) * 10;
    return base10Superior - total;
}

$(() => {
    $('.js-barcode_add').on('click', function () {
        var bar_code_container = $('.js-barcode_container').first().clone();

        $(':input', bar_code_container).val('');
        $('.js-barcode_container').last().after(bar_code_container);
    });
    var barcodes_container = $('.js-barcodes_container');


    barcodes_container.on('click', '.js-create_barcode', function () {
        var barcode_container = $(this).closest('.js-barcode_container');
        var barcode = Math.floor(Date.now() / 10);

        var checksum = calculateChecksum(barcode); //validator.calculateChecksum(barcode);

        $('.fw_products_barcode', barcode_container).val(barcode + '' + checksum);
    });

});