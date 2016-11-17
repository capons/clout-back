<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining actions related to events.
 *
 * @author Rebecca Lin <rebeccal@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 06/02/2016
 */
class Event extends REST_Controller
{
	#Constructor to set some default values at class load
	public function __construct()
	{
		parent::__construct();
	}

	# Get list of events
	public function list_get()
	{
		log_message('debug', 'Event/list_get');
		log_message('debug', 'Event/list_get:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

      $result = server_curl(CRON_SERVER_URL, array('__action'=>'get_event_list',
				'user_id'=>extract_id($this->get('userId')),
            'location'=>$this->get('location'),
            'details'=>$this->get('details'),
            'filters'=>$this->get('filters'),
            'limit'=>$this->get('limit'),
            'offset'=>$this->get('offset')
			));

		log_message('debug', 'Event/list_get:: [2] result='.json_encode($result));
		$this->response($result);
	}

	# Get event details
	public function details_get()
	{
		log_message('debug', 'Event/detail_get');
		log_message('debug', 'Event/detail_get:: [1] userId='.(!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

      $result = server_curl(CRON_SERVER_URL, array('__action'=>'get_event_details',
				'user_id'=>extract_id($this->get('userId')),
            'event_id'=>$this->get('eventId')
			));

		log_message('debug', 'Event/detail_get:: [2] result='.json_encode($result));
		$this->response($result);
	}

	# Record response of user to the event
	public function update_post()
	{
		log_message('debug', 'Event/update_post');
		log_message('debug', 'Event/update_post:: [1] userId='. (!empty($this->post('userId'))? extract_id($this->post('userId')): ''));

		$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_event_notice',
				'user_id'=>(!empty($this->post('userId'))? extract_id($this->post('userId')): ''),
            'promotion_id'=>$this->post('promotionId'),
            'store_id'=>$this->post('storeId'),
            'attend_status'=>$this->post('attendStatus'),
            'event_status'=>$this->post('eventStatus'),
				'schedule_time'=>$this->post('scheduledSendDate'),
				'base_url'=>$this->post('baseUrl')
			));

		log_message('debug', 'Event/update_post:: [5] result='.json_encode($result));
		$this->response($result);

	}

}


