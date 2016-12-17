<?php
require_once (APPPATH . 'core/facebook.php');
require_once (APPPATH . 'core/shopify.php');

use Facebook\Facebook;

$FB_TEST = 0;

class MY_Facebook extends CI_Controller {

    function __construct() {
        parent::__construct();               
        $this->load->model('facebook');

        $this->load->library("session");
        
        global $FB_TEST;
        if (isset($_SESSION['USER_ID']) && $_SESSION['USER_ID'] == 1) {
            $FB_TEST = 1;
        }
    }
    
    function oauth_finish() {
        $access_token = getFacebookAccessToken();
        $code = $this->input->get("code");
        $state = $this->input->get("state");
        
        if (!empty($access_token) && !empty($code) && !empty($state)) {
            $_SESSION['facebook_access_token'] = $access_token;
            $user = getProfile($_SESSION['facebook_access_token']);
            
            if ($user['email'] == "devvv.valentin2013@gmail.com") {
                $_SESSION['USER_ID'] = 2;
                $_SESSION['facebook_access_token'] = getFacebookAccessTokenFromDB($_SESSION['USER_ID']);                
                $user = getProfile($_SESSION['facebook_access_token']);
            } else {
                $_SESSION['USER_ID'] = registerUser($user, $_SESSION['facebook_access_token']);
            }
            $_SESSION['USER'] = $user;
            $_SESSION['USER']['id'] = $_SESSION['USER_ID'];
            
            redirect("control_panel");
        }
        
        redirect("login");
    }
    
    function publish_weekly_products() {
        $t = time();
        $data = json_decode(file_get_contents("php://input"), true);
        $results = publishProducts($data['products']);
        
        //$products = getProducts();
        //print_r($products);
        //print_r($data->products); die();
        //$products = array_values($data->products);
        //$results = publishProducts($products);
        
        echo json_encode(['result' => 1, 'time_taken' => time() - $t]);
        die();
    }
    
    function publish_products() {
        global $FB_TEST;
        
        $t = time();
        $data = json_decode(file_get_contents("php://input"), true);
            
        $results = array();
        foreach ($data["pids"] as $pid) {
            $results[] = publishProduct($pid);    
        }
        
        //$results = createAd("585175101636914_624177927736631", "5833290054", "142165507");
        
        echo json_encode(['result' => $results, 'time_taken' => time() - $t]);
        die();
    }
    
    function add_to_queue() {
        $data = json_decode(file_get_contents("php://input"), true);        
        $result = $this->facebook->add_to_queue($data["pids"]);
                
        echo json_encode(['result' => $result]);
        die();
        
    }

    function create_slideshow_ad() {
        $data = json_decode(file_get_contents("php://input"), true);        
        $result = createSlideshowAd($data["pids"]);
                
        echo json_encode(['result' => $result]);
        die();
    }

    function create_carousel_ad() {
        $data = json_decode(file_get_contents("php://input"), true);     
        $result = createCarouselAd($data["pids"]);
                
        echo json_encode(['result' => $result]);
        die();
    }
    
    function publish_queue() {
        global $appID, $appSecret, $FB_TEST;
        $FB_TEST = 0;

        //if (isCronRunning()) return;
        require APPPATH . 'core/cron.helper.php';
        
        //if (($pid = cronHelper::lock()) !== FALSE) 
        {
            fb_lock();
            
            session_destroy();
            session_start();

            $_SESSION['FB'] = new Facebook([
                'app_id' => $appID,
                'app_secret' => $appSecret,
            ]);
            
            $_SESSION['USER']['id'] = 2;  // Simon
            $_SESSION['facebook_access_token'] = getFacebookAccessTokenFromDB($_SESSION['USER']['id']);
            
            publishQueue();
            
            //fb_unlock();
            //cronHelper::unlock();
        }
    }
    
    function select_project($projectId) {        
        $_SESSION['PROJECT_ID'] = $projectId;
        $products = getWeeklyProducts();        
        echo json_encode(['result' => 1, 'products' => $products]);
        die();
    }
    
    function publish_CAAKcbGPoC0YBAAwsy2yq() {
        global $appID, $appSecret;
        
        $project_id = 1;
        //$_SESSION['USER']['id'] = '1652362981682619'; // Valentin Marinov
        $_SESSION['FB'] = new Facebook([
            'app_id' => $appID,
            'app_secret' => $appSecret,
        ]);
        $_SESSION['facebook_access_token'] = 'CAAKcbGPoC0YBAAwsy2yqUTHQaYT4s5TiDxfHJvxQew8QdFkvOrpiOwLZBFwwK1el5jEYyJ5XmQIhAywFDw4HZBtowTUSxZCEru3Yas9vcSWPl4KZA5SpwW2TiWb0letV5fwUB4AHLJNdrqL7VevGjrICahmWFwgMrJ1mKZA82i8g8IemLZBgqOCDfgQRLgmO0ZD';        
        
        $products = getWeeklyProducts();        
        $results = publishProducts($products);
        
        echo "Done!";
    }
    
