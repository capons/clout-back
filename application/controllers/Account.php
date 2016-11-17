<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining account information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 06/03/2015
 */
class Account extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_account');
	}
	

	# default DELETE
	public function index_delete()
  	{
    	
  	}
	

	# default PUT
	public function index_put()
  	{
    	
  	}

	# default POST
	public function index_post()
  	{
    	
  	}
	
	
	# default GET
	public function index_get()
  	{
  		log_message('debug', 'Account/index_get');
  		
		if(!empty($this->get('apiId'))) $user = $this->_account->user(extract_id($this->get('apiId')));
		else $user = array();
		
		log_message('debug', 'Account/list_get:: [1] user= '.json_encode($user));
		$this->response($user);
	}

	
	# Get a list of all the users with the given user-type within the set restrictions
	public function list_get()
  	{
  		log_message('debug', 'Account/list_get');
  		
		$result = array();
		# Only proceed if the type of account list is given
		if(!empty($this->get('type')))
		{
			$restrictions = array(
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'), 
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
				'phrase'=>(!empty($this->get('phrase'))? $this->get('phrase'): ''), 
				'status'=>(!empty($this->get('status'))? $this->get('status'): 'active'),
				'return'=>'list'
			);
			
			log_message('debug', 'Account/list_get:: [1] restrictions= '.json_encode($restrictions));
			$result = $this->_account->types($this->get('type'), $restrictions);
		}
		
		log_message('debug', 'Account/list_get:: [2] result='.json_encode($result));
		
		$this->response($result);
	}
	
	
	
	
	
	

	# POST to login 
	public function login_post()
  	{
  		log_message('debug', 'Account/login_post');
  		log_message('debug', 'Account/login_get:: [1] username=' . (!empty($this->post('userName')) ? $this->post('userName') : ''));
  		
    	$result = $this->_account->login(
			$this->post('userName'), 
			$this->post('password'),
			array(
				'device'=>(!empty($this->post('userDevice'))? $this->post('userDevice'): ''), 
				'browser'=>(!empty($this->post('userBrowser'))? $this->post('userBrowser'): ''), 
				'ip_address'=>(!empty($this->post('userIp'))? $this->post('userIp'): ''), 
				'uri'=>(!empty($this->post('uri'))? $this->post('uri'): '')
			)
		);
    	
    	log_message('debug', 'Account/login_post:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
	
	
	public function login_get()
  	{
  		log_message('debug', 'Account/login_get');
  		log_message('debug', 'Account/login_get:: [1] username=' . (!empty($this->post('userName')) ? $this->post('userName') : ''));
  		
    	$result = $this->_account->login(
			$this->get('userName'), 
			$this->get('password'),
			array(
				'device'=>(!empty($this->get('userDevice'))? $this->get('userDevice'): ''), 
				'browser'=>(!empty($this->get('userBrowser'))? $this->get('userBrowser'): ''), 
				'ip_address'=>(!empty($this->get('userIp'))? $this->get('userIp'): ''), 
				'uri'=>(!empty($this->get('uri'))? $this->get('uri'): '')
			)
		);
		
    	log_message('debug', 'Account/login_get:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
	
	

	# POST to logout
	public function logout_post()
  	{
  		log_message('debug', 'Account/logout_post');
  		log_message('debug', 'Account/login_post:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));
  		
    	$result = $this->_account->logout(
			(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
			array(
				'device'=>(!empty($this->post('userDevice'))? $this->post('userDevice'): ''), 
				'browser'=>(!empty($this->post('userBrowser'))? $this->post('userBrowser'): ''), 
				'ip_address'=>(!empty($this->post('userIp'))? $this->post('userIp'): ''), 
				'uri'=>(!empty($this->post('uri'))? $this->post('uri'): '')
			)
		);
		
    	log_message('debug', 'Account/logout_post:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
		
	
	

	

	# POST the application 
	public function add_post()
  	{
  		log_message('debug', 'Account/add_post');
  		log_message('debug', 'Account/add_post:: [1] first=' . $this->post('firstName') . ' last='.$this->post('lastName').' email='.$this->post('emailAddress').' tel='.$this->post('telephone').' zip='.$this->post('zipcode') . ' referral='.$this->post('reffererId'));

  		/*
  		 * TODO 
  		 * 
  		 * Third party feature
  		 * 
  		 * In the model _account/add, update the logic to pick the referral store ID from the publicButtonId 
  		 * (if provided) and save the user referral to the database as type='store' on successful creation of a user.
  		 */
		$result = $this->_account->add(
			$this->post('firstName'),
			$this->post('lastName'),
			$this->post('emailAddress'),
			$this->post('emailVerified'),
			$this->post('password'),
			$this->post('telephone'),
			$this->post('provider'),
			$this->post('gender'),
			$this->post('zipcode'),
			$this->post('birthDate'),
			(!empty($this->post('reffererId'))? $this->post('reffererId'): ''),
			(!empty($this->post('facebookId'))? $this->post('facebookId'): ''),
			
			array(
				'base_link'=>(!empty($this->post('baseLink'))? $this->post('baseLink'): ''),
				'device'=>(!empty($this->post('userDevice'))? $this->post('userDevice'): ''), 
				'browser'=>(!empty($this->post('userBrowser'))? $this->post('userBrowser'): ''), 
				'ip_address'=>(!empty($this->post('userIp'))? $this->post('userIp'): ''), 
				'uri'=>(!empty($this->post('uri'))? $this->post('uri'): '')
			)
		);
		
		log_message('debug', 'Account/add_post:: [2] result='.json_encode($result));
		$this->response($result); 
  	}

	
	# TEMP
	public function add_get()
  	{
  		log_message('debug', 'Account/add_get');
  		log_message('debug', 'Account/add_get:: [1] first=' . $this->get('firstName') . ' last='.$this->get('lastName').' email='.$this->get('emailAddress').' tel='.$this->get('telephone').' zip='.$this->get('zipcode') . ' referral='.$this->get('reffererId'));
  		
		$result = $this->_account->add(
			$this->get('firstName'),
			$this->get('lastName'),
			$this->get('emailAddress'),
			$this->get('emailVerified'),
			$this->get('password'),
			$this->get('telephone'),
			$this->get('provider'),
			$this->get('gender'),
			$this->get('zipcode'),
			$this->get('birthDate'),
			(!empty($this->get('reffererId'))? $this->get('reffererId'): ''),
			(!empty($this->get('facebookId'))? $this->get('facebookId'): ''),
			
			array('base_link'=>(!empty($this->get('baseLink'))? $this->get('baseLink'): ''),
				'device'=>(!empty($this->get('userDevice'))? $this->get('userDevice'): ''), 
				'browser'=>(!empty($this->get('userBrowser'))? $this->get('userBrowser'): ''), 
				'ip_address'=>(!empty($this->get('userIp'))? $this->get('userIp'): ''), 
				'uri'=>(!empty($this->get('uri'))? $this->get('uri'): '')
				)
		);

		
		log_message('debug', 'Account/add_get:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
	
	

	# POST verification of account creation
	public function verify_post()
  	{
  		log_message('debug', 'Account/verify_post');
  		log_message('debug', 'Account/verify_post:: [1] code=' . $this->post('code') . ' tel='. (!empty($this->post('telephone'))? $this->post('telephone'): '') . ' userId='.extract_id($this->post('userId')));
  		
		$result = $this->_account->verify(
			$this->post('code'),
			(!empty($this->post('telephone'))? $this->post('telephone'): ''),
			extract_id($this->post('userId')),
			$this->post('baseLink')
		);
		
		log_message('debug', 'Account/verify_post:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
	
	public function verify_get()
  	{
  		log_message('debug', 'Account/verify_get');
  		log_message('debug', 'Account/verify_get:: [1] code=' . $this->get('code') . ' tel='. (!empty($this->get('telephone'))? $this->get('telephone'): '') . ' userId='.extract_id($this->get('userId')));
  		
		$result = $this->_account->verify(
			$this->get('code'),
			(!empty($this->get('telephone'))? $this->get('telephone'): ''),
			extract_id($this->get('userId')),
			$this->get('baseLink')
		);
		
		log_message('debug', 'Account/verify_get:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
	
	
	

	

	# POST resend the account verification link
	public function resend_link_post()
  	{
  		log_message('debug', 'Account/resend_link_post');
  		log_message('debug', 'Account/resend_link_post:: [1] email=' . $this->post('emailAddress') . ' userId='.extract_id($this->get('userId')));
  		
		$result = $this->_account->resend_link(
			$this->post('emailAddress'),
			extract_id($this->post('userId')),
			$this->post('baseLink')
		);
		
		log_message('debug', 'Account/resend_link_post:: [2] result='.json_encode($result));
		$this->response($result); 
  	}
	
	
	
	
	
	
	# POST to send link to recover a forgotten password
	public function forgot_post()
	{
		log_message('debug', 'Account/forgot_post');
		log_message('debug', 'Account/forgot_post:: [1] email=' . $this->post('emailAddress'));
		
		$result = $this->_account->send_password_link(
			$this->post('emailAddress'),
			$this->post('baseLink')
		);
		
		log_message('debug', 'Account/forgot_post:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	
	
	# get to send link to recover a forgotten password
	public function forgot_get()
	{
		log_message('debug', 'Account/forgot_get');
		log_message('debug', 'Account/forgot_get:: [1] email=' . $this->get('emailAddress'));
		
		$result = $this->_account->send_password_link(
			$this->get('emailAddress'),
			$this->get('baseLink')
		);
		
		log_message('debug', 'Account/forgot_get:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	
	# POST to recover your account by resetting the password
	public function recover_post()
	{
		log_message('debug', 'Account/recover_post');
		log_message('debug', 'Account/recover_post:: [1] userId='.(!empty($this->post('userId'))? $this->post('userId'): ''));
		
		$result = $this->_account->reset_password(
			(!empty($this->post('userId'))? $this->post('userId'): ''),
			(!empty($this->post('tempPassword'))? $this->post('tempPassword'): ''),
			(!empty($this->post('newPassword'))? $this->post('newPassword'): '')
		);
		
		log_message('debug', 'Account/recover_post:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	# get to recover your account by resetting the password
	public function recover_get()
	{
		log_message('debug', 'Account/recover_get');
		log_message('debug', 'Account/recover_get:: [1] userId='.(!empty($this->get('userId'))? $this->get('userId'): ''));
		
		$result = $this->_account->reset_password(
			(!empty($this->get('userId'))? $this->get('userId'): ''),
			(!empty($this->get('tempPassword'))? $this->get('tempPassword'): ''),
			(!empty($this->get('newPassword'))? $this->get('newPassword'): '')
		);
		
		log_message('debug', 'Account/recover_get:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	
	
	# POST to apply the default user group 
	public function apply_default_group_post()
	{
		log_message('debug', 'Account/apply_default_group_post');
		log_message('debug', 'Account/apply_default_group_post:: [1] userId='.(!empty($this->post('userId'))? $this->post('userId'): ''));
		
		$result = $this->_account->apply_default_user_group(extract_id($this->post('userId')));
		
		log_message('debug', 'Account/apply_default_group_post:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	
	# POST to apply the default user group 
	public function apply_default_group_get()
	{
		log_message('debug', 'Account/apply_default_group_get');
		log_message('debug', 'Account/apply_default_group_get:: [1] userId='.(!empty($this->get('userId'))? $this->get('userId'): ''));
		
		$result = $this->_account->apply_default_user_group(extract_id($this->get('userId')));

		log_message('debug', 'Account/apply_default_group_get:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	
	# POST to recover your account by resetting the password
	public function purge_user_post()
	{		
		log_message('debug', 'Account/purge_user_post');
		log_message('debug', 'Account/purge_user_post:: [1] purgeUsers='.(!empty($this->post('purgeUsers'))? $this->post('purgeUsers'): ''));
		
		$result = $this->_account->purge($this->post('purgeUsers'));
		
		log_message('debug', 'Account/purge_user_post:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	# For testing. REMOVE IN PRODUCTION
	public function purge_user_get()
	{
		log_message('debug', 'Account/purge_user_get');
		log_message('debug', 'Account/purge_user_get:: [1] userId='.(!empty($this->get('userId'))? $this->get('userId'): ''));
		
		$result = $this->_account->purge(array($this->get('userId')));
		
		log_message('debug', 'Account/purge_user_get:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
	
	
	# POST user facebook data
	public function facebook_post()
	{
		log_message('debug', 'Account/facebook_post');
		log_message('debug', 'Account/facebook_post:: [1] facebookId='.(!empty($this->post('facebookId'))? $this->post('facebookId'): ''));
		
		$result = $this->_account->save_facebook_data(
			$this->post('facebookId'),
			array(
				'email'=>(!empty($this->post('email'))? $this->post('email'): ''),
				'name'=>(!empty($this->post('name'))? $this->post('name'): ''),
				'firstName'=>(!empty($this->post('firstName'))? $this->post('firstName'): ''),
				'lastName'=>(!empty($this->post('lastName'))? $this->post('lastName'): ''),
				'ageRange'=>(!empty($this->post('ageRange'))? $this->post('ageRange'): ''),
				'gender'=>(!empty($this->post('gender'))? $this->post('gender'): ''),
				'birthday'=>(!empty($this->post('birthday'))? $this->post('birthday'): ''),
				'profileLink'=>(!empty($this->post('profileLink'))? $this->post('profileLink'): ''),
				'timezoneOffset'=>(!empty($this->post('timezoneOffset'))? $this->post('timezoneOffset'): ''),
				'photoUrl'=>(!empty($this->post('photoUrl'))? $this->post('photoUrl'): ''),
				'isSilhouette'=>(!empty($this->post('isSilhouette'))? $this->post('isSilhouette'): '')
			)
		);

		log_message('debug', 'Account/facebook_post:: [2] result='.json_encode($result));
		$this->response($result); 
	}
	
	
}


/* End of controller file */