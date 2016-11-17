<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining store information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/09/2015
 */
class Store extends REST_Controller 
{
	
	# constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_store');
	}
	
	
	# default GET
	public function index_get()
  	{
  		log_message('debug', 'Store/index_get');
  		
		$result = array();
		
		# Wants fields of a store
		if(!empty($this->get('apiId')) && !empty($this->get('fields')) && empty($this->get('offerId')) ){
			$result = $this->_store->details(
				extract_id($this->get('apiId')),
				(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				array(
					'longitude'=>(!empty($this->get('longitude'))? $this->get('longitude'): LOOKUP_START_LONGITUDE),
					'latitude'=>(!empty($this->get('latitude'))? $this->get('latitude'): LOOKUP_START_LATITUDE)
				), 
				explode(',', $this->get('fields'))
			);
			log_message('debug', 'Store/index_get:: [1] result='.json_encode($result));
		
		# Wants offers of a store
		} else if(!empty($this->get('offers'))){
			$result = $this->_store->offers(
				extract_id($this->get('apiId')), 
				$this->get('offers'), 
				(!empty($this->get('userId'))? extract_id($this->get('userId')): ''), 
				((!empty($this->get('level')) || $this->get('level') == '0')? $this->get('level'): '')
			);
			log_message('debug', 'Store/index_get:: [2] result='.json_encode($result));
				
		# Wants details of an offer ID
		} else if(!empty($this->get('offerId')) && !empty($this->get('fields'))){
			$result = $this->_store->offer_details(
				$this->get('offerId'), 
				explode(',', $this->get('fields')), 
				(!empty($this->get('userId'))? extract_id($this->get('userId')): '')
			);
			log_message('debug', 'Store/index_get:: [3] result='.json_encode($result));
			
				
		# Wants hours of operation
		} else if(!empty($this->get('hours'))){
			$result = $this->_store->hours(
				extract_id($this->get('apiId')),
				(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				$this->get('hours')
			);
			log_message('debug', 'Store/index_get:: [4] result='.json_encode($result));
		
				
		# Wants features of the store
		} else if(!empty($this->get('features'))){
			$result = $this->_store->features(
				extract_id($this->get('apiId')),
				(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				$this->get('features')
			);
			log_message('debug', 'Store/index_get:: [5] result='.json_encode($result));
		
		
				
		# Wants statistics on the store
		} else if(!empty($this->get('statistics'))){
			$result = $this->_store->statistics(
				extract_id($this->get('apiId')),
				(!empty($this->get('userId'))? extract_id($this->get('userId')): ''),
				explode(',', $this->get('statistics')), 
				$this->get('type')
			);
			log_message('debug', 'Store/index_get:: [6] result='.json_encode($result));
		}
		
		$this->response($result);
	}

	
	
	
	# Checkin user POST
	public function checkin_post()
  	{
  		log_message('debug', 'Store/checkin_post');
  		
		if(!empty($this->post('userId')) && !empty($this->post('location'))){
			$checkinResult = $this->_store->checkin(
				extract_id($this->post('userId')), 
				$this->post('offerId'), 
				$this->post('location'),
				(!empty($this->post('storeId'))? extract_id($this->post('storeId')):'')
			);
			log_message('debug', 'Store/checkin_post:: [1] checkinResult='.json_encode($checkinResult));
		}
		$result['checkinSuccess'] = !empty($checkinResult) && $checkinResult? 'Y': 'N';
		
		log_message('debug', 'Store/checkin_post:: [2] checkinSuccess='.json_encode($result['checkinSuccess']));
		$this->response($result);
	}
	
	
	
	
	# Make reservation POST
	public function reservation_post()
  	{
  		log_message('debug', 'Store/reservation_post');
  		
		if(!empty($this->post('offerId')) && !empty($this->post('userId')) && !empty($this->post('reservation'))){
			$reservationResult = $this->_store->reservation(
				extract_id($this->post('userId')), 
				$this->post('offerId'), 
				$this->post('reservation')
			);
			log_message('debug', 'Store/reservation_post:: [1] reservationResult='.json_encode($reservationResult));
		}
		$result['reservationSuccess'] = !empty($reservationResult) && $reservationResult? 'Y': 'N';
		
		log_message('debug', 'Store/reservation_post:: [2] reservationSuccess='.json_encode($result['reservationSuccess']));
		$this->response($result);
	}
	
	
	
	# Get a list of the store categories
	public function categories_get()
  	{
  		log_message('debug', 'Store/categories_get');

		$result = $this->_store->categories(
			$this->get('level'), 
			(!empty($this->get('parentId'))? $this->get('parentId'): ''), 
			(!empty($this->get('storeId'))? extract_id($this->get('storeId')): ''),
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Store/categories_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# Search through stores by given fields - focus is on obtaining the name not the full store record
	# This is an internal function - optimized especially for drop downs
	public function search_get()
  	{
  		log_message('debug', 'Store/search_get');
  		
		$result = $this->_store->search(
			$this->get('fields'), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			(!empty($this->get('phrase'))? $this->get('phrase'): '')
		);
		
		log_message('debug', 'Store/search_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Suggest a new store
	public function suggest_post()
	{
		log_message('debug', 'Store/suggest_post');
		
		$details = array('name'=>$this->post('name'), 'address'=>$this->post('address'), 'category'=>$this->post('level1Category'), 'zipcode'=>$this->post('zipcode'), 'website'=>(!empty($this->post('website'))? $this->post('website'): ''), 'reference_links'=>(!empty($this->post('referenceLinks'))? $this->post('referenceLinks'): ''), 'chain_id'=>(!empty($this->post('chainId'))? $this->post('chainId'): ''), 'chain_type'=>(!empty($this->post('chainType'))? $this->post('chainType'): ''));
		log_message('debug', 'Store/suggest_post:: [1] details='.json_encode($details));
		
		$result = $this->_store->suggest(
			(!empty($this->post('descriptorId'))? $this->post('descriptorId'): ''),
			extract_id($this->post('userId')), 
			$details
		);
		
		log_message('debug', 'Store/suggest_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# Get a list of stores based on a given search phrase
	public function list_get()
	{
		log_message('debug', 'Store/list_get');
		
		$result = $this->_store->list_stores(
			$this->get('phrase'), 
			$this->get('address'), 
			$this->get('categories'), 
			$this->get('zipcode'), 
			$this->get('website'), 
			(!empty($this->get('otherFilters'))? $this->get('otherFilters'): array()),
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			extract_id($this->get('userId'))
		);
		
		log_message('debug', 'Store/list_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# Get a list of stores based on a google search phrase
	public function google_get()
	{
		log_message('debug', 'Store/google_get');
		
		$result = $this->_store->google_stores(
			$this->get('phrase'), 
			$this->get('address'), 
			$this->get('zipcode'), 
			$this->get('website'), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE)
		);
		
		log_message('debug', 'Store/google_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# Get store links
	public function links_get()
	{
		log_message('debug', 'Store/links_get');
		log_message('debug', 'Store/links_get:: [1] storeSuggestionId='.$this->get('storeSuggestionId'));
		
		$result = $this->_store->links(
			$this->get('storeSuggestionId')
		);
		
		log_message('debug', 'Store/links_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# Get store chains
	public function chains_get()
	{
		log_message('debug', 'Store/chains_get');
		log_message('debug', 'Store/chains_get:: [1] storeId='.(!empty($this->get('storeId'))? $this->get('storeId'): ''));
		
		$result = $this->_store->chains(
			(!empty($this->get('phrase'))? $this->get('phrase'): ''), 
			(!empty($this->get('storeId'))? $this->get('storeId'): ''), 
			(!empty($this->get('storeType'))? $this->get('storeType'): 'store'), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE)
		);
		
		log_message('debug', 'Store/links_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Add a chain
	public function add_chain_post()
	{
		log_message('debug', 'Store/add_chain_post');
		log_message('debug', 'Store/add_chain_post:: [1] storeId='.(!empty($this->post('storeId'))? $this->post('storeId'): ''));
		
		$result = $this->_store->add_chain(
			$this->post('chainName'), 
			(!empty($this->post('chainId'))? $this->post('chainId'): ''), 
			(!empty($this->post('storeId'))? $this->post('storeId'): ''),
			(!empty($this->post('storeType'))? $this->post('storeType'): 'store'),
			(!empty($this->post('descriptorId'))? $this->post('descriptorId'): ''),
			extract_id($this->post('userId'))
		);
		
		log_message('debug', 'Store/add_chain_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# GET a chain's details for editing
	public function edit_chain_details_get()
	{
		log_message('debug', 'Store/edit_chain_details_get');
		log_message('debug', 'Store/edit_chain_details_get:: [1] chainId='.$this->get('chainId'));
		
		$result = $this->_store->edit_chain_details(
			$this->get('chainId'), 
			$this->get('chainType')
		);
		
		log_message('debug', 'Store/edit_chain_details_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# POST chain's details for editing
	public function edit_chain_details_post()
	{
		log_message('debug', 'Store/edit_chain_details_post');
		log_message('debug', 'Store/edit_chain_details_post:: [1] chainId='.$this->post('chainId'));
		
		$result = $this->_store->edit_chain(
			$this->post('chainId'), 
			$this->post('fields'), 
			$this->post('fieldValues'),
			extract_id($this->post('userId'))
		);
		
		log_message('debug', 'Store/edit_chain_details_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# GET a store's details for editing
	public function edit_store_details_get()
	{
		log_message('debug', 'Store/edit_store_details_get');
		log_message('debug', 'Store/edit_store_details_get:: [1] storeId='.$this->get('storeId'));
		
		$result = $this->_store->edit_store_details(
			$this->get('storeId')
		);
		
		log_message('debug', 'Store/edit_store_details_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Edit store details
	public function edit_store_details_post()
	{
		log_message('debug', 'Store/edit_store_details_post');
		log_message('debug', 'Store/edit_store_details_post:: [1] storeId='.$this->post('storeId'));
		
		$result = $this->_store->edit_store(
			$this->post('storeId'), 
			$this->post('fields'), 
			$this->post('fieldValues'),
			extract_id($this->post('userId'))
		);
		
		log_message('debug', 'Store/edit_store_details_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# POST a favorite store
	public function favorite_post()
	{
		log_message('debug', 'Store/favorite_post');
		log_message('debug', 'Store/favorite_post:: [1] storeId='.extract_id($this->post('storeId')));
		
		$result = $this->_store->favorite(
			extract_id($this->post('storeId')),
			extract_id($this->post('userId')),
			$this->post('action')
		);
		
		log_message('debug', 'Store/favorite_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# Get reviews
	public function reviews_get()
	{
		log_message('debug', 'Store/reviews_get');
		log_message('debug', 'Store/reviews_get:: [1] storeId='.extract_id($this->get('storeId')));
		
		$result = $this->_store->reviews(
			extract_id($this->get('storeId')),
			extract_id($this->get('userId')), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE)
		);
		
		log_message('debug', 'Store/reviews_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	# GET the user's store review
	public function review_get()
	{
		log_message('debug', 'Store/review_get');
		log_message('debug', 'Store/review_get:: [1] storeId='.extract_id($this->get('storeId')));
		
		$result = $this->_store->get_review(
			extract_id($this->get('storeId')),
			extract_id($this->get('userId'))
		);
		
		log_message('debug', 'Store/review_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# POST a store review
	public function review_post()
	{
		log_message('debug', 'Store/review_post');
		log_message('debug', 'Store/review_post:: [1] storeId='.extract_id($this->post('storeId')));
		
		$result = $this->_store->add_review(
			extract_id($this->post('storeId')),
			extract_id($this->post('userId')),
			$this->post('score'),
			$this->post('comment')
		);
		
		log_message('debug', 'Store/review_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	
	
	
	# POST a store photo
	public function photo_post()
	{
		log_message('debug', 'Store/photo_post');
		log_message('debug', 'Store/photo_post:: [1] storeId='.extract_id($this->post('storeId')));
		
		$result = $this->_store->add_photo(
			extract_id($this->post('storeId')),
			extract_id($this->post('userId')),
			$this->post('photo'),
			$this->post('comment')
		);
		
		log_message('debug', 'Store/photo_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	# Get store photos
	public function photos_get()
	{
		
		log_message('debug', 'Store/photos_get');
		log_message('debug', 'Store/photos_get:: [1] storeId='.extract_id($this->get('storeId')));
		
		$result = $this->_store->photos(
			extract_id($this->get('storeId')),
			extract_id($this->get('userId')), 
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): NUM_OF_ROWS_PER_PAGE),
			$this->get('baseUrl')
		);
		
		log_message('debug', 'Store/photos_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	
	
	# POST offer requests
	public function request_offers_post()
	{
		log_message('debug', 'Store/request_offers_post');
		log_message('debug', 'Store/request_offers_post:: [1] storeId='.extract_id($this->post('storeId')));
		
		$result = $this->_store->request_offers(
			extract_id($this->post('storeId')),
			extract_id($this->post('userId')),
			$this->post('type'),
			$this->post('requests')
		);
		
		log_message('debug', 'Store/request_offers_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	
	
	# POST a store staff
	public function store_staff_post()
	{
		log_message('debug', 'Store/store_staff_post');
		log_message('debug', 'Store/store_staff_post:: [1] storeId='.$this->post('storeId'));
		
		$result = $this->_store->staff(
			$this->post('storeId')
		);
		
		log_message('debug', 'Store/request_offers_post:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
	
	
	# get store id
	public function public_mapping_get() 
	{
		log_message('debug', 'Store/public_mapping_get');
		
		$result = $this->_store->public_mapping(
				urldecode($this->get('chainName')),
				urldecode($this->get('address'))
				);
		
		log_message('debug', 'Store/public_mapping_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	
	
}


/* End of controller file */