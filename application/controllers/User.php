<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining user information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/25/2015
 */
class User extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_user');
	}
	
	
	# GET the user list
	public function list_get()
  	{
  		log_message('debug', 'User/list_get');
  		
		$result = $this->_user->get_list(
			$this->get('view'), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): ''), 
			(!empty($this->get('category'))? $this->get('category'): ''),
			array(
				'viewUserIds'=>(!empty($this->get('viewUserIds'))? $this->get('viewUserIds'): '')
			)
		);
		
		log_message('debug', 'User/request_offers_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# GET the user settings
	public function settings_get()
  	{
  		log_message('debug', 'User/settings_get');
  		
		$result = $this->_user->get_settings(
			extract_id($this->get('userId')),
			$this->get('fields')
		);
		
		log_message('debug', 'User/settings_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	# GET the user's facebook info
	public function social_media_get() 
	{
		log_message('debug', 'User/social_media_get');
		
		if(!empty($this->get('userId')) && !empty($this->get('mediaType'))) {
			$result = $this->_user->get_social_media_data(
						extract_id($this->get('userId')),
						$this->get('mediaType'),
						explode(',', str_replace(' ', '', $this->get('fields'))			)	
						);
			
			log_message('debug', 'User/social_media_get:: [1] result='.json_encode($result));
			$this->response($result);
		} 
		else {
			log_message('debug', 'User/social_media_get:: [2] result='.json_encode(array('responseCode'=>'400', 'message'=>'Bad Request.  Invalid Parameters', 'moreInfo'=>'https://developers.clout.com/errors/400001', 'messageCode'=>'400001')));
			echo json_encode(array('responseCode'=>'400', 'message'=>'Bad Request.  Invalid Parameters', 'moreInfo'=>'https://developers.clout.com/errors/400001', 'messageCode'=>'400001'));
		}
	}
	
	
	# POST a user photo
	public function photo_post()
  	{
  		log_message('debug', 'User/photo_post');
  		
		$result = $this->_user->update_photo(
			extract_id($this->post('userId')),
			$this->post('photo')
		);
		
		log_message('debug', 'User/photo_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST a new user password
	public function password_post()
  	{
  		log_message('debug', 'User/password_post');
  		
		$result = $this->_user->update_password(
			extract_id($this->post('userId')),
			decrypt_value($this->post('password'))
		);
		
		log_message('debug', 'User/password_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	

					
	# POST a user address
	public function address_post()
  	{
  		log_message('debug', 'User/address_post');
  		
		$result = $this->_user->add_address(
			extract_id($this->post('userId')),
			$this->post('addressLine1'),
			$this->post('addressLine2'),
			$this->post('city'),
			$this->post('state'),
			$this->post('country'),
			$this->post('zipcode')
		);
		
		log_message('debug', 'User/address_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST a change in address type
	public function address_type_post()
  	{
  		log_message('debug', 'User/address_type_post');
  		
		$result = $this->_user->update_address_type(
			extract_id($this->post('userId')),
			$this->post('contactId'),
			$this->post('addressType')
		);
		
		log_message('debug', 'User/address_type_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST an address removal
	public function remove_address_post()
  	{
  		log_message('debug', 'User/remove_address_post');
  		
		$result = $this->_user->remove_address(
			extract_id($this->post('userId')),
			$this->post('contactId')
		);
		
		log_message('debug', 'User/remove_address_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST communication privacy
	public function communication_privacy_post()
  	{
  		log_message('debug', 'User/communication_privacy_post');
  		
		$result = $this->_user->communication_privacy(
			extract_id($this->post('userId')),
			$this->post('method'),
			$this->post('methodValue')
		);
		
		log_message('debug', 'User/communication_privacy_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST a new emailaddress
	public function email_address_post()
  	{
  		log_message('debug', 'User/email_address_post');
  		
		$result = $this->_user->add_email_address(
			extract_id($this->post('userId')),
			$this->post('emailAddress')
		);
		
		log_message('debug', 'User/email_address_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST a new telephone
	public function telephone_post()
  	{
  		log_message('debug', 'User/telephone_post');
  		
		$result = $this->_user->add_telephone(
			extract_id($this->post('userId')),
			$this->post('telephone'),
			$this->post('provider'),
			(!empty($this->post('isPrimary'))? $this->post('isPrimary'): 'N')
		);
		
		log_message('debug', 'User/telephone_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# get a new telephone
	public function telephone_get()
  	{
  		log_message('debug', 'User/telephone_get');
  		
		$result = $this->_user->add_telephone(
			extract_id($this->get('userId')),
			$this->get('telephone'),
			$this->get('provider'),
			(!empty($this->get('isPrimary'))? $this->get('isPrimary'): 'N')
		);
		
		log_message('debug', 'User/telephone_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# POST an email activation code
	public function activate_email_address_post()
	{
		log_message('debug', 'User/activate_email_address_post');
		
		$result = $this->_user->activate_email_address(
			extract_id($this->post('userId')),
			$this->post('contactId'),
			$this->post('code')
		);
		
		log_message('debug', 'User/activate_email_address_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# POST a telephone activation code
	public function activate_telephone_post()
	{
		log_message('debug', 'User/activate_telephone_post');
		
		$result = $this->_user->activate_telephone(
			extract_id($this->post('userId')),
			$this->post('contactId'),
			$this->post('code')
		);
		
		log_message('debug', 'User/activate_telephone_post:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	
	
	
}


/* End of controller file */