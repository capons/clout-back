<?php
/**
 * This file contains functions that are used to handle a bad API request.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 02/25/2016
 */



# if this is a new request, record and notify back-end developers to add new end-point
function send_new_api_end_point_request($uri, $data, $checkFile)
{
	log_message('debug', 'handle_404_helper/send_new_api_end_point_request');
	
	if(!file_exists($checkFile)){
		$message['details'] = "Hi,
		<br><br>A new API end-point has been requested with the following details: 
		<br>END-POINT: ".$uri."
		<br>SAMPLE RESULT: ".json_encode($data)."
		<br><br>Regards,
		<br>Your Clout API Server";
		
		$message['emailfrom'] = NOREPLY_EMAIL;
		$message['fromname'] = "Clout No-Reply";
		$message['subject'] = "New API End Point Request: ".$uri;
		$message['emailaddress'] = BACK_END_DEVELOPER_EMAILS;
	
		log_message('debug', 'handle_404_helper/send_new_api_end_point_request:: [1] message='.json_encode($message));
		
		$isSent = server_curl(INVITATION_SERVER_URL, array('__action'=>'send_to_unverified_email', 'return'=>'plain', 'message'=>$message));
		log_message('debug', 'handle_404_helper/send_new_api_end_point_request:: [2] isSent='.$isSent);
		# create the file if a request has been sent
		if($isSent) shell_exec("touch ".$checkFile); 
	}
}



?>