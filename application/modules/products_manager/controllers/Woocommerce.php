<?php

use Automattic\WooCommerce\Client;

require __DIR__ . '/../vendor/autoload.php';

class Woocommerce extends MY_Controller
{
    private $wc;

    function __construct()
    {
        parent::__construct();

        $woocommerce = $this->apilib->searchFirst('woocommerce');

        if (empty($woocommerce['woocommerce_consumer_key']) || empty($woocommerce['woocommerce_consumer_secret']) || empty($woocommerce['woocommerce_endpoint'])) {
            throw new ApiException('Consumer key, secret or endpoint url not declared');
            exit;
        }

        $url = (empty(parse_url($woocommerce['woocommerce_endpoint'])['scheme'])) ? 'https://' . $woocommerce['woocommerce_endpoint'] : $woocommerce['woocommerce_endpoint'];

        $this->wc = new Client(
            $url,
            $woocommerce['woocommerce_consumer_key'],
            $woocommerce['woocommerce_consumer_secret'],
            [
                'version' => 'wc/v3',
            ]
        );
    }

    public function updatebatchqty()
    {
        echo date('Y-m-d H:i:s') . " Starting massive update to Woocommerce\n";
        $crm_products = $this->db->where('fw_products_woocommerce_external_code IS NOT NULL')->get('fw_products')->result_array();

        foreach ($crm_products as $crm_product) {
            try {
                if (empty($crm_product['fw_products_parent'])) {
                    $this->wc->put("products/{$crm_product['fw_products_woocommerce_external_code']}", ['stock_quantity' => $crm_product['fw_products_quantity']]);
                } else {
                    $parent = $this->apilib->view("fw_products", $crm_product['fw_products_parent']);
                    if (!empty($parent)) {
                        $this->wc->put("products/{$parent['fw_products_woocommerce_external_code']}/variations/{$crm_product['fw_products_woocommerce_external_code']}", ['stock_quantity' => $crm_product['fw_products_quantity']]);
                    }
                }

                echo date('Y-m-d H:i:s') . " Done {$crm_product['fw_products_name']}\n";
            } catch (Exception $e) {
                echo date('Y-m-d H:i:s') . " Error {$crm_product['fw_products_name']}\n";
            }

            sleep(1);
        }
        echo date('Y-m-d H:i:s') . " Finished\n";
    }

