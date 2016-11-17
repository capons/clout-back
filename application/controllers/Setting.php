<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining user information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/26/2015
 */
class Setting extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_setting');
	}
	
	
	# GET the permission group list
	public function permission_group_list_get()
  	{

  		log_message('debug', 'Setting/permission_group_list_get');
  		log_message('debug', 'Setting/permission_group_list_get:: [1] category='.$this->get('category'));
  		
		$result = $this->_setting->permission_group_list(
			$this->get('category'), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/permission_group_list_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the permission list
	public function permissions_get()
	{
		log_message('debug', 'Setting/permissions_get');
		log_message('debug', 'Setting/permissions_get:: [1] phrase='.(!empty($this->get('phrase'))? $this->get('phrase'): ''));
		
		$result = $this->_setting->permission_list(
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/permissions_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# GET the permission group types
	public function group_types_get()
	{
		log_message('debug', 'Setting/group_types_get');
		log_message('debug', 'Setting/group_types_get:: [1] userId='.extract_id($this->get('userId')));
		
		$result = $this->_setting->permission_group_types(
			$this->get('groupCategory'),
			extract_id($this->get('userId'))
		);
		
		log_message('debug', 'Setting/group_types_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the details of a permission group
	public function permission_group_get()
	{

		log_message('debug', 'Setting/permission_group_get');
		log_message('debug', 'Setting/permission_group_get:: [1] userId='.extract_id($this->get('userId')));
		
		$result = $this->_setting->permission_group_details(
			extract_id($this->get('groupId'))
		);
		
		log_message('debug', 'Setting/permission_group_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST to save or edit the details of a permission group
	public function permission_group_post()
	{
		log_message('debug', 'Setting/permission_group_post');
		log_message('debug', 'Setting/permission_group_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->save_permission_group(
			(!empty($this->post('groupId'))?extract_id($this->post('groupId')): '0'),
			$this->post('groupName'),
			$this->post('groupType'),
			$this->post('groupCategory'),
			$this->post('rules'),
			$this->post('permissions'),
			(!empty($this->post('userId'))?extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Setting/permission_group_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# GET the rule categories
	public function rule_categories_get()
	{
		log_message('debug', 'Setting/rule_categories_get');
		log_message('debug', 'Setting/rule_categories_get:: [1] phrase='.(!empty($this->get('phrase'))? $this->get('phrase'): ''));
		$result = $this->_setting->rule_categories(
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/rule_categories_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the rule names
	public function rule_names_get()
	{
		log_message('debug', 'Setting/rule_names_get');
		log_message('debug', 'Setting/rule_names_get:: [1] phrase='.(!empty($this->get('phrase'))? $this->get('phrase'): ''));
		
		$result = $this->_setting->rule_names(
			$this->get('category'),
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/rule_names_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the rule details
	public function rule_details_get()
	{
		log_message('debug', 'Setting/rule_details_get');
		log_message('debug', 'Setting/rule_details_get:: [1] ruleId='.$this->get('ruleId'));
		
		$result = $this->_setting->rule_details(
			$this->get('ruleId')
		);
		
		log_message('debug', 'Setting/rule_details_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST the new status of a permission group
	public function permission_group_status_post()
	{

		log_message('debug', 'Setting/permission_group_status_post');
		log_message('debug', 'Setting/permission_group_status_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->update_permission_group_status(
			$this->post('groupId'),
			$this->post('status'),
			(!empty($this->post('userId'))?extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Setting/permission_group_status_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the cron job list
	public function cron_job_list_get()
	{
		log_message('debug', 'Setting/cron_job_list_get');
		log_message('debug', 'Setting/cron_job_list_get:: [1] phrase='.(!empty($this->get('phrase'))? $this->get('phrase'): ''));
		
		$result = $this->_setting->cron_jobs(
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/cron_job_list_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST the new status of a cron job
	public function cron_job_status_post()
	{
		log_message('debug', 'Setting/cron_job_status_post');
		log_message('debug', 'Setting/cron_job_status_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->update_cron_job_status(
			$this->post('jobId'),
			$this->post('status'),
			(!empty($this->post('userId'))?extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Setting/cron_job_status_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# GET the score settings list
	public function score_settings_list_get()
	{
		log_message('debug', 'Setting/score_settings_list_get');
		log_message('debug', 'Setting/score_settings_list_get:: [1] phrase='.(!empty($this->get('phrase'))? $this->get('phrase'): ''));
		
		$result = $this->_setting->score_settings(
			$this->get('scoreType'),
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/score_settings_list_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	

	
	# POST the new value of a score setting
	public function score_value_post()
	{
		log_message('debug', 'Setting/score_value_post');
		log_message('debug', 'Setting/score_value_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->update_score_value(
			$this->post('settingId'),
			$this->post('scoreValue'),
			$this->post('scoreType'),
			(!empty($this->post('userId'))?extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Setting/score_value_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# GET the rules list
	public function rule_settings_list_get()
	{
		log_message('debug', 'Setting/rule_settings_list_get');
		log_message('debug', 'Setting/rule_settings_list_get:: [1] phrase='.(!empty($this->get('phrase'))? $this->get('phrase'): ''));
		
		$result = $this->_setting->rule_settings(
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Setting/rule_settings_list_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST the new status of a rule setting
	public function rule_setting_status_post()
	{
		log_message('debug', 'Setting/rule_setting_status_post');
		log_message('debug', 'Setting/rule_setting_status_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->update_rule_setting_status(
			$this->post('ruleId'),
			$this->post('status'),
			(!empty($this->post('userId'))?extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Setting/rule_setting_status_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST update a value in a rule setting
	public function setting_value_post()
	{
		log_message('debug', 'Setting/setting_value_post');
		log_message('debug', 'Setting/setting_value_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->update_setting_value(
			$this->post('settingId'),
			$this->post('settingValue'),
			$this->post('settingType'),
			(!empty($this->post('userId'))? extract_id($this->post('userId')): '')
		);
		
		log_message('debug', 'Setting/setting_value_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST update a user group mapping
	public function user_group_mapping_post()
	{
		log_message('debug', 'Setting/user_group_mapping_post');
		log_message('debug', 'Setting/user_group_mapping_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->update_user_group_mapping(
			$this->post('newGroupId'),
			$this->post('userIdList'),
			(!empty($this->post('userId'))? extract_id($this->post('userId')): ''),
			array(
				'device'=>(!empty($this->post('userDevice'))? $this->post('userDevice'): ''), 
				'browser'=>(!empty($this->post('userBrowser'))? $this->post('userBrowser'): ''), 
				'ip_address'=>(!empty($this->post('userIp'))? $this->post('userIp'): ''), 
				'uri'=>(!empty($this->post('uri'))? $this->post('uri'): '')
			)
		);
		
		log_message('debug', 'Setting/user_group_mapping_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST instruction to delete a permission group
	public function delete_permission_group_post()
	{
		log_message('debug', 'Setting/delete_permission_group_post');
		log_message('debug', 'Setting/delete_permission_group_post:: [1] userId='.(!empty($this->post('userId'))?extract_id($this->post('userId')): ''));
		
		$result = $this->_setting->delete_permission_group(
			$this->post('groupId'),
			extract_id($this->post('userId')),
			array(
				'device'=>(!empty($this->post('userDevice'))? $this->post('userDevice'): ''), 
				'browser'=>(!empty($this->post('userBrowser'))? $this->post('userBrowser'): ''), 
				'ip_address'=>(!empty($this->post('userIp'))? $this->post('userIp'): ''), 
				'uri'=>(!empty($this->post('uri'))? $this->post('uri'): '')
			)
		);
		
		log_message('debug', 'Setting/delete_permission_group_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST to check if the rule applies for the given details
	public function rule_check_post()
	{
		log_message('debug', 'Setting/rule_check_post');
		log_message('debug', 'Setting/rule_check_post:: [1] ruleCode='.$this->post('ruleCode'));
		
		$result = $this->_setting->rule_check_applies(
			$this->post('ruleCode'),
			(!empty($this->post('details'))? $this->post('details'): array())
		);
		
		log_message('debug', 'Setting/rule_check_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
}


/* End of controller file */