<?php
require_once (APPPATH . 'core/facebook.php');
require_once (APPPATH . 'core/shopify.php');

use Facebook\Facebook;

class Test extends CI_Controller {

    function __construct() {
        parent::__construct();               
        $this->load->model('facebook');
    }
    
    function publish($pid) {
        global $appID, $appSecret;
        
        //session_start();
        session_destroy();
        
        //$_SESSION['USER']['id'] = '10156659297160541';  // Simon
        //$_SESSION['USER']['id'] = '1548381398747445'; // Valentin Marinov
        $_SESSION['facebook_access_token'] = getFacebookAccessTokenFromDB($_SESSION['USER']['id']);
        
        $_SESSION['FB'] = new Facebook([
            'app_id' => $appID,
            'app_secret' => $appSecret,
        ]);
        
        $result = publishProduct($pid);
    }
}
