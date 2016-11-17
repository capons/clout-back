<?php
/**
 * This class generates and formats setting details.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/26/2015
 */
class _setting extends CI_Model
{

	# Get list of permission groups
	function permission_group_list($category, $offset, $limit, $phrase)
	{
		log_message('debug', '_setting/permission_group_list');
		log_message('debug', '_setting/permission_group_list:: [1] category='.$category.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['phrase_condition'] = !empty($phrase)? " AND G.name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%'": '';
		$values['category_condition'] = $category != 'all'? " AND G.group_category ='".$category."' ": "";
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_permission_group_list', 'variables'=>$values));
		log_message('debug', '_setting/permission_group_list:: [2] result='.json_encode($result));

		return $result;
	}





	# Get list permissions
	function permission_list($offset, $limit, $phrase)
	{
		log_message('debug', '_setting/permission_list');
		log_message('debug', '_setting/permission_list:: [1] offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['phrase_condition'] = !empty($phrase)? " AND P.display LIKE '%".htmlentities($phrase, ENT_QUOTES)."%'": '';
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$list = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_permission_list', 'variables'=>$values));

		$finalList = array();
		#Group the permissions by their category
		foreach($list AS $row) {
			if(empty($finalList[$row['category']])) $finalList[$row['category']] = array();
			$finalList[$row['category']][$row['permission_id']] = $row;
		}
		log_message('debug', '_setting/permission_list:: [2] finalList='.json_encode($finalList));

		return $finalList;
	}




	# Get the permission group details
	function permission_group_details($groupId)
	{
		log_message('debug', '_setting/permission_group_details');
		log_message('debug', '_setting/permission_group_details:: [1] groupId='.$groupId);

		$details = array('id'=>'', 'name'=>'', 'is_removable', 'rules'=>array(), 'permissions'=>array());

		# Details
		$group = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_group_by_id', 'variables'=>array('group_id'=>$groupId )));
		$details['id'] = $group['id'];
		$details['name'] = $group['name'];
		$details['is_removable'] = $group['is_removable'];
		$details['group_type'] = $group['group_type'];
		$details['group_category'] = $group['group_category'];

		# Rules
		$rules = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_group_rules', 'variables'=>array('group_id'=>$groupId )));

		$finalList = array();
		# Store the rules by their id
		foreach($rules AS $row) {
			$finalList[$row['id']] = $row;
		}
		$details['rules'] = $finalList;

		# Permissions
		$permissions = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_group_permissions', 'variables'=>array('group_id'=>$groupId )));
		$finalList = array();
		# Group the permissions by their category
		foreach($permissions AS $row) {
			if(empty($finalList[$row['category']])) $finalList[$row['category']] = array();
			$finalList[$row['category']][$row['permission_id']] = $row;
		}
		$details['permissions'] = $finalList;

		log_message('debug', '_setting/permission_group_details:: [2] details='.json_encode($details));

		return $details;
	}




	# Get the list of rule categories
	function rule_categories($offset, $limit, $phrase)
	{
		log_message('debug', '_setting/rule_categories');
		log_message('debug', '_setting/rule_categories:: [1] offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['phrase_condition'] = !empty($phrase)? " AND A.category_display LIKE '%".htmlentities($phrase, ENT_QUOTES)."%'": '';
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_rule_category_list', 'variables'=>$values));
		log_message('debug', '_setting/permission_group_details:: [2] result='.json_encode($result));

		return $result;
        //return $values;
	}





	# the permission group types
	function permission_group_types($category='all',$userId='')
	{
		log_message('debug', '_setting/permission_group_types');
		log_message('debug', '_setting/permission_group_types:: [1] category='.$category.' userId='.$userId);

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_permission_group_types', 'variables'=>array(
			'category_condition'=>($category != 'all'? " AND group_category='".$category."' ": "")
		)));
		log_message('debug', '_setting/permission_group_types:: [2] result='.json_encode($result));

		return $result;
	}








