<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining money information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/29/2015
 */
class Money extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_money');
	}
	
	
	
	# get the featured banks
	public function banks_get()
  	{
  		log_message('debug', 'Money/banks_get');
  		
		$restrictions = array(
			'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'), 
			'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			'type'=>(!empty($this->get('isFeatured'))? 'featured': 'all'),
			'phrase'=>(!empty($this->get('phrase'))? $this->get('phrase'): ''),
			'excludeBanks'=>(!empty($this->get('excludeBanks')) ? $this->get('excludeBanks') : '')
		);
		
		log_message('debug', 'Money/banks_get:: [1] restrictions='.json_encode($restrictions));
		
		$result = $this->_money->banks($restrictions);
		
		log_message('debug', 'Money/banks_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the bank's details
	public function bank_get()
  	{
  		log_message('debug', 'Money/bank_get');
  		log_message('debug', 'Money/bank_get:: [1] bankId='.((!empty($this->get('bankId'))) ? $this->get('bankId') : ''));
  		
		$result = $this->_money->bank_details(
			$this->get('bankId'), 
			$this->get('fields')
		);
		
		log_message('debug', 'Money/bank_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Connect to the bank API
	public function connect_to_bank_post()
	{
		log_message('debug', 'Money/connect_to_bank_post');
		log_message('debug', 'Money/connect_to_bank_post:: [1] userId='.(!empty($this->post('userId'))? extract_id($this->post('userId')): ''));

		$result = $this->_money->connect_to_bank(
			$this->post('credentials'), 
			$this->post('postData'), 
			(!empty($this->post('userId'))? extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Money/connect_to_bank_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Connect to the bank API
	public function connect_to_bank_get()
	{
		log_message('debug', 'Money/connect_to_bank_get');
		log_message('debug', 'Money/connect_to_bank_get:: [1] userId='.(!empty($this->get('userId'))? extract_id($this->get('userId')): ''));
		
		$result = $this->_money->connect_to_bank(
			$this->get('credentials'), 
			$this->get('postData'), 
			(!empty($this->get('userId'))? $this->get('userId'): '')
		);
		
		log_message('debug', 'Money/connect_to_bank_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get the current user's banks
	public function current_user_banks_get()
	{
		log_message('debug', 'Money/current_user_banks_get');
		log_message('debug', 'Money/current_user_banks_get:: [1] userId='.(!empty($this->get('userId'))? extract_id($this->get('userId')): ''));
		
		$result = $this->_money->user_banks(
			(!empty($this->get('userId'))? extract_id($this->get('userId')): '')
		);
		
		log_message('debug', 'Money/current_user_banks_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
}


/* End of controller file */