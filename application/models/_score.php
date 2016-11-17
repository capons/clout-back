<?php
/**
 * This class generates and formats score details. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/10/2015
 */
class _score extends CI_Model
{
	
	
	# Get a store score breakdown
	public function get_store_score_breakdown($storeId, $userId)
	{
		log_message('debug', '_score/get_store_score_breakdown');
		log_message('debug', '_score/get_store_score_breakdown:: [1] storeId='.$storeId.' userId='.$userId);
		
		#Store score
		$fragmentation=array();
		$fragmentation['store_score_same_store_spending'] = array('my_store_spending_last90days', 'my_store_spending_last12months', 'my_store_spending_lifetime');
		$fragmentation['store_score_same_chain_spending'] = array('my_chain_spending_last90days', 'my_chain_spending_last12months', 'my_chain_spending_lifetime');
		$fragmentation['store_score_competitor_spending'] = array('my_direct_competitors_spending_last90days', 'my_direct_competitors_spending_last12months', 'my_direct_competitors_spending_lifetime');
		$fragmentation['store_score_category_spending'] = array('my_category_spending_last90days', 'my_category_spending_last12months', 'my_category_spending_lifetime');
		$fragmentation['store_score_related_category_spending'] = array('related_categories_spending_last90days', 'related_categories_spending_last12months', 'related_categories_spending_lifetime');
		$fragmentation['store_score_activity'] = array('did_store_survey_last90days', 'did_competitor_store_survey_last90days', 'did_my_category_survey_last90days', 'did_related_categories_survey_last90days');
		$fragmentation['store_score_overall_spending'] = array('spending_last180days','spending_last360days','spending_total');
		$fragmentation['store_score_linked_accounts'] = array('bank_verified_and_active','credit_verified_and_active','cash_balance_today','average_cash_balance_last24months','credit_balance_today','average_credit_balance_last24months');
		$fragmentationKeys = array_keys($fragmentation);
			
		$storeScoreBreakdown = $this->get_store_score_explanation($userId, $storeId, $fragmentation);
		log_message('debug', '_score/get_store_score_breakdown:: [2] storeScoreBreakdown='.json_encode($storeScoreBreakdown));
		
		return $storeScoreBreakdown;
	}
	
	
	
	
	
	
	
	#Function to obtain an explanation of the store score based on the passed breakdown
	public function get_store_score_explanation($userId, $storeId, $scoreBreakdown) 
	{
		log_message('debug', '_score/get_store_score_explanation');
		log_message('debug', '_score/get_store_score_explanation:: [1] userId='.$userId.' storeId='.$storeId.' scoreBreakdown'.json_encode($scoreBreakdown));
		
		$criteria = $categoryExplanation = $categoryArray = array();
		$explanation = $this->get_category_explanation($scoreBreakdown);
		$criteria = $explanation['criteria'];
		$categoryExplanation = $explanation['explanation'];
		
		# Get the cached score details
		$scoreDetails = $this->get_store_score($storeId, $userId, TRUE);
		
		# Get the score breakdown
		foreach($scoreBreakdown AS $category=>$categoryItems)
		{
			# Construct the score breakdown array
			$categoryArray[$category] = array('description'=>(!empty($categoryExplanation[$category])?$categoryExplanation[$category]:''), 'total_score'=>0, 'max_total_score'=>0, 'codes'=>$categoryItems, 'code_scores'=>array(), 'previous_total_score'=>0);  
			$noOfItems = $maxTotal = $actualTotal = 0; 
			$codeScores = array();
			
			# Now bundle the codes and their scores by category
			foreach($categoryItems AS $scoreCode)
			{
				#Get the score by code for further explanation of the score - when needed
				$codeScores[$scoreCode] = !empty($scoreDetails[$scoreCode.'_score'])? $scoreDetails[$scoreCode.'_score']: 0;
				$maxTotal += !empty($criteria[$scoreCode]['high_range'])? $criteria[$scoreCode]['high_range']: 0;	
				$actualTotal += $codeScores[$scoreCode];
			}
			
			# Now pre-populate the category array
			$categoryArray[$category]['code_scores'] = $codeScores;		
			$categoryArray[$category]['total_score'] = $actualTotal;
			$categoryArray[$category]['max_total_score'] = $maxTotal;
		}
		log_message('debug', '_score/get_store_score_explanation:: [2] categoryArray='.json_encode($categoryArray));
		
		return $categoryArray;
	}
	
	
	
	
	
	
	
	
	