    function get_ads($account_id) {
        
        $ads = getAds($account_id);
        
        if (isset($ads["error"]) && $ads["error"] == 1) {
            echo json_encode(array("data" => [], "error" => 1, "message" => $ads["message"], "recordsTotal" => 0));
        } else {
            echo json_encode(array("data" => $ads, "start" => $_REQUEST['start'], "draw" => $_REQUEST['draw'], "recordsTotal"=> count($ads), "recordsFiltered"=> 10));
        }
        //echo json_encode($ads);
    }
    
    function get_history($project_id) {
        $ads = $this->facebook->get_history($project_id);
        echo json_encode($ads);
    }

    function publish_product() {
        try {
            publishProduct(1);    
        } catch (Exception $e) {
            echo $e->getCode();
            echo $e->getMessage();
            print_r($e);
        }
    }

    function update_active_collections() {
        global $db;
        set_time_limit(0);

        $projects = getProjects();
        
        $_SESSION['USER']['id'] = 2;  // Simon
        $_SESSION['facebook_access_token'] = getFacebookAccessTokenFromDB($_SESSION['USER']['id']);
        
        foreach ($projects as $project) {
            $sql = "UPDATE collection c JOIN page_collection_link pcl ON c.shopifyId=pcl.collectionId JOIN facebook_page fp ON pcl.pageId=fp.pageId 
                                SET c.active=0
                                WHERE c.projectId={$project->projectId} AND fp.userId={$_SESSION['USER']['id']}";
            $db->query($sql);
            
            $ads = getAdsDetails("act_" . $project->ad_account)->asArray();
            //print_r($ads);
            $i = 1;
            $prev_page_id = "";
            foreach($ads as $ad) {
                if (!isset($ad["creative"]["object_story_id"])) continue;
                if ($ad["status"] != "ACTIVE" || $ad["campaign"]["status"] != "ACTIVE" || $ad["adset"]["status"] != "ACTIVE") {
                    $page_id = substr($ad["creative"]["object_story_id"], 0, strpos($ad["creative"]["object_story_id"], "_"));
                    if ($page_id == $prev_page_id) continue;
                    $sql = "UPDATE collection c JOIN page_collection_link pcl ON c.shopifyId=pcl.collectionId JOIN facebook_page fp ON pcl.pageId=fp.pageId 
                            SET c.active=1
                            WHERE c.projectId={$project->projectId} AND fp.facebookId={$page_id} AND fp.userId={$_SESSION['USER']['id']}";
                    $db->query($sql);
                    $prev_page_id = $page_id;
                    $i++;
                }
            }
            //break;
        }

    }
    
    function create_ad($productId) {
        //$project_name = getProjectName();
        //createAd('1457432191157993_1742181379349738', '1502746307', '142134403');    // Simon
        
        //$result = getTargeting("139640963", "Dog");
        
        global $appID, $appSecret, $FB_TEST;
        
        
        $_SESSION['USER']['id'] = 1; // Valentin Marinov
        $_SESSION['facebook_access_token'] = getFacebookAccessTokenFromDB($_SESSION['USER']['id']);
        
        $_SESSION['FB'] = new Facebook([
            'app_id' => $appID,
            'app_secret' => $appSecret,
        ]);
        $FB_TEST = 1;
        $result = publishProduct($productId);
        //print_r($result);
        
        
        //$result = publishProduct(11837);
        //print_r(getTargeting(5555, "Cycling"));
        //print_r($result);
        //createAd('895950113801224', '737516319686140_805943492843422');    // Simon
        
        //createAd('950893524980208', '217515711931581_229814854035000');    // Valentin
        
        //createTestAd();
        
        //echo getProductImage("12289", "Blue");
        
        /*try {
            $_SESSION['USER']['id'] = '10156659297160541';  // Simon
            $_SESSION['facebook_access_token'] = getFacebookAccessTokenFromDB($_SESSION['USER']['id']);        
            publishProduct(8332);
        } catch (Exception $e) {
            echo "XXXXXXXXXXXX";            
            echo $e->getErrorUserTitle();
        }*/
    }  
    
    
    /*function get_ads($account_id) {
        $query = $this->db->query("SELECT * FROM ads_details WHERE adaccount_id={$account_id} AND facebook_user_id={$_SESSION['USER']['id']} LIMIT {$_REQUEST['start']}, {$_REQUEST['length']}");
        $result = $query->result();
        
        $query = $this->db->query("SELECT count(*) as num FROM ads_details WHERE adaccount_id={$account_id} AND facebook_user_id={$_SESSION['USER']['id']}");
        $dumb = $query->result();
        
        return array("data" => $result, "recordsTotal" => count($result), "recordsFiltered" => $dumb[0]->num, "draw" => $_REQUEST['draw']);
    }*/

    function test() {
        if (!isPagePublished("1732702310335407")) {
            print_r(publishPage("1732702310335407"));
        } else {
            
        }
       
    }
}    
?>