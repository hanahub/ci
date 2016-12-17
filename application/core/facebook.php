<?php

require_once (APPPATH . 'libraries/vendor/autoload.php');

define('NUM_PRODUCTS_LOADING', 5);
        
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use FacebookAds\Http\Exception\RequestException;

use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\AdCreative;
use FacebookAds\Object\Fields\AdCreativeFields;
use FacebookAds\Object\Values\BillingEvents;
use FacebookAds\Object\Fields\TargetingSpecsFields;
use FacebookAds\Object\TargetingSpecs;
use FacebookAds\Object\Values\OptimizationGoals;
use FacebookAds\Object\Values\PageTypes;

use FacebookAds\Object\Values\AdObjectives;
use FacebookAds\Object\TargetingSearch;
use FacebookAds\Object\Search\TargetingSearchTypes;
use FacebookAds\Object\AdImage;
use FacebookAds\Object\Fields\AdImageFields;
use FacebookAds\Object\Fields\AdAccountFields;

use FacebookAds\Object\AdVideo;
use FacebookAds\Object\Fields\AdVideoFields;
use FacebookAds\Object\Values\CampaignObjectiveValues;
use FacebookAds\Object\Values\AdSetBillingEventValues;
use FacebookAds\Object\Values\AdSetOptimizationGoalValues;

use FacebookAds\Object\AdCreativeVideoData;
use FacebookAds\Object\Fields\AdCreativeVideoDataFields;
use FacebookAds\Object\AdCreativeObjectStorySpec;
use FacebookAds\Object\Fields\AdCreativeObjectStorySpecFields;
use FacebookAds\Object\Values\AdCreativeCallToActionTypeValues;

use FacebookAds\Object\Fields\AdCreativeLinkDataFields;
use FacebookAds\Object\Fields\AdCreativeLinkDataChildAttachmentFields;
use FacebookAds\Object\AdCreativeLinkDataChildAttachment;
use FacebookAds\Object\AdCreativeLinkData;

date_default_timezone_set('UTC');

$appID = '734939299973958';                                   //supermug
$appSecret = '74dcb8a602900ab3c4cce9a4203d674b';

$appID = '1578743819036248';                                   // staging goop
$appSecret = 'ceaba7e408b9535543c1802079c39ae8';

$db = null;
$fb = new Facebook([
        'app_id' => $appID, // Replace {app-id} with your app id
        'app_secret' => $appSecret
    ]);

$rightArrow = '➡';
$rightTopArrow = '↗';

function getFacebookAccessToken() {
    
    global $appID, $appSecret; 
    
    $fb = new Facebook([
        'app_id' => $appID, // Replace {app-id} with your app id
        'app_secret' => $appSecret
    ]);

    $helper = $fb->getRedirectLoginHelper();

    try {
        $accessToken = $helper->getAccessToken();
    } catch(FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    if (! isset($accessToken)) {
        if ($helper->getError()) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Error: " . $helper->getError() . "\n";
            echo "Error Code: " . $helper->getErrorCode() . "\n";
            echo "Error Reason: " . $helper->getErrorReason() . "\n";
            echo "Error Description: " . $helper->getErrorDescription() . "\n";
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo 'Bad request';
        }
        exit;
    }

    
    // The OAuth 2.0 client handler helps us manage access tokens
    $oAuth2Client = $fb->getOAuth2Client();

    // Get the access token metadata from /debug_token
    $tokenMetadata = $oAuth2Client->debugToken($accessToken);

    // Validation (these will throw FacebookSDKException's when they fail)
    $tokenMetadata->validateAppId($appID); // Replace {app-id} with your app id
    // If you know the user ID this access token belongs to, you can validate it here
    //$tokenMetadata->validateUserId('123');
    $tokenMetadata->validateExpiration();

    if (! $accessToken->isLongLived()) {
        // Exchanges a short-lived access token for a long-lived one
        try {
            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
        } catch (FacebookSDKException $e) {
            echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
            exit;
        }
    }

    return (string) $accessToken;
}

function getFacebookLoginURL($redirect = '') {  
    
    global $appID, $appSecret; 
    
    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);     
    
    $helper = $fb->getRedirectLoginHelper();
    //$permissions = ['ads_management', 'email', 'ads_read', 'user_friends', 'publish_pages', 'manage_pages', 'publish_actions', 'pages_show_list'];
    $permissions = ['ads_management', 'email', 'ads_read', 'manage_pages', 'publish_pages', 'publish_actions'];
    $loginUrl = $helper->getLoginUrl($redirect, $permissions);
    
    return $loginUrl;
}

function registerAccessToken($uid, $token) {
    global $db;    
    if (empty($db)) $db = DB();
    
    $query = $db->query("SELECT * FROM options WHERE meta_key='{$uid}'");
    if ($query->num_rows() > 0) {
        $db->query("UPDATE options SET meta_value='{$token}' WHERE meta_key='{$uid}'");
    } else {        
        $db->query("INSERT INTO options(meta_key, meta_value) VALUES ('{$uid}', '{$token}')");
    }
}

function registerUser($user, $token) {
    global $db;    
    if (empty($db)) $db = DB();
    
    $query = $db->get_where("users", array("email" => $user['email']));
    if ($query->num_rows() > 0) {
        $db->where(array("email" => $user['email']));
        $result = $db->update("users", array(
                "facebook_uid" => $user["id"],
                "name" => $user["name"],
                "picture" => $user['picture']['url'],
                "facebook_access_token" => $token,
            ));
        
        $result = $query->result_array();
        $user_id = $result[0]['id'];
    } else {
        $result = $db->insert("users", array(
                "facebook_uid" => $user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "picture" => $user['picture']['url'],
                "facebook_access_token" => $token,
            ));
        $user_id = $db->insert_id();
    }
    return $user_id;
}

function getUserID($user) {
    global $db;    
    if (empty($db)) $db = DB();

    $user_id = 0;
    $query = $db->query("SELECT * FROM users WHERE email='{$user['email']}'");
    if ($query->num_rows() > 0) {
        $result = $query->result_array();
        $user_id = $result[0]['id'];
    }
    return $user_id;
}
function getFacebookAccessTokenFromDB($uid) {
    global $db;    
    if (empty($db)) $db = DB();
        
    $query = $db->query("SELECT * FROM users WHERE id='{$uid}'");
    if ($query->num_rows() > 0) {
        $result = $query->result_array();
        return $result[0]['facebook_access_token'];
    }
    
    return '';
}

function getAdAccount($token) {
    global $appID, $appSecret;
    
    Api::init(
        $appID,
        $appSecret,
        $token
    );    
    
    // Add after Api::init()
    $me = new AdUser('me');
    $my_adaccount = $me->getAdAccounts()->current();  
    
    return $my_adaccount->getData();
}

function getMyAdAccounts($token) {    
    global $appID, $appSecret;

    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);
            
    $response = $fb->get('/me/adaccounts?fields=id,account_id,name,currency,timezone_name,users', $_SESSION['facebook_access_token']);
    $results = $response->getGraphEdge();
    
    return $results;   
}

function getProfile($token) {
    
    global $appID, $appSecret;
    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);

    try {        
        $response = $fb->get('/me?fields=id,name,email,picture', $token);
    } catch(FacebookResponseException $e) {
        session_destroy();
        redirect("login"); //echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        session_destroy();
        redirect("login");
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    $user = $response->getGraphUser();
    
    return $user;
}

function getBusiness($token) {
    
    global $appID, $appSecret;
    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);

    $response = $fb->get('/me/businesses', $token);
    $businesses = $response->getGraphEdge();
    
    return $businesses;
}

function getMyBusiness($token, $username) {
    $businesses = getBusiness($token);
    $businesses = $businesses->asArray();
    
    foreach ($businesses as $business) {
        if ($business['name'] == $username) {
            return $business;
        }
    }
    
    return $businesses[0];
}

function getAdsCreative($account_id) {
    global $fb;
    $response = $fb->get("/$account_id/ads?fields=creative&limit=999999", $_SESSION['facebook_access_token']);
    $results = $response->getGraphEdge();
    return $results;   
}

function isPagePublished($page_id) {
    global $fb;
    $response = $fb->get("/$page_id?fields=is_published", $_SESSION['facebook_access_token']);
    $results = $response->getGraphNode()->asArray();
    return $results["is_published"];
}

function getVideoStatus($video_id) {
    global $fb;
    $response = $fb->get("/$video_id?fields=status", $_SESSION['facebook_access_token']);
    $results = $response->getGraphNode()->asArray();
    return $results["status"]["video_status"];
}

function publishPage($page_id) {
    global $fb;
    
    $page_access_token = getPageAccessToken($page_id, $_SESSION['facebook_access_token']);
    $data = [
        'is_published' => true
    ];
    $response = $fb->post("/$page_id", $data, $page_access_token);
    return $response;
}

function getCreativeDetails($creative_id) {
    global $fb;
    $response = $fb->get("/$creative_id?fields=name,object_story_id,run_status", $_SESSION['facebook_access_token']);
    $results = $response->getGraphNode();
    return $results;   
}

function getAdsDetails($account_id) {
    global $fb;
    $response = $fb->get("/$account_id/ads?fields=name,campaign{status},adset{status},status,creative{object_story_id}&limit=999999", $_SESSION['facebook_access_token']);
    $results = $response->getGraphEdge();
    return $results;   
}

function getPageAccessToken($id, $token) {
    global $appID, $appSecret;
    $fb = $_SESSION['FB'];
    
    $response = $fb->get('/' . $id . '?fields=access_token', $token);    
    $result = $response->getGraphNode();    

    return $result['access_token'];
}

