<?php $this->layout->addModuleJavascript('contabilita', 'axios.min.js'); ?>
<?php $this->layout->addModuleStylesheet('contabilita', 'css/vue-select.css'); ?>

<?php
$settings = $this->apilib->searchFirst('magazzino_settings');
$current_userdata = $this->auth->getSessionUserdata();
$magazzino = $current_userdata['users_magazzino'] ?? null;

?>

<style>
.mt-10 {
    margin-bottom: 10px;
}

.mt-20 {
    margin-top: 20px;
}

.cart_container {
    max-height: 550px;
    overflow-y: auto;
}

.cart_empty {
    font-size: 18px;
}

.cart_totale_container {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    /*height: 34px;*/
    height: 70px;
}

.cart_amount_price {
    text-align: right;
}

.cart_totale {
    font-weight: 700;
    font-size: 1.8rem;
    margin-bottom: 0;
    width: 100%;
    display: flex;
    justify-content: space-between;
}

.info-box-content-inner {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-direction: row;
}

.prezzo_prodotto {
    align-self: flex-start;
}

.prezzo_prodotto input {
    max-width: 120px
}

.prezzo_prodotto input.sconto {
    max-width: 50px
}

.cart_item_sku {
    margin-bottom: 5px;
}

.cart_item_price {
    margin-bottom: 5px;
}

.select_product_option {
    display: flex;
    justify-content: space-between;
    align-items: center
}

.select_product_option_image {
    max-width: 70px;
}

