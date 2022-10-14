<?php

class Cron extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contabilita/docs');

        $this->settings = $this->db->get('settings')->row_array();
        $this->contabilita_settings = $this->apilib->searchFirst('documenti_contabilita_settings');
    }

    // Cron da processare ogni 5 minuti. Non farlo prima di  minuti per sicurezza in quanto il nome file contiene il minuto di creazione
    public function cron_documenti_da_processare_sdi()
    {
        // Viene usato un search limit 1 per fare in modo che venga elaborato un documento alla volta, ad ogni passaggio cron. Potenzialmente funziona anche con il ->search normale ma alcune volte, inviando piu fatture nello stesso supporto FI. non riceviamo correttamente gli esiti da Sogei.
        $documenti = $this->apilib->search("documenti_contabilita", [
            "documenti_contabilita_stato_invio_sdi" => 2,
            "documenti_contabilita_formato_elettronico" => DB_BOOL_TRUE,
        ], 1);

        echo "Trovati: " . count($documenti) . " da processare \n";

        //debug($documenti);

        if (!empty($documenti)) {
            foreach ($documenti as $documento) {
                echo "Processo documento:  " . $documento['documenti_contabilita_id'] . "\n";
                if (!$this->send_to_sdiftp($documento['documenti_contabilita_id'])) {
                    echo "Invio fallito! Controllare file di log!";
                } else {
                    echo "File processato";
                }
            }
        }
    }

    // Funzione richiamabile anche dall'esterno per cambiare lo stato ad
    private function change_sdi_status($documento, $status, $extra = null)
    {
        //debug($this->input->post());
        $data = (!empty($this->input->post())) ? $this->input->post() : array("documenti_contabilita_stato_invio_sdi" => $status, "documenti_contabilita_stato_invio_sdi_errore_gestito" => $extra);

        if (is_numeric($documento)) {
            //Pare ci sia un bug... arriva un this->input->post sbagliato e soprattutto con nome file vuoto. Forzo il valore eventualmente già ssalvato su db
            $documenti_contabilita_nome_file_xml = $this->db->query("SELECT documenti_contabilita_nome_file_xml FROM documenti_contabilita WHERE documenti_contabilita_id = '$documento'")->row()->documenti_contabilita_nome_file_xml;
            $data['documenti_contabilita_nome_file_xml'] = $documenti_contabilita_nome_file_xml;
            $this->apilib->edit("documenti_contabilita", $documento, $data);
            //debug($this->db->query("SELECT documenti_contabilita_nome_file_xml FROM documenti_contabilita WHERE documenti_contabilita_id = '$documento'")->row()->documenti_contabilita_nome_file_xml);
        } else {
            $zipname = $documento;
            $documento = $this->apilib->searchFirst("documenti_contabilita", ["documenti_contabilita_nome_zip_sdi" => $zipname]);

            $this->apilib->edit("documenti_contabilita", $documento['documenti_contabilita_id'], $data);
        }
    }

    // Funzione per inviare manualmente un documento al nostro server centralizzato
    private function send_to_sdiftp($documento_id)
    {
        $this->load->library('ftp');

        // Dir temporeanea
        $physicalDir = FCPATH . "uploads";
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }

        // Estrazione documento
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        if (empty($documento)) {
            log_message('error', "send_to_sdiftp: Documento '$documento_id' non esistente!");
            return false;
            // die('Documento id non valido');
        }
        //debug($documento['documenti_contabilita_nome_file_xml']);
        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
        $file = base64_decode($documento['documenti_contabilita_file']);

        // Composizione del nome file
        $prefisso = "IT" . $settings['documenti_contabilita_settings_company_vat_number'];

        if (empty($documento['documenti_contabilita_nome_file_xml'])) {
            $xmlfilename = $this->docs->generateXmlFilename($prefisso, $documento_id);
        } else {
            $xmlfilename = $documento['documenti_contabilita_nome_file_xml'];
        }

        // Situazione limite ma non puo proseguire
        if (empty($xmlfilename)) {
            return false;
        }

        // Generazione nome file zip
        $partita_iva = '02675040303'; //$settings['documenti_contabilita_settings_company_vat_number']; // Partita iva dell'intermediario.
        $data_gregoriana = (date('Y')) . (str_pad(date('z') + 1, 3, '0', STR_PAD_LEFT));
        $ora = date('Hi');
        $incrementale = "001";

        $xmlquadname = "FI.$partita_iva.$data_gregoriana.$ora.$incrementale.xml";
        $zipname = "FI.$partita_iva.$data_gregoriana.$ora.$incrementale.zip";

        //20220928 - Controllo che lo zipname non esista già. Questo serve perchè se chiamiamo a mano il cron nello stesso minuto, evitiamo che vada a generare uno zip con più file dentro. La logica invece deve esseere "1 FILE ALLA VOLTA" (o meglio: uno zip al minuto)!
        $check_zipname_exists = $this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_zip_sdi = '$zipname' LIMIT 1");
        if ($check_zipname_exists->num_rows() > 0) {
            log_message('error', "Nome '$zipname' già presente su documenti_contabilità (documento: '$documento_id')");
            return false;
        }

        // Aggiorno il documento indicando il nome zip utilizzato per l'invio del documento e anche il nome del file xml per fare match piu facilmente con le notifiche di scarto
        //20220929 - Spostato qui: prima lo faceva alla fine
        $this->apilib->edit("documenti_contabilita", $documento_id, ["documenti_contabilita_nome_zip_sdi" => $zipname]);

        //Cambio lo stato su elaborazione in corso solo quando sono sicuro di aver generato un nome file zip unico
        //In caso di errori sucessivi (es.: connessione ftp fallita) sarà compito del codice successivo cambiare nuovamente lo stato
        $this->change_sdi_status($documento_id, '3');

        // Creazione file di quadratura xml temporaneo TODO: Da rifare con generazione xml fatta bene
        // Per scarti ET02 ricevuti senza motivo abbiamo cambiato la dataoracreazione da date('c') a gmdate('Y-m-d\TH:i:s\Z') cosi da portare tutto in UTC
        $file_quadratura = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <ns2:FileQuadraturaFTP xmlns:ns2="http://www.fatturapa.it/sdi/ftp/v2.0" versione="2.0">
                <IdentificativoNodo>' . $partita_iva . '</IdentificativoNodo>
                <DataOraCreazione>' . gmdate('Y-m-d\TH:i:s\Z') . '</DataOraCreazione>
                <NomeSupporto>' . $zipname . '</NomeSupporto>
                <NumeroFile>
                        <File>
                                <Tipo>FA</Tipo>
                                <Numero>1</Numero>
                        </File>
                </NumeroFile>
                </ns2:FileQuadraturaFTP>';
        /* $tmpQuadXml = "{$physicalDir}/{$xmlquadname}";
        file_put_contents($tmpQuadXml, $file_quadratura, LOCK_EX); */

        // Creo lo zip
        $tmpZipFile = "{$physicalDir}/{$zipname}";
        $zip = new ZipArchive();
        if ($zip->open($tmpZipFile, ZipArchive::CREATE) !== true) {
            $this->change_sdi_status($documento_id, '4', "Zip file creation failed. Cannot open zip file");
            exit("cannot open <$tmpZipFile>\n");
        }
        $zip->addFromString($xmlquadname, $file_quadratura);
        $zip->addFromString($xmlfilename, $file);
        $zip->close();

        // Configurazioni FTP TODO: Portare in costanti
        $config['hostname'] = '195.201.22.125';
        $config['username'] = 'docftpuser';
        $config['password'] = 'nB@NGs9u292';
        $config['debug'] = true;

        // Connession ed upload ... TODO Verificare eventuali errori di upload
        $this->ftp->connect($config);
        if ($this->ftp->upload($tmpZipFile, "./ftp_temp_dir/" . $zipname, FTP_BINARY, 0775)) {
            $this->change_sdi_status($documento_id, '8');
            $this->ftp->close();

            return true;
        } else {
            log_message('error', "send_to_sdiftp: Invio FTP al server centralizzato non riuscito (documento '$documento_id')");

            $this->change_sdi_status($documento_id, '4', "Invio FTP al server centralizzato non riuscito.");
            $this->ftp->close();
            return false;
        }

    }
}
