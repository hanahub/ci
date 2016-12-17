<?php

class Control_Panel extends Auth_Controller {

    protected $crud;
    
    function __construct() {
        parent::__construct();               
        
        $this->load->library('grocery_CRUD');
        $this->crud = new grocery_CRUD();
        
        $this->crud->unset_jquery();
        //$this->crud->unset_jquery_ui();
    }
    
    function import_pages() {
        $project_id = getSelectedProjectId();
        _import_pages($project_id);
        
        //import_page_collection_link($project_id);
        echo json_encode(['result' => 1]);
        //die();
    }    
    
    function import_products() {
        $project_id = getSelectedProjectId();
        $sc = $_SESSION['SC'];
        $param = array(
                    'fields' => 'id,title,image,created_at,published_at,handle',
                    'limit' => 250,
                    'published_status' => 'published',                    
                    'since_id' => $this->facebook->get_last_product_id($project_id),
                );
        do {
            $products = $sc->call('GET', '/admin/products.json', $param);
            if (!empty($products)) {
                $this->facebook->insert_shopify_products($project_id, $products);            
                foreach ($products as $p) {
                    $this->import_product_collection_link($p['id']);
                }
                $param['since_id'] = end($products)['id'];   
            }
        } while (!empty($products));
        
        //$this->import_product_collection_link(0);
    }
    
    function import_product_collection_link($product_id = 0) {
        $project_id = getSelectedProjectId();
        $sc = $_SESSION['SC'];
        $param = array(
                        'fields' => 'id',
                        'limit' => 250,
                        'published_status' => 'published'
                    );
                    
        if ($product_id == 0) {
            $since_id = $this->facebook->get_last_PCL_product_id($project_id);
            $products = $this->facebook->get_products($project_id, $since_id);
            if (empty($products)) return;
            foreach ($products as $p) {
                try {
                    foreach (array('smart', 'custom') as $type) {                    
                        $collections = $sc->call('GET', '/admin/' . $type . '_collections.json?fields=id&product_id=' . $p["shopifyId"], $param);                        
                        $this->facebook->insert_product_collection_links($project_id, $p["shopifyId"], $collections);
                    }
                } catch (ShopifyApiException $e) {
                    continue;
                }
            }
        } else { 
            foreach (array('smart', 'custom') as $type) {                
                $collections = $sc->call('GET', '/admin/' . $type . '_collections.json?fields=id&product_id=' . $product_id, $param);                
                $this->facebook->insert_product_collection_links($project_id, $product_id, $collections);
            }
        }
    }
    
    // import product collection link based on collection
    function import_product_collection_link_by_collection($collections, $project_id = 0) {
        $sc = $_SESSION['SC'];
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
            
        if (!empty($collections)) {
            foreach ($collections as $collection) {
                $param2 = array(
                    'fields' => 'id',
                    'limit' => 250,
                    'published_status' => 'published',
                    'collection_id' => $collection['id'],
                    'since_id' => $this->facebook->get_last_product_collection_id($project_id, $collection['id']),
                );
                
                do {
                    $products = $sc->call('GET', '/admin/products.json', $param2);
                    $this->facebook->insert_products_collection_link($project_id, $products, $collection['id']);
                    
                    if (!empty($products)) $param2['since_id'] = end($products)['id'];
                } while (!empty($products));
            }
        }
    }
    
    // import collections and relationship
    function import_collections($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        $sc = $_SESSION['SC'];
        
        foreach (array('smart', 'custom') as $type) {
            $param = array(
                        'fields' => 'id,title',
                        'limit' => 250,
                        'published_status' => 'published',
                        'since_id' => $this->facebook->get_last_collection_id($project_id, $type)
                    );

            do {
                $collections = $sc->call('GET', '/admin/' . $type . '_collections.json', $param);
                $this->facebook->insert_shopify_collections($project_id, $collections, $type);
                $this->import_product_collection_link_by_collection($collections, $project_id);
                if (!empty($collections)) $param['since_id'] = end($collections)['id'];
            } while (!empty($collections));
        }

        $this->import_product_collection_link(0);
    }
    
