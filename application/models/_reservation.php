<?php
/**
 * This class controls obtaining actions related to reservations.
 *
 * @author Rebecca Lin <rebeccal@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 05/24/2016
 */
class _reservation extends CI_Model
{
   # Get initial list of events and also can get archived list also with search functionality
   function get_reservation_list($userId, $offset, $limit, $filters)
   {
		log_message('debug', '_reservation/get_reservation_list');
		log_message('debug', '_reservation/get_reservation_list:: [1] userId='.$userId.' filters='.json_encode($filters));

		$status = (!empty($filters['status'])? $filters['status']: 'active');
      $limit = " LIMIT ".$offset.", ".$limit;

		$searchString = '';
		if($status == 'active') $searchString .= "AND DATE(schedule_date) >= NOW()";
		if(!empty($filters['promotionId'])) $searchString .= " AND _promotion_id = '".$filters['promotionId']."'";
		$searchPhrase = (!empty($filters['searchString'])? " WHERE MATCH(S.name) AGAINST('+".$filters['searchString']."')": '');
		$searchCatogory = (!empty($filters['categoryId'])? "AND (SELECT _category_id FROM store_sub_categories WHERE _store_id =S._store_id AND _category_id='".$filters['categoryId']."' LIMIT 1) IS NOT NULL ": '');
		$searchDate = (!empty($filters['reservationDate'])? "AND DATE(schedule_date) = DATE('".date('Y-m-d H:i:s',strtotime($filters['reservationDate']))."')": '');
		$searchString .= ($searchCatogory.$searchDate);

		log_message('debug', '_reservation/get_reservation_list:: [2] searchstring='.$searchString);

      $result = $this->_query_reader->get_list('get_list_of_reservations',array(
			'user_id'=>$userId,
			'status'=>$status,
			'limit_text'=>$limit,
			'search_string'=>$searchString,
         'phrase_condition'=>$searchPhrase
			));

      if($status == 'archived') $result = array_reverse($result);
      log_message('debug', '_reservation/get_reservation_list:: [3] result='.json_encode($result));

      return $result;
   }

   # Get reservation detail by reservation id
   function list_by_id($reservationId)
	{
		log_message('debug', '_reservation/list');
		log_message('debug', '_reservation/list:: [1] reservationId='.$reservationId);

		$result = $this->_query_reader->get_list('get_reservation_by_id',array('reservation_id'=>$reservationId));
		log_message('debug', '_reservation/list:: [2] result='.json_encode($result));

		return $result;
	}

