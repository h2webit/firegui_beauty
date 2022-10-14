<?php

class Woocomm extends CI_Model
{
    function import_products($product, $parent_id = null)
    {
        $product_name = $product->name;
        $product_name = trim(preg_replace('/<[^>]*>/', ' ', $product_name));
        $product_name = trim(preg_replace('!\s+!', ' ', $product_name));
    
        echo date('Y-m-d H:i:s') . " [?] Importing {$product_name}\n";
        $crm_product = $this->apilib->searchFirst('fw_products', ['fw_products_woocommerce_external_code' => $product->id]);
    
        $price = (float) number_format((!empty($product->regular_price)) ? $product->regular_price : $product->price, 2);
    
        $vat_perc = 10;
    
        $price_no_vat = $price / (1 + ($vat_perc / 100));
    
        $product_fields = [
            'fw_products_name' => $product_name,
            'fw_products_description' => strip_tags($product->description),
            'fw_products_sku' => $product->sku,
            'fw_products_weight' => $product->weight,
            'fw_products_height' => $product->dimensions->height,
            'fw_products_width' => $product->dimensions->width,
            'fw_products_discounted_price' => $product->sale_price,
            'fw_products_sell_price' => $price_no_vat,
            'fw_products_tax' => 4,
            'fw_products_type' => 1,
        ];

        foreach ($product->meta_data as $metadata) {
            if ($metadata->key === '_woosea_ean' && !empty($metadata->value)) {
                $product_fields['fw_products_ean'] = $metadata->value;
            }
        }

        if (!$parent_id) {
            $brand_id = null;
            foreach ($product->attributes as $attr) {
                if ($attr->name == 'Brand') {
                    $crm_brand = $this->apilib->searchFirst('fw_products_brand', [
                        "LOWER(fw_products_brand_value) = LOWER(\"{$attr->options[0]}\")"
                    ]);

                    if (empty($crm_brand)) {
                        $new_brand = $this->apilib->create('fw_products_brand', [
                            'fw_products_brand_value' => $attr->options[0]
                        ]);

                        $brand_id = $new_brand['fw_products_brand_id'];
                        echo ("+ {$attr->options[0]}\n");
                    } else {
                        $brand_id = $crm_brand['fw_products_brand_id'];
                        echo ("* {$attr->options[0]}\n");
                    }
                }
                break;
            }

            if (!empty($brand_id)) {
                $product_fields['fw_products_brand'] = $brand_id;
            }
        }

        if (!empty($parent_id)) {
            $product_fields['fw_products_parent'] = $parent_id;

            $parent_product = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $parent_id]);