    function import_page_collection_link($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        
        global $db;
        $query = $db->query("SELECT * FROM collection WHERE projectId={$project_id}");
        $collections = $query->result_array();
        
        foreach ($collections as $c) {
            $collectionId = $c['shopifyId'];
            $query = $db->query('SELECT * FROM facebook_page WHERE userId=' . $_SESSION['USER']['id'] . ' AND projectId=' . $project_id . ' AND name like "%' . $c['title'] . '%"');
            if ($query->num_rows() > 0) {
                $pageId = $query->result_array()[0]['pageId'];
            } else {
                $query2 = $db->query("SELECT default_page FROM project WHERE projectId={$project_id}");                
                $pageId = $query2->result_array()[0]['default_page'];
            }
            
            $query = $db->query("SELECT * FROM page_collection_link WHERE collectionId=$collectionId AND projectId=$project_id");
            if ($query->num_rows() > 0) {
                $db->query("UPDATE page_collection_link SET pageId=$pageId WHERE collectionId=$collectionId AND projectId=$project_id");
            } else {
                $db->query("INSERT INTO page_collection_link (pageId, collectionId, projectId) VALUES({$pageId}, {$collectionId}, {$project_id})");
            }            
        }
        
        //print_r($result);
    }

    public function index() {        
        $this->products();    
    }
    
