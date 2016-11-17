<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * Third party user data access
 *
 * @author Khim Ung <khimu@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 03/22/2016
 */
class Profile extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_profile');
	}
	

	
	# Allow third party to request user information
	public function user_get() {
		$result = $this->_plugin->third_party_user_access(
				$this->get('clientId'),
				$this->get('password'),
				$this->get('userId'),
				$this->get('scope') == null ? '' : $this->get('scope'),
				$this->get('redirectUrl') == null ? '' : $this->get('redirectUrl')
				);
			
		$this->response($result);
	}



}