function getPages($offset = 0, $limit = 25, $byId = 0) {
    global $appID, $appSecret;
    
    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);
    
    //$response = $fb->get('/' . $_SESSION['BUSINESS_ID'] . '/pages?fields=id,name,likes,about,link,picture&offset=' . $offset . '&limit=' . $limit, $_SESSION['facebook_access_token']);
    $response = $fb->get('/me/accounts?fields=id,name,likes,about,link,picture,is_published&offset=' . $offset . '&limit=' . $limit, $_SESSION['facebook_access_token']);
    $results = $response->getGraphEdge();
    
    $i = 0;
    $pages = [];
    foreach ($results as $result) {        
        
        if ($result['is_published']) {
            if (empty($result['likes'])) $result['likes'] = 0;
            if ($byId == 1)
                $pages[$result['id']] = $result->asArray();
            else
                $pages[$i] = $result->asArray();
            $i++;
        }
    }
    
    return $pages;
}

function _import_pages($project_id = 1, $limit = 999999999) {
    global $appID, $appSecret, $db;
 
    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);
    
    if (empty($db)) $db = DB();

    $project_id = getSelectedProjectId();    
    $response = $fb->get('/me/accounts?fields=id,name,likes,about,link,picture,is_published&limit=' . $limit, $_SESSION['facebook_access_token']);
    $results = $response->getGraphEdge();    
    
    foreach ($results as $result) {                
        if ($result['is_published']) {            
            $query = $db->query("SELECT pageId FROM facebook_page WHERE projectId=$project_id AND userId={$_SESSION['USER']['id']} AND facebookId='{$result['id']}'");
            if ($query->num_rows() == 0) {
                $page = $result->asArray();
                $value = array(
                            'projectId'     => $project_id,
                            'facebookId'    => $page['id'],
                            'name'          => $page['name'],
                            'url'           => $page['link'],
                            'userId'        => $_SESSION['USER']['id'],
                            'picture'       => $page['picture']['url'],
                        );
                $data[] = $value;
            }
        }
    }

    if (!empty($data)) {
        $db->insert_batch("facebook_page", $data);
    }
}

function getProjects() {
    global $db;
    if (empty($db)) $db = DB();
    $query = $db->query("SELECT * FROM project");
    $projects = $query->result();
    return $projects;
}

function getShopifyProducts($since_id = '', $limit = 10) {
    $sc = $_SESSION['SC'];
    $param = array(
                    'fields' => 'id,title,image,published_at,handle',
                    'limit' => $limit,
                    'published_status' => 'published',                    
                );
    
    if (!empty($since_id)) $param['since_id'] = $since_id;
    
    $products = $sc->call('GET', '/admin/products.json', $param, '');
    
    foreach($products as $key => $product) {
                
        $metafield = $sc->call('GET', '/admin/products/' . $product['id'] . '/metafields.json', 
                    array(
                        'key' => 'facebook_published',
                    )
                );
        
        if (!empty($metafield) && $metafield[0]['value'] == 1) {
             unset($products[$key]);
             continue;
        }
        
        if (isset($product['image']['src'])) {
            $str = $product['image']['src'];
            $last_slash_pos = strripos($str, '/');                
            $last_dot_pos = strripos($str, '.');
            
            $filename = substr($str, $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1);
            $small_filename = substr_replace($str, $filename . '_small', $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1 );
        
            $products[$key]['published_at'] = date('D, j M Y H:i:s \G\M\T', strtotime($products[$key]['published_at']));
            $products[$key]['image']['small_src'] = $small_filename;            
            $products[$key]['link'] = SHOPIFY_STORE . "products/" . $products[$key]['handle'];
            $products[$key]['content'] = "NEW {COLLECTION} MUG!
Order Here: {LINK}
Click The Link Above To Order!";
        }
    }
    
    return $products;
}

function getProjectDetails($project_id = 0) {
    global $db;
    if (empty($db)) $db = DB();
    if ($project_id == 0) $project_id = getSelectedProjectId();
    
    $query = $db->query("SELECT * FROM project WHERE projectId=" . $project_id);
    $result = $query->result_array();
    return $result[0];
}

function getProjectName() {
    $project_id = getSelectedProjectId();
    $db = DB();
    $query = $db->query("SELECT name FROM project WHERE projectId=" . $project_id);
    $result = $query->result();
    return $result[0]->name;
}

function getProjectURL() {
    $project_id = getSelectedProjectId();
    $db = DB();
    $query = $db->query("SELECT url FROM project WHERE projectId=" . $project_id);
    $result = $query->result();
    return $result[0]->url;
}

function getCollectionName($id) {
    $project_id = getSelectedProjectId();
    $db = DB();
    $query = $db->query("SELECT title FROM collection WHERE shopifyId=" . $id . " AND projectId=$project_id");
    $result = $query->result();
    return $result[0]->title;
}
                   
function getProductName($id) {
    $project_id = getSelectedProjectId();
    $db = DB();
    $query = $db->query("SELECT title FROM product WHERE shopifyId=" . $id . " AND projectId=$project_id");
    $result = $query->result();
    return $result[0]->title;
}

function getCollections($product_id) {
    $project_id = getSelectedProjectId();
    global $db;
    $query = $db->query("SELECT * FROM product_collection_link INNER JOIN collection on product_collection_link.collectionId=collection.collectionId WHERE productId={$product_id} AND collection.projectId={$project_id}");
    $result = $query->result_array();    
    return $result;
}

