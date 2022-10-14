<link rel="stylesheet" type="text/css" href="<?php echo base_url_template('template/adminlte/dist/css/jquery-ui.min.css'); ?>"></link>
<link rel="stylesheet" type="text/css" href="<?php echo base_url_template('template/adminlte/dist/css/jquery-ui.theme.css'); ?>"></link>
<style media="screen">
    .big-button span{height:auto!important;width:100%;padding:15px;margin-top:30px}
    .text{margin-top:10px;font-family:"Arial";font-size:.80em;width:100%;overflow:hidden;height:80px}
    .big_icon{font-size:2.4em}
    .btn-app{margin-left:0!important}
    .punto_cassa_display{background:#000;min-height:100px;color:#0be386;font-size:3em;text-align:right;padding:12px}
    .sub_totali{font-size:.4em}
    .nome_prodotto{font-size:.4em;text-align:left}
    .punto_cassa_display{margin-top:10px}
    .calculator{position:relative;padding:1em 0;display:block-inline;background-color:#296d94;font-family:'Arial Black';-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}
    .calc-row{text-align:center;font-size:2.9em}
    .calc-row .button{cursor:pointer;width:20%;text-align:center;display:inline-block;font-weight:bold;border:2px solid #666;background-color:#eee;padding:10px 0;margin:7px 5px}
    .calc-row div.screen{font-family:Droid Sans Mono;display:table;width:85%;background-color:#aaa;text-align:right;font-size:2em;min-height:1.2em;margin-left:.5em;padding-right:.5em;border:1px solid #888;color:#333}
    .calc-row div.zero{width:112px}
    .calc-row div.zero{margin-right:5px}
    .pay_button > .row > .col-md-8 {padding:20px;height:70px;background-color:#0b94ea;color:white;border-radius:5px;margin:20px auto 0 auto;}
    .pay_button > .row > .col-md-4 {padding:20px 20px 20px 64px;height:70px;background-color:#00a65a;color:white;border-radius:5px;margin:20px auto 0 auto;}
    .total_box_height{height:30px}
    .articles_box_height{min-height:400px}
    /*.pay_button{padding:20px;width:400px;height:70px;margin:20px auto 0 auto}*/
    .pay_button_main{font-size:x-large}
    .pay_button_item{font-size:larger;font-weight:200}
    .pay_button_price{font-size:x-large;font-weight:300}
    .article_row{border-top:1px solid lightgray;margin:10px;padding:15px 0 15px 0}
    .border_total_footer{border-top:1px solid lightgray;margin:10px;padding:15px 0 0 15px}
    .border_total_footer_bottom{border-bottom:1px solid lightgray}
    .contract_payment_box{margin-top:25px}
    .box.box-primary{padding:0 10px 10px 10px}
    .btn.articles_box_height{padding-bottom:10px}
    .btn-primary{/*height:80px*/}
    .js-expand-object:hover{cursor:pointer}
    .font-adjust-categorie{font-size:26px!important}
    .cerca-prodotti{width:80%}
    .margin-top-10{margin-top:20px}
    .top_sellers{text-align:center}
    .gallery_categoria{text-align:center}
    .productDisplay{width:100%;height:auto}
    .rapidProduct{width:140px;height:180px;margin:10px 5px 10px 5px;background-color:white;display:inline-block;border:#9dbdd9 1px solid;cursor:pointer}
    .rapid_image{width:100%;height:130px}
    .rapid_image img{max-width:100%;max-height:100%}
    .rapid_info{padding-top:5px}
    .categoria_prodotti{margin-top:10px}
    .ui-autocomplete-loading{background:white url(<?php echo @base_url('images/sandglass.gif');?>) right center no-repeat;background-size:40px 40px}
    .close{color:#aaa;float:right;font-size:28px;font-weight:bold}
    .close:hover,.close:focus{color:black;text-decoration:none;cursor:pointer}
</style>

<?php

$prodotti = $this->apilib->search('fw_products');
//debug($prodotti);

$configurati = $this->apilib->search('prodotti_configurati');
//debug($configurati);

$categorie = $this->apilib->search('fw_categories', ['fw_categories_punto_cassa' => DB_BOOL_TRUE]);
//$categorie_ids = array_filter(array_unique(array_map(function($dato) {
//                                    return $dato['fw_categories_id'];
//                                }, $categorie)));

foreach ($categorie as $key => $categoria) {
    $categorie[$key]['prodotti'] = $this->db->query(
            "SELECT * 
              FROM fw_products 
            LEFT JOIN prodotti_immagini ON (prodotti_immagini_id = fw_products_immagine) 
              WHERE (fw_products_is_active = 1 OR fw_products_is_active IS NULL) 
            AND (fw_products_deleted IS NULL OR fw_products_deleted = 0)
            AND fw_products_id IN (SELECT fw_products_id FROM fw_products_fw_categories WHERE fw_categories_id = {$categoria['fw_categories_id']})
")->result_array();
}


//debug($categorie);
?>

<div class="row">
    <div class="col-md-6">
        <div class="product_search_box">
            <div class="row">
                <div class="col-md-10">
                    <div class="form-group">
                        <label for="search_articolo">Cerca prodotti</label>
                        <input type="text" class="form-control input-medium cerca-prodotti" id="search_articolo"
                               name="search_autocomplete"
                               placeholder="Digita un nome o codice prodotto...">
                    </div>
                </div>
                <div class="overlay col-md-2 hide">
                    <i class="fa fa-refresh fa-spin"></i>
                </div>
            </div>
            <div class="row">
                <div class="product_category_box">
                    <div class="col-md-12">
                        <div class="box box-primary no-border">
                            <!--                            <div class="box-header margin-bottom">-->
                            <!--                                <p class="box-title font-adjust-categorie">Categorie</p>-->
                            <!--                            </div>-->
                            <div class="row margin-top-10">
                                <div class="col-sm-6">
                                    <button type="button"
                                            class="btn btn-block btn-primary btn-lg js_categoria_prodotti js_top_sellers categoria_prodotti">Più venduti</button>
                                </div>
                                <?php $categoria_num = 0; ?>
                                <?php foreach ($categorie as $categoria): ?>
                                    <?php if ($categoria['fw_categories_punto_cassa'] == 1): ?>
                                        <div class="col-sm-6">
                                            <button type="button"
                                                    class="btn btn-block btn-primary btn-lg <?php if ($categoria['prodotti'] == null): ?>disabled<?php endif; ?> js_categoria_prodotti categoria_prodotti"
                                                    categoria_num="<?php echo($categoria_num); ?>"><?php echo($categoria['fw_categories_name']); ?></button>
                                        </div>
                                        <?php $categoria_num++; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="top_sellers margin-top-10 js_categoria_display top_sellers_display">
                <div class="row">
                    <?php $prodotto_num = 0; ?>

                    <?php foreach ($prodotti as $prodotto): //debug($prodotto); ?>

                        <div class="rapidProduct" data-toggle="modal" data-target="#product<?php echo($prodotto['fw_products_id']); ?>"
                             prodottonum="<?php echo($prodotto_num); ?>">
                            <div class="rapid_image ">
                                <img src="<?php echo @base_url('imgn/1/100/100/uploads/' . $prodotto['prodotti_immagini_immagine']); ?>">
                            </div>
                            <div class="rapid_info">
                                <?php echo $prodotto['fw_products_name']; ?><br>
                                <?php if ($prodotto['fw_products_sku']): ?>
                                    <?php echo $prodotto['fw_products_sku'] ?>
                                    -  <?php endif; ?><?php echo $prodotto['fw_products_sell_price']; ?> €
                            </div>
                        </div>
                        <?php $prodotto_num++; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $categoria_num = 0; ?>

            <?php foreach ($categorie as $categoria): ?>
                <?php if ($categoria['fw_categories_punto_cassa'] == 1): ?>
                    <div class="categoria_<?php echo($categoria_num); ?> margin-top-10 js_categoria_display gallery_categoria" style="display: none">
                        <div class="row">
                            <?php $prodotto_num = 0; ?>
                            <?php foreach ($categoria['prodotti'] as $prodotto): //debug($prodotto); ?>
                                <div class="rapidProduct"
                                     categorianum="<?php echo($categoria_num); ?>"
                                     data-target="#product<?php echo($prodotto['fw_products_id']); ?>"
                                     prodottonum="<?php echo($prodotto_num); ?>">
                                    <div class="rapid_image ">
                                        <img src="<?php echo @base_url('imgn/1/100/100/uploads/' . $prodotto['prodotti_immagini_immagine']); ?>">
                                    </div>
                                    <div class="rapid_info">
                                        <?php echo $prodotto['fw_products_name']; ?><br>
                                        <?php if ($prodotto['fw_products_sku']): ?>
                                            <?php echo $prodotto['fw_products_sku'] ?>
                                            -  <?php endif; ?><?php echo $prodotto['fw_products_sell_price']; ?> €
                                    </div>
                                </div>
                                <?php $prodotto_num++; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $categoria_num++; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Modal -->
        <div id="token_details_modal" class="modal fade">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Dettaglio Prodotto</h4>
                    </div>
                    <div class="modal-body"><div id="token_details"></div></div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-md-6">
        <div class="contract_payment_box">
            <div class="products_box">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <input type="text" class="form-control input-medium" id="search_cliente"
                                   name="search_autocomplete"
                                   placeholder="Aggiungi un cliente">
                            
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="form-group">
                            <select class="form-control input-medium" id="search_contract">
                                <option value="" disabled selected hidden>Seleziona un contratto</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-plus"></i> Nuovo <span class="fa fa-caret-down"></span></button>
                        <ul class="dropdown-menu">
                            <li><a class="js_open_modal" href="<?php echo base_url('get_ajax/modal_form/7'); ?>"><i class="fa fa-user"></i> Cliente</a></li>
                            <li><a class="js_open_modal" href="<?php echo base_url('get_ajax/modal_form/8'); ?>"><i class="fa fa-handshake-o"></i> Contratto</a></li>
                        </ul>
                    </div>
                    
                    <!--
                    <div class="col-md-1">
                        <a class="btn btn-success js_open_modal" href="<?php echo base_url('get_ajax/layout_modal/60'); ?>"><i class="fa fa-plus"></i></a>
                    </div>
                    <div class="col-md-1">
                        <a class="btn btn-primary js_open_modal" href="<?php echo base_url('get_ajax/modal_form/8'); ?>"><span class="fa fa-plus"></span></a>
                    </div>-->
                </div>
                <div class="articles_box_height">
                    <!-- Qui vengono inseriti gli articoli aggiunti al carrello -->
                </div>
                <div class="box">
                    <div class="box-header with-border js_custom_product">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="custom_product_name" placeholder="Prodotto">
                        </div>

                        <div class="col-md-4">
                            <input type="text" class="form-control" id="custom_product_price" placeholder="Prezzo €">
                        </div>

                        <div class="col-md-2">
                            <button type="button" class="prodottoCustom btn btn-primary"><i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                </div>
                <table id="punto_cassa_acquisti">
                    <tbody></tbody>
                </table>
            </div>
            <div class="total_box ">
                <div class="row border_total_footer">
                    <div class="col-md-12 total_box_height ">
                        <div class="form-group">
                            <label for="scontoSulTotale" class="col-sm-9 control-label">Sconto sul totale</label>

                            <div class="col-sm-2">
                                <input type="email" class="form-control sconto js-sconto_percentuale" id="scontoSulTotale" value=""
                                       placeholder="0%">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row border_total_footer">
                    <div class="col-md-12 total_box_height ">
                        <div class="col-md-9">
                            Competenze
                        </div>
                        <div class="col-md-3 js-subTotal">
                            <span>€ 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="row border_total_footer border_total_footer_bottom">
                    <div class="col-md-12 total_box_height ">
                        <div class="col-md-2">
                            Tasse
                        </div>
                        <div class="col-md-7">
                            IVA 22%
                        </div>
                        <div class="col-md-3 js-taxes">
                            <span>€ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pay_button">
                <div class="row">
                    <div class="col-md-8">
                        <div class="col-md-5">
                            <span class="pay_button_main">Totale</span> <span class="pay_button_item"></span>
                        </div>
                        <div class="col-md-7 pay_button_price js-total">
                            <span>€ 0.00</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <span class="btn pay_button_main js-paga">PAGA</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix visible-sm-block"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-migrate-3.0.0.min.js"></script>

<script type="text/javascript">
    var carrello = [];
    var cliente = null;

    function randomnum(min,max){
        return Math.floor(Math.random()*(max-min+1)+min);
    }

    $(document).ready(function() {
        //Recupero dallo storage l'eventuale carrello precedente

        //Se il carrello è pieno
        if (localStorage.getItem('cart') != null) {
            var carrello1 = JSON.parse(localStorage.getItem('cart'));

            carrello = carrello1;

            stampaArticoli(carrello1);
            
            
        }
        
        if (localStorage.getItem('cliente') != null) {
            var cliente = JSON.parse(localStorage.getItem('cliente'));

            //console.log(cliente);
            drawCliente(cliente);
            
            
        }
        

        $('.modal').on('click', '.js_add_to_cart', function () {
            var product_id = $(this).data('productid');
            var product_name = $(this).data('productname');
            var product_price = $(this).data('productprice');
            var product_size = $(this).data('productsize');
            var product_color = $(this).data('productcolor');
            //console.log("Product ID: "+product_id);

            //carrello.push({id:product_id, name:product_name, price:product_price, size:product_size,color:product_color});
            drawProdotto(product_id,product_name,product_price,product_size,product_color);
            //console.log(carrello);

            //TODO: salvare il carrello in local storage
            localStorage.clear();
            localStorage.setItem('cart', JSON.stringify(carrello));

            stampaArticoli(carrello);

            $('.modal').modal('hide');
        });

        $('.js_custom_product').on('click', '.prodottoCustom', function(){
            var product_id = '9999'+randomnum(700,999);
            var product_name = $('#custom_product_name').val();
            var product_price = $('#custom_product_price').val();
            //console.log(product_id + ' ' + product_name + ' ' + product_price);
            //alert(custom_product_id + ' ' + custom_product_name + ' ' + custom_product_price + ' ' + custom_product_sale);

            drawCustom(parseFloat(product_id),product_name,parseFloat(product_price));

            //TODO: salvare il carrello in local storage
            localStorage.clear();
            localStorage.setItem('cart', JSON.stringify(carrello));

            stampaArticoli(carrello);
        });

        $('.js-sconto_percentuale').on('change', function () {
            pricesUpdate();
        });

        $('.articles_box_height').on('click', '.js_remove_from_cart', function () {
            var cart_item = $(this).closest('.cart_item');
            var cart_item_id = $(this).data('id');
            for (var i in carrello) {
                if (carrello[i].id == cart_item_id) {
                    carrello.splice(i, 1);
                    break;
                }
            }
            //salvare il carrello in local storage

            localStorage.clear();
            
            localStorage.setItem('cart', JSON.stringify(carrello));
            

            stampaArticoli(carrello);
        });
        
        $('.articles_box_height').on('focusin', '.js-input_product_price', function(){
            $(this).data('val', $(this).val());
        });
        
        $('.articles_box_height').on('change', '.js-input_product_price', function () {
            var prev = $(this).data('val');
            var nuovo_prezzo = ($.isNumeric($(this).val()) ? $(this).val() : alert('Il campo accetta solo valori numerici.') ); // L'ideale sarebbe di mettere il valore numerico precedente o svuotare il campo... Dopo il refresh della pagina, viene NULL e non si riesce più a cambiare
            var cart_item = $(this).closest('.cart_item');
            var cart_item_id = cart_item.data('id');
            for (var i in carrello) {
                if (carrello[i].id == cart_item_id && prev == carrello[i].price) {
                    carrello[i].price = parseFloat(nuovo_prezzo);
                    break;
                }
            }
            //salvare il carrello in local storage

            localStorage.clear();
            localStorage.setItem('cart', JSON.stringify(carrello));
            stampaArticoli(carrello);
        });

        // NOTE
        $('.articles_box_height').on('focusin', '.js-note', function(){
            $(this).data('val', $(this).val());
        });

        $('.articles_box_height').on('change', '.js-note', function () {
            //console.log($(this).val());
            var prev = $(this).data('');
            var nnotes = $(this).val();
            var cart_item = $(this).closest('.cart_item');
            var cart_item_id = cart_item.data('id');
            for (var i in carrello) {
                //alert('TEST1');
                if (carrello[i].id === cart_item_id && prev !== carrello[i].notes) {
                    //alert('TEST2');
                    carrello[i].notes = nnotes;
                    $(this).val(nnotes);
                    break;
                }
            }
            //salvare il carrello in local storage
            localStorage.clear();
            localStorage.setItem('cart', JSON.stringify(carrello));
            stampaArticoli(carrello);
        });
        
        $('.articles_box_height').on('focusin', '.sconto', function(){
            $(this).data('val', $(this).val());
        });

        $('.articles_box_height').on('change', '.sconto', function () {
            var sconto = ($.isNumeric($(this).val()) ? $(this).val() : alert('Il campo accetta solo valori numerici.') ); // L'ideale sarebbe di mettere il valore numerico precedente o svuotare il campo...Dopo il refresh della pagina, viene NULL e non si riesce più a cambiare
            var prev = $(this).data('val');
            var cart_item = $(this).closest('.cart_item');
            var cart_item_id = cart_item.data('id');
            for (var i in carrello) {
                if (carrello[i].id == cart_item_id && prev == carrello[i].discount) {
                    carrello[i].discount = parseFloat(sconto);
                    break;
                }
            }
            //salvare il carrello in local storage

            localStorage.clear();
            localStorage.setItem('cart', JSON.stringify(carrello));
            stampaArticoli(carrello);
        });
        
        
        $(document).on('click','.rapidProduct',function(){
            var product_id = $(this).data('target');

            $.ajax({
                url:base_url + "custom/qdb/product",
                method:"post",
                data:{product_id:product_id},
                success:function(data){
                    $('#token_details').html(data);
                    $('#token_details_modal').modal("show");
                }
            });
        });

        $('.js-paga').on('click', function () {
            var prezzo = pricesUpdate();
            $.ajax({
                url:base_url + "custom/qdb/salva_ordine",
                method:"post",
                data:{
                    carrello:JSON.stringify(carrello),
                    cliente:JSON.stringify(parseInt(cliente.clienti_id)),
                    prezzo:JSON.stringify(prezzo),
                    contratto:$('#search_contract').val()
                },
                success:function(data) {
                    //console.log(data);
                    if(JSON.parse(data).success === false) { 
                        alert(JSON.parse(data).error); 
                    } else {
                        console.log(cliente.clienti_id);
                        location.href = base_url+'main/layout/5/'+cliente.clienti_id;
                    }
                }
            });
        });

    });

    $(function () {
        /******* ARTICOLO *******/
        $("#search_articolo").autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "custom/qdb/autocomplete/prodotti_configurati",
                    dataType: "json",
                    data: {
                        search: request.term
                    },
                    // search: function( event, ui ) {
                    //     loading(true);
                    // },
                    success: function (data) {
                        var collection = [];
                        loading(false);
                        if (data.count_total == 1) {
                            drawAutocomplete(data.results.data[0]);

                            /*var pc_id = p.prodotti_configurati_id;
                            var pc_label = p.prodotti_configurati_nome;
                            var pc_price = p.prodotti_configurati_prezzo_vendita;
                            var pc_value = p;

                            drawAutoProdotto(pc_id,pc_label,pc_price);*/
                        } else {
                            //alert('dadada');
                            $.each(data.results.data, function (i, p) {
                                //alert(p.prodotti_configurati_nome);
                                //console.log(p);
                                collection.push({
                                    "id": p.prodotti_configurati_id,
                                    "label": p.prodotti_configurati_nome,
                                    "price": p.prodotti_configurati_prezzo_vendita,
                                    "value": p
                                });
                            });
                        }
                        //console.log(collection);
                        response(collection);
                    }
                    // response: function(event, ui) {
                    //     $('.overlay').hide();
                    // }
                });
            },
            minLength: 3,
//            focus: function (event, ui) {
//                return false;
//            },
            select: function (event, ui) {
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                //console.log(ui);

                var itemlabel = ui.item;

                drawAutocomplete(itemlabel);
                return false;
            }
        });

        /******* CLIENTE *******/
        $("#search_cliente").autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "custom/qdb/autocomplete/clienti",
                    dataType: "json",
                    data: {
                        search: request.term
                    },
                    /*search: function( event, ui ) {
                        loading(true);
                    },*/
                    error: function (msg) {
                        alert('Ajax error');
                    },
                    success: function (data) {
                        var collection = [];
                        loading(false);

                        if (data.count_total == 1) {
                            //console.log(data);
                            drawCliente(data.results.data[0], true);
                        } else {
                            $.each(data.results.data, function (i, p) {
                                //console.log(p);
                                collection.push({
                                    "label": p.clienti_nome + ' ' + p.clienti_cognome,
                                    "value": p
                                });
                            });
                        }
                        //console.log(collection);
                        response(collection);
                    }
                });
            },
            minLength: 3,
//            focus: function (event, ui) {
//                return false;
//            },
            select: function (event, ui) {
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                drawCliente(ui.item.value, true);
                return false;
            }
        });
    });

    function drawProdotto(product_id,product_name,product_price,product_size,product_color){
        carrello.push({
            id: product_id,
            name: product_name,
            price: product_price,
            originalPrice: product_price,
            size: product_size,
            color: product_color,
            discount: 0,
            notes: '',
        });
    }

    function drawCustom(product_id,product_name,product_price){
        carrello.push({
            id: product_id,
            name: product_name,
            price: product_price,
            discount: 0,
            notes: '',
        });
    }

    //index: convertire le variabili utilizzate da str a int.
    function pricesUpdate() {
//        var articoli_in_carrello = JSON.parse(localStorage.cart);
//        var totalPriceCarrello = 0;
//        var scontoPercentuale = $('.js-sconto_percentuale').val() ? $('.js-sconto_percentuale').val() : 0;
//        var pricePercent = 100 - scontoPercentuale;
//        var x = 0;
//        for (var i in articoli_in_carrello) {
//            j = parseInt(x) + 1;
//            riga_prodotto = $('.cart_item[riga=' + j + ']');
//            articolo_in_carrello = articoli_in_carrello[i];
//            itemScontoPercentuale = $('.js-discount', riga_prodotto).val() ? $('.js-discount', riga_prodotto).val() : 0;
//            itemPricePercent = 100 - itemScontoPercentuale;
//            articolo_adjust_discount = ((itemPricePercent) / 100);
//            $('.js-article-price', riga_prodotto).text(articolo_adjust_discount);
//
//            totalPriceCarrello += articolo_adjust_discount;
//            x++;
//        }
//        totalPriceCarrello = ((totalPriceCarrello * pricePercent) / 100);
//        totalTasse = ((totalPriceCarrello * 22) / 100);
        var subtotale = 0;
        var scontoPercentuale = $('.js-sconto_percentuale').val() ? $('.js-sconto_percentuale').val() : 0;
        
        $.each (carrello,function (i,prodotto) {
            riga_prodotto = $('.cart_item').data('id');
            //itemScontoPercentuale = $('.js-discount', riga_prodotto).val() ? $('.js-discount', riga_prodotto).val() : 0;
            //console.log(prodotto);
            
            if (typeof prodotto.discount !== 'undefined') {
                //alert('ok');
                price_discounted = (prodotto.price / 100) * (100-prodotto.discount);
            } else {
                price_discounted = prodotto.price;
            }
            subtotale+=price_discounted;
        });
        totalTasse = ((subtotale * 22) / 100);

        subtotale = subtotale - (subtotale / 100 * scontoPercentuale);

        totale = subtotale + totalTasse;

        subtotale = subtotale.toFixed(2);
        totalTasse = totalTasse.toFixed(2);
        totale = totale.toFixed(2);

        //console.log(totale);

        $('.js-subTotal span').text('€ ' + subtotale);
        $('.js-taxes span').text('€ ' + totalTasse);
        $('.js-total span').text('€ ' + totale);

        return {sconto:scontoPercentuale, subtotale: subtotale, tasse:totalTasse, totale:totale};
    }

    function drawCliente(cliente_selezionato) {
        $('#search_cliente').val(cliente_selezionato['clienti_nome'] + ' ' + cliente_selezionato['clienti_cognome']);
        cliente = cliente_selezionato['clienti_id'];

        //console.log(cliente);

        localStorage.setItem('cliente', JSON.stringify(cliente_selezionato));

        $.ajax({
            method: 'get',
            url: base_url + "custom/qdb/search_contratti/"+cliente,
            dataType: "json",
            
            error: function (msg) {
                alert('Ajax error');
            },
            success: function (data) {
                $('#search_contract').html('');
                //console.log('DRAW_CLIENTE '+data);
                $.each(data, function (i, p) {
                    $('#search_contract').append('<option value="'+data[i].contratti_id+'">'+data[i].contratti_numero+'</option>');
                });
            }
        });
            
        
    }

    function drawAutocomplete(item){
        //alert('drawprodotto');
        $('#search_articolo').val(item.label);
        drawCustom(parseFloat(item.id),item.label,parseFloat(item.price));

        localStorage.clear();
        localStorage.setItem('cart', JSON.stringify(carrello));

        stampaArticoli(carrello);
    }

    // Il codice seguente serve a gestire la comparsa dei prodotti al click delle categorie
    $(document).ready(function () {
        $('.js_categoria_prodotti').click(function () {
            categoriaNum = $(this).attr('categoria_num');
            $('.js_categoria_display').hide();
            $('.categoria_' + categoriaNum).show();
        });
    });

    // Il codice seguente serve a gestire la ricomparsa dei prodotti più venduti
    $(document).ready(function () {
        $('.js_top_sellers').click(function () {
            $('.js_categoria_display').hide();
            $('.top_sellers_display').show();
        });
    });

    $(document).ready(function(){
//        $('.sconto').change('#scontoSulTotale', function(){
//            pricesUpdate();
//        });
    });
    
    $(document).ready(function(){
        $('.products_box').on('change', '.ordina_fornitore', function(){
            
                var cart_item = $(this).closest('.cart_item');
                var cart_item_id = cart_item.data('id');
//                var numero_riga = cart_item.data('row');
//                console.log(cart_item);
                
                for (var i in carrello) {
                    if (carrello[i].id == cart_item_id) { //Sappiamo che c'è un bug: l'id può essere doppio e appena lo trova fa breck. Quindi se clicco il secondo, checka il primo e il secondo lo lascia com'è.... correggeremo!
                        if ($(this).is(':checked')) {
                            carrello[i].ordina_fornitore = true;
                        } else {
                            carrello[i].ordina_fornitore = false;
                        }
//                        console.log('dentro');
                        //console.log(carrello);
                        break;
                    }
                }
                //salvare il carrello in local storage

                localStorage.clear();

                localStorage.setItem('cart', JSON.stringify(carrello));


                stampaArticoli(carrello);
            });
        
    });
    

    function stampaArticoli(carrello){

        //console.log(carrello);
        $('.articles_box_height').empty();
        //console.log("emptied total-scale-men");
        var numero_riga = 1;
        $.each(carrello, function(i, item) {
            //console.log(item);

            if(typeof item.size !== 'undefined' && item.color !== 'undefined'){
                var item_name = item.name+' / '+item.size+' / '+item.color;
            } else {
                var item_name = item.name;
            }

            var is_checked_ordina_fornitore = (item.ordina_fornitore == true)?'checked="checked"':'';

            $('.articles_box_height').append(`
                  <div class="box box-default cart_item" data-id="`+item.id+`">
                    <div class="box-header with-border js-expand-object">
                      <div class="col-md-4 "><input type="checkbox" class="ordina_fornitore" `+is_checked_ordina_fornitore+` title="ordina subito al fornitore"/> <span title="ordina subito al fornitore">?</span></div>
                      <div class="col-md-4 ">`+item_name+`<br/><span class=""></span></div>
                      <div class="col-md-3 js-expand-exclude">
                        <div class="input-group js-expand-exclude">
                            <span class="input-group-addon"><i class="fa fa-eur"></i></span>
                            <input class="form-control js-expand-exclude js-input_product_price" type="text" value="`+item.price+`" />
                        </div>
                      </div>

                      <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-danger js_remove_from_cart" data-id="`+item.id+`"><i class="fa fa-times"></i></button>
                      </div>

                    </div>

                    <div class="box-header js-expand-content" style="display:none">
                      <div class="col-md-5 col-md-offset-2">
                        <div class="form-group"><input type="text" class="form-control input-sm js-note" value="`+item.notes+`" placeholder="Note prodotto"></div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group"><input type="text" class="form-control input-sm sconto" placeholder="Sconto %" value="`+item.discount+`"></div>
                      </div>
                    </div>
                  </div>
                `);
        });

        pricesUpdate();

        
    }
    
    //Il codice seguente serve ad espandere i prodotti nel carrello
    $("body").on('click', '.js-expand-object:not(".js-expand-exclude")', function () {
        //alert('test');
        $(this).next().toggle();
        pricesUpdate();

    });
</script>