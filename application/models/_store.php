<?php
/**
 * This class generates and formats store details.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/10/2015
 */
class _store extends CI_Model
{

	# Get store details
	function details($storeId, $userId, $location, $fields)
	{
		log_message('debug', '_store/details');
		log_message('debug', '_store/details:: [1] storeId='.$storeId.' userId='.$userId.' location='.json_encode($location).' fields='.json_encode($fields));

		$details = array();
		$store = $this->_query_reader->get_row_as_array('get_store_details_by_id', array('store_id'=>$storeId, 'user_id'=>$userId, 'latitude'=>$location['latitude'], 'longitude'=>$location['longitude'] ));

		foreach($fields AS $field){
			$details[$field] = !empty($store[$field]) ? $store[$field]: '';
		}
		log_message('debug', '_store/details:: [2] details='.json_encode($details));

		return $details;
	}




	# Get store details for editing
	function edit_chain_details($chainId, $chainType)
	{
		log_message('debug', '_store/edit_chain_details');
		log_message('debug', '_store/edit_chain_details:: [1] chainId='.$chainId.' chainType='.$chainType);

		$details = $this->_query_reader->get_row_as_array('get_edit_chain_details_by_id', array('chain_id'=>$chainId ));
		$details['links'] = $this->_query_reader->get_list('get_chain_links', array('chain_id'=>$chainId ));
		log_message('debug', '_store/edit_chain_details:: [2] details='.json_encode($details));

		return $details;
	}





	# Get store offers
	function offers($storeId, $type, $userId='', $level='')
	{
		log_message('debug', '_store/offers');
		log_message('debug', '_store/offers:: [1] storeId='.$storeId.' type='.$type.' userId='.$userId.' level='.$level);

		$offers = array();
		if($type == 'cashback_ranges') $offers = $this->get_level_cashback_ranges($storeId);
		# Looking for the offer list
		else if(in_array($type, array('cashback','perk','cashback,perk','perk,cashback'))) $offers = $this->get_offer_list($storeId, $type, $userId, $level);
		log_message('debug', '_store/offers:: [2] offers='.json_encode($offers));

		return $offers;
	}



	#Get the list of current offers from the store
	function get_offer_list($storeId, $type, $userId, $level)
	{
		log_message('debug', '_store/get_offer_list');
		log_message('debug', '_store/get_offer_list:: [1] storeId='.$storeId.' type='.$type.' userId='.$userId.' level='.$level);

		#If a userId is specified, then get offers that meet a specific score
		if(!empty($userId) && $level == ''){
			$this->load->model('_score');
			$storeScore = $this->_score->get_store_score($storeId, $userId);
		}
		# We've got a level only
		else if(!empty($level) || $level == '0'){
			$levelDetails = $this->_query_reader->get_row_as_array('get_score_level_data', array('condition'=>" AND level='".$level."' ", 'order_by'=>''));
			#Cater for extreme high end score
			$storeScore = !empty($levelDetails['low_end_score']) && empty($levelDetails['high_end_score'])? '1000': $levelDetails['high_end_score'];
		}
		# No user or level is specified
		else {
			$storeScore = '1000';
		}
		log_message('debug', '_store/get_offer_list:: [2] storeScore='.$storeScore);

		$scoreTypes = in_array($type, array('cashback','perk'))? "'".$type."'": "'cashback','perk'";

		$offers = $this->_query_reader->get_list('get_promotions_within_score_range', array('score'=>$storeScore,  'store_id'=>$storeId, 'promotion_types'=>$scoreTypes, 'additional_conditions'=>" AND status='active' ", 'order_condition'=>" ORDER BY promotion_type ASC, amount DESC ", 'limit_text'=>""));

		# Apply the promotion rules to filter out offers the user may not qualify for even if they have the score
		if(!empty($userId)){
			$this->load->model('_promotion');
			$offers = $this->_promotion->apply_rules($storeId, $userId, $offers);
		}
		log_message('debug', '_store/get_offer_list:: [3] offers='.json_encode($offers));

		return $offers;
	}




	# Get level cashback ranges
	function get_level_cashback_ranges($storeId)
	{
		log_message('debug', '_store/get_level_cashback_ranges');
		log_message('debug', '_store/get_level_cashback_ranges:: [1] storeId='.$storeId);

		$scoreData = $this->_query_reader->get_list('get_score_level_data', array('condition'=>'', 'order_by'=>" ORDER BY 0+level ASC "));

		#Then add the offer range for each score
		foreach($scoreData AS $key=>$data)
		{
			$range = $this->get_cash_back_range($storeId, $data['low_end_score']);
			$scoreData[$key]['min_cashback'] = $range['min'];
			$scoreData[$key]['max_cashback'] = $range['max'];
		}
		log_message('debug', '_store/get_level_cashback_ranges:: [2] scoreData='.json_encode($scoreData));

		return $scoreData;
	}





