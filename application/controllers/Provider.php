<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining telephone provider information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 06/23/2015
 */
class Provider extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_provider');
	}
	
	
	# GET list of providers
	public function list_get()
  	{
  		log_message('debug', 'Provider/list_get');
  		log_message('debug', 'Provider/list_get:: [1] bankId='.((!empty($this->get('bankId'))) ? $this->get('bankId') : ''));
  		
		$result = $this->_provider->get_list(
			(!empty($this->get('phrase'))? $this->get('phrase'): ''), 
			(!empty($this->get('offset'))? $this->get('offset'): 0), 
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE)
		);
		
		log_message('debug', 'Provider/list_get:: [2] result='.json_encode($result));
		$this->response($result);
	}


	
}


/* End of controller file */