<?php
/**
 * Searches data in the system.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 09/13/2015
 */
class _search extends CI_Model
{
	
	# The search categories
	function categories($userId)
	{
		log_message('debug', '_search/categories');
		log_message('debug', '_search/categories:: [1] userid='.$userId);
		
		$result =  $this->_query_reader->get_list('get_store_categories');
		log_message('debug', '_search/categories:: [1] result='.json_encode($result));
		
		return $result;
	}

	# Get categories with subcategories
	function categories_all($userId)
	{
		log_message('debug', '_search/categories_all');
		log_message('debug', '_search/categories_all:: [1] userid='.$userId);

		$categories = $this->categories($userId);
		foreach($categories as $key => $category) {
			$subcategories = $this->_query_reader->get_list('get_categories_level_2_by_parent', array('category_id' => $category['id']));
			if ($subcategories) {
				$categories[$key]['subcategories'] = $subcategories;
			}
		}

		return $categories;
	}

	# Get competitors of store
	function competitors_list($store_id)
	{
		log_message('debug', '_search/competitors');
		log_message('debug', '_search/competitors:: [1] storeId='.$store_id);

		$result = $this->_query_reader->get_list('get_store_competitors', array(
			'store_id' => $store_id
		));
		
		return $result;
	}
	
	
	# The store search phrase suggestions
	function store_phrase_suggestions($userId, $restrictions = array())
	{
		log_message('debug', '_search/store_phrase_suggestions');
		log_message('debug', '_search/store_phrase_suggestions:: [1] userid='.$userId.' restrictions='.json_encode($restrictions));
		
		$suggestions = array();
		
		# offer a suggestion if the phrase is more than 3 characters
		if(!empty($restrictions['phrase']) && strlen($restrictions['phrase']) > 2){
			$phrase = htmlentities(trim($restrictions['phrase']), ENT_QUOTES);
			$suggestions = $this->_query_reader->get_list('store_phrase_suggestions', array('phrase'=>$phrase, 'limit_text'=>" LIMIT 0,".$restrictions['limit']." "));
		}
		
		log_message('debug', '_search/store_phrase_suggestions:: [2] suggestions='.json_encode($suggestions));
		return $suggestions;
	}
	
	
	
	
	# The store search suggestions
	function store_suggestions($userId, $restrictions)
	{
		log_message('debug', '_search/store_suggestions');
		log_message('debug', '_search/store_suggestions:: [1] userid='.$userId.' restrictions='.json_encode($restrictions));
		
		if($restrictions['type'] == 'name') $list = $this->simple_store_search($restrictions);
		else if($restrictions['type'] == 'details') $list = $this->advanced_store_search($userId, $restrictions);
		
		log_message('debug', '_search/store_suggestions:: [2] list='.json_encode($list));
		return !empty($list)? $list: array();
	}
	
	
	
	
	# Perform a simple store search phrase suggestions
	function simple_store_search($restrictions)
	{
		log_message('debug', '_search/simple_store_search');
		log_message('debug', '_search/simple_store_search:: [1] restrictions='.json_encode($restrictions));
		
		$result = $this->_query_reader->get_list('get_search_suggestions', array(
			'phrase'=>(!empty($restrictions['phrase'])? htmlentities($restrictions['phrase'], ENT_QUOTES): ''),
			'limit_text_1'=>" LIMIT ".$restrictions['offset'].", ".$restrictions['limit']
		));
		log_message('debug', '_search/simple_store_search:: [2] result='.json_encode($result));
		
		return $result;
	}
	
	
	
	
	
	
	# Perform a advanced store search
	function advanced_store_search($userId, $restrictions)
	{
		log_message('debug', '_search/advanced_store_search');
		log_message('debug', '_search/advanced_store_search:: [1] userId='.$userId.'restrictions='.json_encode($restrictions));
		$this->benchmark->mark('advanced_store_search_start');
		
		$start = time();
		$MAX_EXECUTION_TIME = 10; #Seconds
		$MAX_PREFERRED_SEARCH_DISTANCE = 50; #kilometers
		
		$exclude = $restrictions['exclude'];
		$final = $stores = $response = $categories = $subCategories = array();
		if(empty($restrictions['exclude'])) $restrictions['exclude'] = array();
		log_message('debug', '_search/advanced_store_search:: [2] exclude stores='.json_encode($restrictions['exclude']));
		
		$this->benchmark->mark('update_location_center_start');
		# Next process the location phrase if one is given, then update the location conditions accordingly
		if(!empty($restrictions['filters']['locationEntered'])) $restrictions = $this->update_location_center($userId,$restrictions);
		
		$response['origin'] = array(
			'latitude'=>(!empty($restrictions['latitude'])? $restrictions['latitude']: ''), 
			'longitude'=>(!empty($restrictions['longitude'])? $restrictions['longitude']: ''), 
			'zipcode'=>(!empty($restrictions['filters']['zipcode'])? $restrictions['filters']['zipcode']: '')
		);
		log_message('debug', '_search/advanced_store_search:: [3] new origin based on location phrase='.json_encode($response['origin']));
		$this->benchmark->mark('update_location_center_end');
		log_message('debug', '_search/advanced_store_search:: [2] update_location_center(time)='.$this->benchmark->elapsed_time('update_location_center_start', 'update_location_center_end'));
		
		
		$this->benchmark->mark('get_search_category_start');
		# check if the user has selected a suggestion - which narrows down the category to search
		if(!empty($restrictions['filters']['suggestionId'])){
			if($restrictions['filters']['suggestionType'] == 'sub_category'){
				$subCategories = array($restrictions['filters']['suggestionId']);
			}
			if($restrictions['filters']['suggestionType'] == 'category'){
				$categories = array($restrictions['filters']['suggestionId']);
			}
			log_message('debug', '_search/advanced_store_search:: [4] user selected category or subcategory. hence sub-categories='.json_encode($subCategories).' AND categories='.json_encode($categories));
		}
		
		# Check if the search phrase matches any sub_categories or categories
		if(!empty($restrictions['phrase']) && empty($categories) && empty($subCategories)){
			$categories = $this->_query_reader->get_single_column_as_array('get_category_matches_for_search', 'id', array('phrase'=> htmlentities($restrictions['phrase'], ENT_QUOTES)));
			
			$subCategories = $this->_query_reader->get_single_column_as_array('get_sub_category_matches_for_search', 'id', array('phrase'=> htmlentities($restrictions['phrase'], ENT_QUOTES)));
			log_message('debug', '_search/advanced_store_search:: [4] user entered own phrase. matching sub-categories='.json_encode($subCategories).' AND categories='.json_encode($categories));
		} 
		$this->benchmark->mark('get_search_category_end');
		log_message('debug', '_search/advanced_store_search:: [2] get_search_category(time)='.$this->benchmark->elapsed_time('get_search_category_start', 'get_search_category_end'));
		
		
		$this->benchmark->mark('get_stores_where_shopped_start');
		# Order by 
		if($restrictions['order'] == 'best_deal') $order = " (distance+0) ASC, (max_cashback+0) DESC ";
		else if($restrictions['order'] == 'score') $order = " (store_score+0) DESC, (distance+0) ASC ";
		# sorted by distance by default
		else $order = "(distance+0) ASC, (store_score+0) DESC";
		
		
		# The base query parameters
		$parameters = array(
			'user_id'=>$userId,
			'max_distance'=>DEFAULT_SEARCH_DISTANCE, 
			'latitude'=>$restrictions['latitude'], 
			'longitude'=>$restrictions['longitude'], 
			'categories'=>implode("','",$categories),
			'sub_categories'=>implode("','",$subCategories),
			'exclude_id_list'=>implode("','",$restrictions['exclude']), 
			'exclude_condition'=>(!empty($restrictions['exclude'])? " AND store_id NOT IN ('".implode("','",$restrictions['exclude'])."')": ""),
			'limit_text'=>' LIMIT 0,'.$restrictions['limit'],
			'phrase'=>(!empty($restrictions['phrase'])? htmlentities($restrictions['phrase'], ENT_QUOTES): ''),
			'location'=>(!empty($restrictions['location'])? htmlentities($restrictions['location'], ENT_QUOTES): ''),
			'order'=>$order
		);
		log_message('debug', '_search/advanced_store_search:: [5] final default query parameters='.json_encode($parameters));
		
		
		
		# Now get all the stores where the user shopped
		$whereShopped = $this->_query_reader->get_single_column_as_array('get_stores_where_shopped', 'store_id', array_merge($parameters, array('max_distance'=>$MAX_PREFERRED_SEARCH_DISTANCE)) );
		$this->benchmark->mark('get_stores_where_shopped_end');
		log_message('debug', '_search/advanced_store_search:: [2] get_stores_where_shopped(time)='.$this->benchmark->elapsed_time('get_stores_where_shopped_start', 'get_stores_where_shopped_end'));

		# a-1) first search by exact phrase given
		$this->benchmark->mark('stores_with_phrase_start');
		if(!empty($restrictions['phrase'])) {
			$phraseStores = $this->_query_reader->get_single_column_as_array('get_stores_with_phrase', 'store_id',
					array_merge($parameters, array(
							'max_distance'=>'50',
							'limit_text'=>' LIMIT 0,'.(!empty($categories) || !empty($subCategories)? '5': $restrictions['limit']),
							'phrase'=>addslashes(str_replace('&#039;',"'",$restrictions['phrase'])) 
					)));
			
			$phraseStores = $this->_query_reader->get_list('mongodb__get_stores_shopped', array_merge($parameters, array('include_condition'=>"store_id IN ('".implode("','",$phraseStores)."')")) );
			 
			$otherStores = array();
			foreach($phraseStores AS $key=>$row) {
				if(in_array($row['store_id'], $whereShopped)){
					$row['search_rank'] = '1';
					array_push($final, $row); 
				}
				else {
					$row['search_rank'] = '2';
					array_push($otherStores, $row);
				}
			}
			$final = array_merge($final,$otherStores);
		}
		$this->benchmark->mark('stores_with_phrase_end');
		log_message('debug', '_search/advanced_store_search:: [2] stores_with_phrase(time)='.$this->benchmark->elapsed_time('stores_with_phrase_start', 'stores_with_phrase_end'));
		if(count($final) >= $restrictions['limit']) goto PROCESS_AND_LEAVE;
		
		
		# a -2) get stores by rank within search distance.
		# 1 - featured
		# 2 - have deals
		# 3 - other stores
		if(!empty($categories) || !empty($subCategories)) {
			$this->benchmark->mark('get_stores_in_search_categories_start');
			$condition = !empty($subCategories)? "subcategories IN ('".implode("','",$subCategories)."') ": "categories IN ('".implode("','",$categories)."')";
			$categoryStores = $this->_query_reader->get_list('mongodb__get_stores_in_search_categories', array_merge($parameters, array('category_condition'=>$condition)) );
			
			# repeat search with wider net if previous one did not capture enough
			if(count($categoryStores) < $restrictions['limit']) {
				$categoryStores = $this->_query_reader->get_list('mongodb__get_stores_in_search_categories', 
					array_merge($parameters, array('category_condition'=>$condition, 'max_distance'=>($MAX_PREFERRED_SEARCH_DISTANCE * 5))) );
			}
			$this->benchmark->mark('get_stores_in_search_categories_end'); 
			log_message('debug', '_search/advanced_store_search:: [5-1] get_stores_in_search_categories(time)='.$this->benchmark->elapsed_time('get_stores_in_search_categories_start', 'get_stores_in_search_categories_end'));
		}
		else {
			$this->benchmark->mark('get_stores_featured_start');
			$featuredStoreIds = $this->_query_reader->get_single_column_as_array('get_stores_featured', 'store_id', array_merge($parameters, array('max_distance'=>$MAX_PREFERRED_SEARCH_DISTANCE)));
			if(!empty($featuredStoreIds)){
				$featuredStores = $this->_query_reader->get_list('mongodb__get_stores_shopped',
					array_merge($parameters, array('include_condition'=>"store_id IN ('".implode("','",array_diff($featuredStoreIds, $restrictions['exclude']))."')")) );
			} 
			else $featuredStores= array();
			$this->benchmark->mark('get_stores_featured_end');
			log_message('debug', '_search/advanced_store_search:: [5-2] get_stores_featured(time)='.$this->benchmark->elapsed_time('get_stores_featured_start', 'get_stores_featured_end'));
			
			$this->benchmark->mark('mongodb_get_stores_shopped_start');
			if(count($featuredStores) < $restrictions['limit'] && !empty($whereShopped)) {
				$whereShoppedStores = $this->_query_reader->get_list('mongodb__get_stores_shopped', 
						array_merge($parameters, array('include_condition'=>"store_id IN ('".implode("','",array_diff($whereShopped, $restrictions['exclude']))."')")) );
			}
			else $whereShoppedStores = array();
			# take what to show a user before they search
			$categoryStores = array_merge($featuredStores, $whereShoppedStores);
			$this->benchmark->mark('mongodb_get_stores_shopped_end');
			log_message('debug', '_search/advanced_store_search:: [5-2] mongodb_get_stores_shopped(time)='.$this->benchmark->elapsed_time('mongodb_get_stores_shopped_start', 'mongodb_get_stores_shopped_end'));
		}
		log_message('debug', '_search/advanced_store_search:: [6] categoryStores='.json_encode($categoryStores));
		
		# b) sort stores with rank by:
		# 1~1 - shopped here
		# 1~2 - not shopped here
		# 2~1 - shopped here
		# 2~2 - not shopped here
		# 3~1 - shopped here
		# 3~2 - not shopped here
		$this->benchmark->mark('merge_searched_stores_start');
		$otherStores = $shoppedStores = array();
		foreach($categoryStores AS $row) {
			if(!empty($row['search_rank'])){
				if(empty($shoppedStores[$row['search_rank']])) $shoppedStores[$row['search_rank']] = array();
				if(empty($otherStores[$row['search_rank']])) $otherStores[$row['search_rank']] = array();
				
				if(in_array($row['store_id'], $whereShopped)) array_push($shoppedStores[$row['search_rank']], $row);
				else array_push($otherStores[$row['search_rank']], $row);
			}
		}

		# rank is assumed to be 1 to 3
		for($i=1;$i < 4; $i++){
			# first merge the shopped stores
			if(!empty($shoppedStores[$i])) $final = array_merge($final, $shoppedStores[$i]);
			# then merge every other store in that rank
			elseif(!empty($otherStores[$i])) $final = array_merge($final, $otherStores[$i]);
		}
		$this->benchmark->mark('merge_searched_stores_end');
		log_message('debug', '_search/advanced_store_search:: [2] merge_searched_stores(time)='.$this->benchmark->elapsed_time('merge_searched_stores_start', 'merge_searched_stores_end'));
		
		# Start here to process before you leave the function to fetch the store details as needed
		PROCESS_AND_LEAVE:
		$this->benchmark->mark('add_store_search_details_start');
		$stores = array_slice($final, 0, $restrictions['limit']);
		$response['store_ids'] = get_column_from_multi_array($stores, 'store_id');
		
		$response['list'] = $this->add_store_search_details($userId, $stores,
				array('where_shopped'=>$whereShopped, 'latitude'=>$restrictions['latitude'], 'longitude'=>$restrictions['longitude'], 'order'=>$order)
			);
		$this->benchmark->mark('add_store_search_details_end');
		
		$this->benchmark->mark('advanced_store_search_end');
		
		# for keeping track of time leave if needed
		log_message('debug', '_search/advanced_store_search:: [2] add_store_search_details(time)='.$this->benchmark->elapsed_time('add_store_search_details_start', 'add_store_search_details_end'));
		log_message('debug', '_search/advanced_store_search:: [2] advanced_store_search(time)='.$this->benchmark->elapsed_time('advanced_store_search_start', 'advanced_store_search_end'));
		
		log_message('debug', '_search/advanced_store_search:: [2] response='.json_encode($response));
		return $response;
	}
	
	
	
	
	
	
	
