<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining third party plugin information.
 *
 * @author Khim Ung <khimu@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 03/22/2016
 */
class Plugin extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_plugin');
	}
	

	/*
	 * get third party button information given publicButtonId
	 *
	 * This verifies that the access button is valid and also get the button details
	 *
	 * If the button is invalid show an error message instead of the login form: "The button you used seems to be invalid.
     * Please check and try again or contact the business to fix this button."
     *
	 * curl -X GET https://api.clout.com/v1/plugin/button -d publicButtonId=34Yasda1sdx8932H
	 * 
	 */
	public function button_get() {
		$result = $this->_plugin->button_details(
				$this->get('publicButtonId')
				);
			
		$this->response($result);
	}
	
	# third party request to generate a button
	public function button_post() {
		if(empty($this->post('userId')) || empty($this->post('referralId')) || empty($this->post('length')) || empty($this->post('size')) 
				|| empty($this->post('navigation')) || empty($this->post('website')) ) {
					echo "ERROR: One or more required fields are missing.";
		}
		else {
			if($this->post('navigation') == 'redirect') {
				if(empty($this->post('redirectUrl'))) {
					echo "ERROR: Required redirect URL is missing.";
				}
				// validate response code is 200 for redirect URL
				if(url_exists($redirectUrl) == false) {
					echo "ERROR: Redirect URL does not exist.";
				}
			}
			
			// validate response code is 200
			if(url_exists($website) == false) {
				echo "ERROR: The provided website does not exist.";
			}

			$result = $this->_plugin->generate_button(
					$this->post('userId'),
					$this->post('referralId'),
					$this->post('length'),
					$this->post('size'),
					$this->post('navigation'),
					$this->post('website'),
					$this->post('redirectUrl')
					);

			// return data-appid
			$this->response($result);
		}
	}
	
	/* Whichever button is clicked, asynchronously post to the API a record of the share.
	 * On Clicking the button, close the window (if session value shows button navigation = popup), OR redirect to redirectUrl (if session value shows button navigation = redirect).
	 * Record share action
	 *
	 * curl -X POST https://api.clout.com/v1/plugin/share \
	 * 	-d publicButtonId=34Yasda1sdx8932H
	 *	-d shareAction=continue
	 * 	-d fields=first_name,last_name,user_id,vip_status,store_scores
	 *	-d browser=Mozilla Firefox 3.4.5
	 *	-d ipAddress=234.34.234.23
	 */
	public function share_get() {
		log_message('debug', 'share_get');
		$this->_plugin->save_share(
			$this->get('pluginButtonId'),
			$this->get('shareAction'),
			$this->get('fields'),
			$this->get('browser'),
			$this->get('ipAddress'),
			$this->get('userId')
			);
	}
	
	
	
	# get user information for third party access
	public function user_details_get() {

		$result = $this->_plugin->user_details(
			$this->get('publicButtonId'),
			$this->get('publicUserId')
		);
		
		$this->response($result);
	}
	
	
	# third party verify user id to referral id
	public function verify_get() {
		$result = $this->_plugin->verify(
					$this->get('userId'),
					$this->get('referralId')
				);
		
		$this->response($result);
	}

}
