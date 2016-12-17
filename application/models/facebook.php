<?php

class Facebook extends CI_Model {
    
    public function insert_facebook_pages($project_id, $p) {
        foreach ($p as $page) {
            $condition = "facebookId=" . "'" . $page['id'] . "' AND projectId=$project_id AND userId='" . $_SESSION['USER']['id'] . "'";
            $this->db->select('*');
            $this->db->from('facebook_page');
            $this->db->where($condition);
            $this->db->limit(1);
            $query = $this->db->get();
            if ($query->num_rows() == 0) {
                $data = array(
                        'projectId'     => $project_id,
                        'facebookId'    => $page['id'],
                        'name'          => $page['name'],
                        'url'           => $page['link'],
                        'userId'        => $_SESSION['USER']['id'],
                        'picture'       => $page['picture']['url'],
                    );
                        
                $this->db->insert('facebook_page', $data);                
            }    
        }
    }
    
    public function insert_shopify_collections($project_id, $p, $type = 'custom') {
        foreach ($p as $collection) {
            $condition = "shopifyId='" . $collection['id'] . "' AND type='{$type}' AND projectId={$project_id}";
            $this->db->select('*');
            $this->db->from('collection');
            $this->db->where($condition);
            $this->db->limit(1);
            $query = $this->db->get();
            if ($query->num_rows() == 0) {
                $value = array(
                        'shopifyId'         => $collection['id'],
                        'title'             => $collection['title'],
                        'projectId'         => $project_id,
                        'type'              => $type
                    );
                $data[] = $value;                
            }    
        }
        if (!empty($data)) {
            $this->db->insert_batch("collection", $data);
        }
    }    
    