	# fetch the details of the store for display in a serach result
	function add_store_search_details($userId, $stores, $more)
	{
		log_message('debug', '_search/add_store_search_details:: [1]');
		log_message('debug', '_search/add_store_search_details:: [2] userId='.$userId.' stores='.count($stores).' more='.json_encode($more));
		
		if(!empty($stores)){
			# get all the details for the stores
			$storeQuery = "";
			$storesWithoutMaps = '';
			$counter = 0;
			foreach($stores AS $i=>$row){
				$storeLatitude = (!empty($row['loc']['coordinates'][0])? $row['loc']['coordinates'][0]: '');
				$storeLongitude = (!empty($row['loc']['coordinates'][1])? $row['loc']['coordinates'][1]: '');
				
				$storeQuery .= ($counter > 0? ' UNION ': '')."(SELECT '".$row['store_id']."' AS store_id, 
IFNULL((SELECT total_score FROM clout_v1_3cron.cacheview__store_score_by_store WHERE user_id='".$userId."' AND store_id='".$row['store_id']."' LIMIT 1), 
IFNULL((SELECT MAX(total_score) FROM clout_v1_3cron.cacheview__store_score_by_category WHERE user_id='".$userId."' AND sub_category_id IN ('".implode("','",$row['subcategories'])."')), 
(SELECT total_score FROM clout_v1_3cron.cacheview__store_score_by_default WHERE user_id='".$userId."'))) AS store_score, 
'0' AS store_earnings, 
'".$storeLatitude."' AS latitude,
'".$storeLongitude."' AS longitude, 
'".(!empty($row['search_rank'])? $row['search_rank']: '3')."' AS search_rank, 
".(!empty($row['loc']['coordinates']) && count($row['loc']['coordinates']) == 2? "clout_v1_3.get_distance('".$more['latitude']."','".$more['longitude']."','".$row['loc']['coordinates'][1]."', '".$row['loc']['coordinates'][0]."')": "''")." AS distance,
IF('".$row['store_id']."' IN ('".implode("','", $more['where_shopped'])."'), 'Y', 'N') AS has_shopped_here, 
(SELECT name FROM clout_v1_3.categories_level_1 WHERE id IN ('".implode("','",$row['categories'])."') LIMIT 1) AS search_category,
'' AS small_banner)";
				
				# keep track of the stores without maps
				if(!url_exists(s3_url('banner_'.$row['store_id'].'.png')) && !empty($storeLatitude) && !empty($storeLongitude)) {
					$storesWithoutMaps .= '__'.$storeLatitude.'_'.$storeLongitude.'_'.$row['store_id'];
				}
				$counter++;
			}	
			
			#batch run the search query to collect all details at once
			$rawList = $this->_query_reader->get_list('get_store_search_details', array('search_string'=>$storeQuery, 'sort_by'=>' ORDER BY search_rank, distance' ));
			$storeKeyedList = bundle_by_column_multi_array($stores, 'store_id','', TRUE);
			log_message('debug', '_search/add_store_search_details:: [3] storeKeyedList='.json_encode($rawList));
			
			$list = array();
			foreach($rawList AS $store){
				if(!empty($store['store_id']) && !empty($storeKeyedList[$store['store_id']])) $list[] = array_merge($store, $storeKeyedList[$store['store_id']]);
			}
			
			# schedule job to pull maps from stores
			if(!empty($storesWithoutMaps)){
				log_message('debug', '_search/add_store_search_details:: [4] scheduling job to pull maps for missing stores.');
				$scheduleResult = server_curl(CRON_SERVER_URL,  array('__action'=>'add_job_to_queue',
							'return'=>'plain',
							'jobId'=>'j'.$userId.'-'.strtotime('now').'-'.rand(0,1000000),
							'jobUrl'=>'map_cron',
							'userId'=>$userId,
							'jobCode'=>'basic_curl',
							'parameters'=>array('db_server_url'=>CRON_SERVER.'/map_cron/pull_store_maps/locations/'.trim($storesWithoutMaps, '__'))
					));
			}
			
		}
		else $list = array();
		
