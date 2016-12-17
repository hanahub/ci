<?php

class MY_Shopify extends Auth_Controller {

    function __construct() {
        parent::__construct();               
        $this->load->model('facebook');
    }
    
    function get_products($since_id = '', $limit = 10) {
        $sc = $_SESSION['SC'];
        
        $results = getShopifyProducts($since_id, $limit);
        
        echo json_encode($results);
        die();
    }
    
    function publish_products($since_id = '', $limit = 10) {                
        $data = json_decode(file_get_contents("php://input"));                                  
        $results = publishProducts($data->products, $data->page_id);
        echo json_encode(['result' => 1]);
        die();
    }
    
    function set_project($project_id) {                        
        $_SESSION['PROJECT_ID'] = $project_id;
        $dumb = $this->facebook->get_project_details($project_id);
        $_SESSION['SC'] = new ShopifyClient($dumb['shop_domain'], $dumb['token'], $dumb['api_key'], $dumb['secret']);
        
        echo json_encode(['result' => 1]);
        die();
    }
}    
?>