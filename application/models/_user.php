<?php
/**
 * This class generates and formats user details. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/25/2015
 */
class _user extends CI_Model
{
	
	# Get a list of users based on the passed criteria
	function get_list($view='profile', $offset, $limit, $phrase, $category, $extraFields=array())
	{
		log_message('debug', '_user/get_list');
		log_message('debug', '_user/get_list:: [1] view='.$view.' offset='.$offset.' limit='.$limit.' phrase='.$phrase.' category='.$category.' extraFields='.json_encode($extraFields));
		
		# the user types to pull based on category being shown
		$types['admin'] = array('clout_owner','clout_admin_user');
		$types['store_owner'] = array('store_owner_owner','store_owner_admin_user');
		$types['shopper'] = array('invited_shopper','random_shopper');
		
		# extract and generate the ID condition
		$userIds = !empty($extraFields['viewUserIds'])? explode(',',$extraFields['viewUserIds']): array();
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";
		# values for the query parameters
		$values['id_condition'] = !empty($userIds)? " WHERE U.id IN ('".implode("','",$userIds)."') ":'';
		//if(!empty($category)) {
            $values['type_condition'] = empty($extraFields['viewUserIds']) ? " HAVING user_type IN ('" . implode("','", $types[$category]) . "') " : '';
        //} else {
          //  $values['type_condition'] = '';
        //}
		# id condition
		if(!empty($userIds)){
			$values['id_condition'] = " WHERE U.id IN ('".implode("','",$userIds)."') ";
			$values['order_condition'] = " ORDER BY FIELD(U.id, '".implode("','",$userIds)."') ";
		} 
		else $values['id_condition'] = $values['order_condition'] = "";
			
		# phrase condition
		if(!empty($phrase)){
			$values['phrase_condition'] = (!empty($userIds)? ' AND ': ' WHERE ')."(U.first_name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%' OR U.last_name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%' OR U.email_address LIKE '%".htmlentities($phrase, ENT_QUOTES)."%' )";
		}
		else $values['phrase_condition'] = "";

		
		$list = $this->_query_reader->get_list('get_user_details_list_'.$view, $values); //get_user_details_list_'.$view

		log_message('debug', '_user/get_list:: [2] list='.json_encode($list));
		
		# go through the list of the user details and pick out any extra information needed
		if(in_array($view, array('profile','network','activity'))) {
			$userIds = get_column_from_multi_array($list, 'user_id');
			$list = use_multi_column_as_key($list, 'user_id');
		
		
			# if you need to pick extra stuff
			if(!empty($userIds)){
				$extraList = array();
				
				if($view == 'profile') {
					$extraList = server_curl(IAM_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_user_view_details', 'variables'=>array('id_list'=>implode("','",$userIds)) ));
					$extraList = use_multi_column_as_key($extraList, 'user_id');
				} 
				else if($view == 'network') {
					$referralCount = $this->_query_reader->get_row_as_array('get_referral_level_count_by_id_list', array('id_list'=>implode("','",$userIds)));
					$levelValues = array();
					foreach($referralCount AS $row) {
						$extraList[$row['user_id']] = array('total_joined_level_1'=>$row['level_1_count'], 'total_joined_level_2'=>$row['level_2_count'], 'total_joined_level_3'=>$row['level_3_count'], 'total_joined_level_4'=>$row['level_4_count']);
						$levelValues[$row['user_id']] = $row;
					}
					# separate this from network list in case some users invited but none joined yet
					foreach($userIds AS $id){
						if(empty($extraList[$id])) $extraList[$id] = array();
						# level 1 invitations (by this user)
						$invitesLevel1 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_user_invite_count', 'variables'=>array('user_ids'=>$id) ));
						$extraList[$id]['total_invited_level_1'] = !empty($invitesLevel1['invite_count'])? $invitesLevel1['invite_count']: 0;
						# level 2 invitations
						if(!empty($levelValues[$id]['level_1'])) $invitesLevel2 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_user_invite_count', 'variables'=>array('user_ids'=>str_replace(',',"','",$levelValues[$id]['level_1'])) ));
						$extraList[$id]['total_invited_level_2'] = !empty($invitesLevel2['invite_count'])? $invitesLevel2['invite_count']: 0;
						# level 3 invitations
						if(!empty($levelValues[$id]['level_2'])) $invitesLevel3 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_user_invite_count', 'variables'=>array('user_ids'=>str_replace(',',"','",$levelValues[$id]['level_2'])) ));
						$extraList[$id]['total_invited_level_3'] = !empty($invitesLevel3['invite_count'])? $invitesLevel3['invite_count']: 0;
						# level 4 invitations
						if(!empty($levelValues[$id]['level_3'])) $invitesLevel4 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_user_invite_count', 'variables'=>array('user_ids'=>str_replace(',',"','",$levelValues[$id]['level_3'])) ));
						$extraList[$id]['total_invited_level_4'] = !empty($invitesLevel4['invite_count'])? $invitesLevel4['invite_count']: 0;
					}
					
				}
				else if($view == 'activity') {
					$extraList = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_user_total_messages', 'variables'=>array('id_list'=>implode("','",$userIds)) ));
					$extraList = use_multi_column_as_key($extraList, 'user_id');
				}
				
				log_message('debug', '_user/get_list:: [3] extraList='.json_encode($extraList));
				
				# fill in any extra fields not provided by the results array
				$missingIds = array_diff(array_keys($list), array_keys($extraList));
				
				# the fields to return based on view being shown
				$viewFields['profile-iam'] = array('permission_group');
				$viewFields['network-msg'] = array('total_invited_level_1', 'total_invited_level_2', 'total_invited_level_3', 'total_invited_level_4', 'total_joined_level_1', 'total_joined_level_2', 'total_joined_level_3', 'total_joined_level_4');
				$viewFields['activity-msg'] = array('total_msgs_received');
				
				foreach($missingIds AS $missingUserId){
					if($view == 'profile') foreach($viewFields['profile-iam'] AS $field) $extraList[$missingUserId][$field] = '';
					else if($view == 'network') foreach($viewFields['network-msg'] AS $field) $extraList[$missingUserId][$field] = '';
					else if($view == 'activity') foreach($viewFields['activity-msg'] AS $field) $extraList[$missingUserId][$field] = '';
				}
				
				
				foreach($list AS $userId=>$row) $list[$userId] = !empty($extraList[$userId])? array_merge($row, $extraList[$userId]): $row;
			}
		}
		
		log_message('debug', '_user/get_list:: [4] list='.json_encode($list));
		return $list;
	}
	
	
	
	
	# Get user settings
	function get_settings($userId, $fields)
	{
		log_message('debug', '_user/get_settings');
		log_message('debug', '_user/get_settings:: [1] userId='.$userId.' fields='.$fields);
		$result = array();
		
		$fieldArray = explode(',',$fields);
		$keys = array('userId','name','gender','photo','birthday','emailAddress','telephone', 'addressLine1', 'addressLine2', 'city', 'state', 'country', 'zipcode','dateJoined','passwordLastUpdated', 'groupName', 'groupType', 'groupId');
		$common = array_intersect($keys, $fieldArray);
		$diff = array_diff($fieldArray, $common);
		
		# There are some fields to pick direct from the database
		if(count($common) > 0) {
			$iamFields = array_intersect($common, array('passwordLastUpdated', 'groupName', 'groupType', 'groupId'));
			$generalFields = array_intersect($common, array('userId','name','gender','photo','birthday','emailAddress','telephone', 'addressLine1', 'addressLine2', 'city', 'state', 'country', 'zipcode','dateJoined'));
			
			# include IAM fields
			if(!empty($iamFields)){
				$result1 = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_user_settings', 'variables'=>array('user_id'=>$userId, 'fields'=>implode(',',$iamFields) ) ));
			}
			else $result1 = array();
			log_message('debug', '_user/get_settings:: [2] result1='.json_encode($result1));
			# include general data fields
			if(!empty($generalFields)){
				$result2 = $this->_query_reader->get_row_as_array('get_user_settings', array('user_id'=>$userId, 'fields'=>implode(',',$generalFields), 'base_photo_url'=>S3_URL ));
			}
			else $result2 = array();
			log_message('debug', '_user/get_settings:: [3] result2='.json_encode($result2));
			
			$result = array_merge($result1, $result2);
			log_message('debug', '_user/get_settings:: [4] result='.json_encode($result));
		}
		
		# There are fields to be picked as multi-dimension arrays
		if(count($diff) > 0) {
			foreach($diff AS $field){
				if($field == 'savedAddresses') $result[$field] = $this->_query_reader->get_list('get_saved_addresses', array('user_id'=>$userId, 'is_active'=>'Y'));
				
				if($field == 'savedEmails') $result[$field] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_saved_emails', 'variables'=>array('user_id'=>$userId, 'is_active'=>"N','Y") ));
				
				if($field == 'savedPhones') $result[$field] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_saved_phones', 'variables'=>array('user_id'=>$userId, 'is_active'=>"N','Y") ));
				
				if($field == 'notificationPreferences') $result[$field] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_single_column_as_array', 'query'=>'get_communication_preferences', 'column'=>'message_format', 'variables'=>array('user_id'=>$userId) ));
			}
		}
		
		log_message('debug', '_user/get_settings:: [5] result='.json_encode($result));
		return $result;
	}
	
	
	
	
	
	
	# Update a user's photo
	function update_photo($userId, $photoUrl)
	{
		log_message('debug', '_user/update_photo');
		log_message('debug', '_user/update_photo:: [1] userId='.$userId.' photoUrl='.$photoUrl);
		
		$fileUrl = download_from_url($photoUrl,FALSE);
		if(!empty($fileUrl)) $result = $this->_query_reader->run('add_user_photo', array('user_id'=>$userId, 'photo_url'=> $fileUrl )); 
		
		log_message('debug', '_user/update_photo:: [1] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'), 'photo_url'=>s3_url($fileUrl) );
	}
	
	
	
	
	