	#function to get a breakdown of the clout score in the provided categories
	public function get_clout_score_breakdown($userId, $fragmentation = array())
	{
		log_message('debug', '_score/get_clout_score_breakdown');
		log_message('debug', '_score/get_clout_score_breakdown:: [1] userId='.$userId.' fragmentation='.json_encode($fragmentation));
		
		#use the default fragmentation, if it is not given
		if(empty($fragmentation))
		{
			$fragmentation['clout_score_profile_setup'] = array('facebook_connected','email_verified','mobile_verified','profile_photo_added','bank_verified_and_active','credit_verified_and_active', 
					'location_services_activated','push_notifications_activated','first_adrelated_payment_success',
					'member_processed_promo_payment_last7days','has_answered_survey_in_last90days','number_of_surveys_answered_in_last90days','first_payment_success','member_processed_payment_last7days');
			$fragmentation['clout_score_network_size_growth'] = array('number_of_direct_referrals_last180days','number_of_direct_referrals_last360days','total_direct_referrals','number_of_network_referrals_last180days','number_of_network_referrals_last360days','total_network_referrals');			
			$fragmentation['clout_score_network_spending'] = array('spending_of_direct_referrals_last180days','spending_of_direct_referrals_last360days','total_spending_of_direct_referrals','spending_of_network_referrals_last180days','spending_of_network_referrals_last360days','total_spending_of_network_referrals');
			$fragmentation['clout_score_overall_spending'] = array('spending_last180days','spending_last360days','spending_total');
			$fragmentation['clout_score_ad_related_spending'] = array('ad_spending_last180days','ad_spending_last360days','ad_spending_total');
			$fragmentation['clout_score_linked_accounts'] = array('cash_balance_today','average_cash_balance_last24months','credit_balance_today','average_credit_balance_last24months','has_first_public_checkin_success','has_public_checkin_last7days');
				
		}
		
		#Categorise as desired and return
		return $this->get_clout_score_explanation($userId,$fragmentation);	
	}
	
	
	
	
	
	
	
	
	
	
	
	
	#Function to obtain an explanation of the clout score based on the passed breakdown
	public function get_clout_score_explanation($userId, $scoreBreakdown) 
	{
		log_message('debug', '_score/get_clout_score_explanation');
		log_message('debug', '_score/get_clout_score_explanation:: [1] userId='.$userId.' scoreBreakdown='.json_encode($scoreBreakdown));
		
		$criteria = $categoryExplanation = $categoryArray = array();
		# Get the user details for use in computation of some of the score details
		$explanation = $this->get_category_explanation($scoreBreakdown);
		$criteria = $explanation['criteria'];
		$categoryExplanation = $explanation['explanation'];
		
		# Get the cached score details
		$scoreDetails = $this->get_clout_score($userId, TRUE);
		log_message('debug', '_score/get_clout_score_explanation:: [2] scoreDetails='.json_encode($scoreDetails));
		
		#Get the score breakdown
		foreach($scoreBreakdown AS $category=>$categoryItems)
		{
			#TODO: Add option to store and retrieve the previous averages for use in showing the user trend; Tracked using the 'previous_average_score' variable
			$categoryArray[$category] = array('description'=>(!empty($categoryExplanation[$category])?$categoryExplanation[$category]:''), 'total_score'=>0, 'max_total_score'=>0, 'codes'=>$categoryItems, 'code_scores'=>array(), 'previous_total_score'=>0);  
			$noOfItems = $maxTotal = $actualTotal = 0; 
			$codeScores = array();
			
			#Now bundle the codes and their scores by category
			foreach($categoryItems AS $scoreCode)
			{
				#Get the score by code for further explanation of the score - when needed
				$codeScores[$scoreCode] = !empty($scoreDetails[$scoreCode.'_score'])? $scoreDetails[$scoreCode.'_score']: 0;
				$maxTotal += !empty($criteria[$scoreCode]['high_range'])? $criteria[$scoreCode]['high_range']: 0;	
				$actualTotal += $codeScores[$scoreCode];
			}
			
			#Now prepopulate the category array
			$categoryArray[$category]['code_scores'] = $codeScores;		
			$categoryArray[$category]['total_score'] = $actualTotal;
			$categoryArray[$category]['max_total_score'] = $maxTotal;
		}
		log_message('debug', '_score/get_clout_score_explanation:: [3] categoryArray='.json_encode($categoryArray));
		
		return $categoryArray;
	}
	
	
	
	
	
	
	#Get the category explanation keys
	public function get_category_explanation($scoreBreakdown)
	{
		log_message('debug', '_score/get_category_explanation');
		log_message('debug', '_score/get_category_explanation:: [1] scoreBreakdown='.json_encode($scoreBreakdown));
		
		$criteria = $categoryExplanation = array();
		$explanationKeys = array_keys($scoreBreakdown);
		#Determine the score type
		$firstKeyParts = !empty($explanationKeys[0])? explode('_', $explanationKeys[0]):array();
		$scoreType = (!empty($firstKeyParts[0]) && !empty($firstKeyParts[1]))? $firstKeyParts[0].'_'.$firstKeyParts[1]: 'clout_score';
		
		#Get the user's cached row to pick the explanation
		$scoreDetails = $this->_query_reader->get_row_as_array('get_a_'.$scoreType.'_cache');
		$scoreCriteria = $this->get_criteria_by_keys(array_keys($scoreDetails));
		
		#Make DB result associative array so that the details are easier to retrieve
		foreach($scoreCriteria AS $criteriaDetails) $criteria[$criteriaDetails['code']] = $criteriaDetails;
		
		#Setup the category explanation array for use in the summarization of the score
		$categoryExplanationRaw = $this->_query_reader->get_list('get_content_explanation', array('code_list'=>"'".implode("','", $explanationKeys)."'"));
		foreach($categoryExplanationRaw AS $categoryRow) $categoryExplanation[$categoryRow['content_code']] = $categoryRow['content_details'];
		
		log_message('debug', '_score/get_category_explanation:: [2] criteria='.json_encode($criteria).' categoryExplanation='.json_encode($categoryExplanation));
		
		return array('criteria'=>$criteria, 'explanation'=>$categoryExplanation);
	}

	
	
	
	
