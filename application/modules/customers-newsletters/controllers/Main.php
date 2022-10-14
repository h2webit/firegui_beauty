<?php


class Main extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    // -------------------------------------------- CRON ---------------------
    
    // Send SMS for each contacts in queue. Call this every minute
    public function send_newsletters_sms($force_contact_id=null)
    {
        $this->load->model('modulo-sms/sms_model'); // REQUIRE SKEBBY MODULE
        $config = $this->apilib->searchFirst('sms_configuration');
        if($config['sms_configuration_saldo'] <= 0){
            throw new ApiException('Non puoi inviare SMS in quanto il saldo è 0');
            exit;
        }
        // Check all contacts to send
        if (!empty($force_contact_id)) {
            $contact = $this->db->query("SELECT * FROM newsletters_queue LEFT JOIN newsletters_campaign ON newsletters_queue_campaign_id = newsletters_campaign_id WHERE newsletters_queue_id = '$force_contact_id'")->row_array();
            $contacts[] = $contact;
        } else {
            $contacts = $this->db->query("SELECT * FROM newsletters_queue LEFT JOIN newsletters_campaign ON newsletters_queue_campaign_id = newsletters_campaign_id WHERE newsletters_queue_sent = '0'")->result_array();
        }
        $count = 0;
        foreach ($contacts as $contact) {
            // Check if sms campaign
            if ($contact['newsletters_campaign_type'] != 2) {
                continue;
            }
            
            // Check send minute limit
            if (empty($force_contact_id)) {
                $check = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}' AND newsletters_queue_sent_datetime BETWEEN NOW() - INTERVAL 1 MINUTE AND NOW()")->row()->c;
            } else {
                $check = 0;
            }
            if ($check < $contact['newsletters_campaign_max_minute_send']) {
                if (!empty($contact['newsletters_queue_contact_telephone'])) {
                    try {
                        $this->apilib->create('sms_sent', ['sms_sent_numero' => $contact['newsletters_queue_contact_telephone'], 'sms_sent_testo' => $contact['newsletters_campaign_message']]);
                        $this->db->where('newsletters_queue_id', $contact['newsletters_queue_id'])->update('newsletters_queue', array('newsletters_queue_sent' => 1, 'newsletters_queue_sent_datetime' => date('Y-m-d H:i:s')));
                        $count++;
                    } catch (Exception $e) {
                        $this->db->where('newsletters_queue_id', $contact['newsletters_queue_id'])->update('newsletters_queue', array('newsletters_queue_sent' => 2, 'newsletters_queue_sent_datetime' => date('Y-m-d H:i:s')));
                    }
                }
                else {
                    $this->db->where('newsletters_queue_id', $contact['newsletters_queue_id'])->update('newsletters_queue', array('newsletters_queue_sent' => 2, 'newsletters_queue_sent_datetime' => date('Y-m-d H:i:s')));
                }
            }
            echo "Sent $count messages";
        }
        $this->apilib->clearCache();
        
        if (!empty($force_contact_id)) {
            echo json_encode(array('status'=>3,'txt'=>'SMS sent!'));
        } else {
            // Update Newsletters status
            $sent = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}' AND newsletters_queue_sent = '1'")->row()->c;
            $error = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}' AND newsletters_queue_sent = '2'")->row()->c;
            $total = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}'")->row()->c;
            $totale_sms = $sent+$error;
            if($totale_sms==$total){
                echo "aggiorno!";
                $this->db->query("UPDATE newsletters_campaign SET newsletters_campaign_status = 3 WHERE newsletters_campaign_id IN (SELECT newsletters_queue_campaign_id FROM newsletters_queue WHERE (newsletters_queue_sent = '1' || newsletters_queue_sent = '2'))");
            }
            //$this->db->query("UPDATE newsletters_campaign SET newsletters_campaign_status = 3 WHERE newsletters_campaign_id IN (SELECT newsletters_queue_campaign_id FROM newsletters_queue WHERE newsletters_queue_sent = '".DB_BOOL_TRUE."')");
        }
    }
    
    // Send email for each contacts in queue. Call this every minute
    public function send_newsletters_email($force_contact_id=null)
    {
        // Check all contacts to send
        if (!empty($force_contact_id)) {
            $contact = $this->db->query("SELECT * FROM newsletters_queue LEFT JOIN newsletters_campaign ON newsletters_queue_campaign_id = newsletters_campaign_id WHERE newsletters_queue_id = '$force_contact_id'")->row_array();
            $contacts[] = $contact;
        } else {
            $contacts = $this->db->query("SELECT * FROM newsletters_queue LEFT JOIN newsletters_campaign ON newsletters_queue_campaign_id = newsletters_campaign_id WHERE newsletters_queue_sent = '0'")->result_array();
        }
        foreach ($contacts as $contact) {
            
            // Check if email campaign
            if ($contact['newsletters_campaign_type'] != 1) {
                continue;
            }
            
            // Check send minute limit
            if (empty($force_contact_id)) {
                $check = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}' AND newsletters_queue_sent_datetime BETWEEN NOW() - INTERVAL 1 MINUTE AND NOW()")->row()->c;
            } else {
                $check = 0;
            }
            if ($check < $contact['newsletters_campaign_max_minute_send']) {
                $send = $this->mail_model->sendMessage($contact['newsletters_queue_contact_email'], $contact['newsletters_campaign_subject'], $contact['newsletters_campaign_message']);
                if ($send) {
                    if(!isset($send['mailer_queue_id'])){
                        $this->db->where('newsletters_queue_id', $contact['newsletters_queue_id'])->update('newsletters_queue', array('newsletters_queue_sent' => 2, 'newsletters_queue_sent_datetime' => date('Y-m-d H:i:s')));
                    }
                    else {
                        $this->db->where('newsletters_queue_id', $contact['newsletters_queue_id'])->update('newsletters_queue', array('newsletters_queue_sent' => 1, 'newsletters_queue_sent_datetime' => date('Y-m-d H:i:s')));
                    }
                }
            }
        }
        $this->apilib->clearCache();
        
        if (!empty($force_contact_id)) {
            echo json_encode(array('status'=>3,'txt'=>'Mail sent!'));
        } else {
            // Update Newsletters status
            $sent = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}' AND newsletters_queue_sent = '1'")->row()->c;
            $error = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}' AND newsletters_queue_sent = '2'")->row()->c;
            $total = $this->db->query("SELECT COUNT(*) AS c FROM newsletters_queue WHERE newsletters_queue_campaign_id = '{$contact['newsletters_queue_campaign_id']}'")->row()->c;
            $totale_mail = $sent+$error;
            if($totale_mail==$total){
                $this->db->query("UPDATE newsletters_campaign SET newsletters_campaign_status = 3 WHERE newsletters_campaign_id IN (SELECT newsletters_queue_campaign_id FROM newsletters_queue WHERE newsletters_queue_sent = '1')");
            }
        }
    }
    
    
    
    // -------------------------------------------- SEND ---------------------
    
    public function send_campaign($campaign_id)
    {
        $campaign = $this->apilib->view('newsletters_campaign', $campaign_id);
        
        if (empty($campaign)) {
            echo "Error: Campaign not found";
            return false;
        }
        if ($campaign['newsletters_campaign_status'] != 1) {
            echo "Error: Already sent this campaign.";
            return false;
        }
        
        $contacts = $this->db->query("SELECT * FROM newsletters_contacts WHERE newsletters_contacts_list IN (SELECT newsletters_list_id FROM newsletters_campaign_lists WHERE newsletters_campaign_id = '$campaign_id')")->result_array();
        
        // Import contact to queue table
        foreach ($contacts as $contact) {
            $new_queue['newsletters_queue_campaign_id'] = $campaign_id;
            $new_queue['newsletters_queue_contact_name'] = $contact['newsletters_contacts_name'];
            $new_queue['newsletters_queue_contact_last_name'] = $contact['newsletters_contacts_last_name'];
            $new_queue['newsletters_queue_contact_email'] = $contact['newsletters_contacts_email'];
            $new_queue['newsletters_queue_contact_telephone'] = $contact['newsletters_contacts_telephone'];
            $new_queue['newsletters_queue_contact_list'] = $contact['newsletters_contacts_list'];
            $new_queue['newsletters_queue_sent'] = 0;
            //dd($new_queue);
            $this->db->insert('newsletters_queue', $new_queue);
        }
        $this->apilib->clearCache();
        
        $this->apilib->edit('newsletters_campaign', $campaign_id, array('newsletters_campaign_status' => 2));
        echo json_encode(array('status' => 3, 'txt' => "The campaign has been sent, the queue will be processed according to the parameters entered."));
    }
    
    
    // -------------------------------------------- IMPORT --------------------
    
    public function import_customers()
    {
        $type = $this->input->get('type');
        $list_id = $this->input->get('list_id');
        
        if (empty($type) || empty($list_id)) {
            echo "Not found import type";
            return false;
        }
        
        switch ($type) {
            case 'all_customers_suplliers':
                $query = $this->db->query("
            SELECT customers_company AS name, customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_company IS NOT NULL AND customers_deleted <> '1'
            UNION
            SELECT customers_name AS name, customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_company IS NULL AND customers_deleted <> '1'")->result_array();
                break;
            case 'all_customers':
                $query = $this->db->query("
            SELECT customers_company AS name,  customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_company IS NOT NULL AND customers_type = '1' AND customers_deleted <> '1'
            UNION
            SELECT customers_name AS name, customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_company IS NULL AND customers_type = '1' AND customers_deleted <> '1'")->result_array();
                break;
            case 'all_suppliers':
                $query = $this->db->query("
            SELECT customers_company AS name,  customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_company IS NOT NULL AND customers_type = '2' AND customers_deleted <> '1'
            UNION
            SELECT customers_name AS name, customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_company IS NULL AND customers_type = '2' AND customers_deleted <> '1'")->result_array();
                break;
            case 'all_customers_contacts':
                $query = $this->db->query("
            SELECT customers_contacts_name AS name, customers_contacts_email AS mail, customers_contacts_mobile_number AS telephone FROM customers_contacts WHERE customers_contacts_customer_id IS NOT NULL")->result_array();
                break;
            case 'all_customers_privati':
                $query = $this->db->query("SELECT customers_name AS name, customers_last_name AS last_name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE customers_type = '1' AND customers_deleted <> '1'")->result_array();
                break;
            case 'all_customers_aziende':
                $query = $this->db->query("SELECT customers_company AS name, customers_email AS mail, customers_mobile AS telephone FROM customers WHERE (customers_type = '2' OR customers_type = '3') AND customers_deleted <> '1'")->result_array();
                break;
            case 'all_users':
                $query = $this->db->query("SELECT users_first_name AS name, users_last_name AS last_name, users_email AS mail FROM users WHERE users_deleted <> '1'")->result_array();
                break;
            default:
                echo "Type not found";
                return false;
                break;
        }
        $found = count($query);
        $imported=0;
        $duplicated=0;
        foreach ($query as $contact) {
            if (!$contact['mail'] || !$contact['name']) {
                continue;
            }
            
            if (empty($contact['telephone'])) {
                $contact['telephone'] = null;
            }
            
            if (empty($contact['last_name'])) {
                $contact['last_name'] = null;
            }
            
            $new_contact['newsletters_contacts_name'] = $contact['name'];
            $new_contact['newsletters_contacts_last_name'] = $contact['last_name'];
            $new_contact['newsletters_contacts_email'] = $contact['mail'];
            $new_contact['newsletters_contacts_telephone'] = $contact['telephone'];
            $new_contact['newsletters_contacts_list'] = $list_id;
            
            $check = $this->db->where('newsletters_contacts_email', $contact['mail'])->where('newsletters_contacts_list', $list_id)->get('newsletters_contacts')->num_rows();
            if ($check < 1) {
                $this->db->insert('newsletters_contacts', $new_contact);
                $imported++;
            } else {
                $duplicated++;
            }
        }
        $this->apilib->clearCache();
        
        echo json_encode(array('status' => 3, 'txt' => " Found: $found records.\n Imported with email: $imported.\n Skipped because duplicated: $duplicated"));
    }
    public function iframeMail($id)
    {
        $mail = $this->apilib->view('newsletters_campaign', $id);
        echo (htmlspecialchars_decode($mail['newsletters_campaign_message']));
    }
    public function dettagliTemplate($id)
    {
        $result = $this->apilib->view('newsletters_template', $id);

        if ($result['newsletters_template_id']) {
            echo json_encode($result);
        }
        else {
            echo json_encode(array('status' => 0, 'txt' => "Errore, riprovare."));
        }
    }
    public function duplicaNewsletter($id)
    {
        $result = $this->apilib->view('newsletters_campaign', $id);

        if ($result['newsletters_campaign_id']) {

            $newsletter['newsletters_campaign_name'] = $result['newsletters_campaign_name'];
            $newsletter['newsletters_campaign_created_by'] = $this->auth->get('users_id');
            $newsletter['newsletters_campaign_subject'] = $result['newsletters_campaign_subject'];
            $newsletter['newsletters_campaign_message'] = $result['newsletters_campaign_message'];
            $newsletter['newsletters_campaign_status'] = 1;
            $newsletter['newsletters_campaign_max_minute_send'] = $result['newsletters_campaign_max_minute_send'];
            $newsletter['newsletters_campaign_type'] = $result['newsletters_campaign_type'];
            $newsletter['newsletters_campaign_template'] = $result['newsletters_campaign_template'];
            $newsletter_create = $this->apilib->create('newsletters_campaign', $newsletter);
            
            $result = $this->apilib->searchFirst('newsletters_campaign_lists', ['newsletters_campaign_id' => $result['newsletters_campaign_id']]);
            $newsletters_list_id = $result['newsletters_list_id'];
            $newsletters_campaign_id = $newsletter_create['newsletters_campaign_id'];
            $this->db->query("INSERT INTO newsletters_campaign_lists (newsletters_campaign_id, newsletters_list_id) VALUES ($newsletters_campaign_id, $newsletters_list_id)");
            
            $this->apilib->clearCache();

            echo json_encode(array('status' => 2, 'txt' => "Campagna duplicata correttamente"));
        }
        else {
            echo json_encode(array('status' => 0, 'txt' => "Errore, riprovare."));
        }
    }
}
