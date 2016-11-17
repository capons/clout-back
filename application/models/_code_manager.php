<?php
/**
 * This class generates and formats account details. 
 *
 * @author Rebecca Lin <rebeccal@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 05/03/2015
 */
class _code_manager extends CI_Model
{
	public function activity($userId, $details=array())
	{
		log_message('debug', '_code_manager/activity');
		log_message('debug', '_code_manager/activity:: [1] userId='.$userId.' details='.json_encode($details));
		
		# Log the copy between repos attempt event
		$this->_logger->add_event(array(
			'user_id'=>(!empty($userId)? $userId: ''), 
			'activity_code'=>(!empty($details['from_repo'])? 'copy-between-repos': 'add-tag-to-repo'), 
			'result'=>(!empty($userId)? 'SUCCESS':'FAIL'), 
			'log_details'=>"from_repo=".(!empty($details['from_repo'])? $details['from_repo']: 'unknown')
							."|to_repo=".(!empty($details['to_repo'])? $details['to_repo']: 'unknown')
							."|tag_name=".(!empty($details['tag_name'])? $details['tag_name']: 'unknown')
							."|run_time=".(!empty($details['run_time'])? $details['run_time']: 'unknown')
							."|browser=".(!empty($details['browser'])? $details['browser']: 'unknown'),
			'uri'=>(!empty($details['uri'])? $details['uri']: ''),
			'ip_address'=>(!empty($details['ip_address'])? $details['ip_address']: '')
		));

	}
}
	