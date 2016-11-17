<?php
/**
 * Third party data access
 *
 * @author Khim Ung <khimu@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 04/07/2016
 */
class _profile extends CI_Model
{
	
	# user information made available to third party
	function third_party_user_access($clientId, $password, $userId, $scope, $redirectUrl) {
		return $this->_query_reader->get_row_as_array
		(
				'get_user_details',
				[
						'clientId' => $clientId,
						'password' => $password,
						'userId' => $userId,
						'scope' => $scope,
						'redirectUrl' => $redirectUrl,
				]
		);
	}	
	

}