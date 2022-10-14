<?php

$this->db->query("ALTER TABLE documenti_contabilita DROP column documenti_contabilita_file_preview_xml");

$this->db->query("DELETE FROM fields WHERE fields_name = 'documenti_contabilita_file_preview_xml'");

$this->db->query("UPDATE documenti_contabilita_mappature SET documenti_contabilita_mappature_autocomplete = documenti_contabilita_mappature_value");
