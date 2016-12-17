<?php

require_once (APPPATH . 'core/facebook.php');
require_once (APPPATH . 'core/shopify.php');

use Facebook\Facebook;

class Auth_Controller extends CI_Controller {

    function __construct()
    {
        global $appID, $appSecret, $db;        
        
        parent::__construct();

        if ( empty($_SESSION['facebook_access_token']) || empty($_SESSION['USER']) ) {
            redirect('login');
        }
        $this->load->model('facebook');            
        $this->load->model('user');
        
        $db = DB();

        $_SESSION['USER'] = getProfile($_SESSION['facebook_access_token']);
        $_SESSION['USER_ID'] = getUserID($_SESSION['USER']);
        $_SESSION['USER']['id'] = $_SESSION['USER_ID'];
        
        $business = getMyBusiness($_SESSION['facebook_access_token'], $_SESSION['USER']['name']);
        $_SESSION['BUSINESS_ID'] = $business['id'];
        
        $_SESSION['PROJECT_ID'] = getSelectedProjectId();
        
        $dumb = $this->facebook->get_project_details($_SESSION['PROJECT_ID']);
        $_SESSION['SC'] = new ShopifyClient($dumb['shop_domain'], $dumb['token'], $dumb['api_key'], $dumb['secret']);
        
        $_SESSION['FB'] = new Facebook([
            'app_id' => $appID,
            'app_secret' => $appSecret,
        ]);
        
        date_default_timezone_set('UTC');
    
    }
}