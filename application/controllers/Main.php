<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class controls obtaining or querying the backend server.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 02/17/2016
 */
class Main extends CI_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
	}
	
	
	
	# the receiver for all the queries
	function index()
	{
		log_message('debug', 'Main/index');
		
		$_POST = !empty($_POST)? $_POST: array();
		
		# testing on the API
		$data = filter_forwarded_data($this);
		if(!empty($data) && !empty($data['ctest'])) $_POST = array_merge($_POST, $data);
		if(!empty($_GET) && !empty($_GET['__check'])) $_POST = array_merge($_POST, $_GET);
		
		
		# return error if there is no post
		if(empty($_POST) || empty($_POST['__action'])) {
			echo json_encode(array('code'=>'1000', 'message'=>'bad request', 'resolve'=>'No instruction data posted.'));
			return 0;
		}
		
		log_message('debug', 'Main/index:: [1] _action='. (!empty($_POST['__action']) ? $_POST['__action']: ''));
		# Test IAM DB connection through the API
		if($_POST['__action'] == 'test_db')
		{
			log_message('debug', 'Main/index/test_db');
			
			$mysqli = new mysqli(HOSTNAME, USERNAME, PASSWORD, DATABASE, DBPORT);
			
			log_message('debug', 'Main/index/test_db:: [1] result='.json_encode(array('IS'=>($mysqli->ping()? 'CONNECTED': 'NO CONNECTION') )));
			echo json_encode(array('IS'=>($mysqli->ping()? 'CONNECTED': 'NO CONNECTION') ));
		}
	
	
		# Run a generic query on the database
		else if($_POST['__action'] == 'run')
		{
			log_message('debug', 'Main/index/run');
				
			$result = $this->_query_reader->run($_POST['query'], $_POST['variables'], (!empty($_POST['strict']) && $_POST['strict'] == 'true')); 
			
			log_message('debug', 'Main/index/run:: [1] result='.json_encode($result));
			# determine what to return
			if(!empty($_POST['return']) && $_POST['return'] == 'plain') echo json_encode($result); 
			else echo json_encode(array('result'=>($result? 'SUCCESS': 'FAIL') ));
		}
	
	
		# Run a generic query on the database
		else if($_POST['__action'] == 'add_data')
		{
			log_message('debug', 'Main/index/add_data');
			
			$id = $this->_query_reader->add_data($_POST['query'], $_POST['variables']); 
			
			log_message('debug', 'Main/index/add_data:: [1] id='.json_encode($id));
			# determine what to return
			if(!empty($_POST['return']) && $_POST['return'] == 'plain') echo json_encode($id); 
			else echo json_encode(array('id'=>$id));
		}
	
	
		# Run a generic query on the database
		else if($_POST['__action'] == 'get_list')
		{
			log_message('debug', 'Main/index/get_list');
			
			$list = $this->_query_reader->get_list($_POST['query'], $_POST['variables']); 
			
			log_message('debug', 'Main/index/add_data:: [1] list='.json_encode($list));
			echo json_encode($list);
		}
	
	
		# Run a generic query on the database
		else if($_POST['__action'] == 'get_row_as_array')
		{
			log_message('debug', 'Main/index/get_row_as_array');
			
			$row = $this->_query_reader->get_row_as_array($_POST['query'], $_POST['variables']); 
			
			log_message('debug', 'Main/index/get_row_as_array:: [1] row='.json_encode($row));
			echo json_encode($row);
		}
	
	
		# Run a generic query on the database
		else if($_POST['__action'] == 'get_single_column_as_array')
		{
			log_message('debug', 'Main/index/get_single_column_as_array');
			
			$list = $this->_query_reader->get_single_column_as_array($_POST['query'], $_POST['column'], $_POST['variables']); 
			
			log_message('debug', 'Main/index/get_single_column_as_array:: [1] list='.json_encode($list));
			echo json_encode($list);
		}
		
	
		# cache the mysql queries for this server
		else if($_POST['__action'] == 'load_queries_into_cache')
		{
			log_message('debug', 'Main/index/load_queries_into_cache');
				
			if(ENABLE_QUERY_CACHE) $result = $this->_query_reader->load_queries_into_cache();
			
			log_message('debug', 'Main/index/load_queries_into_cache:: [1] result='.json_encode($result));
			echo json_encode(array('result'=>'DONE'));
		}
	
	
		
		
	
		# run a test function
		else if($_POST['__action'] == 'test_this')
		{
			$this->test_function();
		}
		
		
	}
	
	
	
	# this is a test function
	# put something here to test straight in the browser
	function test_function()
	{
		$result = $this->_query_reader->get_mongo_query_by_code('mongodb__get_stores_in_search_categories');
		print_r($result);
	}
	
	
	
}

/* End of controller file */