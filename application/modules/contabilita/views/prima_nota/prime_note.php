<table class="table js_prime_note">
    <thead>
        <tr>

            <th>Azienda</th>
            <th>Numero</th>
            <th>Data registrazione</th>
            <th>Protocollo</th>
            <th>Rif. Doc.</th>
            <th>Sezionale</th>
            <th>Scadenza</th>
            <th>Causale</th>
            <th><?php e('Actions'); ?></th>


        </tr>
    </thead>
    <tbody>
        <?php $i = 0;
        foreach ($prime_note as $prime_note_id => $prima_nota) : $i++; ?>
            <tr class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?>" data-id="<?php echo $prime_note_id; ?>">
                <td><?php echo $prima_nota['documenti_contabilita_settings_company_name']; ?></td>
                <td><?php echo $prima_nota['prime_note_progressivo_giornaliero']; ?></td>
                <td><?php echo dateFormat($prima_nota['prime_note_data_registrazione']); ?></td>
                <td><?php echo $prima_nota['prime_note_protocollo']; ?></td>
                <td><?php echo $prima_nota['documenti_contabilita_numero']; ?>/<?php echo $prima_nota['documenti_contabilita_serie']; ?></td>
                <td><?php echo $prima_nota['prime_note_sezionale']; ?></td>
                <td><?php echo $prima_nota['prime_note_scadenza']; ?></td>
                <td><?php echo $prima_nota['prime_note_causale_codice']; ?></td>
                <td>
                    <a href="<?php echo base_url("db_ajax/generic_delete/prime_note/$prime_note_id"); ?>" data-confirm-text="<?php e('Are you sure to delete this record?'); ?>" class="btn btn-danger js_confirm_button js_link_ajax " data-toggle="tooltip" title="" data-original-title="<?php e('Delete'); ?>">
                        <?php e('Delete'); ?>
                    </a>
                </td>
            </tr>
            <tr class="<?php echo (is_odd($i)) ? 'prima_nota_table_container_even' : 'prima_nota_table_container_odd'; ?>">
                <td colspan="9" class="pl-30">

                    <?php $this->load->view('contabilita/prima_nota/registrazioni_prima_nota', $prima_nota); ?>

                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>