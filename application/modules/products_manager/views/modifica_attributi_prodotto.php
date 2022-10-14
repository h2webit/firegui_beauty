<?php
$ids = $this->input->post('ids');
$ids_comma = implode(',', $ids);


$_attributi = $this->apilib->search('attributi');
$attributi = $attribute_values_reverse = [];
foreach ($_attributi as $key => $attributo) {
    $attributi[$attributo['attributi_id']] = $attributo;
    $attributi[$attributo['attributi_id']]['attributi_valori'] = $this->apilib->search('attributi_valori', ['attributi_valori_attributo' => $attributo['attributi_id']]);
    foreach ($attributi[$attributo['attributi_id']]['attributi_valori'] as $attributo_valore) {
        $attribute_values_reverse[$attributo_valore['attributi_valori_id']] = $attributo_valore;
    }
}

?>

<form action="<?php echo base_url('products_manager/productsmanager/bulkEditAttributi'); ?>" class="formAjax">
    <?php echo add_csrf(); ?>

    <input type="hidden" name="products" value="<?php echo $ids_comma ?>">

    <?php foreach ($attributi as $key => $attribute) : ?>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label-title js-quantity"><?php echo $attribute['attributi_nome']; ?></label>
                <select class="form-control select2_standard" style="width: 100%;" name="attributes[<?php echo $attribute['attributi_id']; ?>]">
                    <option value="0">Lascia Invariato</option>
                    <option value="-1">Rimuovi</option>
                    <option value="" disabled>---</option>
                    <?php foreach ($attribute['attributi_valori'] as $valore) : ?>
                        <option value="<?php echo $valore['attributi_valori_id']; ?>">
                            <?php echo $valore['attributi_valori_label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    <?php endforeach; ?>

    <div class="col-sm-12">
        <hr />
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-lg btn-save pull-right">Salva</button>
        </div>
    </div>
</form>