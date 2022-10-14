<?php


class Accessextender extends MY_Controller
{
    
    
    var $configurations = null;
    
    
    function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        if (!empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With
        }
        $this->load->model('user-extender/authextender');
        $this->configurations = $this->apilib->searchFirst('users_manager_configurations');
    }
    
    
    public function index()
    {
        $this->login();
    }
    
    
    public function login()
    {
        
        $this->load->view('layout/login');
    }
    
    
    public function login_force($userId = null, $logincode = null, $restore = 1)
    {
        
        if (!$userId or !$logincode) {
            show_error("User id or login code empty", 400);
        }
        if (empty($this->configurations['users_manager_configurations_salt'])) {
            show_error("Salt not configured", 403);
        } else {
            if ($logincode !== md5($this->configurations['users_manager_configurations_salt'] . $userId)) {
                show_error('Incorrect code', 403);
            }
            
            $current = $this->auth->get('id');
            
            //$this->session->sess_destroy();
            // Force login with remember
            $force_logged_in = $this->authextender->login_force($userId, true, 240, $restore);
            
            if ($force_logged_in !== false) {
                if ($restore) {
                    $this->session->set_userdata('previous_user_id', $current);
                    redirect();
                } else {
                    $this->session->unset_userdata('previous_user_id');
                    redirect(base_url());
                }
            } else {
                redirect(base_url());
            }
        }
    }
    public function initialize_permissions()
    {
        $tipologia = $this->db->query("SELECT DISTINCT users_type_value,users_type_id FROM users_type")->result_array();
        $dashobard = $this->apilib->searchFirst('layouts', ['layouts_dashboardable' => 1]);
        foreach ($tipologia as $tipologia_user) {
            if ($tipologia_user) {
                $user_type = $tipologia_user['users_type_value'];
                if ($user_type) {
                    $permissions = $this->db->get_where('permissions', array('permissions_group' => $user_type));
                    if ($permissions->num_rows() == 0) {
                        //non c'è in permission, quindi vado a creare la riga
                        $dati_db['permissions_admin'] = 0;
                        $dati_db['permissions_group'] = $user_type;
                        //prima il campo users_id vuoto così da inizializzarlo
                        $this->db->insert('permissions', $dati_db);
                        //per ogni utente che ha quel permesso, lo vado ad impostare
                        $users = $this->db->query("SELECT users_id FROM users WHERE users_type = ".$tipologia_user['users_type_id']."")->result_array();
                        foreach ($users as $user) {
                            if ($user['users_id']) {
                                $dati_db['permissions_user_id'] = $user['users_id'];
                                $this->db->insert('permissions', $dati_db);
                                //ora imposto i permessi su 0 a tutto, a parte la dashboard iniziale
                                $dati_permissions['unallowed_layouts_user'] = $user['users_id'];
                                $layouts = $this->db->query("SELECT layouts_id FROM layouts")->result_array();
                                foreach ($layouts as $layout) {
                                    //escludo il primo dashboard layout
                                    if ($layout['layouts_id'] != $dashobard['layouts_id']) {
                                        $dati_permissions['unallowed_layouts_layout'] = $layout['layouts_id'];
                                        $this->db->insert('unallowed_layouts', $dati_permissions);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        echo json_encode(array('status' => 2, 'txt' => "Permessi inizializzati correttamente."));
    }
}
