<?php
$prodotto = $this->apilib->getById('fw_products', $value_id);
$medias = $this->db
                ->order_by('prodotti_immagini_ordine')
                ->get_where('prodotti_immagini', ['prodotti_immagini_prodotto' => $value_id])->result_array();
?>

<style>
    .dz-message {
        text-align: center;
        width:100%;
        padding-top:10px;
        font-size: 3em;
        font-style: italic;
    }

</style>

<div id="custom-view-wrapper" data-prodotto="<?php echo $value_id; ?>">
    <!--<link href="<?php echo base_url_admin('template/crm-v2/assets/global/plugins/dropzone/css/dropzone.css'); ?>" rel="stylesheet"/>
    <link href="<?php echo base_url_admin('template/crm-v2/assets/global/plugins/jcrop/css/jquery.Jcrop.min.css'); ?>" rel="stylesheet"/>
    <link href="<?php echo base_url_admin("script/immobili/gestione-foto/style.css?v={$this->config->item('version')}"); ?>" rel="stylesheet"/>-->

    <form action="<?php echo base_url("custom/qdb/uploadImage/{$value_id}"); ?>" id="doc-dropper" class="dropzone callout callout-info" style="min-height: 110px; "></form>

    <div class='row prodotti-media-container' >

            <?php $time = time(); ?>
            <?php foreach ($medias as $media): ?>

                <div class="col-lg-2 js-media-<?php echo $media['prodotti_immagini_id']; ?> media" data-media="<?php echo $media['prodotti_immagini_id']; ?>">
                    <div class="media-left text-center">
                    <?php $isDefault = ($prodotto['fw_products_immagine'] == $media['prodotti_immagini_id']); ?>
                    <?php if ($isDefault) : ?>
                        <!--<span class="label label-success label-sm default-badge"><?php e('Principale'); ?></span>-->
                    <?php endif; ?>

                    <img  class="media-object img-responsive"
                         data-src="<?php echo base_url_uploads("uploads/{$media['prodotti_immagini_immagine']}"); ?>"
                         src="<?php echo base_url_uploads("uploads/{$media['prodotti_immagini_immagine']}"); ?>"
                         alt="<?php echo $media['prodotti_immagini_immagine']; ?>"
                         data-toggle='tooltip'
                         title="<?php e("Riordina immagini"); ?>"
                         data-placement='right'/>

                    <a href="<?php echo base_url("api/delete/media_immobili/{$media['prodotti_immagini_id']}"); ?>" class="btn btn-xs bg-red-thunderbird btn-block js-delete-image" title="<?php e("Elimina"); ?>">
                        <i class="fa fa-remove"></i>
                        <?php e("Elimina"); ?>
                    </a>
                    </div>

                </div>


            <?php endforeach; ?>


    </div>

    <script src="<?php echo base_url_admin('template/crm-v2/assets/global/plugins/dropzone/dropzone.js'); ?>"></script>
    <script src="<?php echo base_url_admin('template/crm-v2/assets/global/plugins/jcrop/js/jquery.Jcrop.min.js'); ?>"></script>
    <script src="<?php echo base_url_admin('script/js/immagini_prodotto.js'); ?>"></script>
</div>
