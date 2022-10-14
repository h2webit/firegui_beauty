<?php
class Customer extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->auth->check()) {
            show_404();
        }
    }

    public function duplicate($id, $new_type)
    {
        $record = $this->db->where('customers_id', $id)->get('customers')->row_array();

        $return_id = $id;
        if (!empty($record) && empty($record['customers_related_customer'])) {
            unset(
                $record['customers_id'],
                $record['customers_listino'],
                $record['customers_mastro'],
                $record['customers_conto'],
                $record['customers_sottoconto'],
                $record['customers_contropartita_mastro'],
                $record['customers_contropartita_conto'],
                $record['customers_contropartita_sottoconto']
            );

            $record['customers_type'] = $new_type;
            $record['customers_related_customer'] = $id;

            try {
                $new_id = $this->apilib->create('customers', $record, false);

                $this->db->where('customers_id', $id)->update('customers', ['customers_related_customer' => $new_id]); // Using this->db avoids entering in post-process so avoids possibles weird erorrs
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                throw new ApiException("An error has occurred.");
                exit;
            }

            $this->apilib->clearCache();

            $return_id = $new_id;
        }

        redirect(base_url('main/layout/customer-detail/' . $return_id));
    }

    public function autologin($userId = null, $logincode = null, $restore = 1)
    {
        if (!$userId or !$logincode) {
            show_error('Inserisci id utente e codice di accesso', 400);
        }

        if ($logincode !== md5('customer' . $userId)) {
            show_error('Incorrect code', 403);
        }

        $current = $this->auth->get('id');

        // Force logout...
        $this->auth->logout();
        //$this->session->sess_destroy();
        // Force login with remember
        $this->auth->login_force($userId, true);

        if ($restore) {
            $this->session->set_userdata('previous_agent_id', $current);

            redirect();
        } else {
            $this->session->unset_userdata('previous_agent_id');
            redirect(base_url(), 'refresh');
        }
    }

    public function createCustomersContact()
    {
        $customers = $this->apilib->search('customers', ["(customers_id NOT IN (SELECT customers_contacts_customer_id FROM customers_contacts))", "(customers_email IS NOT NULL && customers_email <> '')"]);

        $tot_customers = count($customers);
        $elaborated = 0;

        dd($customers);

        if (!empty($customers)) {
            foreach ($customers as $customer) {
                $customer_contact = [];

                if (!empty($customer['customers_name'])) {
                    $customer_contact['customers_contacts_name'] = $customer['customers_name'];

                    if (!empty($customer['customers_last_name'])) {
                        $customer_contact['customers_contacts_last_name'] = $customer['customers_last_name'];
                    }
                } else {
                    $customer_contact['customers_contacts_name'] = t('Customer');
                }

                $customer_contact['customers_contacts_email'] = $customer['customers_email'];
                $customer_contact['customers_contacts_phone'] = $customer['customers_phone'] ?? null;
                $customer_contact['customers_contacts_mobile'] = $customer['customers_mobile'] ?? null;
                $customer_contact['customers_contacts_customer_id'] = $customer['customers_id'];

                $customer_contact['customers_contacts_password'] = generateRandomPassword(12, true, true);

                // dump($customer_contact);

                $this->apilib->create('customers_contacts', $customer_contact);

                $elaborated++;
                progress($elaborated, $tot_customers);
            }
        }
    }

    public function saveActivity()
    {
        $data = $this->input->post();

        if ($data['customer_activities_type'] == 3) { //note
            unset(
                $data['customer_activities_assign_to'],
                $data['customer_activities_reminder_type'],
                $data['customer_activities_date']
            );
        }

        if (!empty($data['customer_activities_customer_id']) && !empty($data['customer_activities_created_by'])) {
            try {
                $this->apilib->create('customer_activities', $data);
                die(json_encode([
                    'status' => 2,
                    'txt' => t('Succesfully saved.'),
                ]));
            } catch (Exception $e) {
                die(json_encode([
                    'status' => 0,
                    'txt' => t('An error has occurred.'),
                ]));
            }
        }
    }

    public function editActivityState($activity_id)
    {
        try {
            $customer_activity = $this->apilib->edit('customer_activities', $activity_id, [
                'customer_activities_done' => DB_BOOL_TRUE,
                'customer_activities_done_date' => date('d/m/Y H:i')
            ]);
            die(json_encode([
                'status' => 2,
                'txt' => t('Succesfully saved.'),
            ]));
        } catch (Exception $e) {
            $error = json_encode([
                'status' => 0,
                'error' => 'An error has occurred'
            ]);
            die($error);
        }
    }
    public function generazionetessera($cliente_id)
    {
        $html_tessera = $this->load->view('custom/pdf_tessera', ['value_id' => $cliente_id], true);
        $html = $this->load->view('custom/pdf_custom', ['html' => $html_tessera], true);

        $pdf = $this->layout->generate_pdf($html, "portrait", "", [], false, true);

        // Send the file
        $fp = fopen($pdf, 'rb');
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdf));
        fpassthru($fp);
    }
}
