<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls promotion actions
 */
class Promotion extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('_promotion');

    }

    # Get promotions list
    public function index_get()
    {

        $promotions = $this->_promotion->get_promotion_list($this->get('userId'), $this->get('storeId'), $this->get('minScore'), $this->get('curDate'));

        $this->response($promotions);

    }


    # Add promotion
    public function add_post()
    {

        $add = $this->_promotion->add_promotion($this->post());
        if($add === FALSE){
            $this->response([
                'success' => FALSE,
                'message' => 'Promotion already exists'
            ],400);
        }

        $this->response($add);
    }

    # Update promotion

    public function update_post()
    {
        $this->response($this->_promotion->update_promotion($this->post()));
    }

    #delete promotion

    public function delete_promotion_post()
    {
        $delete = $this->_promotion->delete_promotion($this->post('promotion_id'));
        if($delete === FALSE){
            $this->response([
                'success' => FALSE,
                'message' => 'Promotion delete false'
            ],400);
        }
        $this->response($delete);
    }

    
    # Get promotions by level
    public function custom_get()
    {
        $promotions = $this->_promotion->get_promotions_by_level($this->get('userId'), $this->get('storeOwnerId'), $this->get('levelId'));
        $this->response($promotions);
    }

    # Change promotion status
    public function status_post()
    {
        //, $this->post('storeOwnerId')
        $result = $this->_promotion->update_promotion_status($this->post('promotionId'), $this->post('userId'), $this->post('newStatusAction'), $this->post('storeId'));

        $this->response(array(
            'success' => $result
        ));


    }

    # Get categories list
    public function get_categories_get()
    {
        $result = $this->_promotion->get_categories_list($this->get('userId'), $this->get('storeOwnerId'));

        $this->response($result);
    }

    # Change value for category-level connection
    public function change_value_post()
    {
        $result = $this->_promotion->change_value($this->post('userId'), $this->post('id'), $this->post('value'));
        
        $this->response($result);
    }

    # Create new level
    public function add_level_post()
    {
        $result = $this->_promotion->add_level($this->post('userId'), $this->post('storeOwnerId'), $this->post('levelId'), $this->post('name'));
        $this->response($result);
    }

    # Change level name
    public function change_level_name_post()
    {
        $result = $this->_promotion->update_level_name($this->post('id'), $this->post('name'), $this->post('userId'));
        $this->response($result);
    }

    # Delete level
    public function delete_level_post()
    {
        $result = $this->_promotion->delete_level($this->post('id'), $this->post('userId'), $this->post('storeOwnerId'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }

    # Add category
    public function add_category_post()
    {

        $storeOwnerId = $this->post('storeOwnerId');
        $categoryType = $this->post('categoryType');
        $categoryIds = $this->post('categoryIds');
        $userId = $this->post('userId');

        $categoryIdsArray = explode(',', $categoryIds);

        $result =array();
        $i=1;
        foreach($categoryIdsArray as $categoryId) {
            $params = explode('_', $categoryId);

            if ($categoryType == 'competitor') {
                $categoryTypeCurrent = $categoryType;
                $subCategory = NULL;
            } else {
                if (isset($params[1])) {
                    $subCategory = (int) $params[1];
                    $categoryTypeCurrent = 'sub_category';
                } else {
                    $categoryTypeCurrent = 'category';
                    $subCategory = NULL;
                }
            }

            $category = (int) $params[0];

            $result[$i] = $this->_promotion->add_category($userId, $storeOwnerId, $categoryTypeCurrent, $category, $subCategory);
            $i++;
        }
        $this->response($result);

    }

    # Delete category
    public function delete_category_post()
    {
        $result = $this->_promotion->delete_category($this->post('id'), $this->post('userId'), $this->post('storeOwnerId'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }

    //get all stores
    public function all_stores_get()
    {
        $result = $this->_promotion->get_all_store();
        $this->response($result);
    }
    //delete promotion blackouts
    public function delete_blackout_post()
    {
        $result = $this->_promotion->delete_blackout($this->post('id'),$this->post('promotion_id'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }
    /*
    public function reservation_status_post()
    {

        $result = $this->_promotion->reservation_status($this->post('promotion_id'),$this->post('status'),$this->post('user_id'),$this->post('time'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }
    */
}