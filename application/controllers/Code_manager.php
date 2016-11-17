<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining admin systems information.
 *
 * @author Rebecca Lin <rebeccal@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 05/03/2016
 */
class Code_manager extends REST_Controller 
{
	#Constructor to set some default values at class load
	public function __construct()
	{
		parent::__construct();
		$this->load->model('_code_manager');
	}
	
	# Log activity of copy between repos
	public function activity_post()
	{
		log_message('debug', 'Code_manager/activity_post');
		log_message('debug', 'Code_manager/activity_post:: [1] userId=' . (!empty($this->post('userId'))? extract_id($this->post('userId')): ''));
		
		$result = $this->_code_manager->activity(
			(!empty($this->post('userId'))? extract_id($this->post('userId')): ''),
			array(
				'from_repo'=>(!empty($this->post('fromRepo'))? $this->post('fromRepo'): ''),
				'to_repo'=>(!empty($this->post('toRepo'))? $this->post('toRepo'): ''),
				'tag_name'=>(!empty($this->post('tagName'))? $this->post('tagName'): ''),
				'run_time'=>(!empty($this->post('runTime'))? $this->post('runTime'): ''),
				'uri'=>(!empty($this->post('uri'))? $this->post('uri'): ''),
				'ip_address'=>(!empty($this->post('userIp'))? $this->post('userIp'): ''),
				'browser'=>(!empty($this->post('userBrowser'))? $this->post('userBrowser'): '')
			)
		);

		log_message('debug', 'Code_manager/activity_post:: [2] result='.json_encode($result));
		$this->response($result);
	}

}