    public function importorders($page = 1)
    {
        $order = $this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_tipo = 5 AND documenti_contabilita_codice_esterno LIKE 'WOOCOMM-%' ORDER BY documenti_contabilita_data_emissione DESC LIMIT 1")->row_array();

        if (!empty($order)) {
            $dateobj = DateTime::createFromFormat('Y-m-d H:i:s', $order['documenti_contabilita_data_emissione']);
            $orderdate = $dateobj->format('Y-m-d\TH:i:s');
        } else {
            $orderdate = null;
        }

        echo_flush(date('Y-m-d H:i:s') . " Inizio import ordini\n");

        try {
            if ($page == null) $page = 1;

            for ($i = $page; $i <= 10; $i++) {
                $ordini_woocommerce = $this->wc->get('orders', ['per_page' => 100, 'page' => $i, 'after' => $orderdate]);

                if (empty($ordini_woocommerce)) {
                    echo_flush("Responso vuoto");
                    break;
                }

                echo_flush(date('Y-m-d H:i:s') . " Estrazione prodotti da woocomm - pag {$i}\n");

                foreach ($ordini_woocommerce as $ordine_woocommerce) {
                    echo_flush("Inizio import ordine {$ordine_woocommerce->id}\n");

                    $cliente_woocommerce = $ordine_woocommerce->billing;
                    $email_cliente = $cliente_woocommerce->email;

                    $cliente_exists = $this->apilib->searchFirst('customers', ['customers_email' => $email_cliente]);
                    if ($cliente_exists) {
                        $cliente = $cliente_exists;
                        $cliente_id = $cliente['customers_id'];
                    } else {
                        $cliente = [
                            'customers_zip_code' => $cliente_woocommerce->postcode,
                            'customers_mobile' => $cliente_woocommerce->phone,
                            'customers_city' => strtoupper($cliente_woocommerce->city),
                            'customers_sdi' => '000000',
                            'customers_last_name' => $cliente_woocommerce->last_name,
                            'customers_description' => 'Importato automaticamente da woocommerce',
                            'customers_email' => $email_cliente,
                            'customers_address' => $cliente_woocommerce->address_1,
                            'customers_country' => strtoupper($cliente_woocommerce->country),
                            'customers_name' => $cliente_woocommerce->first_name,
                            'customers_province' => (empty($cliente_woocommerce->state)) ? '  ' : strtoupper(@$cliente_woocommerce->state),
                            'customers_company' => "{$cliente_woocommerce->first_name} {$cliente_woocommerce->last_name}",
                            'customers_phone' => $cliente_woocommerce->phone,
                            'customers_tipo' => '1',

                        ];

                        $cliente_id = $this->apilib->create('customers', $cliente, false);
                        $cliente['customers_id'] = $cliente_id;
                    }

                    $pagamento = 'bonifico';

                    switch ($ordine_woocommerce->payment_method) {
                        case 'cod':
                            $pagamento = 'contrassegno';
                            break;
                        case 'bacs':
                            $pagamento = 'bonifico';
                            break;
                        case 'cheque':
                            $pagamento = 'assegno';
                            break;
                        case 'stripe':
                        case 'paypal':
                            $pagamento = 'carta di credito';
                            break;
                        case 'stripe_sepa':
                            $pagamento = 'SEPA Direct Debit';
                            break;
                        default:
                            debug($ordine_woocommerce->payment_method, true);
                            break;
                    }

                    $this->load->model('contabilita/docs');

                    $note = '';

                    if (!empty($ordine_woocommerce->coupon_lines)) {
                        foreach ($ordine_woocommerce->coupon_lines as $coupon_line) {
                            foreach ($coupon_line->meta_data as $coupon) {
                                $coupon = $coupon->value;

                                $note .= "Applicato coupon {$coupon->code} del {$coupon->amount}%\n";
                            }
                        }
                    }

                    $ordine = [
                        'documenti_contabilita_accetta_paypal' => '0',
                        'documenti_contabilita_annotazioni_trasporto' => '',
                        'documenti_contabilita_cassa_professionisti_perc' => '0.00',
                        'documenti_contabilita_cassa_professionisti_valore' => '0.00',
                        'documenti_contabilita_causale_pagamento_ritenuta' => '',
                        'documenti_contabilita_causale_trasporto' => '',
                        'documenti_contabilita_centro_di_ricavo' => '1',
                        'documenti_contabilita_customer_id' => $cliente_id,
                        'documenti_contabilita_codice_esterno' => 'WOOCOMM-' . $ordine_woocommerce->id,
                        'documenti_contabilita_competenze' => $ordine_woocommerce->total,
                        'documenti_contabilita_competenze_lordo_rivalsa' => $ordine_woocommerce->total,
                        'documenti_contabilita_conto_corrente' => '2',
                        'documenti_contabilita_data_emissione' => date('Y-m-d H:i:s', strtotime($ordine_woocommerce->date_created)),
                        'documenti_contabilita_data_ritiro_merce' => '',
                        'documenti_contabilita_da_sollecitare' => '0',
                        'documenti_contabilita_destinatario' => json_encode([
                            'ragione_sociale' => "{$cliente_woocommerce->first_name} {$cliente_woocommerce->last_name}",
                            'indirizzo' => $cliente_woocommerce->address_1,
                            'citta' => strtoupper($cliente_woocommerce->city),
                            'nazione' => strtoupper($cliente_woocommerce->country),
                            'cap' => $cliente_woocommerce->postcode,
                            'provincia' => (empty($cliente_woocommerce->state)) ? '  ' : strtoupper(@$cliente_woocommerce->state),
                            'partita_iva' => ' ',
                            'codice_sdi' => ' ',
                            'codice_fiscale' => ' ',
                            'pec' => ' ',
                        ]),
                        'documenti_contabilita_extra_param' => '',
                        'documenti_contabilita_fattura_accompagnatoria' => '0',
                        'documenti_contabilita_file_preview' => '',
                        'documenti_contabilita_file_preview_xml' => '',
                        'documenti_contabilita_formato_elettronico' => '0',
                        'documenti_contabilita_fornitori_id' => '',
                        'documenti_contabilita_imponibile' => $ordine_woocommerce->total,
                        'documenti_contabilita_imponibile_scontato' => $ordine_woocommerce->total - $ordine_woocommerce->discount_total,
                        'documenti_contabilita_importo_bollo' => '0.00',
                        'documenti_contabilita_iva' => $ordine_woocommerce->total_tax,
                        'documenti_contabilita_iva_json' => '{"1":[22,' . $ordine_woocommerce->total_tax . ']}',
                        'documenti_contabilita_luogo_destinazione' => '',
                        'documenti_contabilita_luogo_destinazione_id' => '',
                        'documenti_contabilita_metodo_pagamento' => $pagamento,
                        'documenti_contabilita_nome_file_xml' => '',
                        'documenti_contabilita_nome_zip_sdi' => '',
                        'documenti_contabilita_n_colli' => '0.00',
                        'documenti_contabilita_oggetto' => '',
                        'documenti_contabilita_ordine_chiuso' => '',
                        'documenti_contabilita_peso' => '0.00',
                        'documenti_contabilita_porto' => '',
                        'documenti_contabilita_progressivo_invio' => '',
                        'documenti_contabilita_rif_documento_id' => '',
                        'documenti_contabilita_ritenuta_acconto_imponibile_valore' => $ordine_woocommerce->total,
                        'documenti_contabilita_ritenuta_acconto_perc' => '0.00',
                        'documenti_contabilita_ritenuta_acconto_perc_imponibile' => '100.00',
                        'documenti_contabilita_ritenuta_acconto_valore' => '0.00',
                        'documenti_contabilita_rivalsa_inps_perc' => '0.00',
                        'documenti_contabilita_rivalsa_inps_valore' => '0.00',
                        'documenti_contabilita_sconto_percentuale' => '0.00',
                        'documenti_contabilita_split_payment' => '0',
                        'documenti_contabilita_stato' => '1',
                        'documenti_contabilita_stato_invio_sdi' => '',
                        'documenti_contabilita_tasso_di_cambio' => '1',
                        'documenti_contabilita_template_pdf' => '1',
                        'documenti_contabilita_tipo' => '5',
                        'documenti_contabilita_tipo_destinatario' => '2', //Privato
                        'documenti_contabilita_totale' => $ordine_woocommerce->total,
                        'documenti_contabilita_trasporto_a_cura_di' => '',
                        'documenti_contabilita_valuta' => 'EUR',
                        'documenti_contabilita_vettori_residenza_domicilio' => '',
                        'documenti_contabilita_imponibile_iva_json' => '{"1":[22,' . $ordine_woocommerce->total_tax . ']}',
                        'documenti_contabilita_serie' => '',
                        'documenti_contabilita_numero' => $ordine_woocommerce->number,
                        'documenti_contabilita_note_interne' => $note
                    ];

                    $billing = (array) $ordine_woocommerce->billing;
                    $shipping = (array) $ordine_woocommerce->shipping;

                    if (!empty(array_diff($billing, $shipping))) {
                        if (!empty($shipping['company'])) {
                            $luogo_dest = "{$shipping['company']}\n";
                        } else {
                            $luogo_dest = "{$shipping['first_name']} {$shipping['last_name']}\n";
                        }

                        $luogo_dest .= "{$shipping['address_1']}\n";
                        $luogo_dest .= "{$shipping['city']}, cap: {$shipping['postcode']}\n";
                        if (!empty($shipping['phone'])) {
                            $luogo_dest .= "{$shipping['phone']}";
                        }

                        $ordine['documenti_contabilita_luogo_destinazione'] = $luogo_dest;
                    }

                    $ordine_id = $this->apilib->create('documenti_contabilita', $ordine, false);

                    $ordine = $this->apilib->view('documenti_contabilita', $ordine_id);

                    foreach ($ordine_woocommerce->line_items as $articolo_woocommerce) {
                        $articolo_id = ($articolo_woocommerce->variation_id > 0) ? $articolo_woocommerce->variation_id : $articolo_woocommerce->product_id;
                        $dbproduct = $this->apilib->searchFirst('fw_products', ['fw_products_woocommerce_external_code' => $articolo_id]);

                        $articolo = [
                            'documenti_contabilita_articoli_applica_ritenute' => '1',
                            'documenti_contabilita_articoli_applica_sconto' => '1',
                            'documenti_contabilita_articoli_codice' => $articolo_woocommerce->sku,
                            'documenti_contabilita_articoli_descrizione' => '',
                            'documenti_contabilita_articoli_documento' => $ordine_id,
                            'documenti_contabilita_articoli_imponibile' => '0.00',
                            'documenti_contabilita_articoli_importo_totale' => $articolo_woocommerce->total,
                            'documenti_contabilita_articoli_iva_id' => '1',
                            //                            'documenti_contabilita_articoli_iva_id' => $dbproduct['fw_products_tax_id'],
                            //                            'documenti_contabilita_articoli_iva_perc' => number_format($dbproduct['fw_products_tax_percentage'], 0, '.', ''),
                            'documenti_contabilita_articoli_iva_perc' => 22,
                            'documenti_contabilita_articoli_lotto' => '',
                            'documenti_contabilita_articoli_modified_date' => '',
                            'documenti_contabilita_articoli_name' => $articolo_woocommerce->name,
                            'documenti_contabilita_articoli_prezzo' => ($articolo_woocommerce->price / 122) * 100,
                            'documenti_contabilita_articoli_prodotto_id' => $dbproduct['fw_products_id'],
                            'documenti_contabilita_articoli_quantita' => $articolo_woocommerce->quantity,
                            'documenti_contabilita_articoli_sconto' => '0.00',
                            'documenti_contabilita_articoli_unita_misura' => '',
                        ];

                        $this->apilib->create('documenti_contabilita_articoli', $articolo);
                    }

                    foreach ($ordine_woocommerce->shipping_lines as $shipping_line) {
                        $articolo = [
                            'documenti_contabilita_articoli_applica_ritenute' => '0',
                            'documenti_contabilita_articoli_applica_sconto' => '0',
                            'documenti_contabilita_articoli_codice' => '',
                            'documenti_contabilita_articoli_descrizione' => '',
                            'documenti_contabilita_articoli_documento' => $ordine_id,
                            'documenti_contabilita_articoli_imponibile' => '0.00',
                            'documenti_contabilita_articoli_importo_totale' => $shipping_line->total,
                            'documenti_contabilita_articoli_iva' => '0',
                            'documenti_contabilita_articoli_iva_id' => '6',
                            'documenti_contabilita_articoli_iva_perc' => '0',
                            'documenti_contabilita_articoli_lotto' => '',
                            'documenti_contabilita_articoli_modified_date' => '',
                            'documenti_contabilita_articoli_name' => $shipping_line->method_title,
                            'documenti_contabilita_articoli_prezzo' => $shipping_line->total,
                            'documenti_contabilita_articoli_prodotto_id' => '',
                            'documenti_contabilita_articoli_quantita' => '1',
                            'documenti_contabilita_articoli_sconto' => '0.00',
                            'documenti_contabilita_articoli_unita_misura' => '',
                        ];

                        $this->apilib->create('documenti_contabilita_articoli', $articolo);
                    }

                    if ($ordine['documenti_contabilita_template_pdf']) {
                        $content_html = $this->apilib->view('documenti_contabilita_template_pdf', $ordine['documenti_contabilita_template_pdf']);
                        $pdfFile = $this->layout->generate_pdf($content_html['documenti_contabilita_template_pdf_html'], "portrait", "", ['documento_id' => $ordine_id], 'contabilita', TRUE);
                    }

                    if (file_exists($pdfFile)) {
                        $contents = file_get_contents($pdfFile, true);
                        $pdf_b64 = base64_encode($contents);
                        $this->apilib->edit("documenti_contabilita", $ordine_id, ['documenti_contabilita_file' => $pdf_b64]);
                    }

                    echo_flush("Fine import ordine {$ordine_woocommerce->id}\n");
                }
                echo_flush(date('Y-m-d H:i:s') . " Fine pagina {$i}\n--------------------\n");
            }
            echo_flush(date('Y-m-d H:i:s') . " Fine importazione\n");
        } catch (Exception $e) {
            log_message('error', 'Error while importing orders ' . $e->getMessage());
            echo_flush(date('Y-m-d H:i:s') . " Errore importazione {$e->getMessage()}\n");
        }
    }

