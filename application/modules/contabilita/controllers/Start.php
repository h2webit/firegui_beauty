<?php

class Start extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function InizializzaFile()
    {
        $files = array(
            0 => array(
                'name' => 'template_base_no_iva.tpl',
                //'tipo' => 'fattura', non credo serva
                'nome' => 'Template Base No Iva'
            )
        );
        foreach ( $files as $file ) {
            $localFolder = "tpl";
            if (is_dir(APPPATH . 'modules/contabilita/' . $localFolder)) {
                if(file_exists(APPPATH . 'modules/contabilita/' . $localFolder."/".$file['name'])){
                    if (!is_dir(FCPATH . 'uploads/' . $localFolder)) {
                        mkdir(FCPATH . 'uploads/' . $localFolder, DIR_WRITE_MODE, true);
                    }
                    rename(APPPATH . 'modules/contabilita/' . $localFolder."/".$file['name'], FCPATH . 'uploads/' .$localFolder."/".$file['name']);
                    //aggiorno db
                    $this->db->query("UPDATE documenti_contabilita_template_pdf SET documenti_contabilita_template_pdf_file_html = '".$localFolder."/".$file['name']."' WHERE documenti_contabilita_template_pdf_nome = '".$file['nome']."'");
                    $this->apilib->clearCache();
                }
            }
        }
        //ora cerco ogni tpl se esiste, se non esiste, lo metto vuoto
        $elenco_template = $this->apilib->search('documenti_contabilita_template_pdf');
        foreach ( $elenco_template as $template ) {
            if($template['documenti_contabilita_template_pdf_file_html']){
                if(!file_exists(FCPATH . 'uploads/'.$template['documenti_contabilita_template_pdf_file_html'])){
                    $this->apilib->edit('documenti_contabilita_template_pdf', $template['documenti_contabilita_template_pdf_id'],['documenti_contabilita_template_pdf_file_html' => '']);
                }
            }
        }
        echo json_encode(array('status' => 2, 'txt' => "File inizializzati correttamente."));
    }
}