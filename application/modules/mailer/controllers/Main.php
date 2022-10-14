<?php

class Main extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->settings = $this->db->get('settings')->row_array();
    }


    // Import native (builder) mail template to module
    function import_native_mail_template($override = 0)
    {
        $emails = $this->db->query("SELECT * FROM emails")->result_array();

        foreach ($emails as $email) {

            $mailer_template['mailer_template_native_id'] = $email['emails_id'];
            $mailer_template['mailer_template_language'] = $email['emails_language'];
            $mailer_template['mailer_template_key'] = $email['emails_key'];
            $mailer_template['mailer_template_subject'] = $email['emails_subject'];
            $mailer_template['mailer_template_body'] = $email['emails_template'];
            $mailer_template['mailer_template_headers'] = $email['emails_headers'];
            $mailer_template['mailer_template_module'] = $email['emails_module'];

            // Check if exist and override option 
            $check = $this->db->query("SELECT * FROM mailer_template WHERE mailer_template_native_id = '{$email['emails_id']}'")->num_rows();

            if ($check > 0) {
                if ($override == 1) {
                    $this->db->where("mailer_template_native_id", $email['emails_id']);
                    $this->db->update("mailer_template", $mailer_template);
                }
            } else {
                $this->db->insert('mailer_template', $mailer_template);
            }
        }

        echo json_encode(array('status' => 3, 'txt' => 'Imported!'));
    }
}