function getCollectionPages($collection_ids) {
    $project_id = getSelectedProjectId();
    global $db;
    //$ids = implode(',', $collection_id);
    $ids = $collection_ids;
    $query = $db->query("SELECT * FROM page_collection_link INNER JOIN facebook_page on page_collection_link.pageId=facebook_page.pageId
                            INNER JOIN collection on page_collection_link.collectionId=collection.shopifyId WHERE collection.projectId={$project_id} AND collection.shopifyId in (" . $ids . ")");
    $result = $query->result_array();
    
    return $result;
}

function getSelectedProjectId() {
    if (isset($_SESSION['PROJECT_ID']) && $_SESSION['PROJECT_ID'] != 0)
        return $_SESSION['PROJECT_ID'];
    else
        return 1;
}

function setCurrentProject($id) {
    $_SESSION['PROJECT_ID'] = $id;
}

function getProjectIdFromProduct($pid) {
    global $db;
    
    $query = $db->query("SELECT projectId FROM product WHERE productId=" . $pid);
    $result = $query->result_array();
    return $result[0]['projectId'];
}

function getShopifyClient($projectId) {        
    $project = getProjectDetails($projectId);
    
    return new ShopifyClient($project['shop_domain'], $project['token'], $project['api_key'], $project['secret']);
}

function getSelectedProjectColor() {
    global $db;
    $project_id = getSelectedProjectId();
    
    $query = $db->query("SELECT color FROM project WHERE projectId=" . $project_id);
    $result = $query->result();
    return $result[0]->color;
}

function getFacebookPageId($id) {
    global $db;
    $project_id = getSelectedProjectId();
    
    $query = $db->query("SELECT facebookId FROM facebook_page WHERE pageId=" . $id);
    $result = $query->result();
    return $result[0]->facebookId;
}

function getLastSubmittedProductId($page_id, $collection_id, $db) {
    $query = $db->query("SELECT productId FROM publish_product WHERE pageId='" . $page_id . "' AND collectionId='" . $collection_id . "' ORDER BY publishedAt DESC LIMIT 1");
    //echo "SELECT productId FROM publish_product WHERE pageId='" . $page_id . "' AND collectionId='" . $collection_id . "' ORDER BY published_at DESC LIMIT 1";
    if ($query->num_rows() > 0) {
        $dumb = $query->result_array();
        return $dumb[0]['productId'];
    } else {
        return 0;
    }
}

function getWeeklyProducts($project_id = 0) {
    $t = time();
    $db = DB();
    if ($project_id == 0) $project_id = getSelectedProjectId();
    $query = $db->query("SELECT * FROM page_collection_link AS pcl LEFT JOIN collection AS c ON pcl.collectionId=c.shopifyId
                            JOIN facebook_page AS fb ON fb.pageId=pcl.pageId
                            WHERE userId='" . $_SESSION['USER']['id'] . "' AND c.projectId={$project_id}");
    
    if ($query->num_rows() > 0) {
        $collections = $query->result_array();
        $pids = []; $cids = [];
        foreach ($collections as $collection) {            
            $last_pid = getLastSubmittedProductId($collection['facebookId'], $collection['shopifyId'], $db);            
            $query2 = $db->query("SELECT p.productId FROM product as p
                JOIN product_collection_link AS pc ON p.shopifyId=pc.productId
                JOIN collection AS c ON c.shopifyId=pc.collectionId WHERE c.shopifyId='" . $collection['shopifyId'] . "' AND p.productId>'" . $last_pid . "' ORDER BY p.productId ASC LIMIT 1;");
                
            if ($query2->num_rows() > 0) {
                $dumb = $query2->result_array();
                $pids[] = $dumb[0]['productId'];
                $cids[$dumb[0]['productId']] = $collection['collectionId'];
                $cnames[$dumb[0]['productId']] = $collection['title'];
                //echo $dumb[0]['shopifyId'] . '/' . $collection['shopifyId'] . '///';
            } else {
                //No product in this collection
                //echo "Email collection for " . $collection['shopifyId'] . "<br/>";
                //print_r($collection);
            }
        }
        
        if (empty($pids)) return null;
        $sql = "
                    SELECT * FROM (
                        SELECT p.productId, GROUP_CONCAT(fb.name ORDER BY fb.pageId) AS page_name, GROUP_CONCAT(fb.url ORDER BY fb.pageId) AS page_url, GROUP_CONCAT(fb.facebookId ORDER BY fb.pageId) AS facebookId FROM product AS p
                        JOIN product_collection_link AS pc ON p.productId=pc.productId
                        JOIN collection AS c ON c.collectionId=pc.collectionId
                        LEFT JOIN page_collection_link AS pcl ON pcl.collectionId=c.collectionId
                        LEFT JOIN facebook_page AS fb ON fb.pageId=pcl.pageId
                        WHERE p.projectId={$project_id} AND fb.pageId IS NOT NULL AND fb.userId='" . $_SESSION['USER']['id'] . "'
                        GROUP BY p.productId
                        ORDER BY p.productId
                    ) AS c1
                    INNER JOIN (
                        SELECT p.*, GROUP_CONCAT(c.title ORDER BY c.collectionId) AS collections 
                        FROM product AS p
                        JOIN product_collection_link AS pc ON p.productId=pc.productId
                        JOIN collection AS c ON c.collectionId=pc.collectionId
                        GROUP BY p.productId
                        ORDER BY p.productId 
                    ) AS c2 ON c1.productId=c2.productId
                    WHERE c2.productId in (" . implode(",", $pids) . ") LIMIT 2;";
        //echo $sql;            
        $query = $db->query($sql);
        
        if ($query->num_rows() > 0) {
            $products = $query->result_array();         
            
            $store_url = getProjectURL();
                
            foreach ($products as $key => $product) {            
                if (isset($products[$key]['image'])) {
                    $product['page_name'] = explode(',', $product['page_name']);
                    $product['page_url'] = explode(',', $product['page_url']);
                    $product['facebookId'] = explode(',', $product['facebookId']);                    
                    $products[$key]['collections'] = str_replace(',', ", ", $product['collections']);                    
                    $products[$key]['collection_id'] = $cids[$product['shopifyId']];
                    $products[$key]['collection_name'] = $cnames[$product['shopifyId']];
                    
                    $pages = [];
                    for ($i = 0; $i < count($product['page_name']); $i++) {
                        $pages[] = array(
                                        'name' => $product['page_name'][$i],
                                        'url' => $product['page_url'][$i],
                                        'facebookId' => $product['facebookId'][$i],
                                    );
                        break;
                    }
                    $products[$key]['pages'] = $pages;
                    
                    $str = $products[$key]['image'];
                    $last_slash_pos = strripos($str, '/');                
                    $last_dot_pos = strripos($str, '.');                    
                    $filename = substr($str, $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1);
                    $small_filename = substr_replace($str, $filename . '_small', $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1 );
                
                    $products[$key]['published_at'] = date('D, j M Y H:i:s \G\M\T', strtotime($products[$key]['published_at']));
                    $products[$key]['small_image'] = $small_filename;            
                    $products[$key]['link'] = $store_url . "/products/" . $products[$key]['handle'];
                    $products[$key]['content'] = "NEW " . strtoupper($products[$key]['collection_name']) . " MUG!
Order Here: {LINK}
Click The Link Above To Order!";
                    }                        
            }
        } //print_r($products);
    }
    
    //echo time() - $t;    
    if (!empty($products))
        return array_values($products);
    else
        return null;    
}

function getProducts($project_id = 0) {           
    $db = DB();
    $t = time();
    if ($project_id == 0) $project_id = getSelectedProjectId();
    $query = $db->query("
        SELECT * FROM (
            SELECT p.id, GROUP_CONCAT(fb.name ORDER BY fb.pageId) AS page_name, GROUP_CONCAT(fb.url ORDER BY fb.pageId) AS page_url, GROUP_CONCAT(fb.facebookId ORDER BY fb.pageId) AS facebookId FROM product AS p
            JOIN product_collection_link AS pc ON p.shopifyId=pc.productId
            JOIN collection AS c ON c.shopifyId=pc.collectionId
            LEFT JOIN page_collection_link AS pcl ON pcl.collectionId=c.collectionId
            LEFT JOIN facebook_page AS fb ON fb.pageId=pcl.pageId
            WHERE p.projectId={$project_id} AND fb.pageId IS NOT NULL AND fb.userId='" . $_SESSION['USER']['id'] . "'
            GROUP BY p.id
            ORDER BY p.id
        ) AS c1
        INNER JOIN (
            SELECT p.*, GROUP_CONCAT(c.title ORDER BY c.shopifyId) AS collections 
            FROM product AS p
            JOIN product_collection_link AS pc ON p.shopifyId=pc.productId
            JOIN collection AS c ON c.shopifyId=pc.collectionId
            GROUP BY p.id
            ORDER BY p.id 
        ) AS c2 ON c1.id=c2.id;
    ");
    
    if ($query->num_rows() > 0) {
        $products = $query->result_array();
        $store_url = getProjectURL();
            
        foreach ($products as $key => $product) {            
            if (isset($products[$key]['image'])) {
                $product['page_name'] = explode(',', $product['page_name']);
                $product['page_url'] = explode(',', $product['page_url']);
                $product['facebookId'] = explode(',', $product['facebookId']);
                $products[$key]['collections'] = str_replace(',', ", ", $product['collections']);
                
                $pages = [];
                for ($i = 0; $i < count($product['page_name']); $i++) {
                    $pages[] = array(
                                    'name' => $product['page_name'][$i],
                                    'url' => $product['page_url'][$i],
                                    'facebookId' => $product['facebookId'][$i],
                                );
                }
                $products[$key]['pages'] = $pages;
                
                $str = $products[$key]['image'];
                $last_slash_pos = strripos($str, '/');                
                $last_dot_pos = strripos($str, '.');                    
                $filename = substr($str, $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1);
                $small_filename = substr_replace($str, $filename . '_small', $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1 );
            
                $products[$key]['published_at'] = date('D, j M Y H:i:s \G\M\T', strtotime($products[$key]['published_at']));
                $products[$key]['small_image'] = $small_filename;
                $products[$key]['link'] = $store_url . "/products/" . $products[$key]['handle'];                
                $products[$key]['content'] = "NEW CYCLING MUG!
Order Here: {LINK}
Click The Link Above To Order!";
                }                        
        }
    } //print_r($products);
    
    
    if (!empty($products))
        return array_values($products);
    else
        return null;
}

function publishProducts($products) {    
    $fb = $_SESSION['FB']; 
    $db = DB();
    
    if (empty($products)) return;
    foreach ($products as $product) {
        $product_id = $product['shopifyId'];
        $message = str_replace("{LINK}", $product['link'], $product['content']);
        
        foreach ($product['pages'] as $page) {
            $page_id = $page['facebookId'];
            $collection_id = $product['collection_id'];
            $query = $db->query("SELECT * FROM publish_product WHERE productId=" . $product_id . " AND pageId='" . $page_id . "' AND collectionId='" . $collection_id . "'");
            if ($query->num_rows() == 0) {
                $page_access_token = getPageAccessToken($page_id, $_SESSION['facebook_access_token']);                
                
                $data = [
                    'published' => true,
                    'message' => $message,
                    'source' => $fb->fileToUpload($product['image']),
                ];
                $response = $fb->post('/' . $page_id . '/photos', $data, $page_access_token);
                
                $result = $response->getDecodedBody();
                $post_id = $result["post_id"];
                
                //createAd($post_id, $product_id, $collection_id);
                
                $data2 = array( 'productId' => $product_id, 'pageId' => $page_id, 'collectionId' => $collection_id, 'postId' => $post_id );
                $db->insert('publish_product', $data2);
            }
        }
    }       
}

function getProductCollection($product_id) {
    try {
        $result = $_SESSION['SC']->call('GET', '/admin/smart_collections.json?fields=id,title&product_id=' . $product_id);
        $result2 = $_SESSION['SC']->call('GET', '/admin/custom_collections.json?fields=id,title&product_id=' . $product_id);
        $result = array_merge($result, $result2);

        foreach ($result as $c) {
            if ($c["title"] != "Frontpage" && $c["title"] != "Keep Calm" && $c["title"] != "ALL" && $c["title"] != "BEST SELLING" && $c["title"] != "Latest") {
                $r[] = $c;
            }
        }
    } catch (Exception $e) {
        print_r($e);
    }
    return $r;
}

function getProductPrice($product_id) {
    try {
        $result = $_SESSION['SC']->call('GET', "/admin/products/{$product_id}.json?fields=variants");
        if (count($result["variants"]) == 1) {
            return $result["variants"]["price"];
        } else {
            return $result["variants"][0]["price"];
        }
        
    } catch (Exception $e) {
        print_r($e);
    }
    return 0;
}

function getTargeting($collection_id, $collection_name) {
    global $db;
    if (empty($db)) $db = DB();
        
    $project_id = getSelectedProjectId();
    
    $query = $db->query("SELECT * FROM targeting WHERE projectId=$project_id AND collectionId=$collection_id LIMIT 1");
    if ($query->num_rows() > 0) {
        $dumb = $query->result_array()[0];
        $result = array("min" => $dumb["age_from"], "max" => $dumb["age_to"], "gender" => $dumb["gender"], "interests" => explode(", ", $dumb["interests"]), 
            "job_titles" => explode(", ", $dumb["job_titles"]), "employers" => explode(", ", $dumb["employers"]), "fields_of_study" => explode(", ", $dumb["fields_of_study"]), "schools" => explode(", ", $dumb["schools"]), "country" => $dumb["country"]);
    } else {
        $query = $db->query("SELECT * FROM project AS p JOIN targeting AS t ON p.default_target=t.id WHERE p.projectId=$project_id");
        if ($query->num_rows() > 0) {
            $dumb = $query->result_array()[0];
            $dumb["interests"] = $collection_name;               
            $result = array("min" => $dumb["age_from"], "max" => $dumb["age_to"], "gender" => $dumb["gender"], "interests" => explode(", ", $dumb["interests"]),
                "job_titles" => explode(", ", $dumb["job_titles"]), "employers" => explode(", ", $dumb["employers"]), "fields_of_study" => explode(", ", $dumb["fields_of_study"]), "schools" => explode(", ", $dumb["schools"]), "country" => $dumb["country"]);
        } else {
            $result = array("min" => 40, "max" => 60, "gender" => "Women", "interests" => array($collection_name), "job_titles" => array($collection_name), "employers" => array($collection_name), "fields_of_study" => array($collection_name), "schools" => array($collection_name), "country" => "GB");    
        }
    }
    
    if ($result["gender"] == "Men") $gender = 1;
    else if ($result["gender"] == "Women") $gender = 2;
    else $gender = 0;
    
    $result["gender"] = $gender;
    
    return $result;
}

function getPagePostText() {
    global $db;
    
    $project_id = getSelectedProjectId();    
    $query = $db->query("SELECT ad_text FROM project WHERE projectId=$project_id");
    
    return $query->result_array()[0]['ad_text'];
}

function getProductImage($pid, $color) {
    global $db;
    if (empty($db)) $db = DB();
    
    $projectId = getProjectIdFromProduct($pid);
    $sc = getShopifyClient($projectId);
    
    $query = $db->query("SELECT shopifyId FROM product WHERE productId={$pid}");
    $dumb = $query->result_array()[0];
    $shopifyId = $dumb['shopifyId'];
    $product = $sc->call('GET', "/admin/products/{$shopifyId}.json");
    
    $image_index = 0;
    foreach ($product["options"] as $option) {
        if ($option["name"] == "Colour" || $option["name"] == "Color") {
            $values = $option["values"];
            foreach ($values as $key => $value) {
                if ($value == $color) {
                    $image_index = $key;
                }
            }
        }
    }
    
    if (!empty($product["images"][$image_index]["src"])) $result = $product["images"][$image_index]["src"];
    else $result = "";
    return $result;
}

function getAds($ad_account) {
    global $appID, $appSecret, $db;
    Api::init(
        $appID,
        $appSecret,
        $_SESSION['facebook_access_token']
    );
    
    if (empty($db)) $db = DB();
    
    $account = new AdAccount('act_' . $ad_account);
    
    try {
        $cursor = $account->getAds(
            array(
                AdFields::NAME,
                AdFields::ACCOUNT_ID,
                AdFields::ADSET_ID,
                AdFields::CAMPAIGN_ID,
                "creative",
            ),
            array(
                'limit' => 5000000,
            ));
    
        $i = 0;
        $cursor->setUseImplicitFetch(true);
        $cursor->end();
        
        $ads = array();
        while ($cursor->valid()) {
            
            $ad_id = $cursor->current()->{AdFields::ID};    
            $ad_name = $cursor->current()->{AdFields::NAME};    
            $adset_id = $cursor->current()->{AdFields::ADSET_ID};
            $campaign_id = $cursor->current()->{AdFields::CAMPAIGN_ID};
            $creative = $cursor->current()->creative;
            
            $ads[$i] = array('ad_id' => $ad_id, 'ad_name' => $ad_name, 'adset_id' => $adset_id, 'campaign_id' => $campaign_id, 'creative' => $creative);
                                                     
            $cursor->prev();
            $i++;
        }
        
        return $ads;
        
    } catch(Exception $e) {        
        return (array("error" => 1, "message" => $e->getMessage()));
        exit;
    }    
}

function getProduct($project_id = 0) {
    $db = DB();
    $t = time();
    if ($project_id == 0) $project_id = getSelectedProjectId();
    $query = $db->query("
        SELECT * FROM (
            SELECT p.id, GROUP_CONCAT(fb.name ORDER BY fb.pageId) AS page_name, GROUP_CONCAT(fb.url ORDER BY fb.pageId) AS page_url, GROUP_CONCAT(fb.facebookId ORDER BY fb.pageId) AS facebookId FROM product AS p
            JOIN product_collection_link AS pc ON p.shopifyId=pc.productId
            JOIN collection AS c ON c.shopifyId=pc.collectionId
            LEFT JOIN page_collection_link AS pcl ON pcl.collectionId=c.collectionId
            LEFT JOIN facebook_page AS fb ON fb.pageId=pcl.pageId
            WHERE p.projectId={$project_id} AND fb.pageId IS NOT NULL AND fb.userId='" . $_SESSION['USER']['id'] . "'
            GROUP BY p.id
            ORDER BY p.id
        ) AS c1
        INNER JOIN (
            SELECT p.*, GROUP_CONCAT(c.title ORDER BY c.shopifyId) AS collections 
            FROM product AS p
            JOIN product_collection_link AS pc ON p.shopifyId=pc.productId
            JOIN collection AS c ON c.shopifyId=pc.collectionId
            GROUP BY p.id
            ORDER BY p.id 
        ) AS c2 ON c1.id=c2.id;
    ");
    
    if ($query->num_rows() > 0) {
        $products = $query->result_array();
        $store_url = getProjectURL();
            
        foreach ($products as $key => $product) {            
            if (isset($products[$key]['image'])) {
                $product['page_name'] = explode(',', $product['page_name']);
                $product['page_url'] = explode(',', $product['page_url']);
                $product['facebookId'] = explode(',', $product['facebookId']);
                $products[$key]['collections'] = str_replace(',', ", ", $product['collections']);
                
                $pages = [];
                for ($i = 0; $i < count($product['page_name']); $i++) {
                    $pages[] = array(
                                    'name' => $product['page_name'][$i],
                                    'url' => $product['page_url'][$i],
                                    'facebookId' => $product['facebookId'][$i],
                                );
                }
                $products[$key]['pages'] = $pages;
                
                $str = $products[$key]['image'];
                $last_slash_pos = strripos($str, '/');                
                $last_dot_pos = strripos($str, '.');                    
                $filename = substr($str, $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1);
                $small_filename = substr_replace($str, $filename . '_small', $last_slash_pos + 1, $last_dot_pos - $last_slash_pos - 1 );
            
                $products[$key]['published_at'] = date('D, j M Y H:i:s \G\M\T', strtotime($products[$key]['published_at']));
                $products[$key]['small_image'] = $small_filename;
                $products[$key]['link'] = $store_url . "/products/" . $products[$key]['handle'];                
                $products[$key]['content'] = "NEW CYCLING MUG!
Order Here: {LINK}
Click The Link Above To Order!";
            }
        }
    } //print_r($products);
    
    
    if (!empty($products))
        return array_values($products);
    else
        return null;
}

function createCarouselAd($pids) {

    global $appID, $appSecret, $db, $FB_TEST, $rightArrow, $rightTopArrow;
    Api::init(
        $appID,
        $appSecret,
        $_SESSION['facebook_access_token']
    );
    
    if (empty($db)) $db = DB();
    $store_url = getProjectURL();
    $attachments = array();

    //$query = $db->query("select * from product where ")

    $db->select("*");
    $db->where_in("productId", $pids);
    $order = sprintf('FIELD(productId, %s)', implode(', ', $pids));
    $db->order_by($order);
    $query = $db->get("product");
    $result = $query->result_array();

    $product = $result[0];
    $product_id = $product["productId"];
    $product_shopify_id = $product["shopifyId"];
    $product_name = $product["title"];

    $project = getProjectDetails();
    $project_name = $project["name"];    
    
    if ($FB_TEST == 1) $project["ad_account"] = "895950113801224"; // Secondary account
    $account_id = "act_" . $project["ad_account"];
    
    if (!empty($result)) {
        $collections = getProductCollection($result[0]["shopifyId"]);
        if (!empty($collections)) {
            $collection_name = $collections[0]["title"];
            $collection_id = $collections[0]["id"];

            $colors = array('White', 'Blue', 'Pink', 'Black', 'Red', 'Purple', 'Navy', 'Green');
            $i = 0;
            foreach ($result as $p) {
                $random_index = array_rand($colors, 1);
                if ($i == 0) {
                    $selected_color = $project["color"];
                } else {
                    $selected_color = $colors[$random_index];
                }
                $productImage = getProductImage($p["productId"], $selected_color);
                if (empty($productImage)) { 
                    $images[] = $p["image"]; 
                    $productImage = $p["image"];
                } else {
                    $images[] = $productImage;
                }
                $i = ($i + 1) % 8;
                if ($i >= 7) break;
                $colors = array_diff($colors, array($selected_color));
                if (empty($colors)) $colors = array('White', 'Blue', 'Pink', 'Black', 'Red', 'Purple', 'Navy', 'Green');
                
                $attachment = (new AdCreativeLinkDataChildAttachment())->setData(array(
                    AdCreativeLinkDataChildAttachmentFields::LINK => $store_url . "/products/" . $p['handle'],
                    AdCreativeLinkDataChildAttachmentFields::NAME => $p['title'],
                    AdCreativeLinkDataChildAttachmentFields::DESCRIPTION => $project["currency"] . getProductPrice($p['shopifyId']),
                    AdCreativeLinkDataChildAttachmentFields::PICTURE => $productImage,
                    'call_to_action' => array(
                        'type' => 'SHOP_NOW',
                        'value' => array(
                            'link' => $store_url . "/products/" . $p['handle'],
                            'link_caption' => 'SHOP NOW',
                        ),
                    ),
                ));
                $attachments[] = $attachment;
            }
        } else {
            return array("success" => 0, "message" => "Collections not found.");
        }
    } else {
        return array("success" => 0, "message" => "Images can not be found.");
    }
    
    $campaign_name = strtoupper($project_name . " - " . $collection_name . " - " . $product_name);
    $collectionPages = getCollectionPages($collection_id);

    if ($FB_TEST == 1) {
        $page_id = "585175101636914";
    } else {
        if (!empty($collectionPages)) {
            $page_id = getFacebookPageId($collectionPages[0]["pageId"]);
        } else {
            $page_id = getFacebookPageId($project["default_page"]);
        }
    }

    $message = $project["carousel_text"];
    if (empty($message)) {
        $message = "NEW {COLLECTION} MUG!
Order Here: {LINK}
Click The Link Above To Order!";
    }
    
    $collection_handle = preg_replace('/\s+/', '-', strtolower($collection_name));
    $message = str_replace("{COLLECTION}", strtoupper($collection_name), $message);    
    $message = str_replace("{LINK}", $store_url . "/products/" . $product['handle'], $message);
    $message = str_replace("{RIGHT_ARROW}", $rightArrow, $message);
    $message = str_replace("{RIGHT_TOP_ARROW}", $rightTopArrow, $message);
    $message = str_replace("{COLLECTIONLINK}", $store_url . "/collections/" . $collection_handle, $message);

    $targeting = getTargeting($collection_id, $collection_name);    
    $interests = array();
    $targeting_type = array(
                "interests" => array(TargetingSearchTypes::INTEREST, TargetingSpecsFields::INTERESTS, $targeting["interests"]), 
                "job_titles" => array(TargetingSearchTypes::POSITION, TargetingSpecsFields::WORK_POSITIONS, $targeting["job_titles"]), 
                "employers" => array(TargetingSearchTypes::EMPLOYER, TargetingSpecsFields::WORK_EMPLOYERS, $targeting["employers"]),                 
                "fields_of_study" => array(TargetingSearchTypes::MAJOR, TargetingSpecsFields::EDUCATION_MAJORS, $targeting["fields_of_study"]),
                "schools" => array(TargetingSearchTypes::EDUCATION, TargetingSpecsFields::EDUCATION_SCHOOLS, $targeting["schools"])
            );    
    
    foreach ($targeting_type as $t => $v) {
        foreach ($v[2] as $query) {            
            if (!empty($query)) {
                $results = TargetingSearch::search($v[0], null, $query);
                // we'll take the top result for now
                $target = (count($results)) ? $results->current() : null;
                
                if ($target != null) {
                    $targeting_specs[$v[1]][] = array(
                        'id' => $target->id,
                        'name' => $target->name,
                    );
                }
            } else {
                $targeting_specs[$v[1]][] = array();    
            }
        }
    }

    if (empty($targeting_specs)) {        
        return array("success" => 0, "message" => "Couldn't find relevant targeting.");
    }

    try {    
        $account = new AdAccount($account_id);
        $pixels = $account->getAdsPixels();
        $default_pixel_id = $pixels->current()->id;
        
        if ($targeting["gender"] == 1) {
            $gender_code = "M";
        } else if ($targeting["gender"] == 2) {
            $gender_code = "W";
        } else {
            $gender_code = "B";
        }
        $ad_name = $adset_name = $adcreative_name = $targeting["country"] . " " . $targeting["min"] . "-" . $targeting["max"] . $gender_code . " - " . $collection_name;
        
        // create campaign
        $campaign = new Campaign(null, $account_id);
        $campaign->setData(array(
            CampaignFields::NAME => $campaign_name . " CAROUSEL - MAC",
            //CampaignFields::OBJECTIVE => CampaignObjectiveValues::VIDEO_VIEWS,
            CampaignFields::OBJECTIVE => 'CONVERSIONS',
        ));
        
        if ($FB_TEST == 1) {
            $campaign->validate()->create(array(
                Campaign::STATUS_PARAM_NAME => Campaign::STATUS_PAUSED,
            ));
        } else {
            $campaign->validate()->create(array(
                Campaign::STATUS_PARAM_NAME => Campaign::STATUS_ACTIVE,
            ));
        }

        // create creative
        $link_data = new AdCreativeLinkData();
        $link_data->setData(array(
            AdCreativeLinkDataFields::LINK => $store_url . "/collections/" . $collection_handle,
            AdCreativeLinkDataFields::CAPTION => 'SHOP NOW',            
            AdCreativeLinkDataFields::MESSAGE => $message,
            AdCreativeLinkDataFields::CHILD_ATTACHMENTS => $attachments,
        ));

        $object_story_spec = new AdCreativeObjectStorySpec();
        $object_story_spec->setData(array(
            AdCreativeObjectStorySpecFields::PAGE_ID => $page_id,
            AdCreativeObjectStorySpecFields::LINK_DATA => $link_data,
        ));

        $creative = new AdCreative(null, $account_id);
        $creative->setData(array(
            AdCreativeFields::NAME => $adcreative_name,
            AdCreativeFields::OBJECT_STORY_SPEC => $object_story_spec,
        ));

        $creative->create();

        $date = (new DateTime("+1 day"))->setTime(8, 0);
        $start_time = $date->format(DateTime::ISO8601);
                
        // create adset        
        $adset = new AdSet(null, $account_id);
        $adset->setData(array(
            AdSetFields::NAME => $adset_name,
            AdSetFields::CAMPAIGN_ID => $campaign->id,
            AdSetFields::DAILY_BUDGET => "100",
            AdSetFields::START_TIME => $start_time,
            //AdSetFields::IS_AUTOBID => true,
            //AdSetFields::BILLING_EVENT => AdSetBillingEventValues::VIDEO_VIEWS, //BillingEvents::IMPRESSIONS,
            //AdSetFields::OPTIMIZATION_GOAL => AdSetOptimizationGoalValues::VIDEO_VIEWS, //OptimizationGoals::OFFSITE_CONVERSIONS, //OptimizationGoals::REACH,
            AdSetFields::BILLING_EVENT => BillingEvents::IMPRESSIONS,
            AdSetFields::OPTIMIZATION_GOAL => OptimizationGoals::OFFSITE_CONVERSIONS, //OptimizationGoals::REACH,
            AdSetFields::BID_AMOUNT => 1000,
            AdsetFields::PROMOTED_OBJECT => array(
                'pixel_id' => $default_pixel_id, // 6027573682000, // 6027573682000 : SUPER MUG CHECKOUT
                'custom_event_type' => 'ADD_TO_CART'
            ),
            "attribution_window_days" => 7,
        ));
        
        // create targeting specs
        $targeting_values = new TargetingSpecs();
        $targeting_values->setData(array(
                TargetingSpecsFields::AGE_MAX => $targeting["max"],
                TargetingSpecsFields::AGE_MIN => $targeting["min"],
                TargetingSpecsFields::GENDERS => array("genders" => $targeting["gender"]),
                TargetingSpecsFields::GEO_LOCATIONS => array(
                    'countries' => array($targeting["country"]),
                    'location_types' => array('home', 'recent')
                ),                 
                TargetingSpecsFields::DEVICE_PLATFORMS => array("mobile", "desktop"),
                TargetingSpecsFields::PUBLISHER_PLATFORMS => array("facebook"),
                TargetingSpecsFields::FACEBOOK_POSITIONS => array("feed", "right_hand_column"),     
            ));
        
        $flexiable_spec = array();
        if (!empty($targeting_specs[TargetingSpecsFields::INTERESTS][0])) {
            $flexiable_spec[TargetingSpecsFields::INTERESTS] = $targeting_specs[TargetingSpecsFields::INTERESTS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::WORK_POSITIONS][0])) {
            $flexiable_spec[TargetingSpecsFields::WORK_POSITIONS] = $targeting_specs[TargetingSpecsFields::WORK_POSITIONS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::WORK_EMPLOYERS][0])) {
            $flexiable_spec[TargetingSpecsFields::WORK_EMPLOYERS] = $targeting_specs[TargetingSpecsFields::WORK_EMPLOYERS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::EDUCATION_MAJORS][0])) {
            $flexiable_spec[TargetingSpecsFields::EDUCATION_MAJORS] = $targeting_specs[TargetingSpecsFields::EDUCATION_MAJORS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::EDUCATION_SCHOOLS][0])) {
            $flexiable_spec[TargetingSpecsFields::EDUCATION_SCHOOLS] = $targeting_specs[TargetingSpecsFields::EDUCATION_SCHOOLS];
        }
        
        $flexiable_spec = array_map("array_filter", $flexiable_spec);
        $targeting_values->setData(array(TargetingSpecsFields::FLEXIBLE_SPEC => array($flexiable_spec)));
        
        $adset->setData(array(AdSetFields::TARGETING => $targeting_values));
        $adset->validate()->create(array(
            AdSet::STATUS_PARAM_NAME => AdSet::STATUS_ACTIVE,
        ));    
        
        //create ad
        $ad = new Ad(null, $account_id);
        $ad->setData(array(
            AdFields::NAME => $ad_name,
            AdFields::ADSET_ID => $adset->id,
            AdFields::CREATIVE => array('creative_id' => $creative->id),
        ));
        
        $ad->create(array(
            Ad::STATUS_PARAM_NAME => Ad::STATUS_ACTIVE,
        ));
        $ad_id = $ad->id;
        $description = serialize(array("message" => "Created successfully.", "ad_id" => $ad_id));
        $publishedAt = date('Y-m-d H:i:s', time());
        $result_published = array( 'productId' => $product_shopify_id, 'productIds' => serialize($pids), 'pageId' => $page_id, 'collectionId' => $collection_id, 'postId' => 0, 'ad_id' => $ad_id, 'ad_type' => 'carousel', 'published' => 1, 'description' => $description, 'projectId' => $project["projectId"], 'publishedAt' => $publishedAt);
        $db->insert('publish_product', $result_published);
        
    } catch ( Exception $e) {
        print_r($e);
        return array("success" => 0, "message" => "There was an error when creating ad.");
    }

    return array("success" => 1, "message" => "Created successfully. Ad ID: {$ad_id}", "ad_id" => $ad_id);
}

function createSlideshowAd($pids) {

    global $appID, $appSecret, $db, $FB_TEST, $rightArrow, $rightTopArrow;
    Api::init(
        $appID,
        $appSecret,
        $_SESSION['facebook_access_token']
    );

    if ($_SESSION['USER_ID'] == 1) {
        $FB_TEST = 1;
    }
    
    if (empty($db)) $db = DB();
    
    $db->select("*");
    $db->where_in("productId", $pids);
    $order = sprintf('FIELD(productId, %s)', implode(', ', $pids));
    $db->order_by($order);
    $query = $db->get("product");
    $result = $query->result_array();

    $product = $result[0];
    $product_id = $product["productId"];
    $product_shopify_id = $product["shopifyId"];
    $product_name = $product["title"];

    $project = getProjectDetails();
    $project_name = $project["name"];    
    if ($FB_TEST == 1) $project["ad_account"] = "895950113801224"; // Secondary account
    $account_id = "act_" . $project["ad_account"];
    
    if (!empty($result)) {
        $collections = getProductCollection($result[0]["shopifyId"]);
        if (!empty($collections)) {
            $collection_name = $collections[0]["title"];
            $collection_id = $collections[0]["id"];

            $colors = array('White', 'Blue', 'Pink', 'Black', 'Red', 'Purple', 'Navy', 'Green');
            $i = 0;            
            if (count($result) == 1) {
                $result = array_merge($result, $result, $result, $result, $result, $result, $result);
            }
            foreach ($result as $p) {
                $random_index = array_rand($colors, 1);
                if ($i == 0) {
                    $selected_color = $project["color"];
                } else {
                    $selected_color = $colors[$random_index];
                }
                $productImage = getProductImage($p["productId"], $selected_color);
                if (empty($productImage)) { 
                    $images[] = $p["image"]; 
                } else {
                    $images[] = $productImage;
                }
                $i = ($i + 1) % 8;
                if ($i >= 7) break;
                $colors = array_diff($colors, array($selected_color));
                if (empty($colors)) $colors = array('White', 'Blue', 'Pink', 'Black', 'Red', 'Purple', 'Navy', 'Green');
            }
        } else {
            return array("success" => 0, "message" => "Collections not found.");
        }
    } else {
        return array("success" => 0, "message" => "Images can not be found.");
    }

    $campaign_name = strtoupper($project_name . " - " . $collection_name . " - " . $product_name);
    $result = 0;
    $collectionPages = getCollectionPages($collection_id);

    if ($FB_TEST == 1) {
        $page_id = "585175101636914";
    } else {
        if (!empty($collectionPages)) {
            $page_id = getFacebookPageId($collectionPages[0]["pageId"]);
        } else {
            $page_id = getFacebookPageId($project["default_page"]);
        }
        //$page_id = "1603074649942297"; //SuperMug page
    }

    $message = $project["slideshow_text"];
    $link_title = $project["slideshow_title"];
    $link_description = $project["slideshow_description"];

    if (empty($message)) {
        $message = "NEW {COLLECTION} MUG!
Order Here: {LINK}
Click The Link Above To Order!";
    }
    $store_url = getProjectURL();
    $collection_handle = preg_replace('/\s+/', '-', strtolower($collection_name));
    $message = str_replace("{COLLECTION}", strtoupper($collection_name), $message);    
    $message = str_replace("{LINK}", $store_url . "/products/" . $product['handle'], $message);
    $message = str_replace("{RIGHT_ARROW}", $rightArrow, $message);
    $message = str_replace("{RIGHT_TOP_ARROW}", $rightTopArrow, $message);
    $message = str_replace("{COLLECTIONLINK}", $store_url . "/collections/" . $collection_handle, $message);
    
    $link_title = str_replace("{COLLECTION}", strtoupper($collection_name), $link_title);    

    $targeting = getTargeting($collection_id, $collection_name);    
    $interests = array();
    $targeting_type = array(
                "interests" => array(TargetingSearchTypes::INTEREST, TargetingSpecsFields::INTERESTS, $targeting["interests"]), 
                "job_titles" => array(TargetingSearchTypes::POSITION, TargetingSpecsFields::WORK_POSITIONS, $targeting["job_titles"]), 
                "employers" => array(TargetingSearchTypes::EMPLOYER, TargetingSpecsFields::WORK_EMPLOYERS, $targeting["employers"]),                 
                "fields_of_study" => array(TargetingSearchTypes::MAJOR, TargetingSpecsFields::EDUCATION_MAJORS, $targeting["fields_of_study"]),
                "schools" => array(TargetingSearchTypes::EDUCATION, TargetingSpecsFields::EDUCATION_SCHOOLS, $targeting["schools"])
            );
    
    foreach ($targeting_type as $t => $v) {
        foreach ($v[2] as $query) {
            if (!empty($query)) {
                $results = TargetingSearch::search($v[0], null, $query);
                //if ($t == "job_titles") print_r($results);
                // we'll take the top result for now
                $target = (count($results)) ? $results->current() : null;
                
                if ($target != null) {
                    $targeting_specs[$v[1]][] = array(
                        'id' => $target->id,
                        'name' => $target->name,
                    );
                }
            } else {
                $targeting_specs[$v[1]][] = array();    
            }
        }
    }

    if (empty($targeting_specs)) {        
        return array("success" => 0, "message" => "Couldn't find relevant targeting.");
    }

    try {    
        $account = new AdAccount($account_id);
        $pixels = $account->getAdsPixels();
        $default_pixel_id = $pixels->current()->id;
        
        if ($targeting["gender"] == 1) {
            $gender_code = "M";
        } else if ($targeting["gender"] == 2) {
            $gender_code = "W";
        } else {
            $gender_code = "B";
        }
        $ad_name = $adset_name = $adcreative_name = $targeting["country"] . " " . $targeting["min"] . "-" . $targeting["max"] . $gender_code . " - " . $collection_name;
        
        // create advideo
        $video = new AdVideo(null, $account_id);
        $video->{AdVideoFields::SLIDESHOW_SPEC} = array (
            'images_urls' => $images,
            'duration_ms' => 2000,
            'transition_ms' => 300,
        );
        $video->create();
        $video_id = $video->id;

        // wait until video is uploaded
        sleep(30); $count = 0;
        while (getVideoStatus($video_id) != "ready") {
            $count++;
            if ($count > 30) {
                return array("success" => 0, "message" => "Video is not uploaded yet.");
            }
            sleep(5);
        }

        // create campaign
        $campaign = new Campaign(null, $account_id);
        $campaign->setData(array(
            CampaignFields::NAME => $campaign_name . " SLIDESHOW - MAC",
            //CampaignFields::OBJECTIVE => CampaignObjectiveValues::VIDEO_VIEWS,
            CampaignFields::OBJECTIVE => 'CONVERSIONS',
        ));
        
        if ($FB_TEST == 1) {
            $campaign->validate()->create(array(
                Campaign::STATUS_PARAM_NAME => Campaign::STATUS_PAUSED,
            ));
        } else {
            $campaign->validate()->create(array(
                Campaign::STATUS_PARAM_NAME => Campaign::STATUS_ACTIVE,
            ));
        }

        // create video creative
        $video_data = new AdCreativeVideoData();
        $video_data->setData(array(
            AdCreativeVideoDataFields::DESCRIPTION => $message,
            AdCreativeVideoDataFields::IMAGE_URL => $images[0],
            AdCreativeVideoDataFields::VIDEO_ID => $video_id, //$video->id,
            AdCreativeVideoDataFields::CALL_TO_ACTION => array(
                    'type' => AdCreativeCallToActionTypeValues::SHOP_NOW,
                    'value' => array(
                        //'page' => $page_id,
                        'link' => $store_url . "/collections/" . $collection_handle,
                        'link_caption' => 'SHOP NOW',
                        //'link_title' => $collection_name . ' Mugs - ' . '<a href="' . $store_url . "/collections/" . $collection_handle . '">CLICK TO VIEW!</a>',
                        //'link_title' => $collection_name . ' Mugs - CLICK TO VIEW!',
                        'link_title' => $link_title,
                        'link_description' => $link_description
                    ),
            ),
        ));

        $object_story_spec = new AdCreativeObjectStorySpec();
        $object_story_spec->setData(array(
            AdCreativeObjectStorySpecFields::PAGE_ID => $page_id,
            AdCreativeObjectStorySpecFields::VIDEO_DATA => $video_data,
        ));

        $creative = new AdCreative(null, $account_id);
        $creative->setData(array(
            AdCreativeFields::NAME => $adcreative_name,
            AdCreativeFields::OBJECT_STORY_SPEC => $object_story_spec,
        ));
        $creative->create();

        $date = (new DateTime("+1 day"))->setTime(8, 0);
        $start_time = $date->format(DateTime::ISO8601);
                
        // create adset        
        $adset = new AdSet(null, $account_id);
        $adset->setData(array(
            AdSetFields::NAME => $adset_name,
            AdSetFields::CAMPAIGN_ID => $campaign->id,
            AdSetFields::DAILY_BUDGET => "100",
            AdSetFields::START_TIME => $start_time,
            //AdSetFields::IS_AUTOBID => true,
            //AdSetFields::BILLING_EVENT => AdSetBillingEventValues::VIDEO_VIEWS, //BillingEvents::IMPRESSIONS,
            //AdSetFields::OPTIMIZATION_GOAL => AdSetOptimizationGoalValues::VIDEO_VIEWS, //OptimizationGoals::OFFSITE_CONVERSIONS, //OptimizationGoals::REACH,
            AdSetFields::BILLING_EVENT => BillingEvents::IMPRESSIONS,
            AdSetFields::OPTIMIZATION_GOAL => OptimizationGoals::OFFSITE_CONVERSIONS, //OptimizationGoals::REACH,
            AdSetFields::BID_AMOUNT => 1000,
            AdsetFields::PROMOTED_OBJECT => array(
                'pixel_id' => $default_pixel_id, // 6027573682000, // 6027573682000 : SUPER MUG CHECKOUT
                'custom_event_type' => 'ADD_TO_CART'
            ),
            "attribution_window_days" => 7,
        ));
        
        // create targeting specs
        $targeting_values = new TargetingSpecs();
        $targeting_values->setData(array(
                TargetingSpecsFields::AGE_MAX => $targeting["max"],
                TargetingSpecsFields::AGE_MIN => $targeting["min"],
                TargetingSpecsFields::GENDERS => array("genders" => $targeting["gender"]),
                TargetingSpecsFields::GEO_LOCATIONS => array(
                    'countries' => array($targeting["country"]),
                    'location_types' => array('home', 'recent')
                ),                 
                TargetingSpecsFields::DEVICE_PLATFORMS => array("mobile", "desktop"),
                TargetingSpecsFields::PUBLISHER_PLATFORMS => array("facebook"),
                TargetingSpecsFields::FACEBOOK_POSITIONS => array("feed", "right_hand_column"),     
            ));
        
        $flexiable_spec = array();
        if (!empty($targeting_specs[TargetingSpecsFields::INTERESTS][0])) {
            $flexiable_spec[TargetingSpecsFields::INTERESTS] = $targeting_specs[TargetingSpecsFields::INTERESTS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::WORK_POSITIONS][0])) {
            $flexiable_spec[TargetingSpecsFields::WORK_POSITIONS] = $targeting_specs[TargetingSpecsFields::WORK_POSITIONS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::WORK_EMPLOYERS][0])) {
            $flexiable_spec[TargetingSpecsFields::WORK_EMPLOYERS] = $targeting_specs[TargetingSpecsFields::WORK_EMPLOYERS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::EDUCATION_MAJORS][0])) {
            $flexiable_spec[TargetingSpecsFields::EDUCATION_MAJORS] = $targeting_specs[TargetingSpecsFields::EDUCATION_MAJORS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::EDUCATION_SCHOOLS][0])) {
            $flexiable_spec[TargetingSpecsFields::EDUCATION_SCHOOLS] = $targeting_specs[TargetingSpecsFields::EDUCATION_SCHOOLS];
        }
        
        $flexiable_spec = array_map("array_filter", $flexiable_spec);
        $targeting_values->setData(array(TargetingSpecsFields::FLEXIBLE_SPEC => array($flexiable_spec)));
        
        $adset->setData(array(AdSetFields::TARGETING => $targeting_values));
        $adset->validate()->create(array(
            AdSet::STATUS_PARAM_NAME => AdSet::STATUS_ACTIVE,
        ));    
        
        //create ad
        $ad = new Ad(null, $account_id);
        $ad->setData(array(
            AdFields::NAME => $ad_name,
            AdFields::ADSET_ID => $adset->id,
            AdFields::CREATIVE => array('creative_id' => $creative->id),
        ));
        
        $ad->create(array(
            Ad::STATUS_PARAM_NAME => Ad::STATUS_ACTIVE,
        ));
        $ad_id = $ad->id;
        $description = serialize(array("message" => "Created successfully.", "ad_id" => $ad_id));
        $publishedAt = date('Y-m-d H:i:s', time());
        $result_published = array( 'productId' => $product_shopify_id, 'productIds' => serialize($pids), 'pageId' => $page_id, 'collectionId' => $collection_id, 'postId' => $video_id, 'ad_id' => $ad_id, 'ad_type' => 'video', 'published' => 1, 'description' => $description, 'projectId' => $project["projectId"], 'publishedAt' => $publishedAt);
        $db->insert('publish_product', $result_published);
        
    } catch ( Exception $e) {        
        print_r($e);
        return array("success" => 0, "message" => "There was an error when creating ad.");
    }

    return array("success" => 1, "message" => "Created successfully. Ad ID: {$ad_id}", "ad_id" => $ad_id);
}