	#Get range of cash_back for a given score
	public function get_cash_back_range($storeId, $score)
	{
		log_message('debug', '_store/get_cash_back_range');
		log_message('debug', '_store/get_cash_back_range:: [1] storeId='.$storeId.' score='.$score);

		$range = array('min'=>0, 'max'=>0);
		$promotionsList = $this->_query_reader->get_list('get_promotions_within_score_range', array('score'=>(!empty($score)?$score:0),  'store_id'=>$storeId, 'promotion_types'=>"'cashback'", 'additional_conditions'=>" AND status='active' ", 'order_condition'=>" ORDER BY amount DESC ", 'limit_text'=>""));

		$promoCashbacks = array();

		foreach($promotionsList AS $promotionRow)
		{
			array_push($promoCashbacks, $promotionRow['amount']);
		}

		$range['min'] = !empty($promoCashbacks)? min($promoCashbacks): 0;
		$range['max'] = !empty($promoCashbacks)? max($promoCashbacks): 0;
		log_message('debug', '_store/get_cash_back_range:: [2] range='.json_encode($range));

		return $range;
	}




	# Get offer details
	function offer_details($offerId, $fields, $userId='')
	{
		log_message('debug', '_store/offer_details');
		log_message('debug', '_store/offer_details:: [1] offerId='.$offerId.' fields='.$fields.' userId'.$userId);

		$this->load->model('_promotion');

		#Is a field set for required scheduling
		if(in_array('requires_scheduling',$fields)) {
			$fields = remove_item('requires_scheduling', $fields);
			$requiresScheduling = $this->_promotion->requires_scheduling($offerId);
			log_message('debug', '_store/offer_details:: [2] requiresScheduling='.$requiresScheduling);
		}

		#Proceed and pick the rest of the fields
		if(!empty($fields)) $results = $this->_promotion->details($offerId, $fields);
		if(!empty($requiresScheduling)) $results['requires_scheduling'] = $requiresScheduling;
		log_message('debug', '_store/offer_details:: [3] results='.json_encode($results));

		return $results;
	}