	# Get the list of rule names
	function rule_names($category, $offset, $limit, $phrase)
	{
		log_message('debug', '_setting/rule_names');
		log_message('debug', '_setting/rule_names:: [1] category='.$category.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['category_condition'] = !empty($category)? " AND category ='".$category."' ": '';
		$values['phrase_condition'] = !empty($phrase)? " AND display LIKE '%".htmlentities($phrase, ENT_QUOTES)."%'": '';
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_rule_name_list', 'variables'=>$values));
		log_message('debug', '_setting/rule_names:: [2] result='.json_encode($result));

		return $result;
	}




	# Get the rule details
	function rule_details($ruleId)
	{
		log_message('debug', '_setting/rule_details');
		log_message('debug', '_setting/rule_details:: [1] ruleId='.$ruleId);

		$values['category_condition'] = '';
		$values['phrase_condition'] = " AND id='".$ruleId."' ";
		$values['limit_text'] = '';

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_rule_name_list', 'variables'=>$values));
		log_message('debug', '_setting/rule_details:: [2] result='.json_encode($result));

		return $result;
	}



	# Save the details of a permission group
	function save_permission_group($groupId, $groupName, $groupType, $groupCategory, $rules, $permissions, $userId)
	{
		log_message('debug', '_setting/save_permission_group');
		log_message('debug', '_setting/save_permission_group:: [1] groupId='.$groupId.' groupName='.$groupName.' groupType='.$groupType.' groupCategory='.$groupCategory.' rules='.$rules.' permissions='.$permissions.' userId='.$userId);

		# This is a new group
		if(empty($groupId)) {
			$groupId = server_curl(IAM_SERVER_URL,  array('__action'=>'add_data', 'return'=>'plain', 'query'=>'add_permission_group',
				'variables'=>array(
					'name'=>htmlentities($groupName, ENT_QUOTES),
					'group_type'=>(!empty($groupType)? $groupType: 'random_shopper'),
					'group_category'=>(!empty($groupCategory)? $groupCategory: 'shopper'),
					'is_removable'=>'Y',
					'status'=>'active',
					'user_id'=>$userId
				)));
			log_message('debug', '_setting/save_permission_group:: [2] groupId='.$groupId);

			$result = !empty($groupId);
		}
		# Simply updating a group's details
		else {
			$result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'update_permission_group',
				'variables'=>array(
					'name'=>htmlentities($groupName, ENT_QUOTES),
					'group_type'=>(!empty($groupType)? $groupType: 'random_shopper'),
					'user_id'=>$userId,
					'group_id'=>$groupId
				)));
		}
		log_message('debug', '_setting/save_permission_group:: [3] result='.$result);

		# Proceed if the above passed
		if($result){
			# Remove the old group permissions and then add the new ones
			$result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_group_permissions', 'variables'=>array('group_id'=>$groupId)));

			if($result) $result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_group_permissions', 'variables'=>array('group_id'=>$groupId, 'permission_ids'=>implode("','", $permissions), 'user_id'=>$userId )));
			# Remove the old group rules and then add the new ones
			if($result) $result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_group_rules', 'variables'=>array('group_id'=>$groupId)));

			if($result) $result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_group_rules', 'variables'=>array('group_id'=>$groupId, 'rule_ids'=>implode("','", $rules), 'user_id'=>$userId )));
		}
		log_message('debug', '_setting/save_permission_group:: [4] result='.$result);

