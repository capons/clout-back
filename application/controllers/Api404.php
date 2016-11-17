<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class controls 404 result - where the class does not exist
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/24/2015
 */
class Api404 extends CI_Controller 
{
	
	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
    }
	
	
	# handle not having a class for the end-point requested
	public function index() 
    { 
      	# this is a new API request
		if(ENVIRONMENT == 'development' && (!empty($_GET['__test']) || !empty($_POST['__test']))) {
			$uri = uri_string();
			$data = !empty($_POST['__test'])? $_POST['__test']: $_GET['__test'];
			$checkFile = END_POINT_FOLDER.'new-end-point-request--'.str_replace('/','-',$uri).'.req';
			
			if(!file_exists($checkFile)){
				$this->load->helper('handle_404');
				send_new_api_end_point_request($uri, $data, $checkFile);
			}
			
			echo json_encode($data);
		} 
		
		# this is a normal API command gone wrong!
		else {
			echo json_encode(array('responseCode'=>'404', 'message'=>'Not Found. Unknown Method.', 'moreInfo'=>'https://developers.clout.com/errors/404001', 'messageCode'=>'404001'));
		}
		
		return 0;
    } 
}


/* End of controller file */