    public function importproducts($page = 1, $product_id = null)
    {
        echo date('Y-m-d H:i:s') . " Starting importing\n";
        $this->load->model('products_manager/woocomm');

        try {
            if ($page == null) $page = 1;

            // for ($i = $page; $i <= 3; $i++) {
            echo date('Y-m-d H:i:s') . " Extract products from Woocommerce\n";

            if ($product_id && ctype_digit($product_id)) {
                echo date('Y-m-d H:i:s') . " Importing single product id: $product_id\n";

                $product = $this->wc->get("products/{$product_id}");
                $this->importSingle($product);
            } else {
                echo date('Y-m-d H:i:s') . " Error import product\n";
            }

            if (!$product_id) {
                echo date('Y-m-d H:i:s') . " Importing last 20 products\n";

                $products = $this->wc->get('products', ['per_page' => 20]);

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $this->importSingle($product);
                    }
                }
            }


            // } else {
            //     break;
            // }
            // echo date('Y-m-d H:i:s') . " Done page {$i}\n--------------------\n";
            // }

            echo date('Y-m-d H:i:s') . " Finished import\n";
        } catch (Exception $e) {
            log_message('error', 'Error while importing products ' . $e->getMessage());
            echo date('Y-m-d H:i:s') . " Error import {$e->getMessage()}\n";
        }
    }

    private function importSingle($product)
    {
        // $product_exist = $this->db->query("SELECT * FROM fw_products WHERE fw_products_woocommerce_external_code = '{$product->id}'")->row();

        $attributes = $this->woocomm->import_attributes($product);

        $response_product = $this->woocomm->import_products($product);

        if (!empty($response_product)) {
            $this->woocomm->import_categories($product, $response_product);

            $this->woocomm->import_images($product, $response_product);

            if (!empty($product->variations) && is_array($product->variations)) {
                $editprod = $this->apilib->edit('fw_products', $response_product['fw_products_id'], ['fw_products_type' => 2]);

                foreach ($product->variations as $variant_id) {
                    $variant = $this->wc->get("products/{$variant_id}");

                    $inserted_variant = $this->woocomm->import_products($variant, $editprod['fw_products_id']);

                    $variant_attributes = $this->woocomm->import_attributes($variant, true);

                    $json_variant_attributes = json_encode($variant_attributes);

                    $this->apilib->edit('fw_products', $inserted_variant['fw_products_id'], ['fw_products_json_attributes' => $json_variant_attributes]);
                    echo date('Y-m-d H:i:s') . " [|+] json attr for {$inserted_variant['fw_products_id']} -> $json_variant_attributes\n";

                    $this->woocomm->import_categories($product, $inserted_variant);

                    $this->woocomm->import_images($variant, $inserted_variant);

                    $variant_attr = $variant->attributes[0];

                    echo date('Y-m-d H:i:s') . " [v+] Import variation {$variant_attr->name} {$variant_attr->option}\n";
                }
            }

            $json_attributes = json_encode($attributes);

            $this->apilib->edit('fw_products', $response_product['fw_products_id'], ['fw_products_json_attributes' => $json_attributes]);
            echo date('Y-m-d H:i:s') . " [|+] json attr for {$response_product['fw_products_id']} -> $json_attributes\n";

            unset($attributes);
        }
    }

    public function importbrands()
    {
        die('not allowed');
        echo date('Y-m-d H:i:s') . " Starting importing brands\n\n";

        for ($i = 1; $i <= 1000; $i++) {
            echo date('Y-m-d H:i:s') . " Estrazione prodotti da woocomm - pag {$i}\n\n";

            $products = $this->woocommerce->get('products', ['per_page' => 100, 'page' => $i]);

            if (!empty($products)) {
                echo date('Y-m-d H:i:s') . " Starting products cycling\n";

                foreach ($products as $product) {
                    try {
                        $crm_product = $this->db->get_where('fw_products', ['fw_products_woocommerce_external_code' => $product->id])->row_array();

                        $brand_id = null;
                        foreach ($product->attributes as $attr) {
                            if ($attr->name == 'Brand') {

                                $crm_brand = $this->db->get_where('fw_products_brand', ['fw_products_brand_value' => $attr->options[0]])->row_array();

                                if (empty($crm_brand)) {
                                    $created_brand = $this->apilib->create('fw_products_brand', [
                                        'fw_products_brand_value' => $attr->options[0],
                                    ]);

                                    $brand_id = $created_brand['fw_products_brand_id'];
                                    echo date('Y-m-d H:i:s') . ' {+} ' . $attr->options[0] . "\n";
                                } else {
                                    $brand_id = $crm_brand['fw_products_brand_id'];
                                    echo date('Y-m-d H:i:s') . ' {*} ' . $attr->options[0] . "\n";
                                }
                                break;
                            }
                        }

                        if (!empty($crm_product)) {
                            if (!empty($brand_id)) {
                                $this->db
                                    ->where(['fw_products_woocommerce_external_code' => $product->id])
                                    ->update('fw_products', ['fw_products_brand' => $brand_id]);

                                echo date('Y-m-d H:i:s') . " [#] {$product->name} [{$product->id}]\n";
                            } else {
                                echo date('Y-m-d H:i:s') . " [!!] empty brand\n";
                            }
                        }

                        unset($brand_id);
                    } catch (Exception $e) {
                        echo date('Y-m-d H:i:s') . " [!!] Error {$e->getMessage()}\n";
                    }
                }
            } else {
                echo date('Y-m-d H:i:s') . " This was the last page\n";
                break;
            }
        }

        echo date('Y-m-d H:i:s') . " Finished import\n";
    }
}
