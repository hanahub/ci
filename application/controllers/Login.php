<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once (APPPATH . 'core/facebook.php');

class Login extends CI_Controller {

    public function __construct() {
        parent::__construct();
                
        $this->load->helper('form');
        
        $this->load->library('form_validation');
        
        $this->load->library('session');
        
        $this->load->model('user');
    }

    // Show login page
    public function index() {
        if ( !empty($_SESSION['facebook_access_token']) && !empty($_SESSION['USER']) ) redirect("home");
        $redirect = base_url() . 'oauth_finish';         
        $data['url'] = getFacebookLoginURL($redirect);
        $data['message'] = $this->input->get("message");
        $this->load->template('login', $data);
    }
    
    public function logout() {        
        session_destroy();
        
        $this->session->sess_destroy();        
        $session_data = array(
            'access_token' => ''
        );
        
        $this->session->unset_userdata('logged_in', $session_data);
        $message = 'Successfully Logout';
        
        $redirect = base_url() . 'oauth_finish';         
        $data['url'] = getFacebookLoginURL($redirect);
        
        redirect('login?message=' . $message);
    }
    

}

?>