		return array('success'=>($result? 'TRUE': 'FALSE'));
	}









	# Update permission group status
	function update_permission_group_status($groupId, $status, $userId)
	{
		log_message('debug', '_setting/update_permission_group_status');
		log_message('debug', '_setting/update_permission_group_status:: [1] groupId='.$groupId.' status='.$status.' userId='.$userId);

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'update_permission_group_status', 'variables'=>array('group_id'=>$groupId, 'status'=>$status, 'user_id'=>$userId) ));
		log_message('debug', '_setting/save_permission_group:: [2] result='.$result);

		return array('success'=>($result? 'TRUE': 'FALSE'));
	}




	# Get the list of cron jobs
	function cron_jobs($offset, $limit, $phrase)
	{
		log_message('debug', '_setting/cron_jobs');
		log_message('debug', '_setting/cron_jobs:: [1] offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['phrase_condition'] = !empty($phrase)? " AND S.activity_code LIKE '%".htmlentities(str_replace(' ', '_', strtolower($phrase)), ENT_QUOTES)."%'": '';
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = server_curl(CRON_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_cron_job_list', 'variables'=>$values ));
		log_message('debug', '_setting/cron_jobs:: [2] result='.json_encode($result));

		return $result;
	}





	# Update cron job status
	function update_cron_job_status($jobId, $status, $userId)
	{
		log_message('debug', '_setting/update_cron_job_status');
		log_message('debug', '_setting/update_cron_job_status:: [1] jobId='.$jobId.' status='.$status.' userId='.$userId);

		$response = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'query'=>'update_cron_job_status', 'variables'=>array(
				'job_id'=>$jobId,
				'is_done'=>($status == 'active'? 'N': 'Y'),
				'user_id'=>$userId
			) ));
		log_message('debug', '_setting/cron_jobs:: [2] response='.json_encode($response));

		return array('success'=>((!empty($response['result']) && $response['result'] == 'SUCCESS')? 'TRUE': 'FALSE'));
	}




	# Get the list of score settings
	function score_settings($type, $offset, $limit, $phrase)
	{
		log_message('debug', '_setting/score_settings');
		log_message('debug', '_setting/score_settings:: [1] type='.$type.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['type_condition'] = " AND (categories ='".$type."' OR categories LIKE '".$type.",%' OR categories LIKE '%,".$type."') ";
		$values['phrase_condition'] = !empty($phrase)? " AND (C.code LIKE '%".htmlentities(str_replace(' ', '_', strtolower($phrase)), ENT_QUOTES)."%' OR C.description LIKE '%".htmlentities($phrase, ENT_QUOTES)."%') ": '';
		$values['limit_text'] = " ";

		$rawSettingsList = $this->_query_reader->get_list('get_score_settings_list', $values);

		# Now organize the settings for display
		$fragmentation=array();
		
		# Clout Score
		if($type == 'clout'){
			$fragmentation['activity'] = array('facebook_connected','email_verified','mobile_verified','profile_photo_added','bank_verified_and_active','credit_verified_and_active',
					'location_services_activated','push_notifications_activated','first_adrelated_payment_success',
					'member_processed_promo_payment_last7days','has_answered_survey_in_last90days','number_of_surveys_answered_in_last90days','first_payment_success','member_processed_payment_last7days');
			$fragmentation['referrals'] = array('number_of_direct_referrals_last180days', 'number_of_direct_referrals_last360days', 'total_direct_referrals', 'number_of_network_referrals_last180days', 'number_of_network_referrals_last360days', 'total_network_referrals');
			$fragmentation['spending_of_my_referrals'] = array('spending_of_direct_referrals_last180days', 'spending_of_direct_referrals_last360days', 'total_spending_of_direct_referrals', 'spending_of_network_referrals_last180days', 'spending_of_network_referrals_last360days', 'total_spending_of_network_referrals');
			$fragmentation['overall_spending'] = array('spending_last180days', 'spending_last360days', 'spending_total');
			$fragmentation['ad_related_spending'] = array('ad_spending_last180days', 'ad_spending_last360days', 'ad_spending_total');
			$fragmentation['linked_accounts'] = array('cash_balance_today','average_cash_balance_last24months','credit_balance_today','average_credit_balance_last24months','has_first_public_checkin_success','has_public_checkin_last7days');
		}
		# Store Score
		else if($type == 'store') {
			$fragmentation['linked_accounts'] = array('facebook_connected', 'email_verified', 'mobile_verified', 'profile_photo_added', 'bank_verified_and_active', 'credit_verified_and_active', 'location_services_activated', 'push_notifications_activated','cash_balance_today', 'average_cash_balance_last24months', 'credit_balance_today', 'average_credit_balance_last24months');
			$fragmentation['activity'] = array('first_payment_success', 'member_processed_payment_last7days', 'first_adrelated_payment_success', 'member_processed_promo_payment_last7days', 'has_first_public_checkin_success', 'has_public_checkin_last7days', 'has_answered_survey_in_last90days', 'number_of_surveys_answered_in_last90days');
			$fragmentation['referrals'] = array('number_of_direct_referrals_last180days', 'number_of_direct_referrals_last360days', 'total_direct_referrals', 'number_of_network_referrals_last180days', 'number_of_network_referrals_last360days', 'total_network_referrals');
			$fragmentation['spending_of_my_referrals'] = array('spending_of_direct_referrals_last180days', 'spending_of_direct_referrals_last360days', 'total_spending_of_direct_referrals', 'spending_of_network_referrals_last180days', 'spending_of_network_referrals_last360days', 'total_spending_of_network_referrals');
			$fragmentation['overall_spending'] = array('spending_last180days', 'spending_last360days', 'spending_total');
			$fragmentation['ad_related_spending'] = array('ad_spending_last180days', 'ad_spending_last360days', 'ad_spending_total');
			$fragmentation['same_store_spending'] = array('my_store_spending_last90days', 'my_store_spending_last12months', 'my_store_spending_lifetime');
			$fragmentation['same_chain_spending'] = array('my_chain_spending_last90days', 'my_chain_spending_last12months', 'my_chain_spending_lifetime');
			$fragmentation['competitor_spending'] = array('my_direct_competitors_spending_last90days', 'my_direct_competitors_spending_last12months', 'my_direct_competitors_spending_lifetime');
			$fragmentation['category_spending'] = array('my_category_spending_last90days', 'my_category_spending_last12months', 'my_category_spending_lifetime');
			$fragmentation['related_category_spending'] = array('related_categories_spending_last90days', 'related_categories_spending_last12months', 'related_categories_spending_lifetime');
			$fragmentation['activity'] = array('did_store_survey_last90days', 'did_competitor_store_survey_last90days', 'did_my_category_survey_last90days', 'did_related_categories_survey_last90days');
		}

		$finalList = array();
		$totalList = array();

		foreach($rawSettingsList AS $row){
			foreach($fragmentation AS $category=>$criteria){
				# Create a category section if it does not already exist
				if(!array_key_exists($category, $finalList)) $finalList[$category] = array();
				if(!array_key_exists($category, $totalList)) $totalList[$category] = 0;

				# Add the setting in the appropriate category
				foreach($criteria AS $code){
					if($row['code'] == $code) {
						array_push($finalList[$category], $row);
						$totalList[$category] += $row['max_score'];
					}
				}
			}
		}
		log_message('debug', '_setting/score_settings:: [2] finalList='.json_encode($finalList));
		log_message('debug', '_setting/score_settings:: [3] totalList='.json_encode($totalList));

		return array('list'=>$finalList, 'totals'=>$totalList);
	}




	# Update score value
	function update_score_value($settingId, $scoreValue, $scoreType, $userId)
	{
		log_message('debug', '_setting/update_score_value');
		log_message('debug', '_setting/update_score_value:: [1] settingId='.$settingId.' scoreValue='.$scoreValue.' scoreType='.$scoreType.' userId='.$userId);

		$result = $this->_query_reader->run('update_score_value', array(
			'setting_id'=>$settingId,
			'score_value'=>$scoreValue,
			'score_field'=>($scoreType == 'max'? 'high_range': 'low_range')
		));
		log_message('debug', '_setting/update_score_value:: [2] result='.$result);

		return array('success'=>($result? 'TRUE': 'FALSE'));
	}






	# Get the list of rule settings
	function rule_settings($offset, $limit, $phrase)
	{
		log_message('debug', '_setting/rule_settings');
		log_message('debug', '_setting/rule_settings:: [1] offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$values['phrase_condition'] = !empty($phrase)? " AND R.display LIKE '%".htmlentities($phrase, ENT_QUOTES)."%'": '';
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_rule_settings_list', 'variables'=>$values));
		log_message('debug', '_setting/rule_settings:: [2] result='.json_encode($result));

		return $result;
	}




	# Update the status of a rule setting
	function update_rule_setting_status($ruleId, $status, $userId)
	{
		log_message('debug', '_setting/update_rule_setting_status');
		log_message('debug', '_setting/update_rule_setting_status:: [1] ruleId='.$ruleId.' status='.$status.' userId='.$userId);

		$result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'update_rule_setting_status', 'variables'=>array('rule_id'=>$ruleId, 'status'=>$status, 'user_id'=>$userId) ));
		log_message('debug', '_setting/rule_settings:: [2] result='.$result);

		return array('success'=>($result? 'TRUE': 'FALSE'));
	}




	# Update setting value
	function update_setting_value($settingId, $settingValue, $settingType, $userId)
	{
		log_message('debug', '_setting/update_setting_value');
		log_message('debug', '_setting/update_setting_value:: [1] settingId='.$settingId.' settingValue='.$settingValue.' settingType='.$settingType.' userId='.$userId);

		# 1. Get the previous setting string value
		$rule = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_rule_setting', 'variables'=>array('rule_id'=>$settingId) ));
		log_message('debug', '_setting/update_setting_value:: [2] rule='.json_encode($rule));

		# 2. Now update the new setting string value
		if(!empty($rule['details'])){
			# 2(a) extract the setting itself
			$settingArray = extract_rule_setting_value($rule['details'], $settingType);

			# 2(b) Now replace the setting string value with the new one
			$newString = str_replace($settingArray['value'], htmlentities($settingValue, ENT_QUOTES), $settingArray['setting_string']);


			if(!empty($settingArray['setting_string']) && !empty($newString)) {
				$reponse = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'query'=>'update_setting_value', 'variables'=>array(
						'setting_id'=>$settingId,
						'new_value_string'=>$newString,
						'previous_value_string'=>$settingArray['setting_string']
					)));
				log_message('debug', '_setting/update_setting_value:: [3] reponse='.json_encode($reponse));

				$result = (!empty($response['result']) && $response['result'] == 'SUCCESS');
			}
			else $result = FALSE;
		}
		log_message('debug', '_setting/update_setting_value:: [4] result='.$result);

		return array('success'=>($result? 'TRUE': 'FALSE'));
	}





	# update the group allocations for the selected users
	function update_user_group_mapping($newGroupId, $userIdList, $userId, $details)
	{
		log_message('debug', '_setting/update_user_group_mapping');
		log_message('debug', '_setting/update_user_group_mapping:: [1] newGroupId='.$newGroupId.' userIdList='.$userIdList.' userId='.$userId.' details='.$details);

		$response = server_curl(IAM_SERVER_URL,  array(
			'__action'=>'update_user_group_mapping',
			'newGroupId'=>$newGroupId,
			'userIdList'=>implode(',',$userIdList),
			'userId'=>$userId
		));

		log_message('debug', '_setting/update_user_group_mapping:: [2] response='.json_encode($response));

		# apply the rule for updating the user group types for all referred users based on the
		if(!empty($response['result']) && $response['result'] == 'SUCCESS'){
			$group = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_group_by_id', 'variables'=>array('group_id'=>$newGroupId )));
			log_message('debug', '_setting/update_user_group_mapping:: [3] group='.json_encode($group));

			# but wait! have we updated the user security settings in the main db?
			foreach($userIdList AS $id){
				$result = $this->_query_reader->run('update_user_security_settings', array(
					'user_type'=>$group['group_type'],
					'user_id'=>$id,
					'updated_by'=>$userId
				));
			}
			log_message('debug', '_setting/update_user_group_mapping:: [4] result='.$result);


			# invited user
			if($group['group_type'] == 'invited_shopper') {
				foreach($userIdList AS $id){
					if(rule_check($this,'permission_update_to_invited_user', array('user_id'=>$id))){
						$result = apply_rule($this,'permission_update_to_invited_user', array('user_id'=>$id));
					}
				}
			}
			# random user
			else if($group['group_type'] == 'random_shopper') {
				foreach($userIdList AS $id){
					if(rule_check($this,'permission_update_to_random_user', array('user_id'=>$id))){
						$result = apply_rule($this,'permission_update_to_random_user', array('user_id'=>$id));
					}
				}
			}


			# whats the overall result?
			$response['result'] = $result? 'SUCCESS': 'FAIL';
		}




		# Log activity
		$this->_logger->add_event(array(
			'user_id'=>$userId,
			'activity_code'=>'update_user_group_mapping',
			'result'=>(!empty($response['result']) && $response['result'] == 'SUCCESS'? 'SUCCESS':'FAIL'),
			'log_details'=>"new_group_id=".$newGroupId."|user_id_list=".implode(', ',$userIdList)."|device=".(!empty($details['device'])? $details['device']: 'unknown')."|browser=".(!empty($details['browser'])? $details['browser']: 'unknown'),
			'uri'=>(!empty($details['uri'])? $details['uri']: ''),
			'ip_address'=>(!empty($details['ip_address'])? $details['ip_address']: '')
		));
		log_message('debug', '_setting/update_user_group_mapping:: [5] response='.json_encode($response));

		return $response;
	}








	# delete a permission group
	function delete_permission_group($groupId, $userId, $details)
	{
		log_message('debug', '_setting/delete_permission_group');
		log_message('debug', '_setting/delete_permission_group:: [1] groupId='.$groupId.' userId='.$userId.' details='.json_encode($details));

		$group = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_group_by_id', 'variables'=>array('group_id'=>$groupId )));
		log_message('debug', '_setting/delete_permission_group:: [2] group='.json_encode($group));

		if(!empty($group)){
			$response = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'query'=>'delete_permission_group_rules', 'variables'=>array('group_id'=>$groupId )));
			$result = (!empty($response['result']) && $response['result'] == 'SUCCESS');

			if($result){
				$response = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'query'=>'delete_permission_group_permissions', 'variables'=>array('group_id'=>$groupId )));
				$result = (!empty($response['result']) && $response['result'] == 'SUCCESS');
			}

			if($result){
				$response = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'query'=>'delete_permission_group', 'variables'=>array('group_id'=>$groupId )));
				$result = (!empty($response['result']) && $response['result'] == 'SUCCESS');
			}

			if($result){
				$response = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'query'=>'set_user_access_group_by_field', 'variables'=>array('group_id'=>'0', 'field_name'=>'permission_group_id', 'field_value'=>$groupId) ));
				$result = (!empty($response['result']) && $response['result'] == 'SUCCESS');
			}
		}
		log_message('debug', '_setting/delete_permission_group:: [3] result='.$result);

		# Log activity
		$this->_logger->add_event(array(
			'user_id'=>$userId,
			'activity_code'=>'remove_permission_group',
			'result'=>(!empty($result) && $result? 'SUCCESS':'FAIL'),
			'log_details'=>"group_name=".(!empty($group['name'])? $group['name']: 'Unknown')."|device=".(!empty($details['device'])? $details['device']: 'unknown')."|browser=".(!empty($details['browser'])? $details['browser']: 'unknown'),
			'uri'=>(!empty($details['uri'])? $details['uri']: ''),
			'ip_address'=>(!empty($details['ip_address'])? $details['ip_address']: '')
		));

		return array('success'=>!empty($result) && $result? 'TRUE': 'FALSE');
	}






	# check if a rule applies
	function rule_check_applies($ruleCode, $parameters)
	{
		log_message('debug', '_setting/rule_check_applies');
		log_message('debug', '_setting/rule_check_applies:: [1] ruleCode='.$ruleCode.' parameters='.json_encode($parameters));

		$result = server_curl(IAM_SERVER_URL, array('__action'=>'rule_check', 'return'=>'plain', 'code'=>$ruleCode, 'parameters'=>$parameters));
		log_message('debug', '_setting/rule_check_applies:: [2] result='.$result);

		return array('applies'=>($result? 'TRUE': 'FALSE'));
	}








}

