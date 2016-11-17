<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls promotion actions
 */
class Customer extends REST_Controller
{

    /**
     * Customer constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('_customer');

    }


    //display all customers
    public function index_get()
    {
        //array with all view column access

        $view = $this->get('view');
        $customers = $this->_customer->get_customer_list( $this->get('filter'),$this->get('searchData'), $this->get('sortData'), $this->get('sortType'), $this->get('merchantId'));
        if(!empty($view)) {
            $filter_cutomers_row = $this->_customer->view_field_access($customers, $view);
            $this->response($filter_cutomers_row); //return filter data
        } else {
            $this->response($customers);           //return full data
        }

    }
    //customers by filter
    public function filter_get()
    {

        $filter_customers = $this->_customer->get_filter_customer_list($this->get('countryCode'), $this->get('stateId'), $this->get('storeAddress'));
        $this->response($filter_customers);
    }

    //get all customer country
    public function get_country_get()
    {
        $country = $this->_customer->get_all_country();
        $this->response($country);
    }
    //get all customer state
    public function get_state_get()
    {
        $state = $this->_customer->get_all_state($this->get('countryCode'));
        $this->response($state);

    }
    //get all customer address
    public function get_address_get()
    {
        $store_address = $this->_customer->get_store_address($this->get('stateId'));
        $this->response($store_address);
    }

    //users reservation
    public function reservation_status_post()
    {

        $result = $this->_customer->reservation_status($this->post('users_id'),$this->post('status'),$this->post('user_id'),$this->post('time'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }

    //send message to user
    public function message_send_post()
    {

        $result = $this->_customer->message_send($this->post('users_id'),$this->post('user_id'),$this->post('time'),$this->post('subject'),$this->post('body'),$this->post('sms'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);

    }

    //update perk status
    public function update_perk_status_post()
    {
        $result = $this->_customer->update_perk_status($this->post('users_id'),$this->post('user_id'),$this->post('status'),$this->post('time'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }

    //add taggin (this function using for search customer by tag name)
    public function create_tagging_post()
    {
        $result = $this->_customer->create_tagging($this->post('ownerId'),$this->post('label'),$this->post('customersId'),$this->post('delete'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }
    //delete tagg
    public function delete_tagg_post()
    {
        $result = $this->_customer->delete_tagg($this->post('tag_id'),$this->post('owner_id'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }
    //display all merchant tag
    public function display_tagg_get()
    {
        $result = $this->_customer->display_tagg($this->get('owner_id'));
        if (isset($result['error'])) {
            $this->response(array(
                'status' => 'failed',
                'message' => $result['error']
            ), 400);
        }
        $this->response($result);
    }


}