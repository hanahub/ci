<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');       
        
class Home extends Auth_Controller {

    function __construct() {
        parent::__construct();        
    }

    function index() {                
        $token = $_SESSION['facebook_access_token'];                
        $results = getMyAdAccounts($token);
        
        $i = 0;
        $data['ad_accounts'] = array();
        foreach ($results as $result) {
            $result = $result->asArray();            
            
            if ($_SESSION['USER']['name'] != $result['name']) {
                $account_name = $result['name'];
            } else {
                $account_name = $result['account_id'];
            }
            $data['ad_accounts'][$i] = 
                array(
                    'id'        => $result['account_id'],
                    'name'      => $account_name,
                    'currency'  => $result['currency'],
                    'users'     => end($result['users']),
                    'time_zone' => $result['timezone_name'],                    
                );
            
            $i++;
        }
        
        $this->load->template('ad_accounts', $data);
    }    
    
    function pages() {        
        $data['pages'] = getPages();
        $this->load->template('pages', $data);    
    }
    
    function post($page_id = '') {
        global $appID, $appSecret;
        
        $project_id = getSelectedProjectId();
        //$data['products'] = getProducts($project_id);
        $data['products'] = getWeeklyProducts($project_id);
        $data['projects'] = getProjects();        
        
        $this->load->template('post', $data);
    }
    
    function history($project_id = '') {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        else            
            $_SESSION['PROJECT_ID'] = $project_id;
        $data["history"] = $this->facebook->get_all_history($project_id);
        //$data = array();
        $this->load->template('history', $data);
    }
    
    function queue($project_id = '') {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        else            
            $_SESSION['PROJECT_ID'] = $project_id;
        $data["queue"] = $this->facebook->get_queue($project_id);
        //$data = array();
        $this->load->template('queue', $data);
    }
    
}

?>
