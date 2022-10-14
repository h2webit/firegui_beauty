+
+ Query per fare pulizia e migrazione del modulo contabilita ad una nuova versione
+
+
Per aggiornare il modulo ed avere uno storico di spese funzionante si dovrebbe eseguire questa query:

1. UPDATE spese SET spese_importata_da_xml = 1 WHERE spese_id IN (SELECT documenti_contabilita_ricezione_sdi_rif_spesa FROM documenti_contabilita_ricezione_sdi)
1.1 DELETE FROM spese_scadenze WHERE spese_scadenze_spesa IS NULL OR spese_scadenze_spesa NOT IN (SELECT spese_id FROM spese)
1.2 DELETE FROM spese_articoli WHERE spese_articoli_spesa IS NULL OR spese_articoli_spesa NOT IN (SELECT spese_id FROM spese)

2. Impostare il delete cascade su spese articoli e scadenze


3. Poi cancellare tutte le spese filtrandole per "importate da xml SI"

4. Aggiornare modulo suppliers se è installato perche c'è un campo che permette di mappare la categoria sul fornitore e che imposta la categoria su tutte le spese importare automaticamente.

4. Poi andare sul menu File XML e mettere "Da elaborare" tutti i file.

5. Il cron dovrebbe processarli e ricreare le spese correttamente
