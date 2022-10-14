<?php

$this->db->query("
    UPDATE documenti_contabilita_scadenze 
    SET documenti_contabilita_scadenze_saldata = '" . DB_BOOL_TRUE . "' 
    WHERE 
        documenti_contabilita_scadenze_data_saldo IS NOT NULL 
        AND documenti_contabilita_scadenze_data_saldo <> ''");

$this->db->query("
    UPDATE spese_scadenze 
    SET spese_scadenze_saldata = '" . DB_BOOL_TRUE . "' 
    WHERE 
        spese_scadenze_data_saldo IS NOT NULL 
        AND spese_scadenze_data_saldo <> ''");
