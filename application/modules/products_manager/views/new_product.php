<style>
.head-label {
    font-weight: 200;
    font-size: xx-large
}

.form-label-title {
    font-size: 20px;
    font-weight: 200
}

.sub-label span {
    font-weight: 200;
    font-size: x-large
}

.margin-top-adjust-10 {
    margin-top: 10px
}

.text-size-adjust {
    font-size: 20px;
    font-weight: 200
}

.glyphicon.glyphicon-cloud-upload {
    font-size: 75px
}

.alert-danger {
    border-color: #d73925;
    border-radius: 3px;
    background-color: #dd4b39 !important;
    padding-top: 13px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    min-height: 46px;
    padding-left: 30px
}

.checkbox-centre {
    text-align: center
}

#main_image_container {
    display: flex;
    justify-content: center;
}

#main_image_preview {
    max-height: 400px;
    margin-top: 15px;
}

.img-wrapper {
    position: relative;
}

.img-overlay {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    text-align: center;
}

.img-overlay:before {
    content: ' ';
    display: block;
    /* adjust 'height' to position overlay content vertically */
    height: 5%;
}

.js-btn_calcola_prezzo {
    position: absolute;
    top: 0px;
    right: 15px;
}
</style>

<?php
//    debug($this->apilib->search('fw_products'));
$_attributi = $this->apilib->search('attributi');
$attributi = $attribute_values_reverse = [];
foreach ($_attributi as $key => $attributo) {
    $attributi[$attributo['attributi_id']] = $attributo;
    $attributi[$attributo['attributi_id']]['attributi_valori'] = $this->apilib->search('attributi_valori', ['attributi_valori_attributo' => $attributo['attributi_id']]);
    foreach ($attributi[$attributo['attributi_id']]['attributi_valori'] as $attributo_valore) {
        $attribute_values_reverse[$attributo_valore['attributi_valori_id']] = $attributo_valore;
    }
}

$gruppi = $this->apilib->search('fw_products_gruppi');

$fuori_listino = $this->apilib->search('fw_products_fuori_listino');

$brands = $this->apilib->search('fw_products_brand');

$fornitori = $this->apilib->search('customers', ['customers_type' => 2]);

$categorie = $this->apilib->search('fw_categories');

$unita_misura = $this->apilib->search('fw_products_unita_misura');

$price_list_labels = $this->apilib->search('price_list_labels');

$fw_products_kind = $this->apilib->search('fw_products_kind');

//TODO: prendere la tassazione dalla tabella corretta (es.: se è installato il modulo contabilità l'iva la detta lui!)
$taxes =
    $this->apilib->search('iva', [], null, 0, 'iva_order');

$prodotto_id = $value_id;

// $attributi_prodotto = [];

if ($prodotto_id) {
    $prodotto = $this->apilib->view('fw_products', $prodotto_id);
    $prodotto['configurati'] = $this->apilib->search('fw_products', ['fw_products_parent' => $prodotto_id]);
    $prodotto['attributi'] = json_decode($prodotto['fw_products_json_attributes'], true);
    //debug($prodotto, true);
    // foreach ($prodotto['configurati'] as $pc) {
    //     $attributi_prodotto_configurato = json_decode($pc['fw_products_json_attributes'], true);
    //     if (is_array($attributi_prodotto_configurato)) {
    //         foreach ($attributi_prodotto_configurato as $attributo_label) {
    //             $attributi_prodotto[] = $attributo_label;
    //         }
    //     } else {
    //         //debug($attributi_prodotto_configurato);
    //     }
    // }

    $prodotto['prices'] = [];
    foreach ($price_list_labels as $label) {
        $price = $this->apilib->searchFirst('price_list', ['price_list_product' => $prodotto_id, 'price_list_label' => $label['price_list_labels_id']]);

        $prodotto['prices'][$label['price_list_labels_id']] = ($price) ? $price['price_list_price'] : $prodotto['fw_products_sell_price'];
    }
}

//debug($prodotto, true);

$field = $this->db
    ->join('fields', 'forms_fields_fields_id = fields_id', 'left')
    ->join('fields_draw', 'fields_draw_fields_id=fields_id', 'left')
    ->where("forms_fields_forms_id in (select forms_id from forms where forms_identifier = 'prodotti_immagini')")
    ->get('forms_fields')
    ->row_array();

