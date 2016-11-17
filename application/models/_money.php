<?php
/**
 * This class generates and formats money details.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/29/2015
 */
class _money extends CI_Model
{

	# Get list of banks
	function banks($restrictions)
	{
		log_message('debug', '_money/banks');
		log_message('debug', '_money/banks:: [1] restrictions='.json_encode($restrictions));

		$codeCondition = " AND institution_code <> '' ";
		$codeCondition .= !empty($restrictions['phrase'])? " AND MATCH(institution_name) AGAINST ('_PHRASE_') ": '';
		$codeCondition .= !empty($restrictions['excludeBanks'])? " AND id NOT IN ('".implode("','", $restrictions['excludeBanks'])."') ": "";

		$featuredStatus = ($restrictions['type'] == 'featured'? "'Y'":"'Y','N'");

		$result = server_curl(CRON_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_bank_list', 'variables'=>array('featured_status'=>$featuredStatus, 'phrase'=>(!empty($restrictions['phrase'])? htmlentities($restrictions['phrase'], ENT_QUOTES):''), 'limit_text'=>" LIMIT ".$restrictions['offset'].", ".$restrictions['limit']." ", 'code_condition'=>$codeCondition )));

		log_message('debug', '_money/banks:: [2] result='.json_encode($result));
		return $result;
	}



	# Get the bank details
	function bank_details($bankId, $fields)
	{
		log_message('debug', '_money/bank_details');
		log_message('debug', '_money/bank_details:: [1] bankId='.$bankId.' fields='.json_encode($fields));

		$result = server_curl(CRON_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_bank_details', 'variables'=>array('bank_id'=>$bankId, 'field_list'=>$fields )));

		log_message('debug', '_money/bank_details:: [2] result'.json_encode($result));
		return $result;
	}



	# Get the user banks
	function user_banks($userId)
	{
		log_message('debug', '_money/user_banks');
		log_message('debug', '_money/user_banks:: [1] userId='.$userId);

		$result = server_curl(CRON_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_user_banks', 'variables'=>array('user_id'=>$userId) ));

		log_message('debug', '_money/user_banks:: [2] result'.json_encode($result));
		return $result;
	}



	# connect to the bank
	function connect_to_bank($credentials, $postData, $userId)
	{
		log_message('debug', '_money/connect_to_bank');
		log_message('debug', '_money/connect_to_bank:: [1] userId='.$userId);

		$result = server_curl(CRON_SERVER_URL,  array('__action'=>'connect_to_bank', 'credentials'=>$credentials, 'post_data'=>$postData, 'user_id'=>$userId ));

		log_message('debug', '_money/connect_to_bank:: [2] result'.json_encode($result));
		return $result;
	}



}


