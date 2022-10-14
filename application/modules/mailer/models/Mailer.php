<?php

class Mailer extends CI_Model
{
    public function send($to = '', $key = '', $lang = '', array $data = [], array $additional_headers = [], array $attachments = [])
    {
        if (empty($lang)) {
            $settings = $this->db->join('languages', 'settings_default_language = languages_id')->get('settings')->row_array();
            if (!empty($settings)) {
                $langcode = $settings['languages_code'] ?? 'en-EN';

                $ex = explode('-', $langcode);
                $lang = $ex[0];
            }
        }

        $email = $this->db->get_where(
            'mailer_template',
            array(
                'mailer_template_key' => trim($key),
                'mailer_template_language' => $lang
            )
        )->row_array();

        if (empty($email)) {
            $email = $this->db->get_where('mailer_template', array('mailer_template_key' => trim($key)))->row_array();

            if (empty($email)) {
                $orig_email = $this->db->get_where('emails', array('emails_key' => trim($key), 'emails_language' => $lang))->row_array();

                if (empty($email)) {
                    $orig_email = $this->db->get_where('emails', array('emails_key' => trim($key)))->row_array();

                    if (empty($orig_email)) {
                        return false;
                    }
                }
                $email = $this->apilib->create('mailer_template', [
                    'mailer_template_language' => $orig_email['emails_language'],
                    'mailer_template_subject' => $orig_email['emails_subject'],
                    'mailer_template_body' => $orig_email['emails_template'],
                    'mailer_template_headers' => $orig_email['emails_headers'],
                    'mailer_template_key' => $orig_email['emails_key'],
                    'mailer_template_name' => $orig_email['emails_key'],
                    'mailer_template_module' => $orig_email['emails_module'],
                ]);
            }
        }

        $email_headers = (!empty($email['mailer_template_headers'])) ? unserialize($email['mailer_template_headers']) : [];

        $headers = array_merge(
            array_filter($email_headers),
            array_filter($additional_headers)
        );

        // Usa come replacement i parametri che non sono array, object e risorse
        $filteredData = array_filter($data, 'is_scalar');
        $subject = str_replace_placeholders($email['mailer_template_subject'], $filteredData, true, false);
        //$message = nl2br(str_replace_placeholders($email['mailer_template_body'], $filteredData, true, true));
        $message = str_replace_placeholders($email['mailer_template_body'], $filteredData, true, false);


        $this->sendEmail($to, $headers, $subject, $message, true, [], $attachments, $email);
    }

