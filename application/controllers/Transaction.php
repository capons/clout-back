<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining transaction information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/29/2015
 */
class Transaction extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
	}
	
	
	
	# Get a summary of the transaction match status
	public function match_status_get()
  	{
  		log_message('debug', 'Transaction/match_status_get');
  		log_message('debug', 'Transaction/match_status_get:: [1] adminId='.extract_id($this->get('adminId')));
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'get_transaction_match_status', 
				'admin_id'=>extract_id($this->get('adminId'))
			));
		
		log_message('debug', 'Transaction/match_status_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get a list of the transaction descriptor data in a type preferred as used in the system
	public function descriptor_get()
  	{
  		log_message('debug', 'Transaction/descriptor_get');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'get_transaction_descriptor', 
				'data_type'=>$this->get('dataType'), 
				'descriptor_id'=>(!empty($this->get('descriptorId'))? $this->get('descriptorId'): ''), 
				'user_id'=>(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'),
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
				'filters'=>(!empty($this->get('filters'))? $this->get('filters'): array())
			)); 
			
		log_message('debug', 'Transaction/descriptor_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get a list of the transaction descriptor change data in a type preferred as used in the system
	public function change_get()
  	{
  		log_message('debug', 'Transaction/change_get');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'get_transaction_changes', 
				'data_type'=>$this->get('dataType'), 
				'data_id'=>(!empty($this->get('dataId'))? $this->get('dataId'): ''),
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'),
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
				'phrase'=>(!empty($this->get('phrase'))? $this->get('phrase'): ''), 
				'user_id'=>(!empty($this->get('userId'))? extract_id($this->get('userId')): '')
			));
			
		log_message('debug', 'Transaction/change_get:: [1] result='.json_encode($result));
		$this->response($result);
	}

	
	
	# Add a flag item 
	public function add_flag_post()
  	{
  		log_message('debug', 'Transaction/add_flag_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'add_flag', 
				'data_type'=>$this->post('dataType'), 
				'change_id'=>$this->post('changeId'), 
				'stage'=>$this->post('stage'), 
				'user_id'=>extract_id($this->post('userId')), 
				'details'=>array('displayed'=>$this->post('displayedValue'), 'hidden'=>$this->post('hiddenValue') )
			));
			
		log_message('debug', 'Transaction/add_flag_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# Delete a flag item 
	public function delete_flag_post()
  	{
  		log_message('debug', 'Transaction/delete_flag_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'delete_flag', 
				'data_type'=>$this->get('dataType'), 
				'flag_id'=>$this->post('flagId'), 
				'stage'=>$this->post('stage'), 
				'user_id'=>extract_id($this->post('userId'))
			));
			
		log_message('debug', 'Transaction/delete_flag_post:: [1] result='.json_encode($result));
		$this->response($result);
	}

	
	
	# Update the transaction descriptor scope
	public function update_scope_post()
  	{
  		log_message('debug', 'Transaction/update_scope_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_descriptor_scope', 
				'descriptor_id'=>$this->post('descriptorId'), 
				'scope_id'=>$this->post('scopeId'), 
				'action'=>$this->post('action'), 
				'user_id'=>extract_id($this->post('userId')), 
				'other_details'=>$this->post('otherDetails')
			));
			
		log_message('debug', 'Transaction/update_scope_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Add a transaction sub-category
	public function add_sub_category_post()
	{
		log_message('debug', 'Transaction/add_sub_category_post');
		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'add_transaction_sub_category', 
				'descriptor_id'=>$this->post('descriptorId'), 
				'category_id'=>$this->post('categoryId'), 
				'new_sub_category'=>$this->post('newSubCategory'), 
				'action'=>$this->post('action'),
				'user_id'=>(!empty($this->post('userId'))? extract_id($this->post('userId')): '')
			));
			
		log_message('debug', 'Transaction/add_sub_category_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Update the transaction descriptor sub-category list
	public function update_categories_post()
  	{
  		log_message('debug', 'Transaction/update_categories_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_descriptor_categories', 
				'descriptor_id'=>$this->post('descriptorId'), 
				'sub_categories'=>$this->post('subCategories'), 
				'suggested_sub_categories'=>$this->post('suggestedSubCategories'), 
				'action'=>$this->post('action'), 
				'user_id'=>extract_id($this->post('userId')), 
				'other_details'=>$this->post('otherDetails')
			));
			
		log_message('debug', 'Transaction/update_categories_post:: [1] result='.json_encode($result));
		$this->response($result);
	} 




	# Update the transaction sub-category list
	public function update_transaction_categories_post()
	{
		log_message('debug', 'Transaction/update_transaction_categories_post');
		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_transaction_categories',
				'transaction_id'=>$this->post('transactionId'),
				'sub_categories'=>$this->post('subCategories'),
				'action'=>$this->post('action'),
				'user_id'=>extract_id($this->post('userId')),
				'other_details'=>(!empty($this->post('otherDetails'))?$this->post('otherDetails'): array())
		));
			
		log_message('debug', 'Transaction/update_transaction_categories_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# Update the transaction descriptor location
	public function update_location_post()
  	{
  		log_message('debug', 'Transaction/update_location_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_transaction_location', 
				'descriptor_id'=>$this->post('descriptorId'), 
				'chain'=>$this->post('chain'), 
				'store'=>$this->post('store'), 
				'action'=>$this->post('action'), 
				'user_id'=>extract_id($this->post('userId')), 
				'other_details'=>$this->post('otherDetails')
			));
			
		log_message('debug', 'Transaction/update_location_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get a list of transaction matching rules
	public function matching_rules_get()
  	{
  		log_message('debug', 'Transaction/matching_rules_get');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'get_matching_rules', 
				'descriptor_id'=>$this->get('descriptorId'),  
				'types'=>$this->get('types'), 
				'user_id'=>(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'),
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
				'phrase'=>(!empty($this->get('phrase'))? $this->get('phrase'): '')
			));
		
		log_message('debug', 'Transaction/matching_rules_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	


	
	public function add_rule_post()
	{
		log_message('debug', 'Transaction/add_rule_post');
		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'add_rule', 
				'descriptor_id'=>$this->post('descriptorId'),  
				'criteria'=>$this->post('criteria'), 
				'action'=>$this->post('action'),
				'phrase'=>$this->post('phrase'),
				'category'=>$this->post('category'),
				'store_id'=>(!empty($this->post('storeId'))? $this->post('storeId'): (!empty($this->post('chainId'))? $this->post('chainId'): '')),
				'user_id'=>extract_id($this->post('userId'))
			));
			
		log_message('debug', 'Transaction/add_rule_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# Delete a rule
	public function delete_rule_post()
  	{
  		log_message('debug', 'Transaction/delete_rule_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'delete_rule', 
				'rule_id'=>$this->post('ruleId'), 
				'stage'=>$this->post('stage'), 
				'user_id'=>extract_id($this->post('userId'))
			));
			
		log_message('debug', 'Transaction/delete_rule_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Update the possible matches tracking
	public function update_matches_post()
  	{
  		log_message('debug', 'Transaction/update_matches_post');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_matches', 
				'descriptor_id'=>$this->post('descriptorId'), 
				'rule_ids'=>$this->post('ruleIds'), 
				'action'=>$this->post('action'), 
				'user_id'=>extract_id($this->post('userId')), 
				'other_details'=>$this->post('otherDetails')
			));
		
		log_message('debug', 'Transaction/update_matches_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	



	# Get a list of transactions in the system by set filter
	public function list_get()
	{
		log_message('debug', 'Transaction/list_get');
  		
		$result = server_curl(CRON_SERVER_URL, array('__action'=>'get_transaction_list_by_date', 
				'data_type'=>$this->get('dataType'), 
				'transaction_id'=>(!empty($this->get('transactionId'))? $this->get('transactionId'): ''), 
				'user_id'=>(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'),
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
				'filters'=>(!empty($this->get('filters'))? $this->get('filters'): array())
			)); 
			
		log_message('debug', 'Transaction/list_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
}


/* End of controller file */