.img_product {
    padding: 0 !important;
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.img_product img {
    max-height: 150px;
}

.list-group-item-text button {
    margin-top: 10px;
}

.loading_icon {
    padding: 20px 0;
    font-size: 4rem;
    color: #3c8dbc;
}

.btn_container {
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn_container_prodotto_carrello {
    display: flex;
    justify-content: flex-end;
}

.discount_container {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.discount_container input {
    max-width: 30%;
}

.cart_label {
    font-size: 1.2rem;
}

.removeProduct {
    margin-top: 10px;
}

.cart_product {
    border: 1px solid #ddd;
    /*background: #efefef;*/
    margin-bottom: 15px;
    margin-left: 0;
    margin-right: 0;
    border-radius: 3px;
    padding: 10px 0;
}

.varianti_prodotto {
    margin-top: 15px;
}

/*Single product style*/
.media {
    border: 1px solid #ddd;
    margin-bottom: 10px;
    padding: 10px 15px;
    height: 200px;
    box-shadow: 0px 1px 10px 0px rgb(0 0 0 / 15%);
    border-radius: 5px;
}

.media-footer {
    margin-top: 10px;
}

.product_sku,
.product_price {
    margin-bottom: 5px;
}

.prod_container {
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 15px;
    padding: 10px;
    height: 300px;
    position: relative;
}

.prod_image {
    margin-bottom: 10px;
}

.prod_image img {
    /*max-height: 150px;*/
    max-height: 130px;
    margin: 0 auto;
}

.prod_sku,
.prod_price {
    margin-bottom: 5px;
}

.prod_actions {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 10px;
}

.prod_action {
    position: absolute;
    left: 10px;
    bottom: 10px;
    right: 10px;
}

.cart_table {
    width: 100%;
    padding: 10px 15px;
}

.cart_table table th,
.cart_table table td {
    border-top: 1px solid #cccccc !important;
    font-size: 18px !important;
    font-weight: 600 !important;
}

.font_bold {
    font-weight: 600;
}

.quantity_warning {
    background-color: #ef4444;
    color: #ffffff;
    padding: 2px 4px;
    border-radius: 4px;
}

.products_container {
    max-height: 800px;
    overflow-y: scroll;
}

.total_discount {
    width: 120px;
    float: right;
}

.total_price {
    width: 120px;
    float: right;
}
</style>

<?php if ($settings['magazzino_settings_inventario_in_corso']): ?>
<section class="content-header">
    <div class="alert alert-danger mb-0">
        <h5>Inventario in corso!</h5>

        <div><?php e('Attenzione! E\' in corso l\'inventario. Si sconsiglia di movimentare la merce in questa fase.');?></div>
    </div>
</section>
<?php endif;?>
<div id="app">
    <div class="punto_cassa">
        <div class="row">
            <!-- SELEZIONE PRODOTTI -->
            <div class="col-md-7">
                <div class="box box-primary mh-100vh">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="search_product">Prodotti</label>
                                    <v-select ref="myproduct" label="fw_products_name" v-model="prodotti_select" :options="prodotti_option" @search="fetchOptions" @input="addToCart">
                                        <template slot="no-options">
                                            Cerca un prodotto
                                        </template>
                                        <template slot="option" slot-scope="option">
                                            <div class="d-center">
                                                <div class="select_product_option">
                                                    <div>
                                                        <img :src="printImage(option)" alt="" srcset="" class="img-responsive select_product_option_image">
                                                    </div>
                                                    <div>
                                                        {{ option.fw_products_name }}
                                                        <br />
                                                        <p class="text-right"><strong>{{ ivaProdotto(option) }} €</strong></p>
                                                    </div>
                                                </div>

                                            </div>
                                        </template>
                                        <template slot="selected-option" slot-scope="option">
                                            <div class="selected d-center">
                                                {{ option.fw_products_name}}
                                            </div>
                                        </template>
                                    </v-select>
                                </div>
                            </div>




                            <div class="col-sm-6">
                                <div class="form-group js_barcode_container">
                                    <label for="sarch_barcode">Barcode</label>
                                    <v-select ref="mybarcode" label="fw_products_barcode" v-model="barcode_select" :options="barcode_option" @search="fetchBarcodeOptions" @input="addToCart">
                                        <template slot="no-options">
                                            Cerca tramite barcode
                                        </template>
                                        <template slot="option" slot-scope="option">
                                            <div class="d-center">
                                                <div class="select_product_option">
                                                    <div>
                                                        <img :src="printImage(option)" alt="" srcset="" class="img-responsive select_product_option_image">
                                                    </div>
                                                    <div style="text-align:right">
                                                        {{ option.fw_products_name }}
                                                        <br />
                                                        <p><strong>{{ option.fw_products_barcode }}</strong></p>
                                                        <p><strong>{{ option.fw_products_sku }}</strong></p>
                                                        <p><strong>{{ ivaProdotto(option) }} €</strong></p>
                                                    </div>
                                                </div>

                                            </div>
                                        </template>
                                        <template slot="selected-option" slot-scope="option">
                                            <div class="selected d-center">
                                                {{ option.fw_products_barcode }}
                                            </div>
                                        </template>
                                    </v-select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="search_centro_costo">Centro di ricavo</label>
                                    <v-select label="centri_di_costo_ricavo_nome" :clearable="false" v-model="centro_costo_select" :options="centri_costo" @input="selectCentroCosto"></v-select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="search_magazzino">Magazzino</label>
                                    <p v-if="magazzino.id">{{ magazzino.titolo }}</p>
                                    <v-select v-else label="magazzini_titolo" :clearable="false" v-model="magazzino_select" :options="magazzini_options" @input="selectMagazzino"></v-select>
                                </div>
                            </div>
                        </div>

                        <section class="filter_products mt-20 hidden">
                            <div class="row">
                                <!-- <div class="col-sm-6">
                                    <button type="submit" class="btn btn-success btn-lg btn-block mt-10">
                                        <i class="fas fa-cart-arrow-down"></i>
                                        Più venduti
                                    </button>
                                </div> -->
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-success btn-lg btn-block mt-10" @click="favouriteProducts">
                                        <i class="fas fa-star"></i>
                                        Preferiti
                                    </button>
                                </div>
                            </div>
                        </section>

                        <hr />

                        <!-- PRODOTTI VISUALIZZATI -->
                        <div class="col-sm-12 products_container" v-show="prodotti">
                            <div class="row">
                                <div v-if="product_loading" class="col-sm-12 text-center">
                                    <div class="loading_icon">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-4 col-xl-3" v-for="(prodotto, index) in prodotti" :key="index">

                                    <prodotto :product="prodotto" @clicked="addToCart(prodotto)" />

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!--CARRELLO -->
            <div class="col-md-5">
                <div class="box box-success mh-100vh">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <label for="cliente">Cliente</label>
                                    <v-select label="customers_last_name" v-model="customer_select" :options="customer_option" @search="fetchCustomerOptions" @input="selectCustomer">
                                        <template slot="no-options">
                                            Cerca cliente
                                        </template>
                                        <template slot="option" slot-scope="option">
                                            <div class="d-center">
                                                <div class="select_product_option">
                                                    <div>
                                                        <strong>{{ option.customers_last_name }} {{ option.customers_name }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template slot="selected-option" slot-scope="option">
                                            <div class="selected d-center">
                                                {{ option.customers_last_name}} {{ option.customers_name }}
                                            </div>
                                        </template>
                                    </v-select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="search_articolo">Nuovo cliente</label>
                                    <a href="<?php echo base_url(); ?>get_ajax/modal_form/customers" class="js_open_modal btn btn-success btn-sm" style="display:block">
                                        <i class="fas fa-plus"></i> Crea
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row cart_container">
                            <div v-if="carrello.length == 0" class="col-sm-12">
                                <p class="text-center text-red cart_empty">Non ci sono prodotti nel carrello</p>
                            </div>
                            <div v-if="cart_loading" class="col-sm-12 text-center">
                                <i class="fas fa-spinner fa-spin loading_icon"></i>
                            </div>
                            <div class="col-sm-12" v-for="(prodotto, index) in carrello" :key="index">
                                <div class="row cart_product">
                                    <div class="col-md-2">
                                        <img :src="printImage(prodotto.prodotto)" alt="" srcset="" class="img-responsive" style="max-height:100%;">
                                    </div>
                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-sm-12 col-md-8">
                                                <p class="cart_item_name"><strong>{{ prodotto.prodotto.fw_products_name}}</strong> - {{ prodotto.prodotto.fw_products_sku }}</p>
                                                <p class="cart_item_price">Prezzo: € <strong>{{ prodotto.prezzo_ivato }} </strong> (IVA incl.) - IVA: <strong>{{ prodotto.perc_iva }}% </strong> -
                                                    <span v-if="prodotto.quantita > prodotto.quantita_disponibile">Disp: <span class="font-bold quantity_warning">{{prodotto.quantita_disponibile || "NO"}}</span> </span>
                                                    <span v-else>Disp: <strong>{{prodotto.quantita_disponibile || "NO"}}</strong> </span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 col-sm-12">
                                                <label class="cart_label" for="totale_prodotto">Totale (IVA incl.)</label>
                                                <div class="input-group">
                                                    <input :value="prodotto._totale_ivato_scontato" name="totale_prodotto" class="form-control input-sm" @change="setProductPrice(prodotto, index, $event)">
                                                    <span class="input-group-addon" id="basic-addon1">€</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 col-sm-12">
                                                <label class="cart_label">Quantità</label>
                                                <div class="input-group">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-primary btn-sm" type="button" min="1" @click="decreaseProductQuantity(prodotto, index)"><i class="fas fa-minus"></i></button>
                                                    </span>
                                                    <input :value="prodotto.quantita" type="number" class="form-control quantity_input input-sm" placeholder="Quantità" name="quantita_prodotto" @change="setProductQuantity(prodotto, index, $event)">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-primary btn-sm" type="button" min="1" @click="increaseProductQuantity(prodotto, index)"><i class="fas fa-plus"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-12">
                                                <label class="cart_label" for="sconto_prodotto">Sconto</label>
                                                <div class="input-group">
                                                    <input :value="prodotto.sconto" type="text" class="form-control sconto input-sm" placeholder="" name="sconto_prodotto" @change="setProductDiscount(prodotto, index, $event)">
                                                    <span class="input-group-addon" id="basic-addon1">%</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-12 text-right">
                                                <div style="height:54px;display:flex;justify-content:flex-end;align-items:flex-end">
                                                    <button class="btn btn-danger" @click="removeItem(prodotto)"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div class="row mt-20">
                            <div class="col-sm-12">
                                <div class="cart_table">
                                    <div class="table-responsive">
                                        <table class="table text-uppercase">
                                            <tbody>
                                                <tr>
                                                    <th scope="row" class="text-left">Sconto:</th>
                                                    <td class="text-right">
                                                        <div class="input-group total_discount">
                                                            <input :value="sconto_totale" type="text" class="form-control sconto input-sm text_right" placeholder="" name="sconto_totale" @change="setTotalDiscount($event)">
                                                            <span class="input-group-addon" id="basic-addon1">%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="text-left">imponibile:</th>
                                                    <td class="text-right">€ {{ totalNetCart }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="text-left">iva:</th>
                                                    <td class="text-right">€ {{ totalVatCart }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="text-left">totale:</th>
                                                    <td class="text-right">€ {{ totalCart }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="text-left">Totale scontato:</th>
                                                    <td class="text-right">
                                                        <div class="input-group total_price">
                                                            <input :value="prezzo_totale" type="text" class="form-control sconto input-sm text_right" placeholder="" name="prezzo_totale" @change="setTotalPrice($event)">
                                                            <span class="input-group-addon" id="basic-addon1">€</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="cart_table" v-if="errorOnSave">
                                    <div class="alert alert-danger alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true" @click="resetError">×</button>
                                        <h4><i class="icon fa fa-ban"></i>Errore durante il salvataggio dell'ordine</h4>
                                        <h5>{{ error_text }}</h5>
                                    </div>
                                </div>

                            </div>
                            <div class="col-xs-6">
                                <div class="btn_container">
                                    <button class="btn btn-danger" @click="emptyCart">Svuota carrello</button>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="btn_container">
                                    <button class="btn btn-success" id="saveOrder" @click="saveOrder">Salva ordine</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.box-body -->
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>

<!-- Vue Select -->
<script src="https://unpkg.com/vue-select@latest"></script>
<link rel="stylesheet" href="https://unpkg.com/vue-select@latest/dist/vue-select.css">


<script>
//setTimeout(function() { //Decommentare se lo si vuole far funzionare con chiamata ajax
var endpoint = base_url + '/rest/v1';
const tokenApi = 'Bearer 26d460b39301781b10d37af31e2e5a4a';

Vue.config.devtools = true;

Vue.component('v-select', VueSelect.VueSelect);

Vue.component("prodotto", {
    template: `
        <div class="product">
            <div class="prod_container">
            <a :href="base_url" class="js_open_modal">
                <div class="prod_image">
                    <img :src="printImage(product)" alt="Immagine prodotto" srcset="" class="img-responsive">
                </div>
                </a>
                <div class="prod_info text-center">
                    <h4 class="prod_name">{{ product.fw_products_name}}</h4>
                    <p class="prod_sku"><strong>{{ product.fw_products_sku }}</strong></p>
                    <p class="prod_price"><strong>€ {{ prezzoIvato(product) }} (IVA incl.)</strong></p>
                </div>
                <div class="prod_action">
                    <a class="btn btn-block btn-success btn-success" @click="addToCart"><i class="fas fa-cart-plus"></i>
                        Aggiungi
                    </a>
                </div>
            </div>
        </div>
        `,
    props: {
        product: Object
    },
    data() {
        return {
            modalOpen: false,
            url: base_url + '/uploads/',
            base_url: null
        }
    },
    methods: {
        /**
         * Calculate product VAT 
         */
        ivaProdotto(prodotto) {
            const prezzo = parseFloat(prodotto.fw_products_sell_price);
            const iva_valore = parseInt(prodotto.iva_valore)
            const iva = ((prezzo * iva_valore) / 100).toFixed(2);
            return iva;
        },
        /**
         * Calculate product total price (VAT included)
         */
        prezzoIvato(prodotto) {
            const prezzo = parseFloat(prodotto.fw_products_sell_price);
            const iva = parseInt(prodotto.iva_valore)
            const prezzoIvato = (prezzo + (prezzo * iva) / 100).toFixed(2);
            return prezzoIvato;
        },
        addToCart(event) {
            this.$emit('clicked', this.product)
        },
        /**
         *  Set product image as main image or first of the gallery
         */
        printImage(prodotto) {
            if (prodotto.fw_products_main_image) { //main image
                return this.url + prodotto.fw_products_main_image
            } else if (prodotto.fw_products_images) { //gallery image
                const gallery = JSON.parse(prodotto.fw_products_images);
                if (gallery) {
                    const secondary_image_path = gallery[0].path_local;
                    return this.url + secondary_image_path;
                }
            } else { //placeholder image
                return base_url + '/images/no_image.png';
            }
        },
    },

    mounted() {
        let prod_id = this.product.fw_products_id;
        this.base_url = '<?php echo base_url(); ?>get_ajax/modal_layout/dettagli_prodotto_modale/' + prod_id + '?_size=large';
    }
});



var app = new Vue({
    el: '#app',
    data: {
        url: base_url + '/uploads/',
        prodotti: [],
        magazzini: [],
        carrello: [],
        cart_total: 0,
        options: [],
        debounce: null,
        product_loading: false,
        cart_loading: false,
        //data for product selection
        prodotti_select: [],
        prodotti_option: [],
        //data for barcode selection
        barcode_select: '',
        barcode_option: [],
        //sconto in single product
        sconto: 0,
        applicaSconto: false,
        //data for customer selection
        clienti: [],
        customer_select: [],
        customer_option: [],
        //campo per il cambio di prezzo (da quello che mi arriva come rispsota a quello modificato dall'utente)
        prezzo_prodotto: '',
        errorOnSave: false, //flag per messaggio errore salvataggio ordine
        error_text: '',
        centri_costo: [],
        centro_costo_select: [],
        magazzino: {
            titolo: '',
            id: ''
        },
        magazzino_select: null,
        magazzini_options: [],
        //Sconto totale da applicare sui singoli prodotti
        sconto_totale: 0,
        //Prezzo totale manuale, calcolo inverso dello sconto da applicare sui singoli prodotti
        prezzo_totale: 0,
        //da usare per i controlli quando si inserisce un totale manuale
        carrello_totale: 0,
        //timeout per aggiunta multipla se trova un singolo prodotto
        timeout: null,
    },
    methods: {
        /*********** 
         * ! GESTIONE SCONTO TOTALE,
         * ! lo imposta come sconto per ogni prodotto ed in automatico ricalcola tutti i giusti valori (imponibile iva, ecc ecc)
         * **********/
        setTotalDiscount(event) {
            const sconto = parseFloat(event.target.value);

            if (sconto > 100 || sconto < 0 || Number.isNaN(sconto)) {
                this.sconto_totale = 0;
                return;
            }

            if (sconto >= 0 && this.carrello.length != 0) {
                this.sconto_totale = sconto;

                this.carrello.forEach(product => {
                    product.sconto = sconto;
                });

                this.prezzo_totale = this.totalCart;

                localStorage.setItem('cart', JSON.stringify(this.carrello));
            }
        },
        /***********
         * ! GESTIONE PREZZO TOTALE
         * ! calcola % rispetto al totale e lo imposta come sconto per ogni prodotto ed in automatico ricalcola tutti i giusti valori (imponibile iva, ecc ecc)
         * **********/
        setTotalPrice(event) {
            var prezzo = parseFloat(event.target.value);

            if (prezzo >= this.carrello_totale || prezzo <= 0 || Number.isNaN(prezzo)) {
                //prezzo = 0;
                this.carrello.forEach(product => {
                    product.sconto = 0;
                });
                this.prezzo_totale = this.carrello_totale;
                return;
            }

            if (prezzo > 0 && this.carrello.length != 0) {
                this.prezzo_totale = prezzo;
                //calcolo % a cui corrisponde rispetto al totale e la imposto al singolo prodotto
                var percentage = (100 - ((prezzo * 100) / this.carrello_totale)).toFixed(3);

                this.carrello.forEach(product => {
                    product.sconto = percentage;
                });
                localStorage.setItem('cart', JSON.stringify(this.carrello));
            }
        },

        /*********** GESTIONE CLIENTI  e RICERCA CLIENTI / PRODOTTI E BARCODE **********/
        /**
         * Get all customers (will be used in cart)
         */
        async getCustomers() {
            const response = await fetch(`${endpoint}/search/customers`, {
                headers: {
                    Authorization: `${tokenApi}`,
                },
            })
            const customers = await response.json()
            customers.data.forEach(customer => {
                this.clienti.push(customer)
            });
        },


        ricomputoProdotto(prodotto) {
            console.log('ricomputo prodotto: ', prodotto)
            /**
             * Se entro qua mi aspetto che sia cambiato uno qualsiasi di questi attributi:
             * 
             * prodotto.prezzo_ivato
             * prodotto.qty
             * prodotto.sconto
             * 
             * prodotto.iva_perc è statico, non può essere cambiato e non viene calcolato dinamicamente. Serve per lo scorporo iva.
             * 
             * Tutte le altre informazioni le derivo al volo, e le salvo sul prodotto
             * 
             * Per chiarezza, le chiavi/campi calcolati dinamicamente li ho cambiati tutti con l'underscore davanti così 
             * sappiamo che se da qualche parte troviamo prodotto._xxxxx = qualcosa, è un errore logico. Queste righe di codice devono stare solo in questa funzione
             * 
             */

            //calcolo automatico dello sconto quando cambio prezzo ivato
            //prodotto.sconto = parseFloat((((prodotto.prezzo_ivato - prodotto._prezzo_ivato) / prodotto.prezzo_ivato) * 100).toFixed(2));

            const sconto = (100 - prodotto.sconto);
            if (!prodotto._prezzo_ivato) {
                prodotto._prezzo_ivato = prodotto.prezzo_ivato;
            }

            if (prodotto._lockPrice) {
                prodotto._iva = parseFloat(prodotto._prezzo_ivato / (100 + prodotto.perc_iva) * prodotto.perc_iva);
                prodotto._base_imponibile = parseFloat(prodotto._prezzo_ivato - prodotto._iva);

                prodotto._totale_ivato = parseFloat((prodotto._prezzo_ivato * prodotto.quantita).toFixed(2));

                prodotto.sconto = parseFloat((((prodotto.prezzo_ivato - prodotto._prezzo_ivato) / prodotto.prezzo_ivato) * 100).toFixed(3));
                prodotto._totale_imponibile_scontato = parseFloat(prodotto._base_imponibile * prodotto.quantita);
                prodotto._totale_ivato_scontato = parseFloat((prodotto._totale_ivato).toFixed(2));
                prodotto._iva_totale_scontato = parseFloat(prodotto._iva * prodotto.quantita);
            } else {
                //Se non ho bloccato il prezzo (che viene salvato in _prezzo_ivato), rimetto _prezzo_ivato = al prezzo originale
                prodotto._prezzo_ivato = prodotto.prezzo_ivato;
                prodotto._iva = parseFloat(prodotto._prezzo_ivato / (100 + prodotto.perc_iva) * prodotto.perc_iva);
                prodotto._base_imponibile = parseFloat(prodotto._prezzo_ivato - prodotto._iva);

                prodotto._totale_ivato = parseFloat((prodotto._prezzo_ivato * prodotto.quantita).toFixed(2));

                prodotto._totale_imponibile_scontato = parseFloat(prodotto._base_imponibile * prodotto.quantita * sconto / 100);
                prodotto._totale_ivato_scontato = parseFloat((prodotto._totale_ivato * sconto / 100).toFixed(2));

                prodotto._iva_totale_scontato = parseFloat(prodotto._iva * prodotto.quantita * sconto / 100);
            }

            // const baseImponibile = parseFloat((nuovo_prezzo / (100 + prodotto.perc_iva)) * 100);
            // const nuovaIva = parseFloat(nuovo_prezzo - baseImponibile);
            // const nuovoTotale = nuovo_prezzo;

            // prodotto.iva_totale = parseFloat(nuovaIva.toFixed(2));
            // prodotto.totale_prodotto = parseFloat(nuovoTotale.toFixed(2));
            // prodotto.base_imponibile = parseFloat(baseImponibile.toFixed(2));
            return prodotto;
        },



        /**
         * Remote search in customers select. Print customer name & last_name or customers_company
         */
        fetchCustomerOptions: function(search, loading) {
            var el = this;

            if (search.length >= 3) {
                const formData = new FormData();
                formData.append("where[]", "customers_last_name LIKE '%" + search + "%'")
                fetch(`${endpoint}/search/customers`, {
                        method: 'POST',
                        headers: {
                            Authorization: `${tokenApi}`,
                        },
                        body: formData
                    })
                    .then((response) => response.json())
                    .then(function(response) {
                        console.log(response);
                        // Update options
                        el.customer_option = response.data;
                    })
            } else {
                el.customer_option = [] //svuoto option se sono sotto i caratteri minimi da cercare
            }
        },
        /* Add selected customer to cart storage */
        selectCustomer(customer) {
            if (customer) {
                localStorage.setItem('customer', customer.customers_id);
                if (customer.customers_company) {
                    localStorage.setItem('customerData', customer.customers_company);
                } else {
                    localStorage.setItem('customerData', customer.customers_name + ' ' + customer.customers_last_name);
                }

            }
        },


        /**
         * Filter product by magazzino (magazzino from session)
         */
        async filterProductByMagazzino(magazzino) {
            //console.log(magazzino);
            const formData = new FormData();
            formData.append("where[]", "fw_products_show_in_counter = 1");
            //formData.append("where[]", "fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento AND movimenti_magazzino = '" + magazzino + "') GROUP BY movimenti_articoli_prodotto_id HAVING SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) > 0)")

            const response = await fetch(`${endpoint}/search/fw_products`, {
                method: 'POST',
                headers: {
                    Authorization: `${tokenApi}`,
                },
                body: formData
            })

            const res_prodotti = await response.json();
            res_prodotti.data.forEach(centro => {
                this.prodotti.push(centro)
            });
        },



        /**
         * Remote search in product select. Print product image, name and price
         */
        fetchOptions: function(search, loading) {
            var el = this;
            if (search.length >= 3) {
                console.log('inside tiemout');
                const formData = new FormData();
                magazzino = localStorage.getItem('magazzino');
                formData.append("where[]", "fw_products_name LIKE '%" + search + "%'");
                formData.append("limit", "100");
                //formData.append("where[]", "fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento AND movimenti_magazzino = '" + magazzino + "') GROUP BY movimenti_articoli_prodotto_id HAVING SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) > 0)");

                fetch(`${endpoint}/search/fw_products`, {
                        method: 'POST',
                        headers: {
                            Authorization: `${tokenApi}`,
                        },
                        body: formData
                    })
                    .then((response) => response.json())
                    .then(function(response) {
                        // Update options
                        el.prodotti_option = response.data;
                    })
            } else {
                el.prodotti_option = [];
            }
        },
        selectedOption: function(prodotto) {
            if (prodotto) {
                addToCart(prodotto);
            }
        },

        /**
         * Remote search in barcode select. Print product image, barcode/sku and price
         * Auto add element to cart if seaerch returns only oone product
         */
        fetchBarcodeOptions: function(search, loading) {
            var el = this;
            clearTimeout(el.timeout);
            if (search.length > 2) {
                el.timeout = setTimeout(function() {
                    const formData = new FormData();
                    //magazzino = localStorage.getItem('magazzino');
                    formData.append("where[]", "(fw_products_barcode LIKE '%" + search + "%' OR fw_products_sku LIKE '%" + search + "%')");
                    formData.append("limit", "100");
                    //Spostare in controller custom o intercettare un filtro custom con un pp pre search?
                    //formData.append("where[]", "fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento AND movimenti_magazzino = '" + magazzino + "') GROUP BY movimenti_articoli_prodotto_id HAVING SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) > 0)")
                    fetch(`${endpoint}/search/fw_products`, {
                            method: 'POST',
                            headers: {
                                Authorization: `${tokenApi}`,
                            },
                            body: formData
                        })
                        .then((response) => response.json())
                        .then(function(json) {
                            //console.log(json.data);
                            const res = json.data;
                            if (res.length != 0) {
                                res.forEach(product => {
                                    const barcode = product.fw_products_barcode;
                                    //If multiple, extract only first barcode
                                    if (Array.isArray(JSON.parse(barcode))) {
                                        product.fw_products_barcode = JSON.parse(barcode)[0];
                                    }
                                });
                                //If there are only one product, add to cart automatically and close select
                                if (res.length == 1) {
                                    el.addToCart(res[0]);
                                    el.$refs.mybarcode.$refs.search.blur();
                                    el.$refs.mybarcode.$refs.search.focus();
                                    el.barcode_option = [];
                                }
                            }
                            // Update options
                            el.barcode_option = res;
                        })
                }, 500);
            } else {
                el.barcode_option = [];
                clearTimeout(el.timeout);
            }
        },



        /*********** GESTIONE PRODOTTI  **********/
        /**
         * Get all product (will be used for most sell and favourite products)
         */
        async getProducts() {
            this.product_loading = true;
            this.prodotti = [];
            const formData = new FormData();
            formData.append("where[]", "fw_products_show_in_counter = 1");
            formData.append("limit", "100");
            //formData.append("where[]", "fw_products_quantity > 0");               
            //formData.append("where[]", "fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento AND movimenti_magazzino = '" + magazzino + "') GROUP BY movimenti_articoli_prodotto_id HAVING SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) > 0)")

            const response = await fetch(`${endpoint}/search/fw_products`, {
                method: "post",
                headers: {
                    Authorization: `${tokenApi}`,
                },
                body: formData
            })
            const products = await response.json()
            products.data.forEach(product => {
                this.prodotti.push(product)
            });
            this.product_loading = false;
        },


        /**
         *  Set product image as main image or first of the gallery
         */
        printImage(prodotto) {
            if (prodotto.fw_products_main_image) { //main image
                return this.url + prodotto.fw_products_main_image
            } else if (prodotto.fw_products_images) { //gallery image
                const gallery = JSON.parse(prodotto.fw_products_images);
                if (gallery) {
                    const secondary_image_path = gallery[0].path_local;
                    return this.url + secondary_image_path;
                }
            } else { //placeholder image
                return base_url + '/images/no_image.png';
            }
        },

        favouriteProducts() {
            this.getProducts();
        },


        /*********** GESTIONE CARRELLO  **********/
        /**
         * Add one product to cart from each product visualization
         */
        addProductToCart(product) {
            this.addToCart(product)
        },

        addToCart(prodotto) {
            if (prodotto) {
                this.addProduct(prodotto);
                //Autofocus sul barcode e tolto al product, svuoto barcode_select e le option della select
                this.prodotti_select = null;
                this.prodotti_option = [];

                this.barcode_select = null;
                this.barcode_option = [];

                /*                 console.log(this.$refs.myproduct)
                                this.$refs.myproduct.$refs.search.blur(); */

                //console.log(this.$refs.mybarcode.$refs)
                this.$refs.mybarcode.$refs.search.blur();
                this.$refs.mybarcode.$refs.search.focus();
            }
            // if (prodotto) {
            //     //this.carrello.push(prodotto);

            //     //Calculate price with VAT
            //     const prezzo = parseFloat(prodotto.fw_products_sell_price);
            //     const iva = parseInt(prodotto.iva_valore)
            //     const valore_iva = ((prezzo * iva) / 100);
            //     const prezzoIvato = (prezzo + (prezzo * iva) / 100);

            //     const totale_prodotto = parseFloat(prezzoIvato);
            //     const quantita_disponibile = prodotto.fw_products_quantity;
            //     //Push object into cart array
            //     this.carrello.push({
            //         prezzo_ivato: parseFloat(prezzoIvato),
            //         prezzo: parseFloat(prezzo),
            //         iva: parseFloat(valore_iva),
            //         perc_iva: parseFloat(iva),
            //         iva_totale: parseFloat(valore_iva),
            //         prezzo_scontato: parseFloat(prezzoIvato),
            //         base_imponibile: parseFloat(prezzo),
            //         quantita: 1,
            //         quantita_disponibile: parseInt(quantita_disponibile),
            //         prodotto: prodotto,
            //         totale_prodotto: totale_prodotto,
            //         sconto: 0 //lo aggiorno con la percentuale che viene inserita e lo uso per mostrare test sotto tot. prodotto
            //     });
            //     //Save cart in localstorage, it will be used to check if cart is empty on page load
            //     localStorage.setItem('cart', JSON.stringify(this.carrello));
            //     //console.log(this.carrello)
            // } else {
            //     //error message...
            // }
        },

        /*
         * Add one product to the cart item
         */
        addProduct(prodotto) {
            //console.log(prodotto);
            //Calculate price with VAT
            const prezzo = parseFloat(prodotto.fw_products_sell_price);
            const iva = parseInt(prodotto.iva_valore)
            const valore_iva = ((prezzo * iva) / 100);
            const prezzoIvato = (prezzo + (prezzo * iva) / 100);
            const totale_prodotto = parseFloat(prezzoIvato);
            const quantita_disponibile = prodotto.fw_products_quantity;

            var cart_prodotto = {
                prezzo_ivato: parseFloat(prezzoIvato.toFixed(2)),
                //prezzo: parseFloat(prezzo),
                iva: parseFloat(valore_iva),
                perc_iva: parseFloat(iva),
                //iva_totale: parseFloat(valore_iva),
                //prezzo_scontato: parseFloat(prezzoIvato),
                //base_imponibile: parseFloat(prezzo),
                quantita: 1,
                quantita_disponibile: parseInt(quantita_disponibile),
                prodotto: prodotto,
                //totale_prodotto: totale_prodotto,
                sconto: 0,
                //NUOVI CAMPI DA PASSARE PER IL SALVATAGGIO
                iva_id: prodotto.fw_products_tax,
                prezzo_no_iva: prezzo
            };
            cart_prodotto = this.ricomputoProdotto(cart_prodotto);
            this.carrello.unshift(cart_prodotto);
            //this.carrello.push(cart_prodotto);
            localStorage.setItem('cart', JSON.stringify(this.carrello));
        },

        /**
         * Removes one product from cart
         */
        removeItem(prodotto) {
            var prodIndex = this.carrello.indexOf(prodotto);
            this.carrello.splice(prodIndex, 1);
            localStorage.setItem('cart', JSON.stringify(this.carrello));
        },
        /**
         * Removes all products from cart and customer data
         */
        emptyCart() {
            this.errorOnSave = false;
            //localStorage.clear();
            localStorage.removeItem('cart');
            localStorage.removeItem('customer');
            localStorage.removeItem('customerData');
            this.carrello = [];
            this.prodotti_select = null;
            this.customer_select = null;
            this.prezzo_totale = 0;
        },


        /**
         * Calculate product VAT in cart
         */
        ivaProdotto(prodotto) {
            const prezzo = parseFloat(prodotto.fw_products_sell_price);
            const iva_valore = parseInt(prodotto.iva_valore)
            const prezzoIvato = (prezzo + (prezzo * iva_valore) / 100);

            return prezzoIvato.toFixed(2);
        },
        /**
         * Calculate product total price (VAT included)
         */
        prezzoIvato(prodotto) {
            const prezzo = parseFloat(prodotto.fw_products_sell_price);
            const iva = parseInt(prodotto.iva_valore)
            const prezzoIvato = (prezzo + (prezzo * iva) / 100);

            return prezzoIvato.toFixed(2);
        },


        /**
         * Set product quantity
         */
        setProductQuantity(prodotto, index, event) {
            const nuova_quantita = parseInt(event.target.value);
            if (nuova_quantita > 0) {
                prodotto.quantita = nuova_quantita;
                prodotto = this.ricomputoProdotto(prodotto);

                localStorage.setItem('cart', JSON.stringify(this.carrello));
            } else {
                prodotto.quantita = 0;
                //console.error('La quantità deve essere maggiore di 0');
            }
        },

        /**
         * Descrease product quantity
         */
        decreaseProductQuantity(prodotto, index) {
            const quantity = prodotto.quantita;
            if (quantity > 1) {
                prodotto.quantita--;
                prodotto = this.ricomputoProdotto(prodotto);

                localStorage.setItem('cart', JSON.stringify(this.carrello));
            }
        },

        /**
         * Increase product quantity
         */
        increaseProductQuantity(prodotto, index) {
            prodotto.quantita++;
            prodotto = this.ricomputoProdotto(prodotto);

            localStorage.setItem('cart', JSON.stringify(this.carrello));
        },



        /**
         * Set product discount percentage
         * 
         * 16/02/2022
         * Se modifico sconto devo vedere l'effetto dello sconto anche sul totale del singolo prodotto
         */
        setProductDiscount(prodotto, index, event) {
            const perc_sconto = parseFloat(event.target.value);
            prod = this.carrello[index];
            if (perc_sconto > 0 && perc_sconto <= 100 && perc_sconto.length != 0) {
                prod.sconto = parseFloat(perc_sconto);
            } else {
                prod.sconto = 0;

            }
            //debugger
            prod._lockPrice = false;
            prod = this.ricomputoProdotto(prod);

            localStorage.setItem('cart', JSON.stringify(this.carrello));
        },



        /**
         * Set new product price, update iva and imponibile with new value (prezzo già ivato)
         * 
         * 16/02/2022
         * Se modifico totale devo vedere calcolato lo sconto in automatico
         */
        setProductPrice(prodotto, index, event) {
            const nuovo_prezzo = parseFloat(event.target.value);

            if (nuovo_prezzo > 0) {
                prodotto._prezzo_ivato = nuovo_prezzo / prodotto.quantita;
                //prodotto.sconto = parseFloat((((prodotto.prezzo_ivato - nuovo_prezzo) / prodotto.prezzo_ivato) * 100).toFixed(2));

                prodotto._lockPrice = true;
                prodotto = this.ricomputoProdotto(prodotto);
                localStorage.setItem('cart', JSON.stringify(this.carrello));
            } else {
                console.error('Il prezzo deve essere maggiore di 0')
            }
        },


        /**
         * Use cart in localstorage
         */
        checkCart() {
            const localCart = JSON.parse(localStorage.getItem('cart'));
            const customer = JSON.parse(localStorage.getItem('customer'));
            const customerData = localStorage.getItem('customerData'); //populate customer select
            const nome_centro_costo = localStorage.getItem('nome_centro_costo'); //populate centro di costo select
            const nome_magazzino = localStorage.getItem('nome_magazzino');
            const magazzino = JSON.parse(localStorage.getItem('magazzino_object'));
            if (localCart) {
                this.carrello = localCart;
            }
            if (customerData) {
                this.customer_select = customerData;
            }
            if (nome_centro_costo) {
                this.centro_costo_select = nome_centro_costo;
                //devo settarlo al valore "corrispondente" al magazzino in sessione se c'è, sia localstorage che nella select
                var centro_da_selezionare = this.centri_costo.filter((centro) => centro.centri_di_costo_ricavo_nome === nome_centro_costo);
                if (centro_da_selezionare.length != 0) {
                    this.centro_costo_select = centro_da_selezionare[0];
                    localStorage.setItem('centro_costo', this.centro_costo_select.centri_di_costo_ricavo_id);
                    localStorage.setItem('nome_centro_costo', this.centro_costo_select.centri_di_costo_ricavo_nome);
                }
            }
            if (magazzino) {
                this.magazzino_select = magazzino;
            }
        },

        resetError() {
            this.errorOnSave = !this.errorOnSave;
        },

        saveOrder() {
            var cartContent = {
                cart: JSON.parse(localStorage.getItem('cart')),
                user: localStorage.getItem('customer'),
                centro_di_costo: localStorage.getItem('centro_costo'),
                magazzino: localStorage.getItem('magazzino')
            }

            if (!cartContent.magazzino) {
                this.error_text = 'Non puoi salvare un\'ordine senza selezionare il magazzino';
                this.errorOnSave = true;
                return;
            } else if (!cartContent.cart || cartContent.cart.length === 0) {
                this.error_text = 'Non puoi salvare un\'ordine senza aggiungere prodotti';
                this.errorOnSave = true;
                return;
            } else if (!cartContent.centro_di_costo) {
                this.error_text = 'Non puoi salvare un\'ordine senza indicare il centro di costo';
                this.errorOnSave = true;
                return;
            } else if (cartContent.cart && cartContent.centro_di_costo && cartContent.magazzino) {
                console.log(cartContent);
                this.error_text = '';
                this.errorOnSave = false;

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append('cartContent', JSON.stringify(cartContent));
                params.append('totale_scontato', this.prezzo_totale);

                axios.post(base_url + 'contabilita/puntocassa/save', params)
                    .then(function(response) {
                        if (response.data.status == 1) { //Redirect to punto cassa
                            this.carrello = [];
                            //localStorage.clear();
                            localStorage.removeItem('cart');
                            localStorage.removeItem('customer');
                            localStorage.removeItem('customerData');
                            window.location.href = response.data.txt;
                        } else { //errore, mostro toast con messaggio
                            this.error_text = response.data.txt;
                            this.errorOnSave = true;
                            return false;
                        }
                    });
            } else {
                //console.error('carrello vuoto')
                this.error_text = 'Non puoi salvare un\'ordine senza prodotti, centro di costo e magazzino';
                this.errorOnSave = true;
            }
        },


        /**
         * ! Popola select centri di costo e se c'è un magazzino nella sessione dell'utente loggato
         * ! imposta il centro di costo a quello che corrisponde con la label del magazzino
         */
        async getCentriCosto() {
            const response = await fetch(`${endpoint}/search/centri_di_costo_ricavo`, {
                method: 'POST',
                headers: {
                    Authorization: `${tokenApi}`,
                },
            })
            const centri_costo = await response.json();

            centri_costo.data.forEach(centro => {
                this.centri_costo.push(centro)
            });
            //Se utente è legato ad un magazzino allora il default centro di costo default con quello del magazzino
            if (this.magazzino) {
                var centro_da_selezionare = this.centri_costo.filter((centro) => centro.centri_di_costo_ricavo_nome === this.magazzino.titolo);
                if (centro_da_selezionare.length != 0) {
                    this.centro_costo_select = centro_da_selezionare[0];
                    localStorage.setItem('centro_costo', this.centro_costo_select.centri_di_costo_ricavo_id);
                    localStorage.setItem('nome_centro_costo', this.centro_costo_select.centri_di_costo_ricavo_nome);
                }
            }
        },
        /**
         * ! Aggiunge il centro di costo selezionato al localstorage, usato nel salvataggio ordine
         * ! Ogni volta che ne viene selezionato uno nella select
         */
        selectCentroCosto(centro) {
            if (centro) {
                console.log(centro);
                this.centro_costo_select = centro;
                localStorage.setItem('centro_costo', centro.centri_di_costo_ricavo_id);
                localStorage.setItem('nome_centro_costo', centro.centri_di_costo_ricavo_nome)
            }
        },

        /**
         * POPOLA SELECT MAGAZZINI E SETTA I DATI DEL MAGAZZINO CHE HA L'UTENTE IN SESSIONE
         */
        async getMagazzini() {
            this.magazzino_id = '<?php echo $magazzino ?>';
            const response = await fetch(`${endpoint}/search/magazzini`, {
                method: 'POST',
                headers: {
                    Authorization: `${tokenApi}`,
                },
            })
            const res_magazzini = await response.json();
            //Popola select magazzino
            res_magazzini.data.forEach(magazzino => {
                this.magazzini_options.push(magazzino);
            });
            this.magazzino_selected = JSON.parse(localStorage.getItem('magazzino_object'));
            //Imposto il magazzino selezionato a quello dell'utente in sessione, se presente
            var magazzino_utente = this.magazzini_options.filter(magazzino => magazzino.magazzini_id == this.magazzino_id);
            //console.log('magazzino sessione utente: ', magazzino_utente[0]);
            if (magazzino_utente.length != 0) {
                //imposto dati magazzino e salvo in localstorage
                this.magazzino.titolo = magazzino_utente[0].magazzini_titolo;
                this.magazzino.id = magazzino_utente[0].magazzini_id;
                localStorage.setItem('magazzino', this.magazzino.id);
                localStorage.setItem('nome_magazzino', this.magazzino.titolo);
                localStorage.setItem('magazzino_object', JSON.stringify(magazzino_utente[0]));
            }
            //Popola select centri di costo, preselezionando con quello che corrisponde con il magazzino in sessione (se presente)
            this.getCentriCosto();
        },
        /**
         *  Add selected magazzino to cart storage and filter product by magazzino
         */
        selectMagazzino(magazzino) {
            if (magazzino) {
                this.magazzino_select = magazzino;
                localStorage.setItem('magazzino', magazzino.magazzini_id);
                localStorage.setItem('nome_magazzino', magazzino.magazzini_titolo);
                localStorage.setItem('magazzino_object', JSON.stringify(magazzino))
                //this.filterProductByMagazzino(id);
            }
        },

        /**
         * Prende info del magazzino legato all'utente
         */
        /*         async getMagazzino() {
                    this.magazzino_id = '<?php echo $magazzino ?>';

                    const formData = new FormData();
                    formData.append("where[]", `magazzini_id=${this.magazzino_id}`);

                    const response = await fetch(`${endpoint}/search/magazzini`, {
                        method: 'POST',
                        headers: {
                            Authorization: `${tokenApi}`,
                        },
                        body: formData
                    })
                    const res_magazzini = await response.json();
                    //console.log(res_magazzini)

                    if (res_magazzini.data.length != 0) {
                        const magazzino = res_magazzini.data;
                        magazzino.forEach(magazzino => {
                            const id = magazzino.magazzini_id;
                            const titolo = magazzino.magazzini_titolo;
                            //imposto dati magazzino e salvo in localstorage
                            this.magazzino.titolo = titolo;
                            this.magazzino.id = id;
                            localStorage.setItem('magazzino', id);
                            localStorage.setItem('nome_magazzino', titolo);
                            //this.filterProductByMagazzino(id);
                        });
                    }
                }, */

        /**
         * ! Get magazzino info for the logged user. Used for product filter
         */
        setMagazzino() {
            //Info del solo magazzino utente loggato
            //this.getMagazzino();
            //Popola select magazzino da usare se utente non ha magazzino collegato
            this.getMagazzini();
        },

    },

    computed: {
        /**
         * C! alculate net total cart based on product in cart
         */
        totalNetCart() {
            var mythis = this;
            const totalNet = this.carrello.reduce(function(acc, current) {
                    current = mythis.ricomputoProdotto(current);
                    return acc + current._totale_imponibile_scontato;
                },
                0);
            return totalNet.toFixed(2)
        },
        /**
         * ! Calculate VAT cart total based on product in cart
         * 
         */
        totalVatCart() {
            var mythis = this;
            const totalVat = this.carrello.reduce(function(acc, current) {
                current = mythis.ricomputoProdotto(current);

                return acc + parseFloat(current._iva_totale_scontato);
            }, 0);
            return totalVat.toFixed(2)
        },
        /**
         * ! Calculate cart total based on product in cart
         */
        totalCart() {
            var mythis = this;
            const totalNet = this.carrello.reduce(function(acc, current) {
                    current = mythis.ricomputoProdotto(current);
                    return acc + current._totale_imponibile_scontato;
                },
                0);
            const totalVat = this.carrello.reduce(function(acc, current) {
                current = mythis.ricomputoProdotto(current);

                return acc + parseFloat(current._iva_totale_scontato);
            }, 0);

            mythis.carello_totale = (totalNet + totalVat).toFixed(2);
            //azzero il totale per i controlli e lo ricalcolo ogni volta che cambia il totale del carrello
            this.carrello_totale = 0;
            this.carrello.forEach(prodotto => {
                this.carrello_totale += prodotto.prezzo_ivato * prodotto.quantita;
                //this.carrello_totale += prodotto.prezzo_ivato;
            });
            //console.log(this.carrello_totale)

            return (totalNet + totalVat).toFixed(2);
        },
    },

    beforeMount() {
        //this.getCustomers();
        this.checkCart();
    },

    mounted() {
        //Set focus on barcode
        this.$refs.mybarcode.$refs.search.focus();
        this.$refs.myproduct.$refs.search.blur();
        this.setMagazzino();
        this.favouriteProducts();
    }
});
//}, 2000);
</script>