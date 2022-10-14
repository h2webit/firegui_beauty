<style>
    .iva_codice {
        width: 60px;
        float: left;
    }

    .iva_desc {
        width: 170px;
        float: left;
    }

    .iva_conto {
        width: 50px;
        float: left;
    }

    .inputfloatleft {
        width: 160px;
        float: left;
    }

    .iva_container {
        margin-top: 30px;
        border: 1px solid #cecece;
        padding: 5px 10px;
    }

    .check_container {
        width: 100%;
        display: flex;
        justify-content: space-around;
    }

    .check_container div {
        width: 40%;
    }
</style>

<div class="iva_container js_iva_dependent bg-warning">

    REGISTRO IVA

    <div class="row js_riga_dett_iva hidden">
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">Riga</label>
                <input type="text" data-name="prime_note_righe_iva_riga" id="" class="form-control js_iva_riga js_numero_riga_iva" readonly />
                <input type="hidden" data-name="prime_note_righe_iva_iva_valore" id="" class="form-control js_iva_valore" readonly />
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="control-label">Codice e desc. Aliq./Esenz.</label>

                <select data-name="prime_note_righe_iva_iva" class="form-control js_iva_codice _select2_standard">
                    <?php foreach ($elenco_iva as $iva) : ?>
                        <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo $iva['iva_valore']; ?>" data-natura="<?php echo $iva['iva_codice']; ?>"><?php echo $iva['iva_label']; ?><?php if ($iva['iva_codice']) : ?> (<?php echo $iva['iva_codice']; ?>)<?php endif; ?></option>
                    <?php endforeach; ?>

                </select>
                <!-- <div>
                    <input type="text" name="dettaglio_iva[prime_note_righe_iva_iva][]" size="2" class="form-control iva_codice js_iva_codice" />
                    <input type="text" name="dettaglio_iva[iva_desc][]" class="form-control iva_desc inputfloatleft" readonly />
                </div> -->
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">Rif. mese</label>
                <input type="text" data-name="prime_note_righe_iva_ml" id="" class="form-control js_iva_ml" value="<?php echo date('m'); ?>" />
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">Tipo es.</label>
                <input type="text" data-name="prime_note_righe_iva_tipo_esenzione" id="" class="form-control js_iva_tipo_es" readonly />
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">% Indetraib.</label>
                <input type="text" data-name="prime_note_righe_iva_indetraibilie_perc" id="" class="form-control js_iva_perc_indet" readonly />


            </div>
        </div>


        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">Imponibile totale</label><br />
                <div class="input_container">
                    <div class="eur_sign">€</div>
                    <input type="text" data-name="prime_note_righe_iva_imponibile" id="" class="js_iva_imponibile js_decimal form-control">
                    
                </div>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">Imponibile indet.</label><br />
                <div class="input_container">
                    
                    <input type="text" data-name="prime_note_righe_iva_imponibile_indet" id="" data-toggle="tooltip" data-original-title="Imponibile non detraibile" class="form-control js_iva_imponibile_indetraibile" readonly />
                </div>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">IVA totale</label><br />
                <div class="input_container">
                    <div class="eur_sign">€</div>
                    <input type="text" data-name="prime_note_righe_iva_importo_iva" id="" class="js_iva_importo js_decimal form-control">

                </div>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label">IVA indet.</label><br />
                <div class="input_container">
                    
                    <input type="text" data-name="prime_note_righe_iva_iva_valore_indet" id="" data-toggle="tooltip" data-original-title="Iva non detraibile" class="form-control js_iva_importo_indetraibile" readonly />
                </div>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <label class="control-label" style="display:block">Elimina</label>

                <a href="javascript:void(0);" class="btn btn-danger js_delete_dettaglio_iva">
                    <span class="fas fa-trash"></span>
                </a>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-xs-10">
            <button id="js_add_riga_iva" type="button" class="btn btn-primary btn-sm">
                + Nuova riga
            </button>
        </div>
        <div class="col-xs-2">
            <button id="js_save_iva" type="button" class="btn btn-primary btn-sm" style="display:none;">
                Ricalcola
            </button>
        </div>
    </div>
</div>