    public function get_last_product_id($project_id) {
        $this->db->select("shopifyId");
        $this->db->from('product');
        $this->db->where("projectId=" . $project_id);
        $this->db->order_by("shopifyId", "desc");
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows() == 1) {
            $dumb = $query->result();
            return $dumb[0]->shopifyId;
        } else {
            return 0;
        }
    }
    
    public function get_last_product_collection_id($project_id, $collection_id) {
        $this->db->select("productId");
        $this->db->from('product_collection_link');        
        $this->db->where("collectionId=" . $collection_id . " AND projectId={$project_id}");
        $this->db->order_by("productId", "desc");
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows() == 1) {
            $dumb = $query->result();
            return $dumb[0]->productId;
        } else {
            return 0;
        }
    }
    
    public function get_last_PCL_product_id($project_id) {
        $this->db->select("productId");
        $this->db->from('product_collection_link');        
        $this->db->where("projectId={$project_id}");
        $this->db->order_by("id", "desc");
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows() == 1) {
            $dumb = $query->result();
            return $dumb[0]->productId;
        } else {
            return 0;
        }
    }    
    
    public function get_last_collection_id($project_id, $type = 'custom') {
        $condition = "projectId={$project_id} AND type='{$type}'";
        $this->db->select("shopifyId");
        $this->db->from('collection');
        $this->db->where($condition);
        $this->db->order_by("shopifyId", "desc");
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows() == 1) {
            $dumb = $query->result();
            return $dumb[0]->shopifyId;
        } else {
            return 0;
        }
    }
    
    public function insert_shopify_products($project_id, $p) {
        foreach ($p as $product) {
            $condition = "shopifyId={$product['id']}";
            $this->db->select('*');
            $this->db->from('product');
            $this->db->where($condition);
            $this->db->limit(1);
            $query = $this->db->get();
            if ($query->num_rows() == 0) {
                $value = array(
                        'shopifyId'         => $product['id'],
                        'title'             => $product['title'],
                        'published_at'      => $product['published_at'],
                        'handle'            => $product['handle'],
                        'image'             => $product['image']['src'],
                        'projectId'         => $project_id
                    );
                $data[] = $value;
            }    
        }

        if (!empty($data)) {
            $this->db->insert_batch("product", $data);
        }
    }
    
    public function insert_product_collection_link($project_id, $product_id, $collection_id) {
        $condition = "projectId={$project_id} AND productId={$product_id} AND collectionId={$collection_id}";
        $this->db->select('*');
        $this->db->from('product_collection_link');
        $this->db->where($condition);
        $this->db->limit(1);
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            $data = array(
                    'projectId'       => $project_id,
                    'productId'         => $product_id,
                    'collectionId'      => $collection_id,
                );
            
            $this->db->insert('product_collection_link', $data);                
        }    
    }
    
    public function insert_product_collection_links($project_id, $product_id, $collections) {
        if (!empty($collections)) {
            $v = array();
            foreach ($collections as $c) {
                $v[] = "($project_id, $product_id, $c[id])";
            }
            $values = implode(", ", $v);
            
            $this->db->query("INSERT IGNORE INTO product_collection_link(projectId, productId, collectionId) VALUES $values");
        }
    }
    
    public function insert_products_collection_link($project_id, $products, $collection_id) {
        foreach ($products as $product) {
            $query = $this->db->query("SELECT id FROM product_collection_link WHERE projectId={$project_id} AND productId={$product['id']} AND collectionId={$collection_id}");
            if ($query->num_rows() == 0) {
                $value = array(
                    'projectId'       => $project_id,
                    'productId'       => $product['id'],
                    'collectionId'    => $collection_id,
                );
                $data[] = $value;
            }
        }

        if (!empty($data)) {
            $this->db->insert_batch("product_collection_link", $data);
        }
        
    }
    
    function get_project_details($project_id) {        
        $query = $this->db->query("SELECT * FROM project WHERE projectId={$project_id}");    
        if ($query->num_rows() > 0) {
            $dumb = $query->result_array();
            return $dumb[0];
        } else {
            return 0;
        }
    }
    
    function get_products($project_id, $since_id = 0) {
        $query = $this->db->query("SELECT shopifyId FROM product WHERE projectId={$project_id} AND shopifyId>$since_id");    
        if ($query->num_rows() > 0) {
            return $query->result_array();
        } else {
            return 0;
        }
    }
    
    function add_to_queue($pids) {
        foreach ($pids as $pid) {
            $v[] = "($pid)";
        }
        $values = implode(", ", $v);        
        $result = $this->db->query("INSERT INTO queue(productId) VALUES $values");
        
        return $result;
    }
    
    function get_history($project_id) {
        $sql = "SELECT pp.id, pp.ad_id, pp.published, pp.postId, pp.publishedAt, pp.description, p.title as ptitle, p.handle, fp.name, fp.url, c.title as ctitle, pr.name as prname, pr.url as prurl
                FROM publish_product AS pp 
                LEFT JOIN product AS p ON pp.productId=p.shopifyId
                JOIN facebook_page AS fp ON pp.pageId=fp.facebookId
                JOIN collection AS c ON pp.collectionId=c.shopifyId
                JOIN project AS pr ON pp.projectId=pr.projectId
                WHERE pp.projectId={$project_id}
                GROUP BY pp.id
                ORDER BY publishedAt DESC ";
        $query = $this->db->query($sql . "LIMIT {$_REQUEST['start']}, {$_REQUEST['length']}");
        $result = $query->result();
        
        foreach ($result as $row) {
            $dumb = implode(" - ", unserialize($row->description));            
            $row->description = $dumb;
        }
        
        $query = $this->db->query($sql);
        
        return array("data" => $result, "recordsTotal" => count($result), "recordsFiltered" => $query->num_rows(), "draw" => $_REQUEST['draw']);
    }
    
    function get_all_history($project_id) {
        $sql = "SELECT pp.id as ppid, pp.ad_id, pp.published, pp.postId, pp.publishedAt, pp.description, pp.ad_type, p.title as ptitle, p.handle, fp.name, fp.url, c.title as ctitle, pr.name as prname, pr.url as prurl
                FROM publish_product AS pp 
                LEFT JOIN product AS p ON pp.productId=p.shopifyId
                JOIN facebook_page AS fp ON pp.pageId=fp.facebookId
                JOIN collection AS c ON pp.collectionId=c.shopifyId
                JOIN project AS pr ON pp.projectId=pr.projectId
                WHERE pp.projectId={$project_id}
                GROUP BY pp.id
                ORDER BY pp.id DESC ";
        $query = $this->db->query($sql);
        $result = $query->result();
        
        foreach ($result as $row) {
            $dumb = implode(" - ", unserialize($row->description));            
            $row->description = $dumb;
        }                       
        
        return $result;
    }
    
    function get_queue($project_id) {
        $sql = "SELECT q.id as qid, q.published as qpublished, q.updated_at as qupdated_at, q.created_at as qcreated_at, q.description as qdescription, p.title as ptitle, p.handle as phandle, p.projectId, pr.name as prname, pr.url as prurl
                    FROM queue AS q 
                    LEFT JOIN product AS p ON q.productId=p.productId 
                    JOIN project AS pr ON p.projectId=pr.projectId 
                    WHERE p.projectId={$project_id}                    
                    ORDER BY q.id DESC ";
        $query = $this->db->query($sql);
        $result = $query->result();
        
        foreach ($result as $row) {
            if (!empty($row->qdescription)) {
                $dumb = implode(" - ", unserialize($row->qdescription));            
                $row->qdescription = $dumb;
            } else {
                $row->qdescription = " - ";
            }
            
        }                       
        
        return $result;
    }
}
  
?>