<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls searching through website data.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 09/13/2015
 */
class Search extends REST_Controller
{

	#Constructor to set some default values at class load
	public function __construct()
	{
		parent::__construct();
		$this->load->model('_search');
	}


	# Get a list of store categories for search
	public function categories_get()
	{
		log_message('debug', 'Search/categories_get');
		log_message('debug', 'Search/categories_get:: [1] userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : ''));

		$result = $this->_search->categories(
			(!empty($this->get('userId')) ? extract_id($this->get('userId')) : '')
		);

		log_message('debug', 'Search/categories_get:: [2] result=' . json_encode($result));
		$this->response($result);
	}

	# Get all categories
	public function categories_all_get()
	{
		log_message('debug', 'Search/categories_all_get');
		log_message('debug', 'Search/categories_all_get:: userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : ''));

		$result = $this->_search->categories_all(
			(!empty($this->get('userId')) ? extract_id($this->get('userId')) : '')
		);

		log_message('debug', 'Search/categories_all_get:: [2] result=' . json_encode($result));
		$this->response($result);
	}

	# Get competitors list
	public function competitors_get()
	{
		log_message('debug', 'Search/competitors');
		log_message('debug', 'Search/competitors:: userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : ' userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : '')));

		$result = $this->_search->competitors_list(
			$this->get('storeId')
		);
		$this->response($result);
	}


	# Get a list of store suggestions for search
	public function store_suggestions_get()
	{
		log_message('debug', 'Search/store_suggestions_get');
		log_message('debug', 'Search/store_suggestions_get:: [1] userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : ''));

		$result = $this->_search->store_suggestions(extract_id($this->get('userId')),
			array('offset' => (!empty($this->get('offset')) ? $this->get('offset') : '0'),
				'limit' => (!empty($this->get('limit')) ? $this->get('limit') : NUM_OF_ROWS_PER_PAGE),
				'type' => (!empty($this->get('type')) ? $this->get('type') : 'name'),
				'phrase' => (!empty($this->get('phrase')) ? $this->get('phrase') : ''),
				'location' => (!empty($this->get('location')) ? $this->get('location') : ''),
				'latitude' => (!empty($this->get('latitude')) ? $this->get('latitude') : LOOKUP_START_LATITUDE),
				'longitude' => (!empty($this->get('longitude')) ? $this->get('longitude') : LOOKUP_START_LONGITUDE),
				'order' => (!empty($this->get('order')) ? $this->get('order') : ''),
				'filters' => (!empty($this->get('filters')) ? $this->get('filters') : array()),
				'exclude' => (!empty($this->get('exclude')) ? $this->get('exclude') : '')
			)
		);


		log_message('debug', 'Search/store_suggestions_get:: [2] result=' . json_encode($result));
        $this->response($result);
	}


	# Get a list of store search phrase suggestions
	public function store_phrase_suggestions_get()
	{
		log_message('debug', 'Search/store_phrase_suggestions_get');
		log_message('debug', 'Search/store_phrase_suggestions_get:: [1] userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : ''));

		$result = $this->_search->store_phrase_suggestions(extract_id($this->get('userId')),
			array('offset' => (!empty($this->get('offset')) ? $this->get('offset') : '0'),
				'limit' => (!empty($this->get('limit')) ? $this->get('limit') : NUM_OF_ROWS_PER_PAGE),
				'phrase' => (!empty($this->get('phrase')) ? $this->get('phrase') : '')
			)
		);

		log_message('debug', 'Search/store_phrase_suggestions_get:: [2] result=' . json_encode($result));
		$this->response($result);
	}


	# Get a list of store locations for search
	public function store_locations_get()
	{
		log_message('debug', 'Search/store_locations_get');
		log_message('debug', 'Search/store_locations_get:: [1] userId=' . (!empty($this->get('userId')) ? extract_id($this->get('userId')) : ''));

		$result = $this->_search->store_locations(extract_id($this->get('userId')),
			array('offset' => (!empty($this->get('offset')) ? $this->get('offset') : '0'),
				'limit' => (!empty($this->get('limit')) ? $this->get('limit') : NUM_OF_ROWS_PER_PAGE),
				'phrase' => (!empty($this->get('phrase')) ? $this->get('phrase') : '')
			)
		);

		log_message('debug', 'Search/store_locations_get:: [2] result=' . json_encode($result));
		$this->response($result);
	}


	# Get a list of states for search
	public function states_get()
	{
		log_message('debug', 'Search/states_get');
		log_message('debug', 'Search/states_get:: [1] phrase=' . (!empty($this->get('phrase')) ? $this->get('phrase') : ''));

		$result = $this->_search->states(
			(!empty($this->get('offset')) ? $this->get('offset') : '0'),
			(!empty($this->get('limit')) ? $this->get('limit') : NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase')) ? $this->get('phrase') : '')
		);

		log_message('debug', 'Search/states_get:: [2] result=' . json_encode($result));
		$this->response($result);
	}


	# Get a list of countries for search
	public function countries_get()
	{
		log_message('debug', 'Search/countries_get');

		$result = $this->_search->countries();

		log_message('debug', 'Search/countries_get:: [2] result=' . json_encode($result));
		$this->response($result);
	}

	# Get states by countries
	public function states_list_get()
	{
		log_message('debug', 'Search/states_list_get');
		if (!empty($this->get('countries'))) {
			$result = $this->_search->states_list($this->get('countries'));
			
			log_message('debug', 'Search/states_list_get:: [1] result=' . json_encode($result));
			
			$this->response($result);
		}
		$this->response(array());     
	}

	# Get cities list by countries
	public function cities_list_get()
	{
		log_message('debug', 'Search/cities_list_get');
		if (!empty($this->get('countries'))) {
			$result = $this->_search->cities_list($this->get('countries'));

			log_message('debug', 'Search/cities_list_get:: [1] result='.json_encode($result));
			
			$this->response($result);
		}
		$this->response(array());
	}

	# Get stores by name
	public function stores_by_name_get()
	{
		log_message('debug', 'Search/stores_by_name_get');

		if (!empty($this->get('name'))) {
			$result = $this->_search->stores_by_name($this->get('name'));

			log_message('debug', 'Search/stores_by_name_get:: [1] result=' . json_encode($result));
			
			$this->response($result);
		}
		$this->response(array());
	}
	
	# Get stores by city
	public function stores_by_city_get()
	{
		log_message('debug', 'Search/stores_by_city_get');
		
		$result = $this->_search->stores_by_city($this->get('city'), $this->get('country'));
		
		log_message('debug', 'Search/stores_by_city_get:: [1] result=' . json_encode($result));
		
		$this->response($result);
	}
}


/* End of controller file */