		log_message('debug', '_search/add_store_search_details:: [4] list count='.count($list));
		return $list;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	# Generate store banner
	function generate_store_banner($store)
	{
		log_message('debug', '_search/generate_store_banner');
		log_message('debug', '_search/generate_store_banner:: [1] store='.json_encode($store));
		$fileName = '';
		
		$finalFileName = 'banner_'.$store['store_id'].'.png';
		# File does not exist
		if(!url_exists(s3_url($finalFileName)))
		{
			$fileName = download_from_url("http://maps.googleapis.com/maps/api/staticmap?center=".$store['latitude'].",".$store['longitude']."&zoom=15&size=400x125&markers=icon:http://pro-fe-web1.clout.com/assets/images/map_marker.png|".$store['latitude'].",".$store['longitude'], FALSE, 'name', $finalFileName);
		}
		# File already exists
		else $fileName = $finalFileName;
		
		log_message('debug', '_search/generate_store_banner:: [1] fileName='.$fileName);
		return $fileName;
	}
	
	
	
	
	
	
	
	
	
	# Update the search location center (latitude, longitude and start zipcode)
	function update_location_center($userId,$restrictions)
	{
		log_message('debug', '_search/update_location_center');
		log_message('debug', '_search/update_location_center:: [1] userId='.$userId.' restrictions='.json_encode($restrictions));
		
		$phrase = $restrictions['filters']['locationEntered'];
		
		# Is this a 
		# a) zipcode?
		if(preg_match('/^\d+$/',$phrase)){
			$restrictions['filters']['zipcode'] = strlen($phrase) < 5? sprintf('%05d', $phrase): $phrase;
			$zipcode = $this->_query_reader->get_row_as_array('get_zipcode_details', array('zipcode'=>$restrictions['filters']['zipcode']));
			log_message('debug', '_search/update_location_center:: [2] zipcode='.json_encode($zipcode));
			
			if(!empty($zipcode)) {
				$restrictions['latitude'] = $zipcode['latitude'];
				$restrictions['longitude'] = $zipcode['longitude'];
			}
		}
		# b) any other phrase
		else {
			$this->load->model('_location');
			$location = $this->_location->locate_with_google_lat_lng($phrase);
			log_message('debug', '_search/update_location_center:: [2] google response='.json_encode($location));
			
			if(!empty($location['latitude'])) $restrictions['latitude'] = $location['latitude'];
			if(!empty($location['longitude'])) $restrictions['longitude'] = $location['longitude'];
			
			# Record the user address
			$this->_query_reader->run('add_user_address', array('user_id'=>$userId, 'address_line_1'=>$phrase, 
				'address_line_2'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 
				'zipcode'=>'', 'address_type'=>'other'
			));
		}
		log_message('debug', '_search/update_location_center:: [2] restrictions='.json_encode($restrictions));
		
		return $restrictions;
	}
	
	
	
