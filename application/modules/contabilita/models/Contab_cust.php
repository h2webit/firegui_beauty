<?php

class Contab_cust extends CI_Model
{


    public function generaSottoconto($customer)
    {
        if ($customer['customers_type'] == 1) { //Cliente
            $conto = $this->apilib->searchFirst('documenti_contabilita_conti', ['documenti_contabilita_conti_clienti' => 1]);
        } else if ($customer['customers_type'] == 2) { //Fornitore
            $conto = $this->apilib->searchFirst('documenti_contabilita_conti', ['documenti_contabilita_conti_fornitori' => 1]);
        } else { //Leads e cliente/customer non hanno sottoconto
            return;
        }
        //debug($conto);
        if (!$conto) {
            return;
        }
        //debug('test');

        //Se non è stato specificato un sottoconto o non è ancora stato creato
        if ($this->db->CACHE) {
            $this->db->CACHE->delete_all();
        }
        $last_sottoconto_codice = false;
        if ($customer['customers_codice_sottoconto'] && count(explode('.', $customer['customers_codice_sottoconto'])) == 3) {
            //debug($customer['customers_code'], true);
            $last_sottoconto_codice = explode('.', $customer['customers_codice_sottoconto'])[2];
        }
        if (!$last_sottoconto_codice) {
            $last_sottoconto_codice = $this->db->query("SELECT COALESCE(MAX(CAST(documenti_contabilita_sottoconti_codice AS INTEGER)),0) as m FROM documenti_contabilita_sottoconti WHERE documenti_contabilita_sottoconti_conto = {$conto['documenti_contabilita_conti_id']}")->row()->m;
            $last_sottoconto_codice++;
        }

        $sottoconto_codice_completo =
            $conto['documenti_contabilita_conti_codice_completo'] . '.' . $last_sottoconto_codice;
        //debug($sottoconto_codice_completo, true);
        if (!$sottoconto = $this->apilib->searchFirst('documenti_contabilita_sottoconti', [
            // 'documenti_contabilita_sottoconti_mastro' => $conto['documenti_contabilita_conti_mastro'],
            // 'documenti_contabilita_sottoconti_conto' => $conto['documenti_contabilita_conti_id'],
            // 'documenti_contabilita_sottoconti_codice' => $last_sottoconto_codice,
            // 'documenti_contabilita_sottoconti_descrizione' => $customer['customers_company'] ?: ($customer['customers_name'] . ' ' . $customer['customers_last_name']),
            'documenti_contabilita_sottoconti_codice_completo' => $sottoconto_codice_completo,
            'documenti_contabilita_sottoconti_blocco' => DB_BOOL_FALSE,
            // 'documenti_contabilita_sottoconti_partite_aperte' => DB_BOOL_FALSE,
        ])) {
            //Creo il sottoconto
            $sottoconto = $this->apilib->create('documenti_contabilita_sottoconti', [
                'documenti_contabilita_sottoconti_mastro' => $conto['documenti_contabilita_conti_mastro'],
                'documenti_contabilita_sottoconti_conto' => $conto['documenti_contabilita_conti_id'],
                'documenti_contabilita_sottoconti_codice' => $last_sottoconto_codice,
                'documenti_contabilita_sottoconti_descrizione' => $customer['customers_company'] ?: ($customer['customers_name'] . ' ' . $customer['customers_last_name']),
                'documenti_contabilita_sottoconti_codice_completo' => $sottoconto_codice_completo,
                'documenti_contabilita_sottoconti_blocco' => DB_BOOL_FALSE,
                'documenti_contabilita_sottoconti_partite_aperte' => DB_BOOL_FALSE,
            ]);
        }



        //debug($sottoconto_codice_completo, true);

        //Associo questo sottoconto al cliente (faccio con db altrimenti entra in loop)
        $this->db->where('customers_id', $customer['customers_id'])->update('customers', [
            'customers_sottoconto' => $sottoconto['documenti_contabilita_sottoconti_id'],
            'customers_conto' => $conto['documenti_contabilita_conti_id'],
            'customers_mastro' => $conto['documenti_contabilita_conti_mastro'],
            'customers_codice_sottoconto' => $sottoconto_codice_completo,
        ]);
        $this->apilib->clearCache();
    }
}
