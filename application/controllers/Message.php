<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining network information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 10/03/2015
 */
class Message extends REST_Controller
{

	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
	}



	# Get a list of messages
	public function list_get()
  	{
  		log_message('debug', 'Message/list_get');
  		log_message('debug', 'Message/list_get:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list_of_messages',
				'userId'=>extract_id($this->get('userId')),
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'),
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
				'filters'=>(!empty($this->get('filters'))? $this->get('filters'): array())
			));

		log_message('debug', 'Message/list_get:: [2] result='.json_encode($result));
		$this->response($result);


	}



	# Get statistics messages
	public function statistics_get()
  	{
  		log_message('debug', 'Message/statistics_get');
  		log_message('debug', 'Message/statistics_get:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_statistics',
				'userId'=>extract_id($this->get('userId')),
				'fields'=>$this->get('fields')
			));

		log_message('debug', 'Message/statistics_get:: [2] result='.json_encode($result));
		$this->response($result);
	}



	# Get the message details
	public function details_get()
  	{
  		log_message('debug', 'Message/details_get');
  		log_message('debug', 'Message/details_get:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_details',
				'userId'=>extract_id($this->get('userId')),
				'messageId'=>$this->get('messageId')
			));

		log_message('debug', 'Message/details_get:: [2] result='.json_encode($result));
		$this->response($result);
	}




	# GET message templates for the given user
	public function templates_get()
	{
		log_message('debug', 'Message/templates_get');
		log_message('debug', 'Message/templates_get:: [1] ownerId='.extract_id($this->get('ownerId'))
				.' ownerType='.$this->get('ownerType'));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_templates',
				'ownerId'=>extract_id($this->get('ownerId')),
				'ownerType'=>$this->get('ownerType'),
				'phrase'=>(!empty($this->get('phrase'))? $this->get('phrase'): ''),
				'offset'=>(!empty($this->get('offset'))? $this->get('offset'): '0'),
				'limit'=>(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE)
			));

		log_message('debug', 'Message/templates_get:: [2] result='.json_encode($result));
		$this->response($result);
	}





	# POST message like
	public function like_message_post()
	{
		log_message('debug', 'Message/like_message_post');
		log_message('debug', 'Message/like_message_post:: [1] msg='.$this->post('message').' userId='.extract_id($this->post('userId')));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'like_message',
				'userId'=>extract_id($this->post('userId')),
				'messages'=>$this->post('messages'),
				'action'=>$this->post('action')
			));

		log_message('debug', 'Message/like_message_post:: [2] result='.json_encode($result));
		$this->response($result);
	}





	# POST message mark
	public function add_mark_post()
	{
		log_message('debug', 'Message/add_mark_post');
		log_message('debig', 'Message/add_mark_post:: [1] msg='.$this->post('message').' userId='.extract_id($this->post('userId')));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'add_mark',
				'userId'=>extract_id($this->post('userId')),
				'messages'=>$this->post('messages'),
				'action'=>$this->post('action')
			));

		log_message('debug', 'Message/add_mark_post:: [2] result='.json_encode($result));
		$this->response($result);
	}



	# POST contact message details
	public function contact_post()
	{
		log_message('debug', 'Message/contact_post');
		log_message('debug', 'Message/contact_post:: [1] name='.$this->post('name').' email='.$this->post('emailAddress').' telephone='.$this->post('telephone').' msg='.$this->post('message').' userId='.(!empty($this->post('userId'))? extract_id($this->post('userId')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send_contact_msg',
				'name'=>$this->post('name'),
				'emailAddress'=>$this->post('emailAddress'),
				'telephone'=>$this->post('telephone'),
				'message'=>$this->post('message'),
				'userId'=>(!empty($this->post('userId'))? extract_id($this->post('userId')): '')
			));

		log_message('debug', 'Message/contact_post:: [2] result='.json_encode($result));
		$this->response($result);
	}



	# GET contact message details
	public function contact_get()
	{
		log_message('debug', 'Message/contact_get');
		log_message('debug', 'Message/contact_get:: [1] name='.$this->get('name').' email='.$this->get('emailAddress').' telephone='.$this->post('telephone').' msg='.$this->get('message').' userId='.(!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send_contact_msg',
				'name'=>$this->get('name'),
				'emailAddress'=>$this->get('emailAddress'),
				'telephone'=>$this->post('telephone'),
				'message'=>$this->get('message'),
				'userId'=>(!empty($this->get('userId'))? extract_id($this->get('userId')): '')
			));

		log_message('debug', 'Message/contact_get:: [2] result='.json_encode($result));
		$this->response($result);
	}


	# POST message for sending out
	public function send_post()
	{
		log_message('debug', 'Message/send_post');
		log_message('debug', 'Message/send_post:: [1] msg='.json_encode($this->post('message')));

        $result = '';
        //add new if
        if(!empty($this->post('userId'))){
            if(is_numeric($this->post('userId'))){
                $result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
                    'message'=>$this->post('message'),
                    'userId'=>(!empty($this->post('userId'))? $this->post('userId'): ''),
                    'organizationId'=>(!empty($this->post('organizationId'))? extract_id($this->post('organizationId')): ''),
                    'organizationType'=>(!empty($this->post('organizationType'))? extract_id($this->post('organizationType')): '')
                ));
            } else {
                $result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
                    'message'=>$this->post('message'),
                    'userId'=>(!empty($this->post('userId'))? extract_id($this->post('userId')): ''),
                    'organizationId'=>(!empty($this->post('organizationId'))? extract_id($this->post('organizationId')): ''),
                    'organizationType'=>(!empty($this->post('organizationType'))? extract_id($this->post('organizationType')): '')
                ));
            }
        }

        /* old
		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
				'message'=>$this->post('message'),
				'userId'=>(!empty($this->post('userId'))? extract_id($this->post('userId')): ''),
				'organizationId'=>(!empty($this->post('organizationId'))? extract_id($this->post('organizationId')): ''),
				'organizationType'=>(!empty($this->post('organizationType'))? extract_id($this->post('organizationType')): '')
			));
           */
		log_message('debug', 'Message/send_post:: [2] result='.json_encode($result));
		$this->response($result);


	}


	public function send_get()
	{
		log_message('debug', 'Message/send_get');
		log_message('debug', 'Message/send_get:: [1] msg='.$this->get('message').' orgId='.(!empty($this->get('organizationId'))? extract_id($this->get('organizationId')): '').' orgType='.(!empty($this->get('organizationType'))? extract_id($this->get('organizationType')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
				'message'=>$this->get('message'),
				'userId'=>(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				'organizationId'=>(!empty($this->get('organizationId'))? extract_id($this->get('organizationId')): ''),
				'organizationType'=>(!empty($this->get('organizationType'))? extract_id($this->get('organizationType')): '')
			));

		log_message('debug', 'Message/send_get:: [2] result='.json_encode($result));
		$this->response($result);
	}



	# POST unsubscribe request
	public function unsubscribe_post()
	{
		log_message('debug', 'Message/unsubscribe_post');
		log_message('debug', 'Message/unsubscribe_post:: [1] msg='.$this->get('message').' orgId='.(!empty($this->get('organizationId'))? extract_id($this->get('organizationId')): '').' orgType='.(!empty($this->get('organizationType'))? extract_id($this->get('organizationType')): ''));

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'unsubscribe',
				'emailAddress'=>(!empty($this->post('emailAddress'))? $this->post('emailAddress'): ''),
				'telephone'=>(!empty($this->post('telephone'))? $this->post('telephone'): ''),
				'reason'=>(!empty($this->post('reason'))? $this->post('reason'): '')
			));

		log_message('debug', 'Message/unsubscribe_post:: [2] result='.json_encode($result));
		$this->response($result);
	}

}


/* End of controller file */