            if (!empty($parent_product['fw_products_brand'])) {
                $product_fields['fw_products_brand'] = $parent_product['fw_products_brand'];
            }
        }

        if (empty($crm_product)) {
            $product_fields['fw_products_woocommerce_external_code'] = $product->id;

            return $this->apilib->create('fw_products', $product_fields);
        } else {
            return $this->apilib->edit('fw_products', $crm_product['fw_products_id'], $product_fields);
        }

        unset($product_fields);
    }

    public function import_attributes($product, $is_variant = false)
    {
        $wc_attributes = [];

        foreach ($product->attributes as $attribute) {
            if (!stristr($attribute->name, 'Brand')) {
                $crm_attribute = $this->apilib->searchFirst('attributi', ['attributi_external_code' => $attribute->id]);

                $attribute_id = null;
                if (!empty($crm_attribute)) {
                    $attribute_id = $crm_attribute['attributi_id'];
                } else {
                    $new_attribute = $this->apilib->create('attributi', [
                        'attributi_nome' => $attribute->name,
                        'attributi_external_code' => $attribute->id
                    ]);

                    $attribute_id = $new_attribute['attributi_id'];
                }

                if ($is_variant) {
                    $crm_attribute_opt = $this->apilib->searchFirst('attributi_valori', [
                        'attributi_valori_attributo' => $attribute_id,
                        "LOWER(attributi_valori_label) = LOWER('{$attribute->option}')"
                    ]);

                    $crm_attribute_opt_id = null;
                    if (!empty($crm_attribute_opt)) {
                        $crm_attribute_opt_id = $crm_attribute_opt['attributi_valori_id'];
                    } else {
                        $new_attribute_opt = $this->apilib->create('attributi_valori', [
                            'attributi_valori_attributo' => $attribute_id,
                            'attributi_valori_label' => $attribute->option
                        ]);

                        $crm_attribute_opt_id = $new_attribute_opt['attributi_valori_id'];
                    }

                    if ($crm_attribute_opt_id) {
                        $wc_attributes[$attribute_id] = $crm_attribute_opt_id;
                    }
                } else {
                    // OPTIONS SECTION
                    foreach ($attribute->options as $attribute_opt) {
                        $crm_attribute_opt = $this->apilib->searchFirst('attributi_valori', [
                            'attributi_valori_attributo' => $attribute_id,
                            "LOWER(attributi_valori_label) = LOWER('{$attribute_opt}')"
                        ]);

                        $crm_attribute_opt_id = null;
                        if (!empty($crm_attribute_opt)) {
                            $crm_attribute_opt_id = $crm_attribute_opt['attributi_valori_id'];
                        } else {
                            $new_attribute_opt = $this->apilib->create('attributi_valori', [
                                'attributi_valori_attributo' => $attribute_id,
                                'attributi_valori_label' => $attribute_opt
                            ]);

                            $crm_attribute_opt_id = $new_attribute_opt['attributi_valori_id'];
                        }

                        if ($crm_attribute_opt_id) {
                            $wc_attributes[$attribute_id][] = $crm_attribute_opt_id;
                        }
                    }
                }
            }
        }

        return $wc_attributes;
    }

    public function import_images($product, $response_product)
    {
        if (!empty($product->images)) {
            $this->db
                ->where("prodotti_immagini_id IN (
                                    SELECT prodotti_immagini_id 
                                    FROM rel_prodotti_immagini 
                                    WHERE fw_products_id = '{$response_product['fw_products_id']}'
                                )")
                ->delete('prodotti_immagini');

            $this->db->where('fw_products_id', $response_product['fw_products_id'])->delete('rel_prodotti_immagini');

            foreach ($product->images as $image) {
                if (!empty($response_product)) {
                    $url = $image->src;
                    $ext = explode('.', $url);
                    $ext = end($ext);

                    $contents = file_get_contents($url);

                    $destfile = 'wc_imported/' . md5($contents) . '.' . $ext;

                    $downloaded = file_put_contents(FCPATH . '/uploads/' . $destfile, $contents);

                    if (!$downloaded && !file_exists($destfile)) {
                        echo ("Image for {$response_product['fw_products_name']} not imported (maybe an error or image already exists)" . PHP_EOL);
                    } else {
                        $immagine = $this->apilib->create('prodotti_immagini', [
                            'prodotti_immagini_immagine' => $destfile
                        ]);

                        if (!empty($immagine)) {
                            $this->db->insert('rel_prodotti_immagini', [
                                'fw_products_id' => $response_product['fw_products_id'],
                                'prodotti_immagini_id' => $immagine['prodotti_immagini_id'],
                            ]);
                        }

                        echo ("Imported image for {$response_product['fw_products_name']}" . PHP_EOL);
                    }
                }
            }
        }
    }

    public function import_categories($product, $crm_product)
    {
        $categories = $this->apilib->search('fw_categories');

        $categories_map = [];

        foreach ($categories as $category) {
            $categories_map[$category['fw_categories_woocommerce_external_code']] = $category['fw_categories_id'];
        }

        if (!empty($product->categories)) {
            foreach ($product->categories as $category) {
                if (!empty($crm_product)) {
                    $crm_category = $this->apilib->searchFirst('fw_categories', ['fw_categories_woocommerce_external_code' => $category->id]);

                    $cat_id = null;
                    if (!empty($crm_category)) {
                        $cat_id = $crm_category['fw_categories_id'];
                    } else {
                        $new_category = $this->apilib->create('fw_categories', [
                            'fw_categories_woocommerce_external_code' => $category->id,
                            'fw_categories_name' => $category->name
                        ]);

                        if (!empty($new_category)) {
                            $cat_id = $new_category['fw_categories_id'];
                        }
                    }

                    $this->db->where('fw_products_id', $crm_product['fw_products_id'])->delete('fw_products_fw_categories');

                    $this->db->insert('fw_products_fw_categories', [
                        'fw_categories_id' => $cat_id,
                        'fw_products_id' => $crm_product['fw_products_id']
                    ]);

                    echo date('Y-m-d H:i:s') . " Imported category for {$crm_product['fw_products_name']}\n";
                }
            }
        }
    }
}