	# Update the user password
	function update_password($userId, $password)
	{
		log_message('debug', '_user/update_password');
		log_message('debug', '_user/update_password:: [1] userId='.$userId.' password='.$password);
		
		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'update_user_password', 'userId'=>$userId, 'password'=>$password, 'return'=>'plain'));
		log_message('debug', '_user/update_password:: [2] result='.$result);
		
		if($result) {
			$sendResult = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send', 
				'receiverId'=>$userId, 
				'message'=>array('code'=>'password_has_changed', 'securityemail'=>SECURITY_EMAIL), 
				'requiredFormats'=>array('email'), 
				'strictFormatting'=>FALSE,
				'return'=>'plain' 
			));
			log_message('debug', '_user/update_password:: [3] sendResult='.$sendResult);
			
		}
		return $result;
	}
	
	
	
	
	# Add address
	function add_address($userId, $addressLine1, $addressLine2, $city, $state, $country, $zipcode)
	{
		log_message('debug', '_user/add_address');
		log_message('debug', '_user/add_address:: [1] userId='.$userId.' addressLine1='.$addressLine1.' addressLine2='.$addressLine2.' city='.$city.' country='.$country.' zipcode='.$zipcode);
		
		$result = $this->_query_reader->run('add_user_address', array('user_id'=>$userId, 'address_line_1'=>$addressLine1, 'address_line_2'=>$addressLine2, 'city'=>$city, 'state'=>$state, 'country'=>$country, 'zipcode'=>$zipcode, 'address_type'=>'home'));
		log_message('debug', '_user/add_address:: [1] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	# Update the address type
	function update_address_type($userId, $contactId, $addressType)
	{
		log_message('debug', '_user/update_address_type');
		log_message('debug', '_user/update_address_type:: [1] userId='.$userId.' contactId='.$contactId.' addressType='.$addressType);
		
		$result = $this->_query_reader->run('update_address_type', array('contact_id'=>$contactId, 'address_type'=>$addressType));
		log_message('debug', '_user/update_address_type:: [2] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	# Remove a user address
	function remove_address($userId, $contactId)
	{
		log_message('debug', '_user/remove_address');
		log_message('debug', '_user/remove_address:: [1] userId='.$userId.' contactId='.$contactId);
		
		$result = $this->_query_reader->run('deactivate_user_address', array('contact_id'=>$contactId));
		log_message('debug', '_user/remove_address:: [2] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	
	# Communication method privacy
	function communication_privacy($userId, $method, $methodValue)
	{
		log_message('debug', '_user/communication_privacy');
		log_message('debug', '_user/communication_privacy:: [1] userId='.$userId.' method='.$method.' methodValue='.$methodValue);
		
		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 
					'query'=>(empty($methodValue)? 'delete': 'add').'_communication_privacy', 
					'variables'=>array('user_id'=>$userId, 'message_format'=>$method)
				));
		log_message('debug', '_user/communication_privacy:: [1] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	# Add email address
	function add_email_address($userId, $emailAddress)
	{
		log_message('debug', '_user/add_email_address');
		log_message('debug', '_user/add_email_address:: [1] userId='.$userId.' emailAddress='.$emailAddress);
		
		if(!empty($emailAddress)) {
				$contactId = server_curl(MESSAGE_SERVER_URL, array('__action'=>'add_data', 
		
					'query'=>'add_user_email_address', 'return'=>'plain', 
					'variables'=>array('user_id'=>$userId, 'email_address'=>$emailAddress)
				));
				log_message('debug', '_user/add_email_address:: [2] contactId='.$contactId);
		}
		
		if(!empty($contactId)) {
			$code = format_activation_code($contactId);
			$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send', 
				'receiverId'=>$userId, 
				'message'=>array('code'=>'contact_activation_code', 'method'=>'email', 'activationcode'=>$code, 'emailaddress'=>$emailAddress, 'contactvalue'=>$emailAddress), 
				'requiredFormats'=>array('email'), 
				'strictFormatting'=>TRUE,
				'return'=>'plain' 
			));
			log_message('debug', '_user/add_email_address:: [3] result='.$result);
			
			if($result) $result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'query'=>'add_email_activation_code', 'return'=>'plain', 'variables'=>array('contact_id'=>$contactId, 'activation_code'=>sha1($code)) )); 
			log_message('debug', '_user/add_email_address:: [4] result='.$result);
		}
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	# Add telephone
	function add_telephone($userId, $telephone, $provider, $isPrimary)
	{
		log_message('debug', '_user/add_telephone');
		log_message('debug', '_user/add_telephone:: [1] userId='.$userId.' telephone='.$telephone.' provider='.$provider.' $isPrimary='.$isPrimary);
		
		# Adding the contact telephone record
		$contactId = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'add_data', 'query'=>'add_user_telephone', 'return'=>'plain', 'variables'=>array('user_id'=>$userId, 'telephone'=>$telephone, 'provider_id'=>$provider, 'is_primary'=>$isPrimary ) ));
		
		# update the user profile if this is the primary phone
		if($isPrimary == 'Y')
		{
			$result = $this->_query_reader->run('update_user_value', array('field_name'=>'telephone', 'field_value'=>$telephone, 'user_id'=>$userId));
			log_message('debug', '_user/add_telephone:: [2] result='.$result);
			
			# Send the SMS with the code
			if($result) {
				$code = format_activation_code($userId); 
				$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send', 'receiverId'=>$userId, 'return'=>'plain',
					'message'=>array(
						'verificationcode'=>$code,
						'telephone'=>$telephone, 
						'code'=>'send_verification_code'
					),
					'requiredFormats'=>array('sms'), 
					'strictFormatting'=>TRUE
				));
				log_message('debug', '_user/add_telephone:: [3] result='.$result);
			}
		}
		
		# Send an activation code for non-primary contacts as well
		if(!empty($contactId) && $isPrimary == 'N') {
			$code = format_activation_code($contactId);
			$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send', 'receiverId'=>$userId, 'return'=>'plain',
					'message'=>array(
						'code'=>'contact_activation_code', 
						'method'=>'telephone', 
						'activationcode'=>$code, 
						'telephone'=>$telephone, 
						'contactvalue'=>$telephone
					),
					'requiredFormats'=>array('sms'), 
					'strictFormatting'=>TRUE
				));
			log_message('debug', '_user/add_telephone:: [4] result='.$result);
		}
		
		if(!empty($contactId) && $result) {
			$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_telephone_activation_code', 'variables'=>array('contact_id'=>$contactId, 'activation_code'=>sha1($code)) ));
			log_message('debug', '_user/add_telephone:: [5] result='.$result);
			# Activate a primary contact phone
			if($isPrimary == 'Y' && $result){
				$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'activate_telephone_by_code', 'variables'=>array('contact_id'=>$contactId, 'activation_code'=>sha1($code)) ));
				log_message('debug', '_user/add_telephone:: [6] result='.$result);
			}
		}
		
		if(!empty($result) && $result) $providerData = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_provider_by_id', 'variables'=>array('provider_id'=>$provider) ));
		log_message('debug', '_user/add_telephone:: [5] providerData='.json_encode($providerData));
		
		return array(
			'result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'), 
			'provider'=>(!empty($providerData['full_carrier_name'])? $providerData['full_carrier_name']: '')
			);
	}
	
	
	
	
	# Activate email address
	function activate_email_address($userId, $contactId, $code)
	{
		log_message('debug', '_user/activate_email_address');
		log_message('debug', '_user/activate_email_address:: [1] userId='.$userId.' contactId='.$contactId.' code='.$code);
		
		$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'strict'=>'true', 'query'=>'activate_email_by_code', 'variables'=>array('contact_id'=>$contactId, 'activation_code'=>sha1($code)) ));
		log_message('debug', '_user/activate_email_address:: [2] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	# Activate telephone
	function activate_telephone($userId, $contactId, $code)
	{
		log_message('debug', '_user/activate_telephone');
		log_message('debug', '_user/activate_telephone:: [1] userId='.$userId.' contactId='.$contactId.' code='.$code);
		
		$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'strict'=>'true', 'query'=>'activate_telephone_by_code', 'variables'=>array('contact_id'=>$contactId, 'activation_code'=>sha1($code)) ));
		log_message('debug', '_user/activate_telephone:: [2] result='.$result);
		
		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	# implement query based on parameters passed in
	function get_social_media_data($userId, $mediaType, $fields = array()) 
	{		
		log_message('debug', '_user/get_social_media_data');
		log_message('debug', '_user/get_social_media_data:: [1] userId='.$userId.' mediaType='.$mediaType.' fields='.json_encode($fields));
		
		# a) get user social media details
		$result = $this->_query_reader->get_row_as_array('get_social_media_'.$mediaType.'_data', array('user_id'=>$userId));
		
		if(!empty($result)) {
			
			# b) get photo list from DB in separate query
			if(in_array('photo_list', $fields)) {
				# TODO: add get_list('get_social_media_photos')
				#$result['photo_list'] = array(s3_url('image_45645426323.jpg'), s3_url('image_45645426323.jpg'));
				$result['photo_list'] = array();
			}
			
			# c) convert photo url to s3 url
			if(in_array('photo_url', $fields)) $result['photo_url'] = !empty($result['photo_url'])? s3_url($result['photo_url']): "";
		}
		
		log_message('debug', '_user/get_social_media_data:: [1] result='.json_encode($result));
		return $result;
	}
	
	
	
}

