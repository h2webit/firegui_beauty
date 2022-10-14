<?php

class Conteggi extends CI_Model
{


    /************************** CONTEGGI PER SINGOLO CLIENTE ***********************************/

    // Dato il customer id ritorna il fatturato per ogni anno
    public function getFatturatoCustomer($customer_id)
    {
        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id'")->row_array();
        return $fatturato;
    }

    public function getFatturatoAnnoCustomer($customer_id)
    {
        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id' AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)")->row_array();
        return $fatturato;
    }


    public function getInsolvenzeCustomer($customer_id)
    {
        $insolvenze = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id' AND documenti_contabilita_stato_pagamenti = '1'")->row_array();
        return $insolvenze;
    }

    /************************** CONTEGGI GLOBALI ***********************************/
}