$form_id = $field['forms_fields_forms_id'];
$form = $this->db->join('entity', 'forms_entity_id = entity_id')->get_where('forms', ['forms_id' => $form_id])->row_array();
?>
<?php $this->layout->addModuleJavascript('products_manager', 'js/barcode.js'); ?>
<form role="form" method="post" action="<?php echo base_url("products_manager/productsmanager/create_product"); ?>" class="formAjax" enctype="multipart/form-data" id="form_<?php echo $form_id; ?>" data-edit-id="<?php echo $value_id ?? null ?>">
    <?php add_csrf(); ?>
    <input type="hidden" name="prodotto_id" value="<?php echo $prodotto_id; ?>" />
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-6 head-label">
                <?php if (!$prodotto_id) : ?><?php e('New product'); ?><?php else : ?><?php e('Edit product'); ?><?php endif; ?>
            </div>

            <div class="col-md-6">
                <div class="col-md-6">
                    <button type="button" onclick="goBack()" class="btn btn-block btn-primary btn-lg"><?php e('back'); ?></button>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-block btn-success btn-lg"><?php e('Save'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <hr />

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3 sub-label">

                <div class="row">
                    <span><?php e('General'); ?></span></br>
                    <small><?php e('Edit general information about this product'); ?></small>
                </div>


            </div>
            <div class="col-md-9">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Kind'); ?></label>
                        <small class="text-danger fas fa-asterisk" style="font-size: 85%"></small>
                        <select name="fw_products_kind" id="fw_products_kind" class="form-control select2_standard">
                            <?php foreach ($fw_products_kind as $kind): ?>
                            <option value="<?php echo $kind['fw_products_kind_id'] ?>" <?php echo ($prodotto_id && $kind['fw_products_kind_id'] == $prodotto['fw_products_kind']) ? 'selected' : null; ?>><?php echo t($kind['fw_products_kind_value']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Name'); ?></label>
                        <small class="text-danger fas fa-asterisk" style="font-size: 85%"></small>
                        <input type="text" class="form-control" required placeholder="nome prodotto" name="fw_products_name" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_name'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <?php $form_brand_id = $this->datab->get_form_id_by_identifier('new_brand_fast'); ?>
                        <label class="form-label-title"><?php e('Brand'); ?>
                            <a href="#" class=" btn btn-xs btn-default js_create_brand">
                                <i class="fa fa-plus "></i>
                            </a>
                            <script>
                            $(function() {
                                var button = $('.js_create_brand');
                                var thisField = button.parent().parent().find('[name="fw_products_brand"]');
                                var subform = <?php echo $form_brand_id; ?>;

                                button.on('click', function() {
                                    openCreationForm(subform, 'fw_products_brand', function(id, name) {
                                        thisField.append($('<option/>').attr('value', id).text(name));
                                        thisField.val(id).trigger("change");
                                    });
                                });
                            });
                            </script>
                        </label>
                        <select class="form-control select2_standard" style="width: 100%;" name="fw_products_brand">
                            <option value=""> --- </option>
                            <?php foreach ($brands as $brand) : ?>
                            <option value="<?php echo $brand['fw_products_brand_id']; ?>" <?php if ($prodotto_id && $brand['fw_products_brand_id'] == $prodotto['fw_products_brand']) : ?> selected="selected" <?php endif; ?>><?php echo $brand['fw_products_brand_value']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <?php $form_provider_id = $this->datab->get_form_id_by_identifier('suppliers-subform'); ?>
                        <label class="form-label-title"><?php e('Supplier'); ?>
                            <a href="#" class=" btn btn-xs btn-default js_create_provider">
                                <i class="fa fa-plus "></i>
                            </a>
                            <script>
                            $(function() {
                                var button = $('.js_create_provider');
                                var thisField = button.parent().parent().find('[name="fw_products_supplier"]');
                                var subform = <?php echo $form_provider_id; ?>;

                                button.on('click', function() {
                                    openCreationForm(subform, 'customers', function(id, name) {
                                        thisField.append($('<option/>').attr('value', id).text(name));
                                        thisField.val(id).trigger("change");
                                    });
                                });
                            });
                            </script>
                        </label>
                        <select class="form-control select2_standard" style="width: 100%;" name="fw_products_supplier">
                            <option value=""> --- </option>
                            <?php foreach ($fornitori as $fornitore) : ?>
                            <option value="<?php echo $fornitore['customers_id']; ?>" <?php if ($prodotto_id && $fornitore['customers_id'] == $prodotto['fw_products_supplier']) : ?> selected="selected" <?php endif; ?>><?php echo $fornitore['customers_company']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title js-sku-code"><?php e('Sku'); ?></label>

                        <input type="text" class="form-control" placeholder="<?php e('Sku'); ?>" name="fw_products_sku" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_sku'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12 provider_column">
                                <label class="form-label-title js-provider-code"><?php e('Provider code'); ?></label>
                                <a href="javascript:void(0);" class=" btn btn-xs btn-default js-provider_code-add">
                                    <i class="fa fa-plus "></i>
                                </a>

                                <?php
                                if ($prodotto_id) {
                                    $providers_code = (is_array(json_decode($prodotto['fw_products_provider_code'], true))) ? json_decode($prodotto['fw_products_provider_code'], true) : [$prodotto['fw_products_provider_code']];

                                    if (empty($providers_code)) {
                                        $providers_code = [''];
                                    }
                                } else {
                                    $providers_code = [''];
                                }
                                // debug($providers_code, true);

                                ?>
                                <?php foreach ($providers_code as $key => $provider_code) : ?>
                                <?php
                                    if (!empty($provider_code['code'])) {
                                        $code = $provider_code['code'];
                                    } else {
                                        $code = '';
                                    }
                                    if (!empty($provider_code['supplier'])) {
                                        $supplier = $provider_code['supplier'];
                                    } else {
                                        $supplier = '';
                                    }


                                    ?>
                                <div class="row js-provider_code_container">
                                    <div class="col-xs-12 col-sm-6">
                                        <input type="text" class="form-control" placeholder="<?php e('Provider code'); ?>" name="fw_products_provider_code[code][]" value="<?php echo ($prodotto_id) ? $code : ''; ?>">
                                    </div>
                                    <div class="col-xs-12 col-sm-6">
                                        <select class="form-control" style="width: 100%;" name="fw_products_provider_code[supplier][]">
                                            <option value=""> --- </option>
                                            <?php foreach ($fornitori as $fornitore) : ?>
                                            <option value="<?php echo $fornitore['customers_id']; ?>" <?php if ($prodotto_id && $fornitore['customers_id'] == $supplier) : ?> selected="selected" <?php endif; ?>><?php echo $fornitore['customers_company']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 js-barcodes_container">
                    <div class="form-group">
                        <label class="form-label-title js-sku-code"><?php e('Barcode'); ?></label>
                        <a href="javascript:void(0);" class=" btn btn-xs btn-success js-barcode_add">
                            <i class="fa fa-plus "></i>
                        </a>

                        <?php
                        if ($prodotto_id) {
                            $barcodes = (is_array(json_decode($prodotto['fw_products_barcode'], true))) ? json_decode($prodotto['fw_products_barcode']) : [$prodotto['fw_products_barcode']];
                        } else {
                            $barcodes = [''];
                        }


                        ?>
                        <?php foreach ($barcodes as $barcode) : ?>
                        <div class="row js-barcode_container">
                            <div class="col-md-10">
                                <input type="text" class="form-control fw_products_barcode" placeholder="Barcode" name="fw_products_barcode[]" value="<?php echo $barcode; ?>" />
                            </div>
                            <div class="col-md-1">
                                <a href="javascript:void(0);" class=" btn btn-xs btn-default js-create_barcode">
                                    <i class="fas fa-barcode "></i>
                                </a>

                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Description'); ?></label>
                        <textarea class="form-control" name="fw_products_description" rows="4" placeholder="<?php e('Write here product\'s description'); ?>"><?php echo ($prodotto_id) ? $prodotto['fw_products_description'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Categories'); ?>
                            <?php $form_category_id = $this->datab->get_form_id_by_identifier('new_category_fast'); ?>
                            <a href="javascript:void(0);" class=" btn btn-xs btn-default js_create_category">
                                <i class="fa fa-plus "></i>
                            </a>
                            <script>
                            $(function() {
                                var button = $('.js_create_category');
                                var thisField = button.parent().parent().find('[name="fw_products_categories[]"]');
                                var subform = <?php echo $form_category_id; ?>;

                                button.on('click', function() {
                                    openCreationForm(subform, 'fw_categories', function(id, name) {
                                        thisField.append($('<option/>').attr('value', id).text(name));
                                        thisField.val(id).trigger("change");
                                    });
                                });
                            });
                            </script>
                        </label>
                        <!--                        <small class="text-danger fas fa-asterisk" style="font-size: 85%"></small>-->
                        <select class="form-control select2_standard" multiple="multiple" name="fw_products_categories[]" data-placeholder="<?php e('Choose categories'); ?>" style="width: 100%;">
                            <?php foreach ($categorie as $category) : ?>
                            <option value="<?php echo $category['fw_categories_id']; ?>" <?php if ($prodotto_id && is_array($prodotto['fw_products_categories'])) : ?><?php if ($prodotto_id && in_array($category['fw_categories_id'], array_keys($prodotto['fw_products_categories']))) : ?> selected="selected" <?php endif; ?><?php endif; ?>>
                                <?php echo $category['fw_categories_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label-title"><?php e('Unita di misura'); ?></label>
                    <select class="form-control select2_standard" style="width: 100%;" name="fw_products_unita_misura">
                        <?php foreach ($unita_misura as $um) : ?>
                        <option value="<?php echo $um['fw_products_unita_misura_id']; ?>" <?php if ($prodotto_id && $um['fw_products_unita_misura_id'] == $prodotto['fw_products_unita_misura']) : ?> selected="selected" <?php endif; ?>><?php echo $um['fw_products_unita_misura_value']; ?></option>
                        <?php endforeach; ?>
                    </select>

                </div>
            </div>
        </div>
    </div>

    <div class="row margin-top-adjust-10">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Warehouse location'); ?></label>
                        <textarea class="form-control fw_products_warehouse_location" name="fw_products_warehouse_location"><?php echo ($prodotto_id) ? $prodotto['fw_products_warehouse_location'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Additional informations'); ?></label>
                        <textarea class="form-control fw_products_notes" name="fw_products_notes"><?php echo ($prodotto_id) ? $prodotto['fw_products_notes'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row margin-top-adjust-10">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Uso interno / Listino'); ?></label>
                        <select class="form-control select2_standard" style="width: 100%;" name="fw_products_fuori_listino">
                            <option value=""></option>
                            <?php foreach ($fuori_listino as $listino_prod) : ?>
                            <option value="<?php echo $listino_prod['fw_products_fuori_listino_id']; ?>" <?php if ($prodotto_id && $listino_prod['fw_products_fuori_listino_id'] == $prodotto['fw_products_fuori_listino']) : ?> selected="selected" <?php endif; ?>><?php echo $listino_prod['fw_products_fuori_listino_value']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <?php $form_group_id = $this->datab->get_form_id_by_identifier('new_group_fast'); ?>
                        <label class="form-label-title"><?php e('Groups'); ?>
                            <a href="#" class=" btn btn-xs btn-default js_create_group">
                                <i class="fa fa-plus "></i>
                            </a>
                            <script>
                            $(function() {
                                var button = $('.js_create_group');
                                var thisField = button.parent().parent().find('[name="fw_products_gruppi"]');
                                var subform = <?php echo $form_group_id; ?>;

                                button.on('click', function() {
                                    openCreationForm(subform, 'fw_products_gruppi', function(id, name) {
                                        thisField.append($('<option/>').attr('value', id).text(name));
                                        thisField.val(id).trigger("change");
                                    });
                                });
                            });
                            </script>
                        </label>
                        <select class="form-control select2_standard" style="width: 100%;" name="fw_products_gruppi">
                            <option value=""></option>
                            <?php foreach ($gruppi as $gruppo) : ?>
                            <option value="<?php echo $gruppo['fw_products_gruppi_id']; ?>" <?php if ($prodotto_id && $gruppo['fw_products_gruppi_id'] == $prodotto['fw_products_gruppi']) : ?> selected="selected" <?php endif; ?>><?php echo $gruppo['fw_products_gruppi_value']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Stock Management'); ?></label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="fw_products_stock_management" value="1" <?php if (!$prodotto_id or ($prodotto_id && $prodotto['fw_products_stock_management'] == DB_BOOL_TRUE)) : ?>checked="checked" <?php endif; ?>>
                                <?php e('Yes'); ?>
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="fw_products_stock_management" value="0" <?php if ($prodotto_id && $prodotto['fw_products_stock_management'] == DB_BOOL_FALSE) : ?>checked="checked" <?php endif; ?>>
                                <?php e('No'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Out Of Production'); ?></label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="fw_products_out_of_production" value="1" <?php if ($prodotto_id && ($prodotto_id && $prodotto['fw_products_out_of_production'] == DB_BOOL_TRUE)) : ?>checked="checked" <?php endif; ?>>
                                <?php e('Yes'); ?>
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="fw_products_out_of_production" value="0" <?php if (!$prodotto_id or $prodotto['fw_products_out_of_production'] == DB_BOOL_FALSE) : ?>checked="checked" <?php endif; ?>>
                                <?php e('No'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Main image'); ?></label>
                        <input type='file' id="mainImage" class="form-control" name="fw_products_main_image" />

                        <div class="img-wrapper" id="main_image_container">
                            <img class="img-responsive img-thumbnail img-rounded" id="main_image_preview" height="250px" width="auto" <?php echo (!empty($prodotto['fw_products_main_image'])) ? 'src="' . base_url("uploads/{$prodotto['fw_products_main_image']}") . '"' : null; ?> />

                            <?php if (!empty($prodotto['fw_products_main_image'])) : ?>
                            <div class="img-overlay">
                                <button class="btn btn-xs btn-danger btn-remove-image" data-product_id="<?php echo $prodotto_id; ?>"><i class="far fa-trash-alt"></i> <?php e('Rimuovi'); ?></button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label-title"><?php e('Other images'); ?></label>
                        <?php
                        if ($prodotto_id) {
                            $product = $this->apilib->view('fw_products', $prodotto_id, 1);
                        }
                        $field = $this->datab->processFieldMapping($field, $form);
                        ?>
                        <?php echo $this->datab->build_form_input($field, $prodotto_id ? $product['fw_products_images'] : null) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <label class="form-label-title"><?php e('Show in point of sale'); ?></label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="fw_products_show_in_counter" value="1" <?php if (!$prodotto_id or ($prodotto_id && $prodotto['fw_products_show_in_counter'] == DB_BOOL_TRUE)) : ?>checked="checked" <?php endif; ?>>
                            <?php e('Yes'); ?>
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="fw_products_show_in_counter" value="0" <?php if ($prodotto_id && $prodotto['fw_products_show_in_counter'] == DB_BOOL_FALSE) : ?>checked="checked" <?php endif; ?>>
                            <?php e('No'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3 sub-label">
                <span>Price management</span></br>
            </div>
            <div class="col-md-9">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Provider price (vat excl.)'); ?></label>
                        <input type="text" name="fw_products_provider_price" class="form-control js-provider_price" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_provider_price'] : ''; ?>" placeholder="ex.: 1800,00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Sell price'); ?>
                            <small><?php e('Taxes excl.'); ?></small>
                        </label>
                        <small class="text-danger fas fa-asterisk" style="font-size: 85%"></small>
                        <input type="text" required class="form-control js-prezzo_vendita" name="fw_products_sell_price" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_sell_price'] : ''; ?>" placeholder="ex.: 2000.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Sell price'); ?>
                            <small><?php e('Taxes incl.'); ?></small>
                        </label>
                        <small class="text-danger fas fa-asterisk" style="font-size: 85%"></small>
                        <input type="text" required class="form-control js-prezzo_vendita_iva_inclusa" name="fw_products_sell_price_tax_included" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_sell_price_tax_included'] : ''; ?>" placeholder="ex.: 2000.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Discounted price'); ?>
                            <small><?php e('vat excl.'); ?></small>
                        </label>
                        <input type="text" class="form-control js-prezzo_scontato" name="fw_products_discounted_price" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_discounted_price'] : ''; ?>" placeholder="ex.: 1950.00">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Taxes'); ?></label>
                        <select class="form-control select2_standard" name="fw_products_tax" style="width: 100%;">
                            <?php foreach ($taxes as $tax) : ?>
                            <option data-tax_percentage="<?php echo $tax['iva_valore']; ?>" value="<?php echo $tax['iva_id']; ?>" data-perc="<?php echo $tax['iva_valore']; ?>" <?php if ($prodotto_id && $prodotto['fw_products_tax'] == $tax['iva_id']) : ?> selected="selected" <?php endif; ?>><?php echo $tax['iva_label']; ?></option>

                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Mark-up percentage'); ?></label>
                        <input type="text" class="form-control js-ricarico" name="fw_products_markup_percentage" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_markup_percentage'] : '20'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Discount percentage'); ?></label>
                        <input type="text" class="form-control js-discount" name="fw_products_sconto" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_sconto'] : ''; ?>" placeholder="ex. 35.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Discount percentage'); ?> 2</label>
                        <input type="text" class="form-control js-discount" name="fw_products_sconto2" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_sconto2'] : ''; ?>" placeholder="ex. 20.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title" style="font-size: 18px !important;"><?php e('Discount percentage'); ?> 3</label>
                        <input type="text" class="form-control js-discount" name="fw_products_sconto3" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_sconto3'] : ''; ?>" placeholder="ex. 10.00">
                    </div>
                </div>
                
                <?php if($prodotto_id && $this->module->moduleExists('firecrm')): ?>
                    <hr>

                    <div class="col-sm-12">
                        <label for="applica_prezzo_subscriptions">
                            <input type="checkbox" name="applica_prezzo_subscriptions" id="applica_prezzo_subscriptions">
                            <?php e('Apply price change to any connected subscriptions'); ?>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($price_list_labels) : ?>
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3 sub-label">
                <span>Price list</span></br>
            </div>
            <div class="col-md-9">

                <div class="col-md-12">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php e('Label'); ?></th>
                                <th><?php e('Price'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($price_list_labels as $label) : ?>
                            <tr>
                                <td>
                                    <?php echo $label['price_list_labels_name']; ?>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="product_prices[<?php echo $label['price_list_labels_id']; ?>]" value="<?php echo ($prodotto_id) ? $prodotto['prices'][$label['price_list_labels_id']] : ''; ?>" placeholder="ex.: 2000.00">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
    <?php endif; ?>
    <hr <?php if ($prodotto_id) : ?> style="display:none;" <?php endif; ?>>
    <div class="row" <?php if ($prodotto_id) : ?> style="display:none;" <?php endif; ?>>
        <div class="col-md-12 text-center">
            <div class="btn-group">

                <button type="button" class="btn <?php echo (!$prodotto_id || ($prodotto_id && $prodotto['fw_products_type'] == 1)) ? 'btn-primary' : 'btn-default'; ?> js_btn_simple">Simple</button>
                <button type="button" class="btn <?php echo ($prodotto_id && $prodotto['fw_products_type'] == 2) ? 'btn-primary' : 'btn-default'; ?> js_btn_configurable">Configurable</button>

                <input type="hidden" name="fw_products_type" class="js_fw_products_type" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_type'] : '1'; ?>" />
            </div>
        </div>
    </div>
    <hr>
    <?php //debug($prodotto['fw_products_json_attributes']);
    ?>
    <div class="row js_configurable" style="display:none;">
        <div class="col-md-12">
            <div class="col-md-3 sub-label">
                <span><?php e('Variants'); ?></span></br>
            </div>
            <div class="col-md-9">
                <?php foreach ($attributi as $attributo) : ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label-title"><?php echo $attributo['attributi_nome']; ?>


                                <?php $form_attributo_id = $this->datab->get_form_id_by_identifier('new_attributo_fast'); ?>
                                <a href="javascript:void(0);" class=" btn btn-xs btn-default js_create_attributo<?php echo $attributo['attributi_id']; ?>">
                                    <i class="fa fa-plus "></i>
                                </a>
                                <script>
                                $(function() {
                                    var button = $('.js_create_attributo<?php echo $attributo['attributi_id']; ?>');
                                    var thisField = button.parent().parent().find('.attributi_<?php echo $attributo['attributi_id']; ?>');
                                    var subform = <?php echo $form_attributo_id; ?>;

                                    button.on('click', function() {
                                        openCreationForm(subform, 'attributi_valori', function(id, name) {
                                            thisField.append($('<option/>').attr('value', id).text(name));
                                            thisField.val(id).trigger("change");

                                            //initComponents();
                                        });
                                    });
                                });
                                </script>

                            </label>
                            <select name="fw_products_json_attributes_configurable[<?php echo $attributo['attributi_id']; ?>][]" data-attribute_id="<?php echo $attributo['attributi_id']; ?>" class="form-control select2_standard option-types hidden attributi_<?php echo $attributo['attributi_id']; ?>" multiple="multiple" data-attributo="<?php echo $attributo['attributi_id']; ?>" data-placeholder="<?php e('Choose...'); ?>" style="width: 100%;">
                                <?php foreach ($attributo['attributi_valori'] as $valore) : $json_attributes = @json_decode($prodotto['fw_products_json_attributes'], true); ?>
                                <option value="<?php echo $valore['attributi_valori_id']; ?>" <?php if ($prodotto_id && !empty($json_attributes) && (array_key_exists($attributo['attributi_id'], $json_attributes) && in_array($valore['attributi_valori_id'], $json_attributes[$attributo['attributi_id']]))) : ?> selected="selected" <?php endif; ?>><?php echo $valore['attributi_valori_label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>



    <div class="row js_simple">
        <div class="col-md-12">
            <div class="col-md-3 sub-label">
                <span><?php e('Quantity & Attributes'); ?></span></br>
            </div>
            <div class="col-md-9">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label-title js-quantity"><?php e('Quantity'); ?></label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_quantity" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_quantity'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title js-quantity">Min qty / reorder qty</label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_min_quantity" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_min_quantity'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title js-weight"><?php e('Weight'); ?></label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_weight" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_weight'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title js-width"><?php e('Width'); ?></label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_width" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_width'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title js-height"><?php e('Height'); ?></label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_height" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_height'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title js-depth"><?php e('Depth'); ?></label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_depth" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_depth'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label-title js-specific-weight"><?php e('Specific Weight'); ?></label>
                        <input type="text" class="form-control" placeholder="1" name="fw_products_peso_specifico" value="<?php echo ($prodotto_id) ? $prodotto['fw_products_peso_specifico'] : ''; ?>">
                    </div>
                </div>

                <?php foreach ($attributi as $key => $attribute) : ?>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-title js-quantity"><?php echo $attribute['attributi_nome']; ?></label>
                        <?php if (false) : //Qui si potrebbe pensare di mettere un if "l'attributo supporta la multi selezione nei configurati?"
                            ?>
                        <select class="form-control select2_standard" style="width: 100%;" name="fw_products_json_attributes_simple[<?php echo $attribute['attributi_id']; ?>]">
                            <option value=""></option>
                            <?php foreach ($attribute['attributi_valori'] as $valore) : ?>
                            <option value="<?php echo $valore['attributi_valori_id']; ?>" <?php if ($prodotto_id && !empty($prodotto['attributi'][$attribute['attributi_id']]) && ($prodotto['attributi'][$attribute['attributi_id']] == $valore['attributi_valori_id'] or in_array($valore['attributi_valori_id'], (array)$prodotto['attributi'][$attribute['attributi_id']]))) : ?>selected="selected" <?php endif; ?>>
                                <?php echo $valore['attributi_valori_label']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>

                        <select name="fw_products_json_attributes_simple[<?php echo $attribute['attributi_id']; ?>][]" data-attribute_id="<?php echo $attribute['attributi_id']; ?>" class="form-control select2_standard attributi_<?php echo $attribute['attributi_id']; ?>" multiple="multiple" data-attributo="<?php echo $attribute['attributi_id']; ?>" data-placeholder="<?php e('Choose...'); ?>" style="width: 100%;">
                            <?php foreach ($attribute['attributi_valori'] as $valore) : $json_attributes = @json_decode($prodotto['fw_products_json_attributes'], true); ?>
                            <?php
                                    $selected = false;
                                    //debug($prodotto['attributi'], true);
                                    if (
                                        $prodotto_id
                                        && !empty($prodotto['attributi'][$attribute['attributi_id']])
                                        && ($prodotto['attributi'][$attribute['attributi_id']] == $valore['attributi_valori_id']
                                            or in_array($valore['attributi_valori_id'], (array)$prodotto['attributi'][$attribute['attributi_id']]))
                                    ) {
                                        $selected = true;
                                    }
                                    ?>


                            <option value="<?php echo $valore['attributi_valori_id']; ?>" <?php if ($selected) : ?> selected="selected" <?php endif; ?>><?php echo $valore['attributi_valori_label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div id="js_product_variants" class="<?php if (!$prodotto_id) : ?>hide<?php endif; ?> js_configurable">
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-3 sub-label">
                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <hr>
                        <span class="text-size-adjust"><?php e('This product has'); ?> <span id="js_variants_number"></span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-12">
                    <table id="js_variants_table" class="table table-condensed table-striped table_prodotti">
                        <thead>
                            <tr>
                                <th><?php e('Name'); ?></th>
                                <th><?php e('Sku'); ?></th>
                                <th><?php e('Barcode'); ?></th>
                                <th><?php e('Provider price'); ?></th>
                                <th><?php e('Sell price'); ?></th>
                                <th><?php e('Stock quantity'); ?></th>
                                <th><?php e('Reorder quantity'); ?></th>

                                <th><?php e('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hide js_variant_rows" id="js_variants_table_row">
                                <td class="col-md-3">
                                    <input type="hidden" name="config_attributi[]" class="config_attributi" value="" />


                                    <input type="text" class="form-control nome_prodotto" name="config_nome_prodotto[]" tabindex="1" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control sku_code1" name="config_sku_code[]" tabindex="2" value="" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control barcode1" name="config_barcode[]" tabindex="2" value="" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control p_acquisto" name="config_p_acquisto[]" tabindex="3" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control p_vendita" name="config_p_vendita[]" tabindex="4" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control q_disponibile" name="config_q_disponibile[]" tabindex="7" value="0" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control soglia_riordine" name="config_soglia_riordine[]" tabindex="8" value="0" />
                                </td>

                                <td class="col-md-1">
                                    <button type="button" class="btn  btn-danger js_row_remove">
                                        <span class="fas fa-trash"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php if ($prodotto_id) : ?>
                            <?php foreach ($prodotto['configurati'] as $key => $pc) : ?>

                            <tr class="js_variant_rows" id="js_variants_table_row<?php echo $key + 1; ?>">
                                <td class="col-md-3">
                                    <input type="hidden" name="config_attributi_valori[<?php echo $key + 1; ?>]" class="config_attributi_valori" value='<?php echo json_encode(array_values(json_decode($pc['fw_products_json_attributes'], true))); ?>' />
                                    <input type="hidden" name="config_attributi[<?php echo $key + 1; ?>]" class="config_attributi" value='<?php echo $pc['fw_products_json_attributes']; ?>' />
                                    <input type="hidden" name="config_id[<?php echo $key + 1; ?>]" class="configurabile_id" value="<?php echo $pc['fw_products_id']; ?>" />

                                    <input type="text" class="form-control nome_prodotto" name="config_nome_prodotto[<?php echo $key + 1; ?>]" tabindex="1" value="<?php echo $pc['fw_products_name']; ?>" />
                                </td>
                                <td class="col-md-2">
                                    <input type="text" class="form-control sku_code" name="config_sku_code[<?php echo $key + 1; ?>]" tabindex="2" value="<?php echo $pc['fw_products_sku']; ?>" />
                                </td>
                                <td class="col-md-2">
                                    <input type="text" class="form-control barcode" name="config_barcode[<?php echo $key + 1; ?>]" tabindex="2" value="<?php echo $pc['fw_products_barcode']; ?>" />
                                </td>
                                <td class="col-md-2">
                                    <input type="text" class="form-control p_acquisto" name="config_p_acquisto[<?php echo $key + 1; ?>]" tabindex="3" value="<?php echo $pc['fw_products_provider_price']; ?>" />
                                </td>
                                <td class="col-md-2">
                                    <input type="text" class="form-control p_vendita" name="config_p_vendita[<?php echo $key + 1; ?>]" tabindex="4" value="<?php echo $pc['fw_products_sell_price']; ?>" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control q_disponibile" name="config_q_disponibile[<?php echo $key + 1; ?>]" tabindex="7" value="<?php echo $pc['fw_products_quantity']; ?>" />
                                </td>
                                <td class="col-md-1">
                                    <input type="text" class="form-control soglia_riordine" name="config_soglia_riordine[<?php echo $key + 1; ?>]" tabindex="8" value="<?php echo $pc['fw_products_min_quantity']; ?>" />
                                </td>

                                <td class="col-md-2">
                                    <button type="button" class="btn  btn-danger js_row_remove">
                                        <span class="fas fa-trash"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
    <hr />
    <div class="row">

        <div class="col-md-9"></div>

        <div class="col-md-3"><button type="submit" class="btn btn-block btn-success btn-lg"><?php e('Save'); ?></button></div>
    </div>
</form>
<!-- Questo serve per un bug di autocomplete -->
<!-- <script src="https://code.jquery.com/jquery-migrate-3.0.0.min.js"></script> -->

<script>
var attributes_labels = <?php echo json_encode($attributi); ?>;
var attribute_values_reverse = <?php echo json_encode($attribute_values_reverse); ?>;

function goBack() {
    window.history.back();
}

//Questo pezzo di codice serve a contare i prodotti precedentemente generati al caricamento della pagina.
document.addEventListener('DOMContentLoaded', function() {
    $.variantsCheck($('.js_variant_rows:visible').length)
}, false);

function calcolaPrezzoScontato(prezzoVendita) {
    // var scontoPercentuale = $('.js-sconto_percentuale').val();
    // var pricePercent = 100 - scontoPercentuale;
    // return (prezzoVendita * pricePercent) / 100;
    return prezzoVendita;
}
const diff = (a, b) => {
    return (a > b) ? (a - b) : (b - a);
}

function calcolaPrezzoVendita() {
    var provider_price = $('.js-provider_price').val();
    var tax_percentage = parseFloat($('[name="fw_products_tax"] option:selected').data('tax_percentage'));

    var ricarico = parseFloat($('.js-ricarico').val());
    var provider_price_ivato = provider_price / 100 * (100 + tax_percentage);
    var prezzoVendita = ((provider_price_ivato / 100) * (100 + ricarico));
    //console.log(prezzoVendita);
    $('.js-prezzo_vendita').val(calcolaPrezzoScontato(prezzoVendita).toFixed(2));
    calcolaPrezzoIvato(0);
}
function calcolaPrezzoIvato(previousValue) {
    var provider_price = $('.js-prezzo_vendita').val();
    if(diff(previousValue, provider_price)> 0.05){
        console.log("Differenza " + diff(previousValue, provider_price) +" aggiorno valori");
        var tax_percentage = parseFloat($('[name="fw_products_tax"] option:selected').data('tax_percentage'));
        var provider_price_ivato = provider_price / 100 * (100 + tax_percentage);
        $('.js-prezzo_vendita_iva_inclusa').val(calcolaPrezzoScontato(provider_price_ivato).toFixed(2));
    }
   
}
function calcolaPrezzoNonIvato(previousValue) {
    var provider_price = $('.js-prezzo_vendita_iva_inclusa').val();
    if(diff(previousValue, provider_price)> 0.05){
        console.log("Differenza " + diff(previousValue, provider_price) +" aggiorno valori");
        var tax_percentage = parseFloat($('[name="fw_products_tax"] option:selected').data('tax_percentage'));
        var provider_price_non_ivato = provider_price * 100 / (100 + tax_percentage);
        $('.js-prezzo_vendita').val(calcolaPrezzoScontato(provider_price_non_ivato).toFixed(2));
    } 
}
// function calcolaPrezzoVenditaRev() {
//     var provider_price = $('.js-provider_price').val();
//     var tax_percentage = parseFloat($('[name="fw_products_tax"] option:selected').data('tax_percentage'));

//     var ricarico = parseFloat($('.js-ricarico').val());
//     var provider_price_ivato = provider_price / 100 * (100 + tax_percentage);
//     var prezzoVendita = ((provider_price_ivato / 100) * (100 + ricarico));
//     //console.log(prezzoVendita);
//     $('.js-prezzo_vendita').val(calcolaPrezzoScontato(prezzoVendita).toFixed(2));
// }

$(document).ready(function() {
    var dropzone_parent = $('.dropzone').closest('.form-group');
    dropzone_parent.find('br').remove();
    dropzone_parent.find('.control-label').remove();

    $('.btn-remove-image').on('click', function(e) {
        e.preventDefault();

        console.log('btn clicked');

        if (!confirm('Are you sure?')) {
            console.log('not sure');
            return false;
        }

        var product_id = $(this).data('product_id');

        console.log(product_id);

        if (typeof product_id == 'number') {
            console.log('product id is not undefined and is not empty');

            $.ajax({
                url: base_url + 'products_manager/productsmanager/removeProductImage/' + product_id,
                method: "post",
                dataType: 'json',
                data: {
                    [token_name]: token_hash
                },
                success: function(response) {
                    console.log(response);

                    if (response.status) {
                        alert(response.txt);
                        $('#main_image_preview').removeAttr('src');

                        $('.btn-remove-image').remove();
                    } else {
                        alert(response.txt);
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        } else {
            console.log(typeof product_id);
        }
    });

    $('#js_dtable').dataTable({
        aoColumns: [null, null, null, null, null, null, null, {
            bSortable: false
        }],
        aaSorting: [
            [0, 'desc']
        ]
    });
    $('#js_dtable_wrapper .dataTables_filter input').addClass("form-control input-small"); // modify table search input
    $('#js_dtable_wrapper .dataTables_length select').addClass("form-control input-xsmall"); // modify table per page dropdown
    
    var initialValuePrezzoVendita = $('.js-prezzo_vendita').val();
    $('.js-prezzo_vendita').data('previousValuePrezzoVendita', initialValuePrezzoVendita);
    var initialValuePrezzoVenditaIvato = $('.js-prezzo_vendita_iva_inclusa').val();
    $('.js-prezzo_vendita_iva_inclusa').data('previousValuePrezzoVenditaIvato', initialValuePrezzoVenditaIvato);

    $('.js-ricarico, .js-provider_price, .js-sconto_percentuale, .js-variante_prodotto').on('change', function() {
        calcolaPrezzoVendita();
        aggiorna();
    });
    $('.js-prezzo_vendita').on('change', function() {
        var previousValue = $(this).data('previousValuePrezzoVendita');
        //console.log("Vecchio valore non ivato " + previousValue);
        calcolaPrezzoIvato(previousValue);
        $(this).data('previousValuePrezzoVendita', $(this).val());
    });
    $('.js-prezzo_vendita_iva_inclusa').on('change', function() {
        var previousValue = $(this).data('previousValuePrezzoVenditaIvato');
        //console.log("Vecchio valore ivato " + previousValue);
        calcolaPrezzoNonIvato(previousValue);
        $(this).data('previousValuePrezzoVenditaIvato', $(this).val());
    });
    
    // $('.js-prezzo_vendita').on('change', function() {
    //     var provider_price = $('.js-provider_price').val();
    //     var prezzo_vendita = $(this).val();
    //     if (prezzo_vendita && provider_price) {
    //         $('.js-ricarico').val(((prezzo_vendita / provider_price) * 100).toFixed(2));
    //     } else {
    //         $('.js-ricarico').val('');
    //     }

    //     aggiorna();
    // });

});


$("#js_variants_table").on('click', '.js_row_remove', function() {
    $(this).closest('tr').remove();
});

// Creazione delle varianti prodotto
function aggiorna() {

    // Creo un array di array contenente le categorie da impiegare per la creazione delle varianti prodotto

    options = [];

    jQuery('.option-types').each(function() {

        var attribute_id = $(this).data('attribute_id');
        opts = [];
        //console.log(attribute_id);
        jQuery(':selected', this).each(function() {
            opts.push(jQuery(this).val());
        });
        if (opts.length > 0) {

            options.push(opts);
        }
    });

    // Utilizzo il metodo .combinations per ottenere un array di array contenente i prodotti da visualizzare nella pagina del configuratore
    //console.log(options);
    productsArray = $.combinations(options);
    //console.log(productsArray);
    // Genero le linee necessarie al display dei prodotti

    $(".row_destroy_target").remove();

    var rowNumber = 0;

    for (var indice_prodotto in productsArray) {

        var prodotto = productsArray[indice_prodotto];

        //console.log(prodotto);

        rowNumber += 1;

        newRowId = 'js_variants_table_row' + rowNumber;

        if (!$('.config_attributi_valori[value=\'' + JSON.stringify(prodotto) + '\']').length) {

            var newRow = $('#js_variants_table_row').clone().attr('id', newRowId).addClass('row_destroy_target').removeClass("hide").insertAfter(".js_variant_rows:last");

            // Ora popolo la row con le informazioni del prodotto
            var nomeProdotto = $('[name="fw_products_name"]').val();
            var skuProdotto = $('[name="fw_products_sku"]').val();


            $('.p_acquisto', newRow).val($('.js-provider_price').val());
            $('.barcode', newRow).val($('.fw_products_barcode').val());
            $('.p_vendita', newRow).val($('.js-prezzo_vendita').val());
            var json_attributi = {};
            for (var proprieta in prodotto) {

                var id_attributo = attribute_values_reverse[prodotto[proprieta]].attributi_valori_attributo;
                var label_attributo = attribute_values_reverse[prodotto[proprieta]].attributi_valori_label;
                // console.log(id_attributo);
                // console.log(prodotto[proprieta]);
                json_attributi[id_attributo] = prodotto[proprieta];
                // console.log(json_attributi);
                console.log('Commentata la concatenazione degli attributi nello sku');
                // if (skuProdotto == '') {
                //     skuProdotto = label_attributo.replace(' ', '').toUpperCase();
                // } else {
                //     skuProdotto += label_attributo.replace(' ', '').toUpperCase();
                // }
                //console.log(skuProdotto);
                if (nomeProdotto == '') {
                    nomeProdotto = label_attributo;
                } else {
                    nomeProdotto += ' / ' + label_attributo;
                }
            }
            // console.log(json_attributi);
            // console.log(JSON.stringify(json_attributi));
            $('.config_attributi', newRow).val(JSON.stringify(json_attributi));


            $('.sku_code1', newRow).val(skuProdotto);
            $('.nome_prodotto', newRow).val(nomeProdotto);
        }
    }
    //console.log($('.js_variant_rows:visible').length);
    $("#js_product_variants").removeClass("hide"); //Temporary fix
    $.variantsCheck($('.js_variant_rows:visible').length);


    $('.nome_prodotto').each(function() {
        nomeProdotto = $(this).val();
        nomeProdotto = nomeProdotto.substring(nomeProdotto.indexOf("/") - 1);
        nomeConfigurato = $('input[name="fw_products_name"]').val() + nomeProdotto;
        //console.log(nomeConfigurato);
        skuMadre = $('input[name="fw_products_sku"]').val();
        nomeFixed1 = nomeConfigurato.replace(' / ', '-');
        //console.log(nomeFixed1);
        nomeFixed2 = nomeFixed1.replace(' / ', '-');
        //console.log(nomeFixed2);
        if (!skuMadre) {
            skuMadre = "SKU" + nomeFixed2;
        }
        //console.log(skuMadre);
        skuConfigurato = skuMadre.toUpperCase();
        //console.log($('input[name="fw_products_sku"]').val());
        //console.log("SKU" + nomeConfigurato);
        //console.log(skuConfigurato);
        //$(this).parent().parent().find('.sku_code1').val(skuConfigurato);

        $(this).val(nomeConfigurato);
    });

}

function aggiornaSku() {
    //Genero il codice sku del prodotto a partire dal suo nome, a meno che un codice sku non sia gia stato precedentemente inserito
    nomeConfigurato = $('.nome_prodotto').val();
    //console.log('123 ' + nomeConfigurato);
    skuMadre = $('input[name="fw_products_sku"]').val();
    nomeFixed1 = nomeConfigurato.replace(' / ', '-');
    //console.log('123 ' + nomeFixed1);
    nomeFixed2 = nomeFixed1.replace(' / ', '-');
    //console.log('123 ' + nomeFixed2);
    if (!skuMadre) {
        skuMadre = "SKU" + nomeFixed2;
    }
    //console.log('123 ' + skuMadre);
    skuConfigurato = skuMadre.toUpperCase();
    //console.log('123 ' + nomeConfigurato);
    //console.log('123 ' + skuConfigurato);

    //$('input[name="fw_products_sku"]').val('SKU');
}

$('input[name="fw_products_sku"]').change(
    function() {
        aggiorna();
    }
);

$('.option-types, input[name="fw_products_name"]').change(
    function() {
        aggiornaSku();
        aggiorna();

    });

// Rimozione rows

$("#js_variants_table").on('click', '.js_row_remove', function() {

    $(this).closest('tr').remove();

    $.variantsCheck($('.js_variant_rows:visible').length)
});

// Rimozione rows

$(".js_row_remove").on('click', function() {

    $(this).closest('tr').remove();

    $.variantsCheck($('.js_variant_rows:visible').length)
});
$('.js_btn_simple').on('click', function() {
    $(this).removeClass('btn-default').addClass('btn-primary');
    $('.js_btn_configurable').removeClass('btn-primary').addClass('btn-default');

    $('.js_configurable').hide();
    $('.js_simple').show();
    $('[name="fw_products_type"]').val(1);

});
$('.js_btn_configurable').on('click', function() {
    $(this).removeClass('btn-default').addClass('btn-primary');
    $('.js_btn_simple').removeClass('btn-primary').addClass('btn-default');

    $('.js_configurable').show();
    $('.js_simple').hide();
    $('[name="fw_products_type"]').val(2);
});

$('<?php echo (!$prodotto_id || ($prodotto_id && $prodotto['fw_products_type'] == 1)) ? '.js_btn_simple' : '.js_btn_configurable'; ?> ').trigger('click');

// Anteprima immagine principale del prodotto
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#main_image_preview').attr('src', e.target.result);
        }

        reader.readAsDataURL(input.files[0]);
    }
}
$("#mainImage").change(function() {
    readURL(this);
});
</script>

<script>
(function($) {
    $.variantsCheck = function(variantsNumber) {
        if (variantsNumber != 0 && $("#js_product_variants").hasClass("hide")) {
            $("#js_product_variants").removeClass("hide");
        } else if (variantsNumber == 0 && !$("#js_product_variants").hasClass("hide")) {
            $("#js_product_variants").addClass("hide");
        }

        if (variantsNumber == 1) {
            $("#js_variants_number").text(variantsNumber + ' <?php e('variant'); ?>.');
        } else if (variantsNumber > 1) {
            $("#js_variants_number").text(variantsNumber + ' <?php e('variants'); ?>.');
        }
    }
})(jQuery);
</script>

<script>
// Script per creare un array contenente le combinazioni-prodotto possibili

(function($) {
    $.combinations = function(arrayOfArrays) {
        if (Object.prototype.toString.call(arrayOfArrays) !== '[object Array]') {
            throw new Error("Al metodo non è stato passato un array valido");
        }

        var combinations = [],
            comboKeys = [],
            numOfCombos = arrayOfArrays.length ? 1 : 0,
            arrayOfArraysLength = arrayOfArrays.length;

        for (var n = 0; n < arrayOfArraysLength; ++n) {
            if (Object.prototype.toString.call(arrayOfArrays[n]) !== '[object Array]') {
                throw new Error("Al metodo non è stato passato un array valido");
            }
            //alert(arrayOfArrays[n].length);
            numOfCombos = numOfCombos * arrayOfArrays[n].length;
        }

        for (var x = 0; x < numOfCombos; ++x) {
            var carry = x,
                comboKeys = [],
                combo = [];

            for (var i = 0; i < arrayOfArraysLength; ++i) {
                comboKeys[i] = carry % arrayOfArrays[i].length;
                carry = Math.floor(carry / arrayOfArrays[i].length);
            }
            for (var i = 0; i < comboKeys.length; ++i) {
                combo.push(arrayOfArrays[i][comboKeys[i]]);
            }
            combinations.push(combo);
        }

        return combinations;
    }
})(jQuery);
</script>

<script>
function refreshSupportData() {
    //Aggiorno tutte le select

    //Marche
    $.ajax(base_url + 'api/search/fw_products_brand', {
        dataType: 'json',
        success: function(data) {
            changed = $('[name="fw_products_brand"] option').size() !== data.data.length;
            if (changed) {
                $('[name="fw_products_brand"]').html('');
                $.each(data.data, function(v) {
                    $('[name="fw_products_brand"]').append('<option value="' + data.data[v].fw_products_brand_id + '">' + data.data[v].fw_products_brand_value + '</option>');
                });

                $('[name="fw_products_brand"] option:last').attr("selected", "selected");
            }
        }
    });

    //Fornitori
    $.ajax(base_url + 'api/search/customers', {
        dataType: 'json',
        success: function(data) {
            changed = $('[name="fw_products_supplier"] option').size() !== data.data.length;
            console.log($('[name="fw_products_supplier"] option').size());
            console.log(data.data.length);
            if (changed) {
                $('[name="fw_products_supplier"]').html('');
                $.each(data.data, function(v) {
                    $('[name="fw_products_supplier"]').append('<option value="' + data.data[v].customers_id + '">' + data.data[v].customers_company + '</option>');
                });

                $('[name="fw_products_supplier"] option:last').attr("selected", "selected");
            }
        }
    });

    //Categorie
    $.ajax(base_url + 'api/search/fw_categories', {
        dataType: 'json',
        success: function(data) {
            changed = $('[name="fw_products_categories[]"] option').size() !== data.data.length;
            console.log($('[name="fw_products_categories[]"] option').size());
            console.log(data.data.length);
            if (changed) {
                $('[name="fw_products_categories[]"]').html('');
                $.each(data.data, function(v) {
                    $('[name="fw_products_categories[]"]').append('<option value="' + data.data[v].fw_categories_id + '">' + data.data[v].fw_categories_name + '</option>');
                });

                $('[name="fw_products_categories[]"] option:last').attr("selected", "selected");
            }
        }
    });

    <?php foreach ($attributi as $attributo) : ?>
    //<?php echo $attributo['attributi_nome']; ?>

    $.ajax(base_url + 'api/search/attributi_valori?attributi_valori_attributo=<?php echo $attributo['attributi_id']; ?>', {
        dataType: 'json',
        success: function(data) {
            changed = $('[data-attributo="<?php echo $attributo['attributi_id']; ?>"] option').size() !== data.data.length;
            console.log($('[data-attributo="<?php echo $attributo['attributi_id']; ?>"] option').size());
            console.log(data.data.length);
            if (changed) {
                $('[data-attributo="<?php echo $attributo['attributi_id']; ?>"]').html('');
                $.each(data.data, function(v) {
                    $('[data-attributo="<?php echo $attributo['attributi_id']; ?>"]').append('<option value="' + data.data[v].attributi_valori_id + '">' + data.data[v].attributi_valori_label + '</option>');
                });

                $('[data-attributo="<?php echo $attributo['attributi_id']; ?>"] option:last').attr("selected", "selected");
            }
        }
    });
    <?php endforeach; ?>
}
</script>

<script>
/* Crea nuove coppie (codice fornitore, fornitore) al click*/
$(() => {
    $('.js-provider_code-add').on('click', function() {
        var provider_code_container = $('.js-provider_code_container').first().clone();

        $(':input', provider_code_container).val('');
        $('.js-provider_code_container').last().after(provider_code_container);
        provider_code_container.addClass('mt-5');
    });
});
</script>

<script>
/* Calcolo prezzo senza iva e lo inserisce in sell_price al click*/
$(() => {
    <?php /*if(empty($product_id)): ?>
        $('[name="fw_products_tax"]').val('1');
    <?php endif;*/ ?>

    $('.js-btn_calcola_prezzo').on('click', function(e) {
        e.preventDefault();

        let prezzo_vendita = prompt('Inserisci il prezzo di vendita iva inclusa');
        if (prezzo_vendita) {
            const iva = parseFloat($('[name="fw_products_tax"]').find(':selected').data('perc'));
            const scorporo = (100 * parseFloat(prezzo_vendita)) / (100 + iva);
            $('[name="fw_products_sell_price"]').val(parseFloat(scorporo).toFixed(3));
        }
    });
});
</script>