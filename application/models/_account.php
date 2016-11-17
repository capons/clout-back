<?php
/**
 * This class generates and formats account details.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/30/2015
 */
class _account extends CI_Model
{

	# get a user account details
	function user($userId)
	{
		log_message('debug', '_account/user');
		log_message('debug', '_account/user:: [1] userId='.$userId);

		$result = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$userId ));
		log_message('debug', 'Store/request_offers_post:: [2] result='.json_encode($result));

		return $result;
	}



	# Get a list of accounts of a given type
	function types($types, $restrictions=array())
	{
		log_message('debug', '_account/types');
		log_message('debug', '_account/types:: [1] types='.json_encode($types).' restrictions='.json_encode($restrictions));

		$users = server_curl(IAM_SERVER_URL,  array(
			'__action'=>'get_users_in_group_type',
			'group_type'=>implode("','",$types),
			'offset'=>(!empty($restrictions['offset'])? $restrictions['offset']: '0'),
			'limit'=>(!empty($restrictions['limit'])? $restrictions['limit']: '1000')
		));
		log_message('debug', '_account/types:: [2] users='.json_encode($users));

		# Return the appropriate data format
		if(!empty($restrictions['return']) && $restrictions['return'] == 'list') {
			$result = $this->_query_reader->get_list('get_users_in_id_list', array('id_list'=>implode("','", $users) ));
			log_message('debug', '_account/types:: [3] users='.json_encode($result));
			return $result;
		} else {
			log_message('debug', '_account/types:: [4] users='.json_encode($users));
			return $users;
		}
	}





	# Add an application
	public function add($firstName, $lastName, $emailAddress, $emailVerified, $password, $telephone, $provider, $gender, $zipcode, $birthDate, $reffererId, $facebookId, $details=array())
	{
		log_message('debug', '_account/add');
		log_message('debug', '_account/add:: [1] first='.$firstName.' last='.$lastName.' emailAddress='.$emailAddress.
				' emailVerified='.$emailVerified.' password='.$password.' telephone='.$telephone.' provider='.$provider.
				' gender='.$gender.' zipcode='.$zipcode.' birthDate='.$birthDate.' reffererId='.$reffererId.' facebookId='.$facebookId.' details='.json_encode($details));

		# firstly, record more details about a user with the given email address if they exist in the invitation records
		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'query'=>'update_invite_details', 'variables'=>array(
					'first_name'=>htmlentities($firstName, ENT_QUOTES),
					'last_name'=>htmlentities($lastName, ENT_QUOTES),
					'email_address'=>$emailAddress,
					'phone_number'=>$telephone
				) ));


		log_message('debug', '_account/types:: [2] users='.json_encode($result));


		# now save the user
		$telephone = only_numbers($telephone);
		$reason = '';

		$userId = $this->_query_reader->add_data('add_new_user', array(
			'first_name'=>htmlentities($firstName, ENT_QUOTES),
			'last_name'=>htmlentities($lastName, ENT_QUOTES),
			'email_address'=>$emailAddress,
			'telephone'=>$telephone,
			'gender'=>strtolower($gender),
			'zipcode'=>$zipcode,
			'birthday'=>date('Y-m-d', strtotime($birthDate)),
			'email_verified'=>$emailVerified
		));

		log_message('debug', '_account/add:: [3] userId='.$userId);

		# Add user's contacts and security
		if(!empty($userId)) {

			$result = $this->_query_reader->run('update_user_field', array('user_id'=>$userId, 'field_name'=>'_entered_by', 'field_value'=>$userId));
			log_message('debug', '_account/add:: [4] result=' . json_encode($result));

			# Record user telephone
			if($result) $result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_user_contact', 'variables'=>array('user_id'=>$userId, 'provider_id'=>$provider, 'telephone'=>$telephone) ));
			log_message('debug', '_account/add:: [5] result=' . json_encode($result));

			# Record user security settings
			if($result) $result = $this->_query_reader->run('add_user_security_settings', array('user_id'=>$userId, 'user_type'=>'random_shopper', 'user_type_level'=>'level 1'));
			log_message('debug', '_account/add:: [6] result=' . json_encode($result));

			# Record who referred this user
			if($result && !empty($reffererId)) $result = $this->_query_reader->run('add_user_referral', array('user_id'=>$userId, 'referred_by'=>$reffererId, 'referrer_type'=>'user', 'referred_by_type'=>'user', 'sent_referral_by'=>'email'));
			log_message('debug', '_account/add:: [7] result=' . json_encode($result));

			if(!empty($reffererId)) log_message('debug', '_account/add:: [8] user referralId=' . $reffererId);

			# Record the inactive user permission group
			if($result) $result = server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_user_permission_group', 'variables'=>array('user_id'=>$userId, 'user_name'=>$emailAddress, 'password'=>sha1($password), 'group_name'=>'Random Shopper' )));
			log_message('debug', '_account/add:: [9] result=' . json_encode($result));

			# Assign a new clout ID to the user
			if($result) $result = $this->_query_reader->run('assign_user_new_clout_id', array('user_id'=>$userId));
			log_message('debug', '_account/add:: [10] result=' . json_encode($result));

		} else {
			$reason = 'A user with this email already exists.';
			log_message('debug', '_account/add:: [10] reason=' .$reason);
		}



		# Send verification message to user's email address
		if(!empty($userId) && $result){
			log_message('debug', '_account/add:: [11] Send verification message to users email address' . $emailAddress);

			# Set user to receive all kinds of messages
			$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_message_settings', 'variables'=>array('user_id'=>$userId, 'message_formats'=>"'email','sms','system'", 'message_types'=>'all') ));
			log_message('debug', '_account/add:: [12] result=' . json_encode($result));

			if($result) $result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'add_notification_settings', 'variables'=>array('user_id'=>$userId, 'email_address'=>$emailAddress, 'first_name'=>htmlentities($firstName, ENT_QUOTES), 'last_name'=>htmlentities($lastName, ENT_QUOTES), 'sender_type'=>'user', 'send_me_adverts'=>'Y', 'updated_by'=>$userId) ));
			log_message('debug', '_account/add:: [13] result=' . json_encode($result));

			if($result && $emailVerified == 'Y'){
				log_message('debug', '_account/add:: [14] Email is verified');

				# Activate user
				$result = $this->_query_reader->run('update_user_field', array('user_id'=>$userId, 'field_name'=>'user_status', 'field_value'=>'active'));
				log_message('debug', '_account/add:: [15] result=' . json_encode($result));

				# Add their facebook profile record
				if($result) {
					log_message('debug', '_account/add:: [16] Add social media facebookId='.$facebookId.' userId='.$userId);
					$result = $this->_query_reader->run('add_user_social_media', array('user_id'=>$userId, 'social_media_name'=>'facebook', 'user_name'=>strtolower($emailAddress), 'social_media_id'=>$facebookId, 'status'=>'verified', 'last_ip_address'=>$details['ip_address'] ));
					log_message('debug', '_account/add:: [17] result='.json_encode($result));

					# link their facebook record to their user ID
					$result = $this->_query_reader->run('update_user_facebook_field', array('user_id'=>$userId, 'facebook_id'=>$facebookId));
					log_message('debug', '_account/add:: [18] result='.json_encode($result));

				}
			}
			else if($result && $emailVerified == 'N'){
				log_message('debug', '_account/add:: [19] Email not verified='.$emailAddress);
				$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send', 'return'=>'plain',
					'receiverId'=>$userId,
					'message'=>array(
						'code'=>'account_verification_link',
						'emailaddress'=>$emailAddress,
						'verificationlink'=>$details['base_link'].'u/'.format_id($userId)
						),
					'requiredFormats'=>array('system', 'email'),
					'strictFormatting'=>TRUE
					));
				log_message('debug', '_account/add:: [20] result='.json_encode($result));

				# link new user to invitation (done here because rolling back the change not a mere delete)
				if($result && !empty($reffererId)) {

					$linkResult = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain',
						'query'=>'link_new_user_to_invitation',
						'variables'=>array(
							'new_user_id'=>$userId,
							'email_address'=>$emailAddress,
							'referrer_id'=>$reffererId,
							'referral_status'=>'accepted',
							'message_status'=>'read',
							'read_ip_address'=>$details['ip_address']
							)
						));

					log_message('debug', '_account/add:: [21] linkResult='.json_encode($linkResult ));

					# finally apply the default user group for the user
					apply_rule($this,'new_random_user', array('user_id'=>$userId));
					
					# also add a job to update the user network statistics to the queue
					if($linkResult){
						$scheduleResult = server_curl(CRON_SERVER_URL,  array('__action'=>'add_job_to_queue', 
							'return'=>'plain',
							'jobId'=>'j'.$userId.'-'.strtotime('now').'-'.rand(0,1000000),
							'jobUrl'=>'scoring_cron__update_user_network_cache__user__'.$userId.'__referrer__'.$reffererId,
							'userId'=>$userId,
							'jobCode'=>'update_user_network_cache'
						));
						log_message('debug', '_account/add:: [22] add_job_to_queue result='.$scheduleResult);
					}
					
				} else if(!$result) {
					$reason = 'Your verification link could not be sent';
					log_message('debug', '_account/add:: [22] reason=' .$reason);
				}
			}
		}

		# roll-back the added user details if the user addition failed for whatever reason
		if((empty($result) || !$result) && !empty($userId)){
			log_message('debug', '_account/add:: [23] roll-back the added user details if the user addition failed for whatever reason. userId='.$userId);

			server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'remove_user_contact', 'variables'=>array('user_id'=>$userId)));
			$this->_query_reader->run('remove_user_security_settings', array('user_id'=>$userId));
			$this->_query_reader->run('remove_user_referral', array('user_id'=>$userId));
			server_curl(IAM_SERVER_URL,  array('__action'=>'run', 'query'=>'remove_user_permission_group', 'variables'=>array('user_id'=>$userId)));
			server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'remove_user_message_settings', 'variables'=>array('user_id'=>$userId)));
			$this->_query_reader->run('remove_user_social_media', array('user_id'=>$userId));
			server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'remove_user_sending_record', 'variables'=>array('user_id'=>$userId)));
			server_curl(MESSAGE_SERVER_URL,  array('__action'=>'run', 'return'=>'plain', 'query'=>'remove_user_sending_status', 'variables'=>array('user_id'=>$userId)));
			$this->_query_reader->run('remove_user_by_id', array('user_id'=>$userId));
		}

		# log signup event
		$this->_logger->add_event(array(
			'user_id'=>(!empty($result) && $result? $userId: 'email_address='.$emailAddress),
			'activity_code'=>'signup',
			'result'=>(!empty($result) && $result? 'SUCCESS':'FAIL'),
			'log_details'=>"email_address=".$emailAddress."|device=".(!empty($details['device'])? $details['device']: 'unknown')."|browser=".(!empty($details['browser'])? $details['browser']: 'unknown'),
			'uri'=>(!empty($details['uri'])? $details['uri']: ''),
			'ip_address'=>(!empty($details['ip_address'])? $details['ip_address']: '')
		));

		$returnArray = array('result'=>((!empty($result) && $result)? 'SUCCESS': 'FAIL'), 'new_user_id'=>$userId, 'email_verified'=>$emailVerified, 'reason'=>$reason);
		log_message('debug', '_account/add:: [24] returnArray='.json_encode($returnArray));
		return $returnArray;
	}





	# Verify a new user's account
	public function verify($code, $telephone, $userId, $baseLink)
	{
		log_message('debug', '_account/verify');
		log_message('debug', '_account/verify:: [1] code='.$code.' telephone='.$telephone.' userId='.$userId.' baseLink='.$baseLink);

		# Is it a phone verification or email verification?
		if(!empty($telephone)){

			$user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>hexdec($code)));
			log_message('debug', '_account/verify:: [2] user='.json_encode($user));

			# Mark the telephone and account as verified
			if(!empty($user['id'])) {

				$result = $this->_query_reader->run('update_user_value', array('field_name'=>'mobile_verified', 'field_value'=>'Y', 'user_id'=>$user['id']));
				log_message('debug', '_account/verify:: [3] result='.json_encode($result));

				if($result) $result = $this->_query_reader->run('update_user_field', array('user_id'=>$user['id'], 'field_name'=>'user_status', 'field_value'=>'active'));
				log_message('debug', '_account/verify:: [4] result='.json_encode($result));
			}
		}

		#This is an email verification (by link)
		else {

			$user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>extract_id($code) ));
			log_message('debug', '_account/verify:: [5] user='.json_encode($user));

			# Mark the email and account as verified
			if(!empty($user['id'])) {
				$result = $this->_query_reader->run('update_user_value', array('field_name'=>'email_verified', 'field_value'=>'Y', 'user_id'=>$user['id']));
				log_message('debug', '_account/verify:: [6] result='.json_encode($result));

				if($result) $result = $this->_query_reader->run('update_user_field', array('user_id'=>$user['id'], 'field_name'=>'user_status', 'field_value'=>'active'));
				log_message('debug', '_account/verify:: [7] result='.json_encode($result));
			}
		}

		log_message('debug', '_account/verify:: [7] result='.json_encode($result));
		return array('verified'=>(!empty($result) && $result? 'Y': 'N'));
	}





	# Resend an account verification link
	public function resend_link($emailAddress, $userId, $baseLink)
	{
		log_message('debug', '_account/resend_link');
		log_message('debug', '_account/resend_link:: [1] email='.$emailAddress.' userId='.$userId.' baseLink='.$baseLink);

		$result = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send', 'receiverId'=>$userId, 'return'=>'plain',
					'message'=>array(
							'code'=>'account_verification_link',
							'emailaddress'=>$emailAddress,
							'verificationlink'=>$baseLink.'u/'.format_id($userId)
						),
					'requiredFormats'=>array('system', 'email'),
					'strictFormatting'=>TRUE
				));

		log_message('debug', '_account/verify:: [2] result='.json_encode($result));
		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}




	# Login into the system
	public function login($userName, $password, $details=array())
	{
		log_message('debug', '_account/login');
		log_message('debug', '_account/login:: [1] username='.$userName.' password='.$password.' details'.json_encode($details));

		$response = array('result'=>'FAIL', 'default_view'=>'', 'user_details'=>array(), 'permissions'=>array(), 'rules'=>array(), 'user_types'=>array());

		# Check whether the userName and password match for the user - and get their user id
		$iam = server_curl(IAM_SERVER_URL,  array('__action'=>'login', 'username'=>$userName, 'password'=>$password));
		log_message('debug', '_account/login:: [2] iam='.json_encode($iam));


		# Get the user details if they exist
		if(!empty($iam['user_id'])) $user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$iam['user_id']));
		if(!empty($user)) log_message('debug', '_account/login:: [3] user='.json_encode($user));

		# Collect the rest of the user details if login is successful
		if(!empty($user['user_status']) && $user['user_status']=='active'){
			$userId = $iam['user_id'];
			$response['result'] = 'SUCCESS';
			$response['user_type'] = $this->get_user_type($userId);
			log_message('debug', '_account/login:: [4] user_type='.json_encode($response['user_type']));

			# Default view on login
			$response['default_view'] = $this->get_default_view($userId, $response['user_type']);
			log_message('debug', '_account/login:: [5] default_view='.json_encode($response['default_view']));

			# User details
			if(!empty($user)) {
				$telephone =  server_curl(MESSAGE_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_user_phone_details', 'variables'=>array('user_id'=>$userId) ));
				log_message('debug', '_account/login:: [6] telephone='.json_encode($telephone));

				$response['user_details'] = array(
					'user_id'=>$userId,
					'first_name'=>$user['first_name'],
					'last_name'=>$user['last_name'],
					'email_address'=>$user['email_address'],
					'email_verified'=>$user['email_verified'],
					'telephone'=>$user['telephone'],
					'telephone_carrier'=>(!empty($telephone['telephone_carrier'])? $telephone['telephone_carrier']: ''),
					'provider'=>(!empty($telephone['telephone_carrier'])? $telephone['telephone_carrier']: ''),
					'provider_id'=>(!empty($telephone['telephone_carrier_id'])? $telephone['telephone_carrier_id']: ''),
					'telephone_verified'=>$user['mobile_verified'],
					'has_linked_accounts'=>$user['has_linked_accounts'],
					'photo_url'=>(!empty($user['photo_url'])? S3_URL.$user['photo_url']: ''),
					'direct_invitation_count'=>$this->direct_invitation_count($userId)
				);
				log_message('debug', '_account/login:: [7] user_details='.json_encode($response['user_details']));
			}

			# The allowed permissions for the user
			$response['permissions'] = $this->get_user_permissions($userId);
			log_message('debug', '_account/login:: [8] permissions='.json_encode($response['permissions']));

			# The allowed rules for the user
			$response['rules'] = server_curl(IAM_SERVER_URL,  array('__action'=>'get_single_column_as_array', 'query'=>'get_user_rules', 'variables'=>array('user_id'=>$userId), 'column'=>'rule_code'));
			log_message('debug', '_account/login:: [9] rules='.json_encode($response['rules']));

		}

		# Log the login attempt event
		$this->_logger->add_event(array(
			'user_id'=>(!empty($userId)? $userId: 'username='.$userName),
			'activity_code'=>'login',
			'result'=>(!empty($userId)? 'SUCCESS':'FAIL'),
			'log_details'=>"username=".$userName."|device=".(!empty($details['device'])? $details['device']: 'unknown')."|browser=".(!empty($details['browser'])? $details['browser']: 'unknown'),
			'uri'=>(!empty($details['uri'])? $details['uri']: ''),
			'ip_address'=>(!empty($details['ip_address'])? $details['ip_address']: '')
		));

		log_message('debug', '_account/login:: [9] response='.json_encode($response));
		return $response;
	}



	# get the user permissions
	function get_user_permissions($userId)
	{
		log_message('debug', '_account/get_user_permissions');
		log_message('debug', '_account/get_user_permissions:: [1] userId='.$userId);

		$permissions = array();
		$list = server_curl(IAM_SERVER_URL,  array('__action'=>'get_list', 'query'=>'get_user_permissions', 'variables'=>array('user_id'=>$userId)));
		log_message('debug', '_account/get_user_permissions:: [2] list='.json_encode($list));

		if(!empty($list)){
			foreach($list AS $row) {
				if(empty($permissions[$row['category']])) $permissions[$row['category']] = array();
				array_push($permissions[$row['category']], $row['permission_code']);
			}
		}
		log_message('debug', '_account/get_user_permissions:: [3] permissions='.json_encode($permissions));
		return $permissions;
	}



	# get the user type
	function get_user_type($userId)
	{
		log_message('debug', '_account/get_user_type');
		log_message('debug', '_account/get_user_type:: [1] userId='.$userId);

		$type = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_user_group_types', 'variables'=>array('user_id'=>$userId) ));
		log_message('debug', '_account/get_user_type:: [2] type='.json_encode($type));

		return !empty($type['group_type'])? $type['group_type']: '';
	}




	# count the direct invitations for a given user
	function direct_invitation_count($userId)
	{
		log_message('debug', '_account/direct_invitation_count');
		log_message('debug', '_account/direct_invitation_count:: [1] userId='.$userId);

		$this->load->model('_score');
		$result = $this->_score->get_level_invite_count(array($userId));
		log_message('debug', '_account/direct_invitation_count:: [2] result='.json_encode($result));

		return $result;
	}






	# Logout of the system
	public function logout($userId, $details=array())
	{
		log_message('debug', '_account/logout');
		log_message('debug', '_account/logout:: [1] userId='.$userId.' details='.json_encode($details));
		# Log the logout attempt event
		$this->_logger->add_event(array(
			'user_id'=>(!empty($userId)? $userId: ''),
			'activity_code'=>'logout',
			'result'=>'SUCCESS',
			'log_details'=>"device=".(!empty($details['device'])? $details['device']: 'unknown')."|browser=".(!empty($details['browser'])? $details['browser']: 'unknown'),
			'uri'=>(!empty($details['uri'])? $details['uri']: ''),
			'ip_address'=>(!empty($details['ip_address'])? $details['ip_address']: '')
		));
	}



	# Get the default view to redirect the user on login
	function get_default_view($userId, $type='')
	{
		log_message('debug', '_account/get_default_view');
		log_message('debug', '_account/get_default_view:: [1] userId='.$userId.' type='.$type);

		if(empty($type)) {
			$row = server_curl(IAM_SERVER_URL,  array('__action'=>'get_row_as_array', 'query'=>'get_user_group_types', 'variables'=>array('user_id'=>$userId) ));
			$type = !empty($row['group_type'])? $row['group_type']: '';
			log_message('debug', '_account/get_default_view:: [2] type='.$type);
		}

		# get the default view to show user
		if(!empty($type)){
			if(in_array($type, array('clout_owner','clout_admin_user'))) $view = 'account/admin_dashboard';
			else if(in_array($type, array('store_owner_owner','store_owner_admin_user'))) $view = 'account/store_owner_dashboard';
			else if($type == 'invited_shopper') $view = 'account/shopper_dashboard';
		}
		# does not qualify in any of the above user groups
		if(empty($view)) $view = 'network/home';

		log_message('debug', '_account/get_default_view:: [2] view='.$view);
		return $view;
	}




	# Function to send a password recovery link
	function send_password_link($emailAddress, $baseLink)
	{
		log_message('debug', '_account/send_password_link');
		log_message('debug', '_account/send_password_link:: [1] emailAddress='.$emailAddress.' baseLink='.$baseLink);

		$msg = '';
		$user = $this->_query_reader->get_row_as_array('get_user_by_email',array('email_address'=>$emailAddress));
		log_message('debug', '_account/send_password_link:: [2] user='.json_encode($user));

		if(!empty($user['user_id'])){
			#Generate a temporary password that the user has to update and make it the temp password
			$password = 'TEMP-'.strtoupper(chr(97 + mt_rand(0, 25))).'-'.$user['user_id'];

			$sendResult = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send',
					'receiverId'=>$user['user_id'],
					'return'=>'plain',
					'message'=> array(
							'code'=>'password_recovery_link',
							'securityemail'=>SECURITY_EMAIL,
							'recoverylink'=>$baseLink.'p/'.encrypt_value($password)
						),
					'requiredFormats'=>array('email', 'sms'),
					'strictFormatting'=>TRUE
				));
			log_message('debug', '_account/send_password_link:: [3] sendResult='.json_encode($sendResult));

		}
		else $msg = 'The user with the entered email address does not exist.';
		log_message('debug', '_account/send_password_link:: [4] msg='.$msg);

		return array('result'=>(!empty($sendResult) && $sendResult? 'SUCCESS': 'FAIL'), 'msg'=>$msg);
	}




	# Function to reset the user's password
	function reset_password($userId, $tempPassword, $newPassword)
	{
		log_message('debug', '_account/reset_password');
		log_message('debug', '_account/reset_password:: [1] userId='.$userId.' tempPassword='.$tempPassword.' newPassword='.$newPassword);

		# Simply checking if this link is still valid
		if(!empty($tempPassword)){
			$realPass = decrypt_value($tempPassword);
			$realUserId = substr($realPass, strrpos($realPass, '-') + 1);
			if(!empty($realUserId)) $user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$realUserId));

			log_message('debug', '_account/reset_password:: [2] user='.json_encode($user));
			return array('result'=>(!empty($user)? 'verified': 'failed'), 'user_id'=>(!empty($user)? $realUserId: ''));
		}

		# actually changing the password
		else {
			if(!empty($newPassword) && !empty($userId)){
				$result = server_curl(IAM_SERVER_URL,  array(
					'__action'=>'update_user_password',
					'return'=>'plain',
					'userId'=>$userId,
					'password'=>$newPassword
				));

				log_message('debug', '_account/reset_password:: [3] result='.json_encode($result));
				if($result) {
					$sendResult = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send',
						'receiverId'=>$userId,
						'return'=>'plain',
						'message'=> array('code'=>'password_has_changed', 'securityemail'=>SECURITY_EMAIL),
						'requiredFormats'=>array('email', 'sms'),
						'strictFormatting'=>TRUE
					));
					log_message('debug', '_account/reset_password:: [3] sendResult='.json_encode($sendResult));
				}

			}


			return array('result'=>(!empty($result['result']) && $result['result']=='SUCCESS')? 'SUCCESS': 'FAIL');
		}
	}





	# apply the default user group e.g., after confirmation of the user's access rights
	function apply_default_user_group($userId)
	{
		log_message('debug', '_account/apply_default_user_group');
		log_message('debug', '_account/apply_default_user_group:: [1] userId='.$userId);

		$user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$userId));
		log_message('debug', '_account/apply_default_user_group:: [2] user='.json_encode($user));

		# check applicable rules
		if(!empty($user)){

			if(rule_check($this,'new_inclusion_list_user', array('user_id'=>$userId))) {
				$rresult = apply_rule($this,'new_inclusion_list_user', array('user_id'=>$userId));
				log_message('debug', '_account/apply_default_user_group:: [3] rresult='.json_encode($rresult));
			}
			# apply this if previous was not applicable or did not work
			if((empty($rresult) || !$rresult) && !empty($user['referrer']) && rule_check($this,'new_user_referred_by_invited_user', array('user_id'=>$userId, 'referrer_id'=>$user['referrer']))) {
				$rresult = apply_rule($this,'new_user_referred_by_invited_user', array('user_id'=>$userId, 'referrer_id'=>$user['referrer']));
				log_message('debug', '_account/apply_default_user_group:: [4] rresult='.json_encode($rresult));
			}
			# apply this if previous was not applicable or did not work
			if((empty($rresult) || !$rresult) && !empty($user['referrer']) && rule_check($this,'new_user_referred_by_random_user', array('user_id'=>$userId, 'referrer_id'=>$user['referrer']))) {
				$rresult = apply_rule($this,'new_user_referred_by_random_user', array('user_id'=>$userId, 'referrer_id'=>$user['referrer']));
				log_message('debug', '_account/apply_default_user_group:: [5] rresult='.json_encode($rresult));
			}
			# last resort..
			/*
			if(empty($rresult) || !$rresult) {
				log_message('info', '_account/apply_default_user_group:: [6] last resort setting to random user userId='.$userId);

				$rresult = apply_rule($this,'new_random_user', array('user_id'=>$userId));
			}
			*/
			# final verdict on rule application
			$result = !empty($rresult) && $rresult;
		}

		$returnArray = array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'),
				'default_view'=>$this->get_default_view($userId),
				'user_type'=>$this->get_user_type($userId),
				'user_permissions'=>$this->get_user_permissions($userId),
			);
		log_message('debug', '_account/apply_default_user_group:: [6] returnArray='.json_encode($returnArray));
		return $returnArray;
	}








	# save the user's facebook data
	function save_facebook_data($facebookId, $data)
	{
		log_message('debug', '_account/save_facebook_data');
		log_message('debug', '_account/save_facebook_data:: [1] facebookId='.$facebookId.' data='.json_encode($value));

		# if a photo is submitted, download and save the profile photo
		if(!empty($data['photoUrl'])){
			$fileUrl = download_from_url($data['photoUrl'],FALSE);
			if(!empty($fileUrl)){
				$result = $this->_query_reader->run('save_facebook_photo', array(
					'facebook_id'=>$facebookId,
					'photo_url'=>$fileUrl,
					'is_silhoutte'=>($data['isSilhouette'] == 'false' || !$data['isSilhouette']? 'Y': 'N')
				));
			}
			else $result = FALSE;
			log_message('debug', '_account/save_facebook_data:: [2] result='.json_encode($result));
		}

		# save the rest of the profile details
		else {
			$result = $this->_query_reader->run('save_facebook_details', array(
					'facebook_id'=>$facebookId,
					'email'=>$data['email'],
					'name'=>htmlentities($data['name'], ENT_QUOTES),
					'first_name'=>htmlentities($data['firstName'], ENT_QUOTES),
					'last_name'=>htmlentities($data['lastName'], ENT_QUOTES),
					'age_range'=>$data['ageRange'],
					'gender'=>$data['gender'],
					'birthday'=>$data['birthday'],
					'profile_link'=>$data['profileLink'],
					'timezone_offset'=>$data['timezoneOffset']
				));
			log_message('debug', '_account/save_facebook_data:: [2] result='.json_encode($result));
		}

		return array('boolean'=>$result);
	}






















	# ************************************************************************************************** #
	# WARNING: This function purges the user record from the system. It is a temporary function as no
	# account record should ever be deleted.
	#
	# Do not use if you do not know what you are doing
	# ************************************************************************************************** #
	function purge($userIds)
	{
		log_message('debug', '_account/purge');
		# give this script more time to complete execution
		set_time_limit(300);

		$msg = "";

		$apiResponse = server_curl(CRON_SERVER_URL, array('__action'=>'delete_plaid_user_accounts','user_ids'=>$userIds));

		# Remove the account from the database
		$results = array();
		foreach($userIds AS $userId)
		{
		$results[0] = $this->db->query("DELETE FROM activity_log WHERE user_id='".$userId."'")
					&& server_curl(IAM_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_activity_log', 'variables'=>array('user_id'=>$userId) ))
					&& server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_activity_log', 'variables'=>array('user_id'=>$userId) ))
					&& server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_activity_log', 'variables'=>array('user_id'=>$userId) ));

		$results[1] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_advert_and_promo_tracking', 'variables'=>array('user_id'=>$userId) ));
		$results[2] = $this->db->query("DELETE FROM archived_photos WHERE _user_id='".$userId."'");

		$results[3] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_bank_accounts', 'variables'=>array('user_id'=>$userId) ));
		$results[4] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_bank_accounts_credit_raw', 'variables'=>array('user_id'=>$userId) ));
		$results[5] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_bank_accounts_other_raw', 'variables'=>array('user_id'=>$userId) ));

		$results[6] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_cacheview__clout_score_data', 'variables'=>array('user_id'=>$userId) ));

		$results[7] = $this->db->query("DELETE FROM cacheview__default_search_suggestions WHERE user_id='".$userId."'");
		$results[8] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_cacheview__where_user_shopped', 'variables'=>array('user_id'=>$userId) ));
		$results[9] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_cacheview__store_score_data_by_default', 'variables'=>array('user_id'=>$userId) ));
		$results[10] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_cacheview__store_score_data_by_category', 'variables'=>array('user_id'=>$userId) ));
		$results[11] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_cacheview__store_score_data_by_store', 'variables'=>array('user_id'=>$userId) ));

		$results[12] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_commissions_network', 'variables'=>array('user_id'=>$userId, 'source_user_id'=>$userId) ));
		$results[13] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_commissions_transactions', 'variables'=>array('user_id'=>$userId) ));

		$results[14] = $this->db->query("DELETE FROM contact_addresses WHERE _user_id='".$userId."'");

		$results[15] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_contact_emails', 'variables'=>array('user_id'=>$userId) ));
		$results[16] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_contact_phones', 'variables'=>array('user_id'=>$userId) ));
		$results[17] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_cron_schedule', 'variables'=>array('user_id'=>$userId) ));

		$results[18] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_message_exchange', 'variables'=>array('user_id'=>$userId) ));

		$results[19] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_message_invites', 'variables'=>array('user_id'=>$userId) ));
		$results[20] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_message_likes', 'variables'=>array('user_id'=>$userId) ));

		$results[21] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_message_status', 'variables'=>array('user_id'=>$userId) ));

		$results[22] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_plaid_access_token', 'variables'=>array('user_id'=>$userId) ));
		$results[23] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_promotion_notices', 'variables'=>array('user_id'=>$userId) ));
		$results[24] = $this->db->query("DELETE FROM referral_url_ids WHERE _user_id='".$userId."'");
		$results[25] = $this->db->query("DELETE FROM referrals WHERE _user_id='".$userId."'");
		$results[26] = $this->db->query("DELETE FROM reviews WHERE _user_id='".$userId."'");

		$results[27] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_score_tracking_clout', 'variables'=>array('user_id'=>$userId) ));
		$results[28] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_score_tracking_stores', 'variables'=>array('user_id'=>$userId) ));

		$results[29] = $this->db->query("DELETE FROM store_favorites WHERE _user_id='".$userId."'");
		$results[30] = $this->db->query("DELETE FROM store_offer_requests WHERE _user_id='".$userId."'");
		$results[31] = $this->db->query("DELETE FROM store_owner_stores WHERE _store_owner_id='".$userId."'");
		$results[32] = $this->db->query("DELETE FROM store_owners WHERE user_id='".$userId."'");
		$results[33] = $this->db->query("DELETE FROM store_staff WHERE _staff_user_id='".$userId."'");
		$results[34] = $this->db->query("DELETE FROM survey_responses WHERE _user_id='".$userId."'");

		$results[35] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_transactions', 'variables'=>array('user_id'=>$userId) ));
		$results[36] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_transactions_raw', 'variables'=>array('user_id'=>$userId) ));

		$results[37] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_user_cash_tracking', 'variables'=>array('user_id'=>$userId) ));
		$results[38] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_user_credit_tracking', 'variables'=>array('user_id'=>$userId) ));
		$results[40] = server_curl(CRON_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_user_payment_tracking', 'variables'=>array('user_id'=>$userId) ));

		$results[39] = $this->db->query("DELETE FROM user_geo_tracking WHERE _user_id='".$userId."'");
		$results[42] = $this->db->query("DELETE FROM user_search_tracking WHERE _user_id='".$userId."'");

		$results[41] = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_user_preferred_communication', 'variables'=>array('user_id'=>$userId) ));

		$results[43] = $this->db->query("DELETE FROM user_security_settings WHERE _user_id='".$userId."'");
		$results[44] = $this->db->query("DELETE FROM user_social_media WHERE _user_id='".$userId."'");
		$results[44] = $this->db->query("DELETE FROM user_facebook_data WHERE owner_user_id='".$userId."'");
		$results[45] = $this->db->query("DELETE FROM users WHERE id='".$userId."'");

		$results[46] = server_curl(IAM_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_security_answers', 'variables'=>array('user_id'=>$userId) ));
		$results[47] = server_curl(IAM_SERVER_URL, array('__action'=>'run', 'return'=>'plain', 'query'=>'delete_user_access', 'variables'=>array('user_id'=>$userId) ));
		}

		log_message('debug', '_account/purge:: [1] results='.json_encode($results));
		return array('result'=>(get_decision($results)? 'SUCCESS': 'FAIL'), 'msg'=>$msg);
	}








}


