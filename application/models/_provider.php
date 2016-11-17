<?php
/**
 * This class handles functions related to communication providers in the system.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/24/2015
 */
class _provider extends CI_Model
{
	
	# get list of providers
	function get_list($phrase, $offset, $limit)
	{
		log_message('debug', '_provider/get_list');
		log_message('debug', '_provider/get_list:: [1] phrase='.$phrase.' offset='.$offset.' limit='.$limit);

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_provider_list', 'variables'=>array('search_phrase'=>(!empty($phrase)? "%".htmlentities($phrase, ENT_QUOTES)."%": "%"),
			'limit_text'=>' LIMIT '.$offset.','.$limit.';'
			) ));

		log_message('debug', '_provider/get_list:: [2] result='.json_encode($result));

        return $result;
		
	}




	# Get the email domain of a provider for use in sending an email-to-sms message
	function get_email_domain($telephone, $userId)
	{
		log_message('debug', '_provider/get_email_domain');
		log_message('debug', '_provider/get_email_domain:: [1] telephone='.$telephone.' userId='.$userId);
		
		$domain = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_provider_email_domain', 'variables'=>array('telephone'=>$telephone, 'user_id'=>$userId) )); 
		
		log_message('debug', '_provider/get_list:: [2] domain='.json_encode($domain));
		return !empty($domain['email_domain'])? $domain['email_domain']: '';
	}

}