   # Update status of a reservation if user cancel reservation
   function update_status($userId, $reservationStatus, $status, $promotionId, $storeId)
	{
		log_message('debug', '_reservation/update_status');
		log_message('debug', '_reservation/update_status:: [1] userId='.$userId.' reservationStatus='.$reservationStatus.' status='.$status.' promotionId='.$promotionId);
      $msg='';

      $result = $this->_query_reader->run('update_reservation_status',array(
		   'user_id'=>$userId,
			'status'=>$status,
         'reservation_status'=>$reservationStatus,
         'promotion_id'=>$promotionId
      ));
		log_message('debug', '_reservation/update_status:: [2] result='.json_encode($result));

      if($result){

			$result = server_curl(CRON_SERVER_URL, array('__action'=>'update_event_notice',
					'user_id'=>$userId,
	            'promotion_id'=>$promotionId,
	            'store_id'=>$storeId,
	            'attend_status'=>'cancelled',
	            'event_status'=>'read'
				));
			log_message('debug', '_reservation/update_status:: [3] result='.json_encode($result));

			# Send a confirmation message to user
			$info = $this->get_extra_information($userId, $storeId);
			log_message('debug', '_reservation/update_status:: [4] info='.json_encode($info));

         $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'cancel_reservation_notice_to_user')
         ));
         log_message('debug', '_reservation/update_status:: [5] template='.json_encode($template));

         $remider = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
                  'message'=>array(
                     'senderType'=>'user',
                     'sendToType'=>'list',
                     'sendTo'=>array($userId),
                     'template'=>$template,
                     'templateId'=>$template['id'],
                     'subject'=>$template['subject'],
                     'body'=>$template['details'],
                     'sms'=>$template['sms'],
                     'saveTemplate'=>'N',
                     'scheduledSendDate'=>'',
                     'sendDate'=>date('Y-m-d H:i:s',strtotime('now')),
                     'methods'=>array("system","email","sms"),
                     'templateVariables'=>array('storename'=>$info['store_name'])
                  ),
                  'userId'=>$userId,
                  'organizationId'=>'',
                  'organizationType'=>''
         ));
			log_message('debug', '_reservation/update_status:: [6] remider='.json_encode($remider));

			# Send out message to store owner immediately
         $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'cancel_reservation_notice_to_owner')
         ));
         log_message('debug', '_reservation/update_status:: [7] template='.json_encode($template));

			$message = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
						'message'=>array(
							'senderType'=>'user',
							'sendToType'=>'list',
							'sendTo'=>array($info['owner_id']),
                     'template'=>$template,
                     'templateId'=>$template['id'],
							'subject'=>$template['subject'],
							'body'=>$template['details'],
                     'sms'=>$template['sms'],
							'saveTemplate'=>'N',
							'scheduledSendDate'=>'',
							'sendDate'=>date('Y-m-d H:i:s',strtotime('now')),
							'methods'=>array("system","email","sms"),
                     'templateVariables'=>array('customername'=>$info['user']['scheduler_name'])
						),
						'userId'=>'1',
						'organizationId'=>'',
						'organizationType'=>''
			));
			log_message('debug', '_reservation/update_status:: [8] message='.json_encode($message));
		}

      return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'), 'msg'=>$msg);
	}

   # Update reservation details
   function update_details($userId, $reservationId, $details, $storeId)
	{
		log_message('debug', '_reservation/update_status');
		log_message('debug', '_reservation/update_status:: [1] userId='.$userId.' reservationId='.$reservationId.' details='.json_encode($details).' storeId='.$storeId);
      $msg='';

      $result = $this->_query_reader->run('update_reservation_details',array(
			'reservation_id'=>$reservationId,
		   'user_id'=>$userId,
         'scheduler_name'=>$details['schedulerName'],
         'scheduler_email'=>$details['schedulerEmail'],
         'scheduler_phone'=>$details['schedulerPhone'],
         'telephone_provider_id'=>$details['telephoneProviderId'],
         'phone_type'=>$details['phoneType'],
         'schedule_date'=>date('Y-m-d H:i:s',strtotime($details['scheduleDate'])),
         'number_in_party'=>$details['numberInParty'],
         'special_request'=>$details['specialRequest']
		));

      if($result){

         # Send a confirmation message to user
         $info = $this->_reservation->get_extra_information($userId, $storeId);
         log_message('debug', '_reservation/update_status:: [2] info='.json_encode($info));

         $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'modify_reservation_notice_to_user')
         ));
         log_message('debug', '_reservation/update_status:: [3] template='.json_encode($template));

			$remider = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
						'message'=>array(
							'senderType'=>'user',
							'sendToType'=>'list',
							'sendTo'=>array($userId),
                     'template'=>$template,
                     'templateId'=>$template['id'],
							'subject'=>$template['subject'],
							'body'=>$template['details'],
                     'sms'=>$template['sms'],
							'saveTemplate'=>'N',
							'scheduledSendDate'=>'',
							'sendDate'=>date('Y-m-d H:i:s',strtotime('now')),
							'methods'=>array("system","email","sms"),
                     'templateVariables'=>array('storename'=>$info['store_name'])
						),
						'userId'=>$userId,
						'organizationId'=>'',
						'organizationType'=>''
			));
         log_message('debug', '_reservation/update_status:: [4] remider='.json_encode($remider));

         # Send out message to store owner immediately
         $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'modify_reservation_notice_to_owner')
         ));
         log_message('debug', '_reservation/update_status:: [5] template='.json_encode($template));

			$message = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
						'message'=>array(
							'senderType'=>'user',
							'sendToType'=>'list',
							'sendTo'=>array($info['owner_id']),
                     'template'=>$template,
                     'templateId'=>$template['id'],
							'subject'=>$template['subject'],
							'body'=>$template['details'],
                     'sms'=>$template['sms'],
							'saveTemplate'=>'N',
							'scheduledSendDate'=>'',
							'sendDate'=>date('Y-m-d H:i:s',strtotime('now')),
							'methods'=>array("system","email","sms"),
                     'templateVariables'=>array('customername'=>$info['user']['scheduler_name'])
						),
						'userId'=>'1',
						'organizationId'=>'',
						'organizationType'=>''
			));
         log_message('debug', '_reservation/update_status:: [6] message='.json_encode($message));

      } else $msg = 'Can not update reservation';

		log_message('debug', '_reservation/update_status:: [7] result='.json_encode($result));
      return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'), 'msg'=>$msg);
	}

   # Add a new reservation
   function add($userId, $details, $baseUrl)
	{
		log_message('debug', '_reservation/add');
		log_message('debug', '_reservation/add:: [1] userId='.$userId.' details='.json_encode($details));
      $msg='';
      $user = array();

      #  if empty then query user details
      if(empty($details['schedulerName'])) {
         $user = $this->_query_reader->get_row_as_array('get_user_contact_information', array('user_id'=>$userId));
      }
      log_message('debug', '_reservation/add:: [2] user='.json_encode($user));

      $result = $this->_query_reader->run('add_store_schedule',array(
			'promotion_id'=>$details['promotionId'],
		   'user_id'=>$userId,
         'scheduler_name'=>(!empty($details['schedulerName'])? $details['schedulerName']: $user['scheduler_name'] ),
         'scheduler_email'=>(!empty($details['schedulerEmail'])? $details['schedulerEmail']: $user['scheduler_email'] ),
         'scheduler_phone'=>(!empty($details['schedulerPhone'])? $details['schedulerPhone']: $user['scheduler_phone'] ),
         'phone_provider_id'=>(!empty($details['telephoneProviderId'])? $details['telephoneProviderId']: $user['provider_id'] ),
         'phone_type'=>(!empty($details['phoneType'])? $details['phoneType']: $user['phone_type'] ),
         'schedule_date'=>date('Y-m-d H:i:s',strtotime($details['scheduleDate'])),
         'number_in_party'=>$details['numberInParty'],
         'special_request'=>$details['specialRequest']
		));
      log_message('debug', '_reservation/add:: [3] result='.json_encode($result));

      if($result && !empty($details['schedulerName'])){

         $scheduleDate = date('Y-m-d H:i:s',strtotime($details['scheduleDate'].' -2 hours'));
   		log_message('debug','_reservation/add:: [4] scheduleDate='.$scheduleDate);

         # Get event details to show in the message
         $eventDetails = server_curl(CRON_SERVER_URL, array('__action'=>'get_event_details',
               'user_id'=>$userId,
               'event_id'=>$details['promotionId']
            ));
         $eventDetails = $eventDetails[0];
         log_message('debug', '_reservation/add:: [5] eventDetails='.json_encode($eventDetails));

         # Prepare the body depends on different conditions for message
         // $body = "You have an upcoming event in 2 hours at ".$eventDetails['store_name']." - ".$eventDetails['promotion_title'].". ";
         $templateVariables = array('storename'=>$eventDetails['store_name'],
                                    'promotiontitle'=>$eventDetails['promotion_title'],
                                    'reservationlink'=>$baseUrl."c/".encrypt_value($eventDetails['event_id']."--".format_id($userId)."--change"),
                                    'requirescheckin'=>'',
                                    'numberofpeople'=>'');
         if($eventDetails['requires_checkin'] == 'Y') {
            $templateVariables['requirescheckin'] = "This event requires that you check in on arrival by clicking this link: ".$baseUrl."c/".encrypt_value($eventDetails['event_id']."--".format_id($userId)."--checkin").". You can also click the check-in button on the business’s profile page. ";
         }

         if($details['numberInParty'] > 1) {
            $templateVariables['numberofpeople'] = "You expect to bring ".$details['numberInParty']." guests. ";
         }
         log_message('debug', '_reservation/add:: [6] templateVariables='.json_encode($templateVariables));

         # Schedule a reminder for user
         $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'reservation_reminder_to_user')
         ));
         log_message('debug', '_reservation/add:: [7] template='.json_encode($template));

         $remider = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
						'message'=>array(
							'senderType'=>'user',
							'sendToType'=>'list',
							'sendTo'=>array($userId),
                     'template'=>$template,
                     'templateId'=>$template['id'],
							'subject'=>$template['subject'],
							'body'=>$template['details'],
                     'sms'=>$template['sms'],
							'saveTemplate'=>'N',
							'scheduledSendDate'=>$scheduleDate,
							'sendDate'=>'',
							'methods'=>array("system","email","sms"),
                     'templateVariables'=>$templateVariables
						),
						'userId'=>$userId,
						'organizationId'=>'',
						'organizationType'=>''
			));
         log_message('debug', '_reservation/add:: [8] remider='.json_encode($remider));

         $info = $this->get_extra_information($userId, $eventDetails['store_id']);
         log_message('debug', '_reservation/add:: [9] info='.json_encode($info));

         # Send out message to store owner immediately
         $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'new_reservation_notice')
         ));
         log_message('debug', '_reservation/add:: [10] template='.json_encode($template));

         $message = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
						'message'=>array(
							'senderType'=>'user',
							'sendToType'=>'list',
							'sendTo'=>array($info['owner_id']),
                     'template'=>$template,
                     'templateId'=>$template['id'],
							'subject'=>$template['subject'],
							'body'=>$template['details'],
                     'sms'=>$template['sms'],
							'saveTemplate'=>'N',
							'scheduledSendDate'=>'',
							'sendDate'=>date('Y-m-d H:i:s',strtotime('now')),
							'methods'=>array("system","email","sms"),
                     'templateVariables'=>array('customername'=>$info['user']['scheduler_name'])
						),
						'userId'=>'1',
						'organizationId'=>'',
						'organizationType'=>''
			));
         log_message('debug', '_reservation/add:: [11] message='.json_encode($message));

      } else $msg = 'Can not make reservation';

      return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'), 'msg'=>$msg);
	}

   # return extra inforamtion to generate subject and detail of messages
   function get_extra_information($userId, $storeId)
   {
      log_message('debug', '_reservation/get_extra_information');
      log_message('debug', '_reservation/get_extra_information:: [1] userId='.$userId.' storeId='.$storeId);

      $storeName = $this->_query_reader->get_row_as_array('get_store_name_by_id', array(
            'store_id'=>$storeId
      ));
      log_message('debug', '_reservation/get_extra_information:: [2] storeName='.json_encode($storeName));

      $ownerId = $this->_query_reader->get_row_as_array('get_store_owner_id', array(
         'store_id'=>$storeId
      ));
      log_message('debug', '_reservation/get_extra_information:: [3] store_owner_id='.json_encode($ownerId));

      $user = $this->_query_reader->get_row_as_array('get_user_contact_information', array(
         'user_id'=>$userId
      ));
      log_message('debug', '_reservation/get_extra_information:: [4] user='.json_encode($user));

      return array('store_name'=>$storeName['name'], 'owner_id'=>$ownerId['user_id'], 'user'=>$user);
   }
}

