<table class="table js_prime_note_registrazioni">
    <thead>
        <tr>

            <th>N° Riga</th>
            <th>Causale</th>
            <th>Conto Dare</th>
            <th>Importo Dare</th>
            <th>Conto Avere</th>
            <th>Importo Avere</th>


        </tr>
    </thead>
    <tbody>
        <?php foreach ($registrazioni as $registrazione) : ?>
            <tr class="js_tr_prima_nota_registrazione" data-id="<?php echo $registrazione['prime_note_registrazioni_id']; ?>">
                <td>
                    <?php echo ($registrazione['prime_note_registrazioni_numero_riga']); ?>
                </td>
                <td><?php echo ($registrazione['prime_note_registrazioni_causale']); ?></td>
                <td><?php echo ($registrazione['prime_note_registrazioni_conto_dare_codice']); ?></td>
                <td class="text-danger"><?php echo ($registrazione['prime_note_registrazioni_importo_dare'] > 0) ? '€ ' . number_format($registrazione['prime_note_registrazioni_importo_dare'], 2, ',', '.') : ''; ?></td>
                <td><?php echo ($registrazione['prime_note_registrazioni_conto_avere_codice']); ?></td>
                <td class="text-success"><?php echo ($registrazione['prime_note_registrazioni_importo_avere'] > 0) ? '€ ' . number_format($registrazione['prime_note_registrazioni_importo_avere'], 2, ',', '.') : ''; ?></td>
            </tr>

        <?php endforeach; ?>
    </tbody>
</table>