	#Get criteria info given a list of keys
	public function get_criteria_by_keys($keysList, $queryPart='', $returnType='raw')
	{
		log_message('debug', '_score/get_criteria_by_keys');
		log_message('debug', '_score/get_criteria_by_keys:: [1] keysList='.json_encode($keysList).' queryPart='.$queryPart.' returnType='.$returnType);
		
		$criteriaList = $this->_query_reader->get_list('get_score_criteria_description', array('criteria_list'=>"'".implode("','", $keysList)."'", 'query_part'=>$queryPart));
		log_message('debug', '_score/get_criteria_by_keys:: [2] criteriaList='.json_encode($criteriaList));
		
		if($returnType == 'array')
		{
			$criteria = array();
			if(!empty($criteriaList))
			{
				#Make DB result associative array so that the details are easier to retrieve
				foreach($criteriaList AS $criteriaDetails)
				{
					if(!empty($criteriaDetails['code'])) $criteria[$criteriaDetails['code']] = $criteriaDetails;
				}
			}
			log_message('debug', '_score/get_criteria_by_keys:: [3] criteria='.json_encode($criteria));
			return $criteria;
		}
		else
		{
			return $criteriaList;
		}
		
	}
	
	
	
	
	# Gets the store score from the database
	function get_store_score($storeId, $userId, $getDetails=FALSE)
	{
		log_message('debug', '_score/get_store_score');
		log_message('debug', '_score/get_store_score:: [1] storeId='.$storeId.' userId='.$userId.' getDetails='.$getDetails);
		
		if($getDetails) {
			$score = $this->_query_reader->get_row_as_array('get_store_score_details', array('store_id'=>$storeId, 'user_id'=>$userId ));
			log_message('debug', '_score/get_store_score:: [2] score='.json_encode($score));
			return $score;
		} else {
			$score = $this->_query_reader->get_row_as_array('get_store_score', array('store_id'=>$storeId, 'user_id'=>$userId ));
			log_message('debug', '_score/get_store_score:: [3] score='.json_encode($score));
			return !empty($score['store_score'])? $score['store_score']: 0;
		}
	}
	
	
	