function createAd($post_id, $product_id, $collection_id) {
    
    global $appID, $appSecret, $FB_TEST;
    
    Api::init(
        $appID,
        $appSecret,
        $_SESSION["facebook_access_token"]        
    );
    
    $dumb = getProjectDetails();
    $project_name = $dumb["name"];
    
    if ($FB_TEST == 1) $dumb["ad_account"] = "895950113801224"; // Secondary account
    
    $account_id = "act_" . $dumb["ad_account"];
    $collection_name = getCollectionName($collection_id);
    $product_name = getProductName($product_id);
    $targeting = getTargeting($collection_id, $collection_name);
    
    $interests = array();
    $targeting_type = array(
                "interests" => array(TargetingSearchTypes::INTEREST, TargetingSpecsFields::INTERESTS, $targeting["interests"]), 
                "job_titles" => array(TargetingSearchTypes::POSITION, TargetingSpecsFields::WORK_POSITIONS, $targeting["job_titles"]), 
                "employers" => array(TargetingSearchTypes::EMPLOYER, TargetingSpecsFields::WORK_EMPLOYERS, $targeting["employers"]),                 
                "fields_of_study" => array(TargetingSearchTypes::MAJOR, TargetingSpecsFields::EDUCATION_MAJORS, $targeting["fields_of_study"]),
                "schools" => array(TargetingSearchTypes::EDUCATION, TargetingSpecsFields::EDUCATION_SCHOOLS, $targeting["schools"])
            );
    
    // INTEREST : Interests => TargetingSpecsFields::INTERESTS
    // POSITION : Job Titles => TargetingSpecsFields::WORK_POSITIONS
    // EMPLOYER : Employers => TargetingSpecsFields::WORK_EMPLOYERS
    // EDUCATION : Schools => TargetingSpecsFields::EDUCATION_SCHOOLS
    // MAJOR : Fields of Study => TargetingSpecsFields::EDUCATION_MAJORS
    foreach ($targeting_type as $t => $v) {
        foreach ($v[2] as $query) {            
            if (!empty($query)) {
                $results = TargetingSearch::search($v[0], null, $query);
                //if ($t == "job_titles") print_r($results);
                // we'll take the top result for now
                $target = (count($results)) ? $results->current() : null;
                
                if ($target != null) {
                    $targeting_specs[$v[1]][] = array(
                        'id' => $target->id,
                        'name' => $target->name,
                    );
                }
            } else {
                $targeting_specs[$v[1]][] = array();    
            }
        }
    }

    if (empty($targeting_specs)) {        
        return array("ad_id" => 0, "message" => "Couldn't find relevant targeting.");
    }
    
    $campaign_name = strtoupper($project_name . " - " . $collection_name . " - " . $product_name);
    $result = 0;
    
    try {
        $account = new AdAccount($account_id);
        $pixels = $account->getAdsPixels();
        $default_pixel_id = $pixels->current()->id;
        
        if ($targeting["gender"] == 1) {
            $gender_code = "M";
        } else if ($targeting["gender"] == 2) {
            $gender_code = "W";
        } else {
            $gender_code = "B";
        }
        $ad_name = $adset_name = $adcreative_name = $targeting["country"] . " " . $targeting["min"] . "-" . $targeting["max"] . $gender_code . " - " . $collection_name;
    
        // create campaign
        $campaign = new Campaign(null, $account_id);
        $campaign->setData(array(
            CampaignFields::NAME => $campaign_name . " - MAC",
            CampaignFields::OBJECTIVE => 'CONVERSIONS', //'POST_ENGAGEMENT'
        ));
        
        if ($FB_TEST == 1) {
            $campaign->validate()->create(array(
                Campaign::STATUS_PARAM_NAME => Campaign::STATUS_PAUSED,
            ));
        } else {
            $campaign->validate()->create(array(
                Campaign::STATUS_PARAM_NAME => Campaign::STATUS_ACTIVE,
            ));
        }
        
        // create creative
        $creative = new AdCreative(null, $account_id);
        $creative->setData(array(
            AdCreativeFields::NAME => $adcreative_name,
            AdCreativeFields::OBJECT_STORY_ID => $post_id,
        ));
        $creative->create();
        
        $date = (new DateTime("+1 day"))->setTime(8, 0);
        $start_time = $date->format(DateTime::ISO8601);
                
        // create adset        
        $adset = new AdSet(null, $account_id);
        $adset->setData(array(
            AdSetFields::NAME => $adset_name,
            AdSetFields::CAMPAIGN_ID => $campaign->id,
            AdSetFields::DAILY_BUDGET => "100",                        
            AdSetFields::START_TIME => $start_time,
            //AdSetFields::IS_AUTOBID => true,
            AdSetFields::BILLING_EVENT => BillingEvents::IMPRESSIONS,
            AdSetFields::OPTIMIZATION_GOAL => OptimizationGoals::OFFSITE_CONVERSIONS, //OptimizationGoals::REACH,
            AdsetFields::PROMOTED_OBJECT => array(
                'pixel_id' => $default_pixel_id, // 6027573682000, // 6027573682000 : SUPER MUG CHECKOUT
                'custom_event_type' => 'ADD_TO_CART'
            ),
            "attribution_window_days" => 7,
            "bid_amount" => 1000,
        ));
        
        // create targeting specs
        $targeting_values = new TargetingSpecs();

        $targeting_values->setData(array(
                TargetingSpecsFields::AGE_MAX => $targeting["max"],
                TargetingSpecsFields::AGE_MIN => $targeting["min"],
                TargetingSpecsFields::GENDERS => array("genders" => $targeting["gender"]),
                TargetingSpecsFields::GEO_LOCATIONS => array(
                    'countries' => array($targeting["country"]),
                    'location_types' => array('home', 'recent')
                ),                 
                TargetingSpecsFields::DEVICE_PLATFORMS => array("mobile", "desktop"),
                TargetingSpecsFields::PUBLISHER_PLATFORMS => array("facebook"),
                TargetingSpecsFields::FACEBOOK_POSITIONS => array("feed", "right_hand_column"),     
            ));
        
        $flexiable_spec = array();
        if (!empty($targeting_specs[TargetingSpecsFields::INTERESTS][0])) {
            $flexiable_spec[TargetingSpecsFields::INTERESTS] = $targeting_specs[TargetingSpecsFields::INTERESTS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::WORK_POSITIONS][0])) {
            $flexiable_spec[TargetingSpecsFields::WORK_POSITIONS] = $targeting_specs[TargetingSpecsFields::WORK_POSITIONS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::WORK_EMPLOYERS][0])) {
            $flexiable_spec[TargetingSpecsFields::WORK_EMPLOYERS] = $targeting_specs[TargetingSpecsFields::WORK_EMPLOYERS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::EDUCATION_MAJORS][0])) {
            $flexiable_spec[TargetingSpecsFields::EDUCATION_MAJORS] = $targeting_specs[TargetingSpecsFields::EDUCATION_MAJORS];
        }
        if (!empty($targeting_specs[TargetingSpecsFields::EDUCATION_SCHOOLS][0])) {
            $flexiable_spec[TargetingSpecsFields::EDUCATION_SCHOOLS] = $targeting_specs[TargetingSpecsFields::EDUCATION_SCHOOLS];
        }
        
        $flexiable_spec = array_map("array_filter", $flexiable_spec);
        $targeting_values->setData(array(TargetingSpecsFields::FLEXIBLE_SPEC => array($flexiable_spec)));
        
        $adset->setData(array(AdSetFields::TARGETING => $targeting_values));
        $adset->validate()->create(array(
            AdSet::STATUS_PARAM_NAME => AdSet::STATUS_ACTIVE,
        ));    
        
        //create ad
        $ad = new Ad(null, $account_id);
        $ad->setData(array(
            AdFields::CREATIVE => array('creative_id' => $creative->id),        
            AdFields::NAME => $ad_name,            
            AdFields::ADSET_ID => $adset->id,
            /*AdFields::TRACKING_SPECS => array(
                'action.type' => 'offsite_conversion',
                'fb_pixel' => $default_pixel_id,   
            )*/
        ));
        
        $ad->create(array(
            Ad::STATUS_PARAM_NAME => Ad::STATUS_ACTIVE,
        ));
        
        $result = array("message" => "Created successfully.", "ad_id" => $ad->id);
        
    } catch ( Exception $e) {        
        throw $e;        
    }
    
    sleep(10);
    return $result;
}

