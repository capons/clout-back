<?php
/**
 * This class generates and formats money details. 
 *
 * @author Khim Ung <khimu@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 03/25/2016
 */
class _plugin extends CI_Model
{
	
	function verify_button($userId, $referralId) {
		return $this->_query_reader->run('verify_button', array('user_id'=>$userId, 'referral_id', $referralId));
	}
	
	
	
	function generate_button($userId, $referralId, $length, $size, $navigation, $website, $redirectUrl) {
		# returns data-appid
		return $this->_query_reader->run('generate_button', array('user_id'=>$userId, 'referral_id'=>$referralId, 'length'=>$length, 'size'=>$size, 'navigation'=>$navigation, 'website'=>$website, 'redirect_url'=>$redirectUrl));
	}
	
	
	
	# get button information
	function button_details($publicButtonId) {
		/*
		 * Returns: 
		 *   _button_id, public_button_id, notes, third_party_user_id, referral_code, button_length, button_size, navigation, website, 
		 *   redirect_url, deactivation_reason_code, is_active, date_entered
		 */
		$details = $this->_query_reader->run('get_share_button_details', array('public_button_id'=>$publicButtonId));
		
		return $result;
	}
	
	# get user information for third party access
	function user_details($publicButtonId, $publicUserId) {
		$result = array();
		# validate required query parameters
		if(!empty($publicButtonId) && !empty($publicUserId)) {		
			# $result = $this->_query_reader->get_row_as_array('third_party_user_details', array('publicButtonId'=>$publicButtonId, 'publicUserId'=>$publicUserId));
			# the query should return the following results
			$result['firstName'] = 'Khim';
			$result['lastName'] = 'Ung';
			$result['email'] = 'khimu@clout.com';
			$result['status'] = 'black';
			$result['score'] = 1212;
			$result['publicUserId'] = 1;
			$result['storeName'] = 'Nike';
		}

		return $result;
	}
	
	
	# TODO implement save to user third party share action to db
	function save_share($publicButtonId, $shareAction, $fields, $browser, $ipAddress, $userId) {
		$this->_query_reader->run('save_share_action', array('user_id'=>$userId, 'public_button_id'=>$publicButtonId, 'action'=>$shareAction, 'fields_shared'=>$fields, 'browser'=>$browser, 'ip_address'=>$ipAddress));
	}

}