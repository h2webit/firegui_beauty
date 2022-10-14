<?php
if(!empty($prodotti)):
?>
    <div class="row">
        <?php foreach ($prodotti as $prodotto){
            //debug($prodotto);
            ?>
            <div class="col-sm-3" style="margin-top: 5px; margin-bottom: 5px; margin-left: -5px; margin-right: -5px;">
                <div class="list-group-item clearfix">
                    <h4 class="list-group-item-heading text-center"><?php echo $prodotto['fw_products_name']; ?></h4>
                    <p class="list-group-item-text">
                        <strong>Taglia:</strong> <?php echo json_decode($prodotto['prodotti_configurati_json_attributi'])[0]; ?><br>
                        <strong>Colore:</strong> <?php echo json_decode($prodotto['prodotti_configurati_json_attributi'])[1]; ?><br>
                        <strong>Prezzo:</strong> <?php echo $prodotto['prodotti_configurati_prezzo_vendita']; ?>€<br>
                    </p>
                    <br>
                    <button style="display: block; margin-left: auto; margin-right: auto;" class="btn btn-sm btn-success js_add_to_cart" data-productid="<?php echo $prodotto['prodotti_configurati_id']; ?>" data-productname="<?php echo $prodotto['fw_products_name']; ?>" data-productprice="<?php echo $prodotto['prodotti_configurati_prezzo_vendita']; ?>" data-productcolor="<?php echo json_decode($prodotto['prodotti_configurati_json_attributi'])[1]; ?>" data-productsize="<?php echo json_decode($prodotto['prodotti_configurati_json_attributi'])[0]; ?>"><i class="fa fa-cart-plus" aria-hidden="true"></i> Aggiungi al carrello</button>
                </div>
            </div>
        <?php } ?>
    </div>
<?php
else:
    echo '<div class="alert alert-success"><h3>Non ci sono prodotti configurati disponibili al momento.</h3></div>';
endif;
?>