function publishProduct($pid) {
    global $db, $FB_TEST, $rightArrow, $rightTopArrow;
    if (empty($db)) $db = DB();
    
    $projectId = getProjectIdFromProduct($pid);
    $projectColor = getSelectedProjectColor();
    
    setCurrentProject($projectId);
    
    $fb = $_SESSION['FB'];
        
    $sql = 'SELECT p.shopifyId AS productId, c.title AS collection_name, p.handle as handle, fp.facebookId as facebookId,
        c.shopifyId as collectionId, p.image as image
        FROM product AS p
        JOIN product_collection_link AS pcl ON p.shopifyId=pcl.productId
        JOIN collection AS c ON pcl.collectionId = c.shopifyId        
        JOIN page_collection_link AS pcl2 ON c.shopifyId=pcl2.collectionId
        JOIN facebook_page AS fp ON pcl2.pageId=fp.pageId
        WHERE c.title!="Frontpage" AND c.title!="Keep Calm" AND c.title!="ALL" AND c.title!="BEST SELLING" AND c.title!="Latest" AND p.productId=' . $pid . '
        GROUP BY p.productId            
        ORDER BY p.productId ASC';
    
    $query = $db->query($sql);
    $result = $query->result_array();

    try {
        if (!empty($result)) {        
            if ($FB_TEST == 1) {
                $result[0]['facebookId'] = '585175101636914'; // For testing
            }
            
            $product = $result[0];

            $product_id = $product['productId'];
            $page_id = $product['facebookId'];
            $collection_id = $product['collectionId'];

            if (!isPagePublished($page_id)) {
                publishPage($page_id);
            }
            
            $message = strip_tags(getPagePostText());
            if (empty($message)) {
                $message = "NEW {COLLECTION} MUG!
    Order Here: {LINK}
    Click The Link Above To Order!";
            }

            $store_url = getProjectURL();
            $collection_handle = preg_replace('/\s+/', '-', strtolower($product['collection_name']));
            $message = str_replace("{COLLECTION}", strtoupper($product['collection_name']), $message);    
            $message = str_replace("{LINK}", $store_url . "/products/" . $product['handle'], $message);
            $message = str_replace("{RIGHT_ARROW}", $rightArrow, $message);
            $message = str_replace("{RIGHT_TOP_ARROW}", $rightTopArrow, $message);
            $message = str_replace("{COLLECTIONLINK}", $store_url . "/collections/" . $collection_handle, $message);

                
            $query = $db->query("SELECT * FROM publish_product WHERE productId=" . $product_id . " AND pageId='" . $page_id . "' AND collectionId='" . $collection_id . "'");
            //if ($query->num_rows() == 0) 
            {   
                $page_access_token = getPageAccessToken($page_id, $_SESSION['facebook_access_token']);
                
                $productImage = getProductImage($pid, $projectColor);
                if (empty($productImage)) { $productImage = $product['image']; }
                
                $data = [
                    'published' => true,
                    'message' => $message,
                    'source' => $fb->fileToUpload($productImage),
                ];
                $response = $fb->post('/' . $page_id . '/photos', $data, $page_access_token);
                
                $dumb = $response->getDecodedBody();
                $post_id = $dumb["post_id"];

                try {
                    // Creating Ad

                    $result = createAd($post_id, $product_id, $collection_id);
                    $ad_id = $result["ad_id"];
                    if ($ad_id != 0) {                        
                        $published = 1;
                    } else {                        
                        $published = 2;
                    }
                    $description = serialize($result);
                    $publishedAt = date('Y-m-d H:i:s', time());
                    $result_published = array( 'productId' => $product_id, 'pageId' => $page_id, 'collectionId' => $collection_id, 'postId' => $post_id, 
                        'ad_id' => $ad_id, 'published' => $published, 'description' => $description, 'projectId' => $projectId, 'publishedAt' => $publishedAt);
                    $db->insert('publish_product', $result_published);
                    
                    $dumb = array("last_published" => $publishedAt);
                    $db->where(array("projectId" => $projectId, "shopifyId" => $collection_id));
                    $db->update("collection", $dumb);
                    
                    return array("status" => 1, "result" => $result_published);
                    
                } catch (Exception $e) {
                    $code = $e->getCode();
                    $message = $e->getMessage();
                    $error_user_title = ""; //$e->getErrorUserTitle();
                    if ($e instanceof RequestException) {
                        $error_user_title = $e->getErrorUserTitle();
                    }
        
                    $description = serialize(array("code" => $code, "message" => $message . ":" . $error_user_title));
                    
                    $result_published = array( 'productId' => $product_id, 'pageId' => $page_id, 'collectionId' => $collection_id, 'postId' => $post_id, 
                        'ad_id' => 0, 'published' => 2, 'description' => $description, 'projectId' => $projectId);
                    $db->insert('publish_product', $result_published);
                    
                    return array("status" => 8, "result" => $result_published);
                }
            }
        } else {
            $description = serialize(array("code" => 222, "message" => "Couldn't find relevant FB page or shopify collection."));
            return array("status" => 2, "result" => array("description" => $description));    
        }
    } catch (Exception $e) {
        $code = $e->getCode();
        $message = $e->getMessage(); 
        
        $error_user_title = "";
        if ($e instanceof RequestException) {
            $error_user_title = $e->getErrorUserTitle();
        }
        $description = serialize(array("code" => $code, "message" => $message . ":" . $error_user_title));    
        if (!empty($result)) {
            $product = $result[0];            
            $product_id = $product['productId'];
            $page_id = $product['facebookId'];
            $collection_id = $product['collectionId'];
            $dumb = array( 'productId' => $product_id, 'pageId' => $page_id, 'collectionId' => $collection_id, 
                        'ad_id' => 0, 'published' => 2, 'description' => $description, 'projectId' => $projectId);    
        } else {
            $dumb = array( 'productId' => 0, 'pageId' => 0, 'collectionId' => 0, 
                        'ad_id' => 0, 'published' => 2, 'description' => $description, 'projectId' => $projectId);
        }
        
        $db->insert('publish_product', $dumb);
        
        return array("status" => 9, "result" => array("description" => $description));
    }
    
    return false;
}

