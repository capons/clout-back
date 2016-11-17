<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining content information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/24/2015
 */
class Content extends REST_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_content');
	}
	
	
	# Tags GET
	public function tags_get()
  	{
  		log_message('debug', 'Content/tags_get');
  		log_message('debug', 'Content/tags_get:: [1] list_type=' . (!empty($this->get('list_type'))? $this->get('list_type'): ''));
  		
		$result = $this->_content->tags(
			$this->get('list_type')
		);
		
		log_message('debug', 'Content/tags_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# Actions GET
	public function actions_get()
  	{
  		log_message('debug', 'Content/actions_get');
  		log_message('debug', 'Content/actions_get:: [1] list_type=' . (!empty($this->get('list_type'))? $this->get('list_type'): ''));
		$result = $this->_content->actions(
			$this->get('list_type')
		);
		
		log_message('debug', 'Content/actions_get:: [2] result='.json_encode($result));
		$this->response($result);
	}
	
	
	# Query GET
	public function query_get()
  	{
  		log_message('debug', 'Content/query_get');
  		
		$result = $this->_query_reader->get_mongo_query_by_code(
			$this->get('code'),
			(!empty($this->get('values'))? $this->get('values'): array())
		);
		
		log_message('debug', 'Content/query_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
	
	
	
	# test file upload
	public function test_upload_get()
	{
		log_message('debug', 'Content/test_upload_get');
		
		$newFile = delete_from_s3('card_icon.png')? 'SUCCESS': 'FAIL';
		
		log_message('debug', 'Content/test_upload_get:: [1] newFile='.json_encode($newFile));
		$this->response($newFile);
	}
	
	
		

	# GET the image from the API by a third-party app
	public function image_get()
	{
		#echo 'http://'.S3_BUCKET_NAME.'.s3.amazonaws.com/'.$this->get('name');
		#$result = !empty($this->get('name'))? 'http://'.S3_BUCKET_NAME.'.s3.amazonaws.com/'.$this->get('name'): '';
		log_message('debug', 'Content/image_get');
		
		$result = download_from_url('https://www.clout.com/assets/uploads/banklogo_38693.png', TRUE);
		
		log_message('debug', 'Content/test_upload_get:: [1] result='.json_encode($result));
		$this->response($result);
	}
}


/* End of controller file */