	# Checkin a user at a store
	function checkin($userId, $offerId, $location, $storeId)
	{
		log_message('debug', '_store/checkin');
		log_message('debug', '_store/checkin:: [1] userId='.$userId.' offerId='.$offerId.' location='.json_encode($location).' storeId='.$storeId);

		if(empty($storeId)) $promotion = server_curl(CRON_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_promotion_by_id', 'variables'=>array('promotion_id'=>$offerId) ));
		if(!empty($promotion)) log_message('debug', '_store/checkin:: [2] promotion='.json_encode($promotion));

		$result = $this->_query_reader->run('add_user_checkin', array('user_id'=>$userId, 'longitude'=>$location['longitude'], 'latitude'=>$location['latitude'], 'address'=>$location['address'],'city'=>$location['city'],'zipcode'=>$location['zipcode'],'state'=>$location['state'], 'store_id'=>(!empty($storeId)? $storeId: (!empty($promotion['store_id'])? $promotion['store_id']: '')), 'offer_id'=>$offerId, 'details'=>'', 'source'=>'checkin'));
		log_message('debug', '_store/checkin:: [3] result='.json_encode($result));
		return $result;
	}




	# Get the store hours of operation
	function hours($storeId, $userId, $hourType)
	{
		log_message('debug', '_store/hours');
		log_message('debug', '_store/hours:: [1] storeId='.$storeId.' userId='.$userId.' hourType='.$hourType);

		$hours = array();

		# Weekly schedule
		if($hourType == 'weekly'){
			$list = $this->_query_reader->get_list('get_store_hours', array('store_id'=>$storeId));
			foreach($list AS $row){
				array_push($hours, array(
					'day'=>$row['week_day'],
					'start'=>($row['start_hour'] != 'any'? date('h:ia', strtotime($row['start_hour'])): 'any'),
					'end'=>($row['end_hour'] != 'any'? date('h:ia', strtotime($row['end_hour'])): 'any')
				));
			}
		}
		#TODO: Add check if a store is open now
		else if($hourType == 'now'){

		}
		log_message('debug', '_store/hours:: [2] hours='.json_encode($hours));

		return $hours;
	}




	# Get the store features
	function features($storeId, $userId, $featureType='list')
	{
		log_message('debug', '_store/features');
		log_message('debug', '_store/features:: [1] storeId='.$storeId.' userId='.$userId.' featureType='.$featureType);

		$result = $this->_query_reader->get_list('get_store_features', array('store_id'=>$storeId));
		log_message('debug', '_store/features:: [2] result='.json_encode($result));

		return $result;
	}





	# Make a reservation
	function reservation($userId, $offerId, $formData)
	{
		log_message('debug', '_store/reservation');
		log_message('debug', '_store/reservation:: [1] userId='.$userId.' offerId='.$offerId.' formData'.$formData);

		$this->load->model('_promotion');

		#1. Record the schedule in the database
		$scheduleResult = $this->_query_reader->run('add_store_schedule', array('promotion_id'=>$offerId, 'user_id'=>$userId, 'scheduler_name'=>$formData['reservationName'], 'scheduler_email'=>$formData['reservationEmail'], 'scheduler_phone'=>$formData['reservationPhone'], 'schedule_date'=>$formData['reservationDate'], 'number_in_party'=>$formData['reservationNumber'], 'special_request'=>htmlentities($formData['specialRequests'], ENT_QUOTES) ));
		log_message('debug', '_store/reservation:: [2] scheduleResult='.$scheduleResult);

		#2. Notify the store owner about the schedule
		#- Pet Peeve: message array has no underscores in keys
		$reservation = $this->_query_reader->get_row_as_array('get_schedule_details', array('promotion_id'=>$offerId));
		log_message('debug', '_store/reservation:: [3] reservation='.json_encode($reservation));

		$message = array('reservationname'=>$formData['reservationName'], 'reservationemail'=>$formData['reservationEmail'], 'reservationphone'=>$formData['reservationPhone'], 'reservationdate'=>date('l F d, Y h:ia', strtotime($formData['reservationDate'])), 'reservationnumber'=>$formData['reservationNumber'], 'specialrequests'=>htmlentities($formData['specialRequests'], ENT_QUOTES), 'offerdescription'=>$reservation['offer_description'], 'offerconditions'=>$reservation['offer_conditions'], 'storename'=>$reservation['store_name'], 'storeaddress'=>$reservation['store_address'], 'senderid'=>$userId, 'promotionbarcode'=>$this->_promotion->bar_code($offerId, $reservation['offer_date']), 'code'=>'send_store_schedule');

		# Send to the store and a copy to the user who made the reservation
		$sendResult = FALSE;
		if($scheduleResult){
			# send to store users
			$sendResult = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send',
							'receiverId'=>$reservation['store_id'],
							'return'=>'plain',
							'message'=> array_merge($message, array('receivertype'=>'store')),
							'requiredFormats'=>array('system', 'email'),
							'strictFormatting'=>TRUE
						));
			log_message('debug', '_store/reservation:: [4] sendResult='.$sendResult);

			# send to the user who made the reservation
			$sendResult = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send',
							'receiverId'=>$userId,
							'return'=>'plain',
							'message'=> $message,
							'requiredFormats'=>array('system', 'email'),
							'strictFormatting'=>TRUE
						));
			log_message('debug', '_store/reservation:: [5] sendResult='.$sendResult);
		}

		return $sendResult;
	}




	# Get store categories
	function categories($level, $parentId, $storeId, $offset, $limit, $phrase)
	{
		log_message('debug', '_store/categories');
		log_message('debug', '_store/categories:: [1] level='.$level.' parentId='.$parentId.' storeId='.$storeId.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);

		$list = array();

		# Parent-less level
		if(empty($parentId)){
			$list = $this->_query_reader->get_list('get_level_'.$level.'_categories');
		}

		# TODO: Add fetch queries for other levels and when filter values are set
		log_message('debug', '_store/categories:: [2] list='.json_encode($list));

		return $list;
	}




	# Search a store by name - optimised for one store table search (e.g., for drop downs)
	function search($fields, $offset, $limit, $phrase, $extraFields=array())
	{
		log_message('debug', '_store/search');
		log_message('debug', '_store/search:: [1] fields='.$fields.' offset='.$offset.' limit='.$limit.' phrase='.$phrase.' extraFileds='.json_encode($extraFields));

		$fields = explode(',',$fields);
		$values['name'] = in_array('name', $fields)? htmlentities($phrase, ENT_QUOTES): '';
		$values['address'] = in_array('address', $fields)? htmlentities(str_replace(' ','%',$phrase), ENT_QUOTES): '%';
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		# TODO: Add filtering for extra fields
		$result = $this->_query_reader->get_list('search_stores_by_fields', $values);
		log_message('debug', '_store/search:: [2] result='.json_encode($result));

		return $result;
	}




	# Suggest a store
	function suggest($descriptorId, $userId, $details, $action='verified')
	{
		log_message('debug', '_store/suggest');
		log_message('debug', '_store/suggest:: [1] descriptorId='.$descriptorId.' userId='.$userId.' details='.json_encode($details).' action='.$action);

		if(!empty($details['chain_id']))  return $this->edit_descriptor_chain($descriptorId, $userId, $details, $action);
		else return $this->add_descriptor_chain($descriptorId, $userId, $details, $action);
	}




	# Edit a chain
	function edit_descriptor_chain($descriptorId, $userId, $details, $action)
	{
		log_message('debug', '_store/edit_descriptor_chain');
		log_message('debug', '_store/edit_descriptor_chain:: [1] descriptorId='.$descriptorId.' userId='.$userId.' details='.json_encode($details).' action='.$action);

		$result = FALSE;

		# Get the current store details
		$chain = $this->_query_reader->get_row_as_array('get_edit_chain_details_by_id', array('chain_id'=>$details['chain_id']));
		log_message('debug', '_store/edit_descriptor_chain:: [2] chain='.json_encode($chain));

		if(!empty($chain)){
			$result = $this->_query_reader->run('update_chain_field', array('field_name'=>'name', 'field_value'=>htmlentities($details['name'], ENT_QUOTES), 'chain_id'=>$details['chain_id']));
			$result = $this->_query_reader->run('update_chain_field', array('field_name'=>'website', 'field_value'=>$details['website'], 'chain_id'=>$details['chain_id']));

			# Update the chain category
			$result = $this->_query_reader->run('remove_chain_categories', array('chain_id'=>$details['chain_id']));
			$result = $this->_query_reader->run('add_chain_categories', array('chain_id'=>$details['chain_id'], 'category_ids'=>$details['category']));
			log_message('debug', '_store/edit_descriptor_chain:: [3] result='.$result);
		}


		# Record the change
		if($result) {
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId,
				'description'=>htmlentities('Chain data for <b>'.(!empty($chain['chain_name'])? $chain['chain_name']: 'Unknown').'</b> has been updated', ENT_QUOTES),
				'change_code'=>'descriptor_chain_update',
				'change_value'=>'suggested_chain_id='.$details['chain_id'],
				'old_status'=>'',
				'new_status'=>($action != 'verified'? 'pending': 'verified'),
				'user_id'=>$userId
			));
			log_message('debug', '_store/edit_descriptor_chain:: [4] result='.$result);
		}

		# Success if you get to this point and result is TRUE
		return $result? array('result'=>'SUCCESS', 'new_chain_id'=>$details['chain_id'], 'is_live'=>((!empty($chain['chain_id']) && $chain['chain_id'] != '0')? 'Y': 'N')): array('result'=>'FAIL');
	}













	# Add a new chain
	function add_descriptor_chain($descriptorId, $userId, $details, $action)
	{
		log_message('debug', '_store/add_descriptor_chain');
		log_message('debug', '_store/add_descriptor_chain:: [1] descriptorId='.$descriptorId.' userId='.$userId.' details='.json_encode($details).' action='.$action);

		$result = FALSE;

		$newChainId = $this->_query_reader->add_data('add_new_chain', array('chain_name'=>htmlentities($details['name'], ENT_QUOTES), 'user_id'=>$userId));
		log_message('debug', '_store/add_descriptor_chain:: [2] newChainId='.$newChainId);

		# If reference links are provided, save them too.
		if(!empty($newChainId) && !empty($details['reference_links'])) $this->add_reference_links($details['reference_links'], $newChainId, $userId);

		# Record the change
		if(!empty($newChainId)) {
			# Save category
			$result = $this->_query_reader->run('add_chain_categories', array('chain_id'=>$newChainId, 'category_ids'=>$details['category']));
			log_message('debug', '_store/add_descriptor_chain:: [3] result='.$result);

			# Attach the chain to the descriptor
			$result = $this->_query_reader->run('add_chain_to_descriptor', array('descriptor_id'=>$descriptorId, 'chain_id'=>$newChainId, 'status'=>'approved', 'user_id'=>$userId));
			log_message('debug', '_store/add_descriptor_chain:: [4] result='.$result);

			# Add a new store if the address is created
			if(!empty($details['address'])) $newStoreId = $this->_query_reader->add_data('add_basic_store', array('name'=>htmlentities($details['name'], ENT_QUOTES), 'status'=>'active', 'address_line_1'=>htmlentities($details['address'], ENT_QUOTES), 'website'=>$details['website'], 'user_id'=>$userId));
			log_message('debug', '_store/add_descriptor_chain:: [5] newStoreId='.$newStoreId);

			# Update the chain details
			if(!empty($details['address'])) $result = $this->_query_reader->run('update_chain_field', array('field_name'=>'address_line_1', 'field_value'=>htmlentities($details['address'], ENT_QUOTES), 'chain_id'=>$newChainId));
			if(empty($details['website'])) $result = $this->_query_reader->run('update_chain_field', array('field_name'=>'website', 'field_value'=>$details['website'], 'chain_id'=>$newChainId));
			if(empty($details['zipcode'])) $result = $this->_query_reader->run('update_chain_field', array('field_name'=>'zipcode', 'field_value'=>$details['zipcode'], 'chain_id'=>$newChainId));
			log_message('debug', '_store/add_descriptor_chain:: [6] result='.$result);

			# Link store to descriptor
			if(!empty($newStoreId)) {
				$result = $this->_query_reader->run('link_store_to_chain', array('store_id'=>$newStoreId, 'chain_id'=>$newChainId, 'user_id'=>$userId ));

				$result = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'add_new_store_descriptor_record', 'variables'=>array('descriptor_id'=>$descriptorId, 'suggested_store_id'=>'0', 'store_id'=>$newStoreId, 'chain_id'=>$newChainId, 'category_id'=>$details['category'], 'status'=>'approved', 'user_id'=>$userId )));

				# Mark store as selected
				$result = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'mark_store_as_selected', array('store_id'=>$newStoreId, 'chain_id'=>$newChainId) ));
				log_message('debug', '_store/add_descriptor_chain:: [7] result='.$result);

			}

			# Record the chain changes
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId,
				'description'=>htmlentities('New chain suggested <b green>'.$details['name'].' | '.$details['address'].'</b>', ENT_QUOTES),
				'change_code'=>'descriptor_new_chain_suggestion',
				'change_value'=>'new_chain_id='.$newChainId.(!empty($newStoreId)? '|new_store_id='.$newStoreId: ''),
				'old_status'=>'',
				'new_status'=>($action != 'verified'? 'pending': 'verified'),
				'user_id'=>$userId
			));
			log_message('debug', '_store/add_descriptor_chain:: [8] result='.$result);
		}

		$returnArray = (((!empty($newChainId) && empty($descriptorId)) || !empty($descriptorId)) && $result)? array('result'=>'SUCCESS', 'new_chain_id'=>$newChainId, 'new_store_id'=>(!empty($newStoreId)? $newStoreId:'') ): array('result'=>'FAIL');
		log_message('debug', '_store/add_descriptor_chain:; [9] returnArray='.json_encode($returnArray));
		# Success if store id is generated for when not attached or attached to a descriptor
		return $returnArray;
	}




	# Add reference links
	function add_reference_links($referenceLinks, $chainId, $userId)
	{
		log_message('debug', '_store/add_reference_links');
		log_message('debug', '_store/add_reference_links:: [1] referenceLinks='.$referenceLinks.' chainId='.$chainId.' userId='.$userId);

		# First remove existing reference links
		$result = $this->_query_reader->run('remove_reference_links', array('chain_id'=>$chainId));
		log_message('debug', '_store/add_reference_links:: [2] result='.$result);

		# Now add the new reference links
		foreach($referenceLinks AS $link){
			$linkParts = explode('||', $link);
			$result = $this->_query_reader->run('add_reference_link', array('chain_id'=>$chainId, 'link'=>htmlentities($linkParts[0], ENT_QUOTES), 'link_text'=>htmlentities($linkParts[1], ENT_QUOTES), 'user_id'=>$userId ));
		}
		log_message('debug', '_store/add_reference_links:: [3] result='.$result);
	}






	# Get a list of stores by the given criteria
	function list_stores($phrase, $address, $categories, $zipcode, $website, $otherFilters, $offset, $limit, $userId)
	{
		log_message('debug', '_store/list_stores');
		log_message('debug', '_store/list_stores:: [1] phrase='.$phrase.' address='.$address.' categories='.$categories.' zipcode='.$zipcode.' website='.$website.' otherFilters='.$otherFilters.
				' offset='.$offset.' limit='.$limit.' userId='.$userId);

		$values['phrase'] = htmlentities($phrase, ENT_QUOTES);
		$values['address'] = htmlentities(str_replace(' ','%',$address), ENT_QUOTES);
		$values['category_ids'] = implode("','",  explode(',',$categories));
		$values['zipcode'] = $zipcode;
		$values['website'] = strtolower($website); #TODO: Add functionality to get website stem instead of whole submission
		$values['user_id'] = $userId;
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = $this->_query_reader->get_list('get_list_of_stores', $values);
		log_message('debug', '_store/list_stores:: [2] result='.$result);

		return $result;
	}









	# Get a list of search results by searching google based on the given criteria
	# Final link like:
	# https://www.googleapis.com/customsearch/v1?q=walmart+at+henderson+in+89123+website+walmart.com+in+Silverado+Ranch+Blvd&key=AIzaSyDzUPJlJ7PkSPrnysdMQPYicvbdciAeTNw&cx=017959134187640591984:vqwnnrzva0i&output=json&fields=items(title,link,snippet)
	function google_stores($phrase, $address, $zipcode, $website, $offset, $limit)
	{
		log_message('debug', '_store/google_stores');
		log_message('debug', '_store/google_stores:: [1] phrase='.$phrase.' address='.$address.' zipcode='.$zipcode.' website='.$website.' offset='.$offset.' limit='.$limit);

		$list = array();

		# Set the CURL Options
		$data['key'] = GOOGLE_API_KEY;
		$data['cx'] = GOOGLE_SEARCH_ENGINE_ID;
		$data['output'] = 'json';
		$data['fields'] = 'items(title,link,snippet)';
		$data['q'] = $phrase.(!empty($address)? ' at '.$address: '').(!empty($zipcode)? ' in '.$zipcode: '').(!empty($website)? ' website '.$website: '');
		$url = GOOGLE_SEARCH_API_URL.'?'.http_build_query($data);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl, CURLOPT_REFERER, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($curl);
		curl_close($curl);
		#Return the result from the cURL execution
		$googleList = json_decode($result, TRUE);
		log_message('debug', '_store/google_stores:: [2] googleList='.json_encode($googleList));

		if(!empty($googleList['items'])){
			foreach($googleList['items'] AS $item){
				array_push($list, array(
					'link'=> (!empty($item['link'])? $item['link']: ''),
					'link_id'=>strtotime('now'),
					'link_text'=> (!empty($item['title'])? $item['title']: ''),
					'link_description'=> (!empty($item['snippet'])? $item['snippet']: '')
				));
			}
		}
		log_message('debug', '_store/google_stores:: [3] list='.json_encode($list));

		return $list;
	}









	# Get a list of chains by the given criteria
	function chains($phrase, $storeId, $storeType, $offset, $limit)
	{
		log_message('debug', '_store/chains');
		log_message('debug', '_store/chains:: [1] phrase='.$phrase.' storeId='.$storeId.' storeType='.$storeType.' offset='.$offset.' limit='.$limit);

		$values['phrase'] = !empty($phrase)? htmlentities($phrase, ENT_QUOTES): '%';
		$values['store_id'] = $storeId;
		$values['limit_text'] = " LIMIT ".$offset.",".$limit." ";

		$result = $this->_query_reader->get_list(
			($storeType == 'suggestion'? 'get_chains_for_suggested': 'get_chains_for_store'), $values);
		log_message('debug', '_store/chains:: [2] result='.json_encode($result));

		return $result;
	}





	# Add a chain
	function add_chain($chainName, $chainId, $storeId, $storeType, $descriptorId, $userId)
	{
		log_message('debug', '_store/add_chain');
		log_message('debug', '_store/add_chain:: [1] chainName='.$chainName.' chainId='.$chainId.' storeId='.$storeId.' storeType='.$storeType.' descriptorId='.$descriptorId.' userId='.$userId);

		# Updating an existing chain
		if(!empty($chainId)){
			$chain = $this->_query_reader->get_row_as_array('get_chain_by_id', array('chain_id'=>$chainId));

			# 1. Update the name of the chain
			$result = $this->_query_reader->run('update_chain_field', array('field_name'=>'name', 'field_value'=>htmlentities($chainName, ENT_QUOTES), 'chain_id'=>$chainId, 'user_id'=>$userId));

			$changeValue = 'chain_name='.$chainName.'|chain_id='.$chainId.'|store_id='.$storeId.'|store_type='.$storeType.'|old_chain_name='.(!empty($chain['name'])? $chain['name']: '');
			$changeCode = 'store_chain_updated';
			$description = 'Chain updated'.(!empty($chain['name'])? ' from <b>'.$chain['name'].'</b>': '').' to <b>'.$chainName.'</b>';
		}
		# Adding a new chain
		else {
			# 1. Add the chain record
			$chainId = $this->_query_reader->add_data('add_new_chain', array('chain_name'=>htmlentities($chainName, ENT_QUOTES), 'user_id'=>$userId));
			$result = !empty($chainId);
			$changeCode = 'store_chain_added';
			$changeValue = 'chain_name='.$chainName.'|chain_id='.$chainId.'|store_id='.$storeId.'|store_type='.$storeType;
			$description = 'New chain added <b green>'.$chainName.'</b>';
		}
		log_message('debug', '_store/add_chain:: [2] chainId='.$chainId.' result='.$result);

		# 2. Link the store to the chain
		if($result && !empty($storeId)) $result = $this->_query_reader->run('link_'.$storeType.'_to_chain', array('store_id'=>$storeId, 'chain_id'=>$chainId, 'user_id'=>$userId));
		log_message('debug', '_store/add_chain:: [3] result='.$result);

		# Record the change
		if($result) {
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId,
				'description'=>htmlentities($description, ENT_QUOTES),
				'change_code'=>$changeCode,
				'change_value'=>$changeValue,
				'old_status'=>'',
				'new_status'=>'verified',
				'user_id'=>$userId
			));
		}
		log_message('debug', '_store/add_chain:: [1] result='.$result);

		return $result? array('result'=>'SUCCESS', 'chainName'=>$chainName, 'chainId'=>$chainId): array('result'=>'FAIL');
	}





	# Edit a chain
	function edit_chain($chainId, $fields, $fieldValues, $userId)
	{
		log_message('debug', '_store/edit_chain');
		log_message('debug', '_store/edit_chain:: [1] chainId='.$chainId.' fields='.$fields.' fieldValues='.$fieldValues.' userId='.$userId);

		$string = '';
		foreach($fields AS $field){
			if(!empty($fieldValues[$field])) $string .= $field."='".$fieldValues[$field]."',";
		}

		$result = !empty($string)? $this->_query_reader->run('update_chain_parts', array('update_string'=>trim($string, ','), 'chain_id'=>$chainId)):FALSE;
		log_message('debug', '_store/edit_chain:: [2] result='.$result);

		return $result? array('result'=>'SUCCESS'): array('result'=>'FAIL');
	}




	# Get store details for editing
	function edit_store_details($storeId)
	{
		log_message('debug', '_store/edit_store_details');
		log_message('debug', '_store/edit_store_details:: [1] storeId='.$storeId);

		$result = $this->_query_reader->get_row_as_array('get_edit_store_details_by_id', array('store_id'=>$storeId ));
		log_message('debug', '_store/edit_store_details:: [2] result='.json_encode($result));

		return $result;
	}





	# Edit store details
	function edit_store($storeId, $fields, $fieldValues, $userId)
	{
		log_message('debug', '_store/edit_store');
		log_message('debug', '_store/edit_store:: [1] storeId='.$storeId.' fields='.$fields.' fieldValues='.$fieldValues.' userId='.$userId);

		if(empty($storeId))
		{
			$chain = $this->_query_reader->get_row_as_array('get_edit_chain_details_by_id', array('chain_id'=>$fieldValues['chain_id']));

			$storeId = $this->_query_reader->add_data('add_basic_store', array('name'=>$chain['chain_name'], 'status'=>'active', 'address_line_1'=>$fieldValues['address_line_1'], 'website'=>$chain['website'], 'user_id'=>$userId));
			log_message('debug', '_store/edit_store:: [2] storeId='.$storeId);

			$result = !empty($storeId)? $this->_query_reader->run('link_store_to_chain', array('store_id'=>$storeId, 'chain_id'=>$fieldValues['chain_id'], 'user_id'=>$userId)): FALSE;
			if($result) $result = $this->_query_reader->run('update_store_parts', array('update_string'=>"zipcode='".$fieldValues['zipcode']."'", 'store_id'=>$storeId));

			if($result) $result = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'add_new_store_descriptor_record', 'variables'=>array('descriptor_id'=>$fieldValues['descriptor_id'], 'suggested_store_id'=>'0', 'store_id'=>$storeId, 'chain_id'=>$fieldValues['chain_id'], 'category_id'=>$chain['category_id'], 'status'=>'approved', 'user_id'=>$userId) ));

		}
		else
		{
			$string = '';
			foreach($fields AS $field){
				if(!empty($fieldValues[$field])) $string .= $field."='".$fieldValues[$field]."',";
			}

			$result = !empty($string)? $this->_query_reader->run('update_store_parts', array('update_string'=>trim($string, ','), 'store_id'=>$storeId)):FALSE;
		}
		log_message('debug', '_store/edit_store:: [1] result='.$result);

		return $result? array('result'=>'SUCCESS', 'new_store_id'=>$storeId): array('result'=>'FAIL');
	}





	# Get store statistics
	function statistics($storeId, $userId, $fields, $type)
	{
		log_message('debug', '_store/statistics');
		log_message('debug', '_store/statistics:: [1] storeId='.$storeId.' userId='.$userId.' fields='.json_encode($fields).' type='.$type);

		$statistics = array();
		$store = server_curl(CRON_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_store_'.$type.'_statistics', 'variables'=>array('store_id'=>$storeId, 'user_id'=>$userId) ));
		log_message('debug', '_store/statistics:: [2] store='.json_encode($store));

		foreach($fields AS $field) $statistics[$field] = !empty($store[$field]) ? $store[$field]: '';
		log_message('debug', '_store/statistics:: [3] statistics='.json_encode($statistics));

		return $statistics;
	}





	# Add a store favorite
	function favorite($storeId, $userId, $action)
	{
		log_message('debug', '_store/favorite');
		log_message('debug', '_store/favorite:: [1] storeId='.$storeId.' userId='.$userId.' action='.$action);

		$result = $this->_query_reader->run($action.'_favorite_store', array('store_id'=>$storeId, 'user_id'=>$userId));
		log_message('debug', '_store/favorite:: [2] result='.$result);

		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}








	# Get a list of store reviews
	function reviews($storeId, $userId, $offset, $limit)
	{
		# TODO: Add option to show un-published reviews based on the user-type
		log_message('debug', '_store/reviews');
		log_message('debug', '_store/reviews:: [1] storeId='.$storeId.' userId='.$userId.' offset='.$offset.' limit='.$limit);

		$result = $this->_query_reader->get_list('get_list_of_store_reviews', array('store_id'=>$storeId,'limit_text'=>" LIMIT ".$offset.",".$limit." "));
		log_message('debug', '_store/reviews:: [2] result='.json_encode($result));

		return $result;
	}



	# Get a user's store review
	function get_review($storeId, $userId)
	{
		log_message('debug', '_store/get_review');
		log_message('debug', '_store/get_review:: [1] storeId='.$storeId.' userId='.$userId);

		$result = $this->_query_reader->get_row_as_array('get_user_store_review', array('store_id'=>$storeId, 'user_id'=>$userId));
		log_message('debug', '_store/get_review:: [2] result='.json_encode($result));

		return $result;
	}




	# Post a user review
	function add_review($storeId, $userId, $score, $comment)
	{
		log_message('debug', '_store/add_review');
		log_message('debug', '_store/add_review:: [1] storeId='.$storeId.' userId='.$userId.' score='.$score.' comment='.$comment);

		$result = $this->_query_reader->run('add_store_review', array('store_id'=>$storeId,'user_id'=>$userId, 'score'=>($score > 5? 5: $score), 'comment'=>htmlentities($comment, ENT_QUOTES) ));
		log_message('debug', '_store/add_review:: [2] result='.$result);

		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}





	# Post a store photo
	function add_photo($storeId, $userId, $photoUrl, $comment)
	{
		log_message('debug', '_store/add_photo');
		log_message('debug', '_store/add_photo:: [1] storeId='.$storeId.' userId='.$userId.' photoUrl='.$photoUrl.' comment='.$comment);

		$fileUrl = download_from_url($photoUrl);
		if(!empty($fileUrl)){
			$result = $this->_query_reader->run('add_store_photo', array('store_id'=>$storeId, 'user_id'=>$userId, 'photo_url'=>$fileUrl, 'photo_note'=>htmlentities($comment, ENT_QUOTES), 'photo_category'=>'store_photo', 'status'=>'active' ));
		}
		log_message('debug', '_store/add_photo:: [2] result='.$result);

		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}






	# Get a list of store photos
	function photos($storeId, $userId, $offset, $limit, $baseUrl)
	{
		# TODO: Get store photos based on user type
		log_message('debug', '_store/photos');
		log_message('debug', '_store/photos:: [1] storeId='.$storeId.' userId='.$userId.' offset='.$offset.' limit='.$limit.' baseUrl='.$baseUrl);

		$result = $this->_query_reader->get_list('get_list_of_store_photos', array('store_id'=>$storeId,'limit_text'=>" LIMIT ".$offset.",".$limit." ", 'base_image_url'=>S3_URL ));
		log_message('debug', '_store/photos:: [2] result='.json_encode($result));

		return $result;
	}




	# Request offers from store
	function request_offers($storeId, $userId, $type, $requests)
	{
		log_message('debug', '_store/request_offers');
		log_message('debug', '_store/request_offers:: [1] storeId='.$storeId.' userId='.$userId.' type='.$type.' requests='.json_encode($requests));

		if($type == 'what_you_want'){
			$result = $this->_query_reader->run('add_offer_request', array('store_id'=>$storeId, 'user_id'=>$userId,
				'wants_cashback'=>(in_array('cashback',$requests)? 'Y': 'N'),
				'wants_perks'=>(in_array('perks',$requests)? 'Y': 'N'),
				'wants_vip'=>(in_array('vip',$requests)? 'Y': 'N')
			));
		}
		else if($type == 'add_to_vip_list')
		{
			$result = $this->_query_reader->run('update_offer_request', array('store_id'=>$storeId, 'user_id'=>$userId,
				'per_visit_spend'=>$requests['perVisitSpend'],
				'per_month_spend'=>$requests['perMonthSpend']
			));
		}
		log_message('debug', '_store/request_offers:: [2] result='.$result);

		return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	}





	# get store staff
	function staff($storeId)
	{
		log_message('debug', '_store/staff');
		log_message('debug', '_store/staff:: [1] storeId='.$storeId.' userId='.$userId.' type='.$type.' requests='.json_encode($requests));

		$result = $this->_query_reader->get_list('get_store_staff', array('store_id'=>$storeId));
		log_message('debug', '_store/staff:: [2] result='.$result);

		return $result;
	}




	# get store id from mongo db
	function public_mapping($chainName, $address) {
		// $result: array('store_id', 'name', 'address')
		log_message('debug', '_store/public_mapping');
		log_message('debug', '_store/public_mapping:: [1] chainName='.$chainName.' address='.$address);

		$result = $this->_query_reader->get_row_as_array('mongodb__get_public_store_mapping', array('public_store_key'=>$chainName . '-' . $address));
		log_message('debug', '_store/public_mapping:: [2] result='.json_encode($result));

		return $result;
	}









}