    public function projects($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        else            
            $_SESSION['PROJECT_ID'] = $project_id;    
        //$this->init($project_id);
        $this->crud->set_table('project')
                   ->columns('name', 'shop_domain', 'ad_account', 'color')
                   ->set_relation('default_page', 'facebook_page', 'name', array('userId' => $_SESSION['USER']['id'], 'projectId' => $project_id))
                   ->set_relation('default_target', 'targeting', 'interests', array('projectId' => $project_id))
                   ->display_as('name', 'Name')
                   ->display_as('shop_domain', 'Shop Domain')
                   ->display_as('url', 'URL')
                   ->display_as('token', 'Shopify Token')
                   ->display_as('api_key', 'Shopify API Key')                   
                   ->display_as('secret', 'Shopify Secret')
                   ->display_as('ad_account', 'Facebook Ad Account')
                   ->display_as('default_page', 'Default Page')
                   ->display_as('default_target', 'Default Target')
                   ->display_as('ad_text', 'Custom Ad Text')
                   ->unset_texteditor('ad_text')
                   ->unset_texteditor('slideshow_text')
                   ->unset_texteditor('slideshow_description')
                   ->unset_texteditor('carousel_text')
                   ->set_subject('Project');

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    public function facebook_pages($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        $_SESSION['PROJECT_ID'] = $project_id;
        //$this->import_pages();        
        $this->crud->set_table('facebook_page')
                   ->columns('facebookId', 'name', 'collections') 
                   ->set_relation('projectId', 'project', 'name')
                   ->where('facebook_page.projectId', $project_id) 
                   ->where('userId', $_SESSION['USER']['id'])
                   ->set_relation_n_n('collections', 'page_collection_link', 'collection', 'pageId', 'collectionId', 'title')                   
                   ->set_primary_key('shopifyId', 'collection')
                   ->display_as('facebookId', 'Facebook Page ID')
                   ->display_as('name', 'Name')                   
                   ->display_as('projectId', 'Project')
                   ->display_as('collections', 'Shopify Collections')
                   ->order_by('pageId', 'desc')
                   ->set_subject('Facebook Page');                
        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    public function collections($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        
        $_SESSION['PROJECT_ID'] = $project_id;
        //$this->import_collections('custom');
        
        $this->crud->set_table('collection')
                   ->columns('title', 'shopifyId', 'audience', 'rating', 'active', 'last_published')
                   ->set_relation('projectId', 'project', 'name')
                   ->where('collection.projectId=' . $project_id)
                   ->display_as('title', 'Title')
                   ->display_as('shopifyId', 'Shopify ID')
                   ->display_as('projectId', 'Project')
                   ->display_as('projectId', 'Last Published Date')
                   ->callback_column('active', array($this, '_callback_collections_active'))
                   ->set_subject('Collection');

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }

    function _callback_collections_active($value, $row) {
        if ($value == 1) return "Yes";
        else return "No";
    }
    
    public function products($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        
        $_SESSION['PROJECT_ID'] = $project_id;
        //$this->import_products($project_id);
        //$this->import_product_collection_link();
        
        $this->crud->set_table('product')
                   ->columns('title', 'published_at', 'shopifyId', 'collections')
                   ->set_relation('projectId', 'project', 'name')
                   ->set_relation('targeting_page', 'facebook_page', 'name')
                   ->set_relation_n_n('collections', 'product_collection_link', 'collection', 'productId', 'collectionId', 'title')                   
                   ->set_primary_key('shopifyId', 'collection')
                   ->set_primary_key('shopifyId', 'product')
                   ->where('product.projectId=' . $project_id)
                   ->display_as('title', 'Title')                   
                   ->display_as('published_at', 'Published At')
                   ->display_as('shopifyId', 'Shopify ID')
                   ->display_as('projectId', 'Project')
                   ->display_as('targeting_page', 'Targeting Page')
                   ->order_by('published_at', 'desc')
                   //->callback_column('productId', array($this, '_callback_do_checkbox'))
                   ->set_subject('Product');
                   
        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    function _callback_do_checkbox($value, $row) {        
        return '<input type="checkbox" class="inline_checkbox product_checkbox" data-id="' . $value . '"/>';
    }    
    
    public function page_collection_link($project_id = 0) {        
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        
        $_SESSION['PROJECT_ID'] = $project_id;
        $this->crud->set_table('page_collection_link')
                   ->columns('pageId', 'collectionId')
                   ->set_relation('pageId', 'facebook_page', 'name', array('projectId' => $project_id))
                   ->set_primary_key('shopifyId', 'collection')
                   ->set_relation('collectionId', 'collection', 'title', array('projectId' => $project_id))
                   ->where('jad02de36.projectId=' . $project_id)
                   ->display_as('pageId', 'Facebook Page')
                   ->display_as('collectionId', 'Collection')                   
                   ->set_subject('Page Collection Link');

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    public function targeting($project_id = 0) {
        if ($project_id == 0 || !is_numeric($project_id))
            $project_id = getSelectedProjectId();
        else            
            $_SESSION['PROJECT_ID'] = $project_id;    
        //$this->init($project_id);
        $this->crud->set_table('targeting')
                   ->columns('country', 'age_from', 'age_to', 'gender', 'collectionId', 'interests', 'job_titles', 'employers', 'fields_of_study', 'schools')
                   ->set_primary_key('shopifyId', 'collection')
                   ->set_relation('collectionId', 'collection', 'title', array('projectId' => $project_id))
                   ->set_relation('projectId', 'project', 'name', array('projectId' => $project_id))
                   ->display_as('title', 'Collection Name')
                   ->display_as('ageFrom', 'Age From')
                   ->display_as('ageTo', 'Age To')
                   ->display_as('collectionId', 'Collection')
                   ->display_as('interests', 'Interests')
                   ->display_as('projectId', 'Project')                   
                   ->where('targeting.projectId=' . $project_id)
                   ->set_subject('Targeting')
                   ->add_fields('projectId', 'country', 'age_from', 'age_to', 'gender', 'collectionId', 'interests', 'job_titles', 'employers', 'fields_of_study', 'schools');
                   //->edit_fields('projectId', 'country', 'ageFrom', 'ageTo', 'gender', 'collectionId', 'interests');

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    public function project_niche_link() {
        $this->crud->set_table('project_niche_link')
                   ->columns('projectId', 'nicheId', 'facebookPageId', 'lastPostDate')
                   ->set_relation('projectId', 'project', 'name')
                   ->set_relation('nicheId', 'niche', 'name')
                   ->set_relation('facebookPageId', 'facebook_page', 'name')
                   ->display_as('projectId', 'Project')
                   ->display_as('nicheId', 'Niche')
                   ->display_as('facebookPageId', 'Facebook Page')
                   ->display_as('lastPostDate', 'Last Post Date')
                   ->set_subject('Project Niche Link');

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    public function category() {
        $this->crud->set_table('category')
                   ->columns('name')
                   ->display_as('name', 'Name')                                      
                   ->set_subject('Category');        

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }
    
    public function niche() {
        $this->crud->set_table('niche')
                   ->columns('name', 'categoryId')
                   ->set_relation('categoryId', 'category', 'name')
                   ->display_as('name', 'Name')
                   ->display_as('categoryId', 'Category')
                   ->set_subject('Niche');        

        $output = $this->crud->render();
        $this->load->template('control_panel', $output);
    }   
    
}
?>