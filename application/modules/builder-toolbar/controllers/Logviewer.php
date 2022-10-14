<?php

class Logviewer extends MY_Controller
{

    var $template = array();

    function __construct()
    {
        parent::__construct();
        
        require FCPATH . "application/modules/builder-toolbar/vendor/autoload.php";
        $this->logViewer = new \CILogViewer\CILogViewer();
    }
    
    private $logViewer;
    const MAX_STRING_LENGTH = 300; //300 chars
    const LOG_LINE_START_PATTERN = "/((INFO)|(ERROR)|(DEBUG)|(ALL))[\s\-\d:\.\/]+(-->)/";
    const LOG_DATE_PATTERN = ["/^((ERROR)|(INFO)|(DEBUG)|(ALL))\s\-\s/", "/\s(-->)/"];
    const LOG_LEVEL_PATTERN = "/^((ERROR)|(INFO)|(DEBUG)|(ALL))/";


    public function index() {
        $pagina = $this->load->view("logs_viewer/logs_viewer", array('data' => array()), true);
        $this->stampa($pagina);
    }

    // Load iframe with log viewer
    public function list()
    {
        echo $this->logViewer->showLogs();
    }


    // Save logs to database
    public function save_logs()
    {
        include_once(APPPATH . 'modules/fi-client-log-viewer/classes/FiLogs.php');
        $logs = new FiLogs();
        $path = ($this->config->item('log_path')) ? $this->config->item('log_path') : APPPATH . "logs";
        $logs->setPath($path);

        $data['last_logs'] = $logs->getLastLogs();

        // Get last log on db
        $last_db_log = $this->db->query("SELECT * FROM builder_toolbar_logs ORDER BY builder_toolbar_logs_date DESC LIMIT 0,1")->row_array();

        // Errors to array
        $count = 0;
        foreach ($data['last_logs'] as $log_line) {

            if(!empty($log_line)) {
                 $count++;
                $logLineStart = $this->getLogLineStart($log_line);

                    if(!empty($logLineStart)) { 
                       
                        $level = $this->getLogLevel($logLineStart);
                        $log['builder_toolbar_logs_level'] = $level;
                        $log['builder_toolbar_logs_date'] = $this->getLogDate($logLineStart);
                        
                        // Skip if exists
                        if (!empty($last_db_log) && ($log['builder_toolbar_logs_date'] < $last_db_log['builder_toolbar_logs_date'])){
                            continue;
                        }

                        // Message
                        $logMessage = preg_replace(self::LOG_LINE_START_PATTERN, '', $log_line);
                        $log['builder_toolbar_logs_message'] = $logMessage;

                        $identifier = md5($log['builder_toolbar_logs_date'].$log['builder_toolbar_logs_level']);
                        $log['builder_toolbar_logs_identifier'] = $identifier;

                        $this->db->insert('builder_toolbar_logs', $log);   
                    }
            }
            
            progress($count, count($data['last_logs']));
        }
    }



    private function getLogLevel($logLineStart) {
        preg_match(self::LOG_LEVEL_PATTERN, $logLineStart, $matches);
        return $matches[0];
    }

    private function getLogDate($logLineStart) {
        return preg_replace(self::LOG_DATE_PATTERN, '', $logLineStart);
    }

     private function getLogLineStart($logLine) {
        preg_match(self::LOG_LINE_START_PATTERN, $logLine, $matches);
        if(!empty($matches)) {
            return $matches[0];
        }
        return "";
    }


    protected function stampa($pagina)
    {
        $this->template['head'] = $this->load->view('layout/head', array(), true);
        $this->template['header'] = $this->load->view('layout/header', array(), true);
        $this->template['sidebar'] = $this->load->view('layout/sidebar', array(), true);
        $this->template['page'] = $pagina;
        $this->template['footer'] = $this->load->view('layout/footer', null, true);

        echo $this->load->view('layout/main', $this->template, true);
    }
}
