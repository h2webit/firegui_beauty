<?php
    class Templates extends MY_Controller
    {
        public function __construct()
        {
            parent::__construct();
        }

        public function zipAndDownload($template_id)
        {
            $template = $this->apilib->view('settings_template', $template_id);

            if (empty($template)) {
                throw new ApiException("Error: template not found");
                exit;
            }

            $view_path = APPPATH.'views/';

            if (!file_exists($view_path.$template['settings_template_folder']) || !is_dir($view_path.$template['settings_template_folder'])) {
                if (!mkdir($view_path.$template['settings_template_folder'])) {
                    throw new ApiException("Error: template folder invalid or insufficient permissions");
                    exit;
                }
            }

            $zip = new ZipArchive;

            $filename = 'tpl_' . $template['settings_template_folder'] . '.zip';

            $this->load->library('zip');

            $this->zip->read_dir($view_path.$template['settings_template_folder'], false, $view_path);
            $this->zip->archive($filename);
            $this->zip->download($filename);
        }
    }