    public function sendEmail($to, array $headers, $subject, $message, $isHtml = true, $extra_data = [], $attachments = [], $template_data = [])
    {
        $smtp = false;
        // If template data is passed and it has a specific smtp, choose that
        if ($template_data && $template_data['mailer_template_smtp']) {
            $smtp = $this->apilib->view('mailer_smtp', $template_data['mailer_template_smtp']);
        }

        //If smtp not passed, search for the default
        if (!$smtp) {
            $smtp = $this->apilib->searchFirst('mailer_smtp', [
                'mailer_smtp_default' => DB_BOOL_TRUE,
                'mailer_smtp_enabled' => DB_BOOL_TRUE,
            ]);
        }
        //If smtp default not found, search for the first smtp
        if (!$smtp) {
            $smtp = $this->apilib->searchFirst('mailer_smtp', [
                'mailer_smtp_enabled' => DB_BOOL_TRUE,
            ]);
        }


        if ($smtp) { //If smtp found, use it!
            $email_config = [];
            $email_config['useragent'] = "PHPMailer";
            $email_config['protocol'] = 'smtp';

            $email_config['smtp_host'] = $smtp['mailer_smtp_host'];
            $email_config['smtp_user'] = $smtp['mailer_smtp_login'];
            $email_config['smtp_pass'] = $smtp['mailer_smtp_password'];
            $email_config['smtp_port'] = $smtp['mailer_smtp_port'];
            $email_config['wordwrap'] = true;
            $email_config['mailtype'] = 'html';
            $email_config['charset'] = 'utf-8';
            $email_config['newline'] = "\r\n";

            switch ($smtp['mailer_smtp_security_value']) {
                case 'TLS':
                    $email_config['smtp_crypto'] = 'tls';
                    break;
                case 'SSL':
                    $email_config['smtp_crypto'] = 'ssl';
                    break;
                default:
                    log_message('debug', "MAILER MODULE: Security value not recognized!");
                    $email_config['smtp_crypto'] = '';
                    break;
            }
            $email_config['smtp_auto_tls']    = true;
            $email_config['smtp_auth']    = true;
            $email_config['smtp_conn_options'] = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $email_config['mailtype']         = 'html';
            $email_config['smtp_debug']       = 0;
            $this->load->library('email', $email_config);
        } else { //Use native client smtp sender (see config/email.php and config/email_custom.php if present)
            $this->load->library('email');
        }

        //debug($smtp, true);

        // HTML mail setup
        if ($isHtml) {
            $this->email->set_mailtype('html');

            if (function_exists('mb_convert_encoding')) {
                $message = mb_convert_encoding(str_replace('&nbsp;', ' ', $message), 'HTML-ENTITIES', 'UTF-8');
            }

            $message = '<html><body>' . $message . '</body></html>';
        }

        // Addinfo to the email
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        // Prepend the default headers
        $defaultHeaders = $this->config->item('email_headers');

        $headers = array_merge($defaultHeaders ?: [], $headers);

        // Setup standard headers
        if (isset($headers['From'])) {
            $from = $this->mail_model->prepareAddress($headers['From']);
            $this->email->from($from['mail'], $from['name']);
        }

        if (isset($headers['Reply-To'])) {
            $replyto = $this->mail_model->prepareAddress($headers['Reply-To']);
            $this->email->reply_to($replyto['mail'], $replyto['name']);
        }

        if (isset($headers['Cc'])) {
            $this->email->cc($headers['Cc']);
        }

        if (isset($headers['Bcc'])) {
            $this->email->bcc($headers['Bcc']);
        }

        if (isset($attachments) && !empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && !empty($attachment['file_name']) && !empty($attachment['file'])) {
                    $this->email->attach($attachment['file'], 'attachment', $attachment['file_name']);
                } else {
                    $this->email->attach($attachment);
                }
            }
        }

        // Send and return the result
        $queue = $this->apilib->create('mailer_queue', [
            'mailer_queue_user_id' => $this->auth->get('id') ?? null,
            'mailer_queue_status' => 1,
            'mailer_queue_from' => $headers['From'],
            'mailer_queue_to' => $to,
            'mailer_queue_headers' => json_encode($headers),
            'mailer_queue_body' => $message,
            'mailer_queue_date_sent' => null,
            'mailer_queue_logs' => null,
            'mailer_queue_boundary' => null,
            'mailer_queue_subject' => $subject,
            'mailer_queue_smtp' => ($smtp) ? $smtp['mailer_smtp_id'] : null,
        ]);

        //debug($queue, true);

        //TODO: check defered
        $sent = $this->email->send();
        $debug = $this->email->print_debugger();

        $this->email->clear(true);

        if (!$sent) {
            $this->apilib->edit(
                'mailer_queue',
                $queue['mailer_queue_id'],
                [
                    'mailer_queue_status' => 3,
                    'mailer_queue_logs' => $debug
                ]
            );
            return $debug;
        } else {
            $this->apilib->edit(
                'mailer_queue',
                $queue['mailer_queue_id'],
                [
                    'mailer_queue_date_sent' => date('Y-m-d H:i:s'),
                    'mailer_queue_status' => 2,
                    'mailer_queue_logs' => $debug
                ]
            );
            return $queue;
        }
    }

    /**
     * L'invio è in differita?
     * @return bool
     */
    public function isDeferred()
    {
        return (true === $this->deferred);
    }

    //Override system sendMessage function
    public function sendMessage($to, $subject, $message, $isHtml = false, array $additionalHeaders = [], array $attachments = [])
    {
        return $this->sendEmail($to, $additionalHeaders, $subject, $message, $isHtml, [], $attachments);

        // Michael E. - 2022-04-19 - Commentato in quanto ancora è da fare e può creare problemi, per ora
        //Verifico se è impostato email_deferred
        // if ($this->isDeferred()) {
        //     //TODO: gestire il deferred
        //     //return $this->queueEmail($to, $additionalHeaders, $subject, $message, $isHtml, $attachments);
        // } else {
        //     return $this->sendEmail($to, $additionalHeaders, $subject, $message, $isHtml, [], $attachments);
        // }
    }
}