	# Gets the clout score from the database
	function get_clout_score($userId, $getDetails=FALSE)
	{
		log_message('debug', '_score/get_clout_score');
		log_message('debug', '_score/get_clout_score:: [1] userId='.$userId.' getDetails='.$getDetails);
		
		$result = $this->_query_reader->get_row_as_array('get_clout_score', array('user_id'=>$userId ));
		log_message('debug', '_score/get_clout_score:: [2] result='.json_encode($result));
		
		return $result;
	}
	
	
	
	
	# Get score level
	function get_score_level($score)
	{
		log_message('debug', '_score/get_score_level');
		log_message('debug', '_score/get_score_level:: [1] score='.$score);
		
		$result = $this->_query_reader->get_row_as_array('get_score_level', array('score'=>$score ));
		log_message('debug', '_score/get_score_level:: [1] result='.json_encode($result));
		
		return $result;
	}
	
	
	
	# Get score settings
	function get_settings($scoreType, $scoreGet, $storeId='') 
	{
		log_message('debug', '_score/get_settings');
		log_message('debug', '_score/get_settings:: [1] scoreType='.$scoreType.' scoreGet='.$scoreGet.' storeId'.$storeId);
		
		if($scoreGet == 'level_data'){
			return $this->get_level_data($storeId);
		} else if($scoreGet == 'key_description'){
			return $this->get_key_description($scoreType);
		}
	}
	
	
	#Get score level data
	public function get_level_data($storeId)
	{
		log_message('debug', '_score/get_level_data');
		log_message('debug', '_score/get_level_data:: [1] storeId='.$storeId);
		
		$scoreData = $this->_query_reader->get_list('get_score_level_data', array('condition'=>'', 'order_by'=>" ORDER BY 0+level ASC "));
		log_message('debug', '_score/get_level_data:: [2] scoreData='.json_encode($scoreData));
		
		#Then add the offer range for each score
		foreach($scoreData AS $key=>$data)
		{
			$range = $this->get_cash_back_range($storeId, $data['low_end_score']);
			$scoreData[$key]['min_cashback'] = $range['min'];
			$scoreData[$key]['max_cashback'] = $range['max'];
		}
		log_message('debug', '_score/get_level_data:: [3] scoreData='.json_encode($scoreData));
		
		return $scoreData;
	}
	
	
	