function publishQueue() {
    global $db;
    if (empty($db)) $db = DB();
    
    $query = $db->query("SELECT * FROM queue WHERE published=0 ORDER BY id LIMIT 500");
    //$query = $db->query("SELECT * FROM queue WHERE id=1713 ORDER BY id LIMIT 500");
    
    if ($query->num_rows() > 0) {
        $queues = $query->result_array();
    
        foreach ($queues as $queue) {
            $updated_date = date('Y-m-d H:i:s', time());
            
            $result = publishProduct($queue['productId']);
            //echo "Queue : " . $queue['id']; print_r($result); echo "<br/>";
            if ($result["status"] == 1) {
                $description = $result["result"]["description"];
                 
                $dumb = array("published" => 1, "updated_at" => $updated_date, "description" => $description);
                $db->update("queue", $dumb, array("id" => $queue["id"]));
                $db->query("UPDATE queue SET published=1, updated_at='{$updated_date}', description='{$description}' WHERE id={$queue['id']}");
                sleep(10);
            } else {
                $description = $result["result"]["description"];
                $dumb = array("published" => 2, "updated_at" => $updated_date, "description" => $description);
                $db->update("queue", $dumb, array("id" => $queue["id"]));
                
                sleep(30);
            }
        }
    }
}

function isCronRunning() {
    global $db;
    if (empty($db)) $db = DB();
    
    $query = $db->query("SELECT meta_value FROM options WHERE meta_key='CRON_RUNNING'");
    $result = $query->result_array();
    if ($result[0]["meta_value"] == "1") {
        return true;
    } else {
        return false;
    }
}

function fb_lock() {
    global $db;
    if (empty($db)) $db = DB();
    
    $date = date('Y-m-d H:i:s', time());
    $query = $db->query("UPDATE options SET meta_value='1' WHERE meta_key='CRON_RUNNING'");
    $query = $db->query("UPDATE options SET meta_value='{$date}' WHERE meta_key='LAST_CRON_STARTED'");
}

function fb_unlock() {
    global $db;
    if (empty($db)) $db = DB();
    
    $date = date('Y-m-d H:i:s', time());
    $query = $db->query("UPDATE options SET meta_value='0' WHERE meta_key='CRON_RUNNING'");
    $query = $db->query("UPDATE options SET meta_value='{$date}' WHERE meta_key='LAST_CRON_ENDED'");
}
//convert an ISO8601 date to a different format
function vm_date($date) { // 
    $time = strtotime($date);
    $fixed = date('Y-m-d H:i:s', $time);
    return $fixed;
}
