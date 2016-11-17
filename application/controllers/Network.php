<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining network information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/09/2015
 */
class Network extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_network');
	}
	
	
	
	# Get the network referrals based on level
	public function referrals_get()
  	{
  		log_message('debug', 'Network/referrals_get');
  		log_message('debug', 'Network/referrals_get:: [1] userId='.(!empty($this->post('userId'))? extract_id($this->post('userId')): ''));
  		
		$result = $this->_network->referrals(
			extract_id($this->get('userId')),
			(!empty($this->get('level'))? $this->get('level'): ''), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Network/referrals_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get the network invites based on level
	public function invites_get()
  	{
  		log_message('debug', 'Network/invites_get');
  		log_message('debug', 'Network/invites_get:: [1] userId='.(!empty($this->post('userId'))? extract_id($this->post('userId')): ''));
		
		$result = $this->_network->invites(
			extract_id($this->get('userId')),
			$this->get('level'), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Network/invites_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get the email host given the email address
	public function email_host_get()
  	{
  		log_message('debug', 'Network/email_host_get');
  		log_message('debug', 'Network/email_host_get:: [1] email='.$this->get('emailAddress'));
  		
		$result = $this->_network->email_host(
			$this->get('emailAddress')
		);
		
		log_message('debug', 'Network/email_host_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Get the contacts from third party email provider
	public function import_contacts_from_email_get()
  	{
  		log_message('debug', 'Network/import_contacts_from_email_get');
  		log_message('debug', 'Network/import_contacts_from_email_get:: [1] email='.$this->get('emailAddress') . ' userId='.extract_id($this->get('userId')));
  		
		ini_set('max_execution_time', 300); # 300 seconds = 5 minutes
		
		$result = $this->_network->import_contacts_from_email(
			extract_id($this->get('userId')),
			$this->get('emailAddress'), 
			$this->get('emailPassword'), 
			(!empty($this->get('emailHost'))? $this->get('emailHost'): ''),
			(!empty($this->get('hostPort'))? $this->get('hostPort'): '')
		);
		
		log_message('debug', 'Network/import_contacts_from_email_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Send invitations to the specified list of contacts
	public function send_invitations_post()
	{
		log_message('debug', 'Network/send_invitations_post');
		log_message('debug', 'Network/send_invitations_post:: [1] emailList='.$this->post('emailList') . ' userId='.extract_id($this->post('userId')));
		
		$result = $this->_network->send_invitations(
			extract_id($this->post('userId')),
			$this->post('emailList'),
			$this->post('userIp'),
			$this->post('baseUrl')
		);
		
		log_message('debug', 'Network/send_invitations_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	public function send_invitations_get()
	{
		log_message('debug', 'Network/send_invitations_get');
		log_message('debug', 'Network/send_invitations_get:: [1] emailList='.$this->get('emailList') . ' userId='.extract_id($this->get('userId')));
		
		$result = $this->_network->send_invitations(
			extract_id($this->get('userId')),
			$this->get('emailList'),
			$this->get('userIp'),
			$this->get('baseUrl')
		);
		
		log_message('debug', 'Network/send_invitations_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	
	
	# Get contacts from file
	public function import_contacts_from_file_get()
  	{
  		log_message('debug', 'Network/import_contacts_from_file_get');
  		log_message('debug', 'Network/import_contacts_from_file_get:: [1] userId='.extract_id($this->get('userId')));
  		
		ini_set('max_execution_time', 300); # 300 seconds = 5 minutes
		
		$result = $this->_network->import_contacts_from_file(
			extract_id($this->get('userId')),
			$this->get('fileFormat'), 
			$this->get('csvFile')
		);
		
		log_message('debug', 'Network/import_contacts_from_file_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Add a share link
	public function share_link_post()
	{
		log_message('debug', 'Network/share_link_post');
		log_message('debug', 'Network/share_link_post:: [1] userId='.extract_id($this->post('userId')));
		
		$result = $this->_network->add_share_link(
			extract_id($this->post('userId'))
		);
		
		log_message('debug', 'Network/share_link_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# GET the user links
	public function links_get()
  	{
  		log_message('debug', 'Network/links_get');
  		log_message('debug', 'Network/links_get:: [1] userId='.extract_id($this->get('userId')));
  		
		$result = $this->_network->links(
			extract_id($this->get('userId'))
            //$this->get('userId')
		);

		
		log_message('debug', 'Network/links_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# POST a new user referral code
	public function referral_code_post()
  	{
  		log_message('debug', 'Network/referral_code_post');
  		log_message('debug', 'Network/referral_code_post:: [1] userId='.extract_id($this->post('userId')) . ' referralCode='.$this->post('newCode'));
  		
		$result = $this->_network->add_referral_code(
			extract_id($this->post('userId')),
			$this->post('newCode')
		);
		
		log_message('debug', 'Network/referral_code_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# GET a user referral code to check whether the suggested one is valid
	public function referral_code_get()
  	{
  		log_message('debug', 'Network/referral_code_get');
  		log_message('debug', 'Network/referral_code_get:: [1] userId='.extract_id($this->get('userId')) . ' referralCode='.$this->get('newCode'));
  		
		$result = $this->_network->check_referral_code(
			extract_id($this->get('userId')),
			$this->get('checkCode')
		);
		
		log_message('debug', 'Network/referral_code_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# GET the id of the referring user based on the code they used
	public function referrer_id_get()
	{
		log_message('debug', 'Network/referrer_id_get');
		log_message('debug', 'Network/referrer_id_get:: [1] referralCode='.$this->get('newCode'));
		
		$result = $this->_network->get_referrer_by_code(
			$this->get('referralCode')
		);
		
		log_message('debug', 'Network/referrer_id_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
}


/* End of controller file */