	# The store search locations
	function store_locations($userId, $restrictions)
	{
		log_message('debug', '_search/store_locations');
		log_message('debug', '_search/store_locations:: [1] userId='.$userId.' restrictions='.json_encode($restrictions));
		
		$list = array();
		$list = $this->_query_reader->get_list('get_location_'.(preg_match('/^\d+$/',$restrictions['phrase'])? 'zipcodes': 'phrases'), array(
			'user_id'=>$userId, 
			'phrase'=>str_replace(' ', ' +', htmlentities($restrictions['phrase'], ENT_QUOTES)), 
			'limit_text'=>" LIMIT ".$restrictions['offset'].", ".$restrictions['limit']." "
		));
		log_message('debug', '_search/store_locations:: [2] list='.json_encode($list));
		
		return $list;
	}
	
	
	
	
	
	# The states
	function states($offset, $limit, $phrase)
	{
		log_message('debug', '_search/states');
		log_message('debug', '_search/states:: [1] offset='.$offset.' limit='.$limit.' phrase='.$phrase);
		
		$result = $this->_query_reader->get_list('get_system_states', array(
			'phrase'=>htmlentities($phrase, ENT_QUOTES), 
			'limit_text'=>" LIMIT ".$offset.", ".$limit." "
		));
		log_message('debug', '_search/states:: [2] result='.json_encode($result));
		
		return $result;
	}
	
	
	
	
	
	# The countries
	function countries()
	{
		log_message('debug', '_search/countries');
		
		$result = $this->_query_reader->get_list('get_system_countries');
		log_message('debug', '_search/countries:: [1] result='.json_encode($result));
		
		return $result;
	}
	
	# Get states by countries
	function states_list($countries)
	{
		log_message('debug', '_search/states_list');
		log_message('debug', '_search/states_list:: [1] countries=' . $countries);

		$countriesArray = explode(',', $countries);
		foreach($countriesArray as $key => $country) {
			if (empty($country)) {
				continue;
			}
			$countriesArray[$key] = "'$country'";
		}

		$countries = implode(',', $countriesArray);

		$result = $this->_query_reader->get_list('get_states_by_countries', array('code' => "$countries"));

		log_message('debug', '_search/countries_list:: [2] result=' . json_encode($result));

		return $result;
	}

	# Get cities by countries
	function cities_list($countries)
	{
		log_message('debug', '_search/cities_list');
		log_message('debug', '_search/cities_list:: [1] countries='.$countries);

		$countriesArray = explode(',', $countries);
		foreach($countriesArray as $key => $country) {
			if (empty($country)) {
				continue;
			}
			$countriesArray[$key] = "'$country'";
		}

		$countries = implode(',', $countriesArray);

		$result = $this->_query_reader->get_list('get_cities_by_countries', array('code' => "$countries"));

		log_message('debug', '_search/cities_list:: [2] countries=' . $countries);

		return $result;
	}

	# Get stores by name
	public function stores_by_name($name)
	{
		log_message('debug', '_search/stores_by_name');
		log_message('debug', '_search/stores_by_name:: [1] name=' . $name);

		$result = $this->_query_reader->get_list('get_stores_by_name', array('store_name' => $name));

		log_message('debug', '_search/stores_by_name:: [2] name=' . json_encode($result));

		return $result;
	}

	# Get stores by city
	public function stores_by_city($cities, $countries)
	{
		log_message('debug', '_search/stores_by_city');
		log_message('debug', '_search/stores_by_city:: [1] cities='.$cities.' countries='.$countries);

		if (!empty($cities)) {
			$citiesArray = explode(',', $cities);
			foreach($citiesArray as $key => $city) {
				if (!empty($city)) {
					$citiesArray[$key] = "(city LIKE '%" . $city ."%')";
				}
			}
			$cities = '(' . implode(' OR ', $citiesArray) . ')';
		}

		if (!empty($countries)) {
			$countriesArray = explode(',', $countries);
			foreach($countriesArray as $key => $country) {
				if (!empty($country)) {
					$countriesArray[$key] = "(_country_code LIKE '%" . $country ."%')";
				}
			}
			$countries = '(' . implode(' OR ', $countriesArray) . ')';
		}
		
		$filters = array();
		if (!empty($countries)) {
			$filters[] = $countries;
		}
		if (!empty($cities)) {
			$filters[] = $cities;
		}
		
		$where = '(' . implode(' AND ', $filters) . ')';

		$result = $this->_query_reader->get_list('get_stores_by_cities', array('where' => $where));

		log_message('debug', '_search/stores_by_city:: [2] result=' . json_encode($result));

		return $this->_query_reader->get_list('get_stores_by_cities', array('where' => $where));
	}
}