	#Get range of cash_back for a given score
	public function get_cash_back_range($storeId, $score)
	{
		log_message('debug', '_score/get_cash_back_range');
		log_message('debug', '_score/get_cash_back_range:: [1] storeId='.$storeId.' score='.$score);
		
		$range = array('min'=>0, 'max'=>0);
		$promotionsList = $this->_query_reader->get_list('get_promotions_within_score_range', array('score'=>(!empty($score)?$score:0),  'store_id'=>$storeId, 'promotion_types'=>"'cashback'", 'additional_conditions'=>" AND status='active' ", 'order_condition'=>" ORDER BY amount DESC ", 'limit_text'=>""));
		$promoCashbacks = array();
		log_message('debug', '_score/get_cash_back_range:: [2] promotionsList='.json_encode($promotionsList));
		
		foreach($promotionsList AS $promotionRow) array_push($promoCashbacks, $promotionRow['amount']);
		
		$range['min'] = !empty($promoCashbacks)? min($promoCashbacks): 0;
		$range['max'] = !empty($promoCashbacks)? max($promoCashbacks): 0;
		
		log_message('debug', '_score/get_cash_back_range:: [3] range='.json_encode($range));
		
		return $range;
	}
	
	
	
	
	# Get the key description 
	function get_key_description($scoreType)
	{
		log_message('debug', '_score/get_key_description');
		log_message('debug', '_score/get_key_description:: [1] scoreType='.$scoreType);
		
		$keysList = array_keys($this->_query_reader->get_row_as_array('get_a_'.$scoreType.'_cache'));
		$criteriaList = $this->_query_reader->get_list('get_score_criteria_description', array('criteria_list'=>"'".implode("','", $keysList)."'", 'query_part'=>''));
		log_message('debug', '_score/get_key_description:: [2] criteriaList='.json_encode($criteriaList));
		
		$criteria = array();
		if(!empty($criteriaList)){
			#Make DB result associative array so that the details are easier to retrieve
			foreach($criteriaList AS $criteriaDetails){
				if(!empty($criteriaDetails['code'])) $criteria[$criteriaDetails['code']] = $criteriaDetails;
			}
		}
		log_message('debug', '_score/get_key_description:: [3] criteria='.json_encode($criteria));
		
		return $criteria;
	}
	
	
	
	
	
	
	# Get details of a score based on given keys
	function get_details($type, $codes, $userId, $storeId)
	{
		log_message('debug', '_score/get_details');
		log_message('debug', '_score/get_details:: [1] type='.$type.' codes='.json_encode($codes).' userId='.$userId.' storeId='.$storeId);
		
		$this->load->model('_network');
		
		$keys = array('last_time_user_joined_my_direct_network', 'last_time_invite_was_sent', 'last_time_commission_was_earned', 'clout_score', 'my_current_commission','clout_score_level');
		# Direct DB details from keys
		$details = $this->_query_reader->get_row_as_array('get_'.$type.'_details_by_key', array('user_id'=>$userId, 'store_id'=>$storeId));
		# fetch the invite stat from the message database if requested
		if(in_array('last_time_invite_was_sent', $codes)){
			$stat = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_last_time_invite_was_sent', 'variables'=>array('user_id'=>$userId) ));
			$details['last_time_invite_was_sent'] = !empty($stat['last_time_invite_was_sent'])? $stat['last_time_invite_was_sent']: '';
		}
		log_message('debug', '_score/get_details:: [2] details='.json_encode($details));
		
		# User Referrals
		$referrals = $this->_query_reader->get_row_as_array('get_referral_level_count', array('user_id'=>$userId));
		log_message('debug', '_score/get_details:: [3] referrals='.json_encode($referrals));
		
		$statValues = array();
		foreach($codes AS $statCode) {
			#These were all picked from the database at once
			if(in_array($statCode, $keys)){
				$statValues[$statCode] = $details[$statCode];
			}
			else if($statCode == 'clout_score_details') $statValues[$statCode] = $this->get_clout_score($userId, TRUE);
			else if($statCode == 'clout_score_breakdown') $statValues[$statCode] = $this->get_clout_score_breakdown($userId);
			else if($statCode == 'clout_score_key_description') $statValues[$statCode] = $this->get_key_description('clout_score');
			else if($statCode == 'score_level_data') $statValues[$statCode] = $this->get_level_data($storeId);
			else if($statCode == 'total_direct_referrals_in_my_network') $statValues[$statCode] = (!empty($referrals['level1'])? $referrals['level1']: 0);
			else if($statCode == 'total_level_2_referrals_in_my_network') $statValues[$statCode] = (!empty($referrals['level2'])? $referrals['level2']: 0);
			else if($statCode == 'total_level_3_referrals_in_my_network') $statValues[$statCode] = (!empty($referrals['level3'])? $referrals['level3']: 0);
			else if($statCode == 'total_level_4_referrals_in_my_network') $statValues[$statCode] = (!empty($referrals['level4'])? $referrals['level4']: 0);
			else if($statCode == 'total_users_in_my_network') $statValues[$statCode] = ($referrals['level1'] + $referrals['level2'] + $referrals['level3'] + $referrals['level4']);
			else if($statCode == 'total_invites_in_my_network') $statValues[$statCode] = $this->get_network_invite_count($userId);
			else if($statCode == 'total_direct_invites_in_my_network') $statValues[$statCode] = $this->get_network_invite_count($userId,'level1');
			else if($statCode == 'total_level_2_invites_in_my_network') $statValues[$statCode] = $this->get_network_invite_count($userId,'level2');
			else if($statCode == 'total_level_3_invites_in_my_network') $statValues[$statCode] = $this->get_network_invite_count($userId,'level3');
			else if($statCode == 'total_level_4_invites_in_my_network') $statValues[$statCode] = $this->get_network_invite_count($userId,'level4');
			else if($statCode == 'total_earnings_in_my_network') $statValues[$statCode] = $this->get_network_earnings($userId, 'all');
			else if($statCode == 'total_direct_earnings_in_my_network') $statValues[$statCode] = $this->get_network_earnings($userId, 'level1');
			else if($statCode == 'total_level_2_earnings_in_my_network') $statValues[$statCode] = $this->get_network_earnings($userId, 'level2');
			else if($statCode == 'total_level_3_earnings_in_my_network') $statValues[$statCode] = $this->get_network_earnings($userId, 'level3');
			else if($statCode == 'total_level_4_earnings_in_my_network') $statValues[$statCode] = $this->get_network_earnings($userId, 'level4');
			else if($statCode == 'points_to_next_level'){
				$score = $this->get_clout_score($userId, FALSE);
				$level = $this->get_score_level($score['clout_score']? $score['clout_score']: 0);
				$statValues[$statCode] = $level['points_to_next_level'];
			} 
			
		}
		log_message('debug', '_score/get_details:: [4] statValues='.json_encode($statValues));
		
		return $statValues;
	}
	
	
	
	
	
	
	
	
	#Function to return a count of the network invites as requested
	public function get_network_invite_count($userId, $networkLevel='all')
	{
		log_message('debug', '_score/get_network_invite_count');
		log_message('debug', '_score/get_network_invite_count:: [1] userId='.$userId.' networkLevel='.$networkLevel);
		
		$this->load->model('_network');
		
		#1. Get the IDS for all referrals at each level
		$networkLevelIds = $this->_query_reader->get_row_as_array('get_network_level_ids', array('user_id'=>$userId));
		log_message('debug', '_score/get_network_invite_count:: [2] networkLevelIds='.json_encode($networkLevelIds));
		
		#2. Get the count of all invites sent by users at each level
		if($networkLevel == 'all' && !empty($networkLevelIds['level_1'])){
			$referrerIdList = $userId
							  .(!empty($networkLevelIds['level_1'])?',':'').$networkLevelIds['level_1']
							  .(!empty($networkLevelIds['level_2'])?',':'').$networkLevelIds['level_2']
							  .(!empty($networkLevelIds['level_3'])?',':'').$networkLevelIds['level_3'];
		}
		else if($networkLevel == 'level1') $referrerIdList = $userId;
		else if($networkLevel == 'level2' && !empty($networkLevelIds['level_1'])) $referrerIdList = $networkLevelIds['level_1'];
		else if($networkLevel == 'level3' && !empty($networkLevelIds['level_2'])) $referrerIdList = $networkLevelIds['level_2'];
		else if($networkLevel == 'level4' && !empty($networkLevelIds['level_3'])) $referrerIdList = $networkLevelIds['level_3'];
		
		if(!empty($referrerIdList)) {
			$invites = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_user_invite_count', 'variables'=>array('user_ids'=>str_replace(',',"','",$referrerIdList)) ));
			log_message('debug', '_score/get_network_invite_count:: [3] inviteArray='.json_encode($invites));
		}
		
		return (!empty($invites['invite_count'])? $invites['invite_count']: 0);
	}
	
	
	
	
	# Get the count if invites given the ID(s) of the referrers
	function get_level_invite_count($referrerIds)
	{
		log_message('debug', '_score/get_level_invite_count');
		log_message('debug', '_score/get_level_invite_count:: [1] referrerIds='.json_encode($referrerIds));
		
		$count = 0;
		foreach($referrerIds AS $userId)
		{
			$levelCount = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_number_of_invites', 'variables'=>array('user_id'=>$userId) ));
			
			$count += (!empty($levelCount['invite_count'])? $levelCount['invite_count']: 0);
		}
		log_message('debug', '_score/get_level_invite_count:: [2] count='.$count);
		
		return $count;
	}
	
	
	
	
	
	
	
	
	#Function to return earnings from the network
	public function get_network_earnings($userId, $networkLevel='all')
	{
		log_message('debug', '_score/get_network_earnings');
		log_message('debug', '_score/get_network_earnings:: [1] userId='.$userId.' networkLevel='.$networkLevel);
		
		$this->load->model('_network');
		
		#1. Get the IDS for all referrals at each level
		$networkLevelIds = $this->_network->get_user_referrals($userId, 'network_level_ids');
		log_message('debug', '_score/get_network_earnings:: [2] networkLevelIds='.json_encode($networkLevelIds));
		
		#2. Get the earning details sent by users at each level
		$earningsArray = array();
		$earningsArray['level1'] = $this->get_network_earning_details($userId, $networkLevelIds['level1']);
		$earningsArray['level2'] = $this->get_network_earning_details($userId, $networkLevelIds['level2']);
		$earningsArray['level3'] = $this->get_network_earning_details($userId, $networkLevelIds['level3']);
		$earningsArray['level4'] = $this->get_network_earning_details($userId, $networkLevelIds['level4']);
		$earningsArray['all'] = $earningsArray['level1'] + $earningsArray['level2'] + $earningsArray['level3'] + $earningsArray['level4'];
		log_message('debug', '_score/get_network_earnings:: [3] earningsArray='.json_encode($earningsArray));
		
		return (!empty($earningsArray[$networkLevel])? $earningsArray[$networkLevel]: 0);
	}
	
	
	
	
	
	
	
	
	
	#Get the network earning details
	public function get_network_earning_details($userId, $networkIds, $earliestDate='') 
	{
		log_message('debug', '_score/get_network_earning_details');
		log_message('debug', '_score/get_network_earning_details:: [1] userId='.$userId.' networkIds='.json_encode($networkIds).' earliestDate='.$earliestDate);
		
		$earningTotal = 0;
		$dateCondition = !empty($earliestDate)? " AND UNIX_TIMESTAMP(date_entered) >= UNIX_TIMESTAMP('".$earliestDate."') ": "";
		
		#2. Go through each user's transaction history in the network and collect the earnings of this user
		foreach($networkIds AS $networkUser)
		{
			$reward = $this->_query_reader->get_row_as_array('get_user_earnings', array('user_id'=>$networkUser, 'date_condition'=>$dateCondition));
			$earningTotal += $reward['amount'];
		}
		log_message('debug', '_score/get_network_earning_details:: [2] earningTotal='.$earningTotal);
		
		return $earningTotal;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}


