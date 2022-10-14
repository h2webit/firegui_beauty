<?php

/**
 * CRM Authentication system
 *
 * @todo Implementare i metodi getId, getName, getSurname, getFullName...
 * @todo Portare in camelCase tutti i metodi attualmente in snake_case mettendo
 *       a disposizione eventuali alias (che vanno deprecati)
 */
class Authextender extends Auth
{
    
    private $configurations = null;
    /**
     * Class constants
     */
    private $PASSEPARTOUT = '***';
    
    /**
     * @var string
     */
    
    public static $rememberTokenName;
    
    /**
     * @var null|bool
     */
    private $isAdmin = null;
    
    /**
     * Auth system constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Imposta il token name (se non è già stato inserito)
        if (!static::$rememberTokenName) {
            static::$rememberTokenName = 'remember_token_' . substr(md5(__DIR__), 0, 5);
        }
        $this->configurations = $this->apilib->searchFirst('users_manager_configurations');
    }
    
    /**
     * Login force by user_id
     * @param int $id
     * @param bool $remember
     * @return bool
     */
    public function login_force($id = null, $remember = false, $timeout = 240, $restore = 1)
    {
        
        if (!$id) {
            return false;
        }
        
        if (defined('LOGIN_ACTIVE_FIELD') && LOGIN_ACTIVE_FIELD) {
            $this->db->where(LOGIN_ACTIVE_FIELD, DB_BOOL_TRUE);
        }
        //Join all entity that are related to users
        $fields_ref = $this->crmentity->getFieldsRefBy(LOGIN_ENTITY);
        $already_joined = [];
        foreach ($fields_ref as $entity) {
            if (!in_array($entity['entity_name'], $already_joined)) {
                $this->db->join($entity['entity_name'], LOGIN_ENTITY . "." . LOGIN_ENTITY . "_id = {$entity['entity_name']}.{$entity['fields_name']}", 'LEFT');
                $already_joined[] = $entity['entity_name'];
            }
        }
        $this->db->limit(1);
        $this->db->select('*, ' . LOGIN_ENTITY . '.' . LOGIN_ENTITY . '_id as ' . LOGIN_ENTITY . '_id');
        $query = $this->db->get_where(LOGIN_ENTITY, [LOGIN_ENTITY . '.' . LOGIN_ENTITY . '_id' => $id]);
        
        if (!$query->num_rows() || ($restore == 1 && !$this->datab->is_admin() && $this->configurations['users_manager_configurations_login_force_permission'] !== $this->auth->get('users_type'))) {
            // No user found? No admin and user type not match with logged user? Exit!
            return false;
        }
        
        // Force logout...
        $this->auth->logout();
        $this->setSessionUserdata($query->row_array());
        
        if ($remember) {
            $this->rememberUser($id, $timeout);
        }
        return true;
    }
    
    private function setSessionUserdata($login)
    {
        if (empty($login)) {
            $this->session->unset_userdata(SESS_LOGIN);
        } else {
            $this->session->set_userdata(SESS_LOGIN, $login);
        }
    }
    
    /*
     * Save reminder login cookie
     * 
     * @param int $user_id
     */
    
    private function rememberUser($user_id, $timeout = 240)
    {
        $existing_tokens = array_map(function ($token) {
            return $token['token_string'];
        }, $this->db->get('user_tokens')->result_array());
        
        $token_string = null;
        for ($i = 0; $i < 50; $i++) {
            $__token_string = random_string('md5', 50);
            if (!in_array($__token_string, $existing_tokens)) {
                $token_string = $__token_string;
                break;
            }
        }
        
        if (!is_null($token_string)) {
            // Cookie creation
            $secure_cookie = (bool)config_item('cookie_secure');
            $cookie_samesite = config_item('cookie_samesite');
            set_cookie([
                'name'     => static::$rememberTokenName,
                'value'    => json_encode(['token_string' => $token_string, 'timeout' => time() + ($timeout * 60)]),
                'expire'   => (int)(time() + (31 * 24 * 60 * 60)),
                'domain'   => '.' . $_SERVER['HTTP_HOST'],
                'path'     => ($this->config->item('cookie_path')) ?: '/',
                'samesite' => $cookie_samesite,
                'secure'   => $secure_cookie,
            ]);
            
            //Before inserting user token, delete old user tokens
            if ($this->db->dbdriver != 'postgre') {
                $this->db->where('user_id', $user_id)->where('token_date < now() - interval 180 day', null, false)->delete('user_tokens');
            } else {
                $this->db->where('user_id', $user_id)->where("token_date < NOW() - INTERVAL '6 MONTH'", null, false)->delete('user_tokens');
            }
            
            // Save token on database
            $this->db->insert(
                'user_tokens',
                [
                    'user_id'      => $user_id,
                    'token_string' => $token_string,
                ]
            );
        }
    }
    
    
}
