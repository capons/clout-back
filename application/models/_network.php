<?php
/**
 * This class generates and formats network data in the system. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 10/1/2015
 */
class _network extends CI_Model
{
	# Get the email host given the user's email address
	function email_host($userEmail, $returnType='actualUrl')
	{
		log_message('debug', '_network/email_host');
		log_message('debug', '_network/email_host:: [1] userEmail='.$userEmail.' returnType='.$returnType);
		
		#First get the domain of the email 
		$emailDomain = strtolower(substr(stristr($userEmail, '@'), 1));
		
		#Search the known mail hosts
		$host = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'get_known_mail_host', 'variables'=>array('domain'=>$emailDomain) ));
		log_message('debug', '_network/email_host:: [2] host='.$host);
		
		if(!empty($host))
		{
			if($returnType == 'actualUrl')
			{
				if(!empty($host['actual_url'])) $emailHost = $host['actual_url'];
				else $emailHost = "{".$host['host_url'].":".$host['port']."/imap/ssl}INBOX";
			}
			else $emailHost = $host['host_url'];
			log_message('debug', '_network/email_host:: [3] emailHost='.$emailHost);
		}
		
		return array('host_url'=>(!empty($emailHost)? $emailHost: ''));
	}
	
	
	
	
	
	# Get the email host given the user's email address
	function import_contacts_from_email($userId, $userEmail, $emailPassword, $emailHost, $hostPort)
	{
		log_message('debug', '_network/import_contacts_from_email');
		log_message('debug', '_network/import_contacts_from_email:: [1] userId='.$userId.' userEmail='.$userEmail.' emailPassword='.$emailPassword.' emailHost='.$emailHost.' hostPort='.$hostPort);
		
		if(empty($emailHost)) {
			$host = $this->email_host($userEmail);
			$hostUrl = $host['host_url'];
		} 
		else $hostUrl = "{".$emailHost.":".$hostPort."/imap/ssl}INBOX";
		
		# Collect the emails
		$existingEmails = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_single_column_as_array', 'query'=>'get_users_invited_emails', 'column'=>'email_address', 'variables'=>array('user_id'=>$userId) ));
		log_message('debug', '_network/import_contacts_from_email:: [2] existingEmails='.json_encode($existingEmails));
		
		$rawContacts = $this->import_emails_from_third_party($userId, $userEmail, $emailPassword, $hostUrl, 'list', $existingEmails);
		log_message('debug', '_network/import_contacts_from_email:: [3] rawContacts='.json_encode($rawContacts));
		
		$contacts = array();
		# Add the new contacts - taking note of those already in the system
		if(!empty($rawContacts)){
			foreach($rawContacts AS $email=>$contact) $contacts[$email] = $this->add_new_contact($contact);
		}
		log_message('debug', '_network/import_contacts_from_email:: [3] contacts='.json_encode($contacts));
		
		return array('result'=>(!empty($contacts)? 'SUCCESS': 'FAIL'), 'contacts'=>$contacts);
	}
	
	
	
	
	
	# Add a new contact for the user
	function add_new_contact($contactInfo)
	{
		log_message('debug', '_network/add_new_contact');
		log_message('debug', '_network/add_new_contact:: [1] contactInfo='.json_encode($contactInfo));
		
		$response = array('success'=>false, 'inDB'=>false, 'alreadyContacted'=>false, 'unsubscribed'=>false, 'canContactAgain'=>true, 'lastContactDate'=>'');
		
		#1. process the name field
		#- Remove the name if it is the same as the email address
		$contactInfo['name'] = (empty($contactInfo['name']) || ($contactInfo['name'] == $contactInfo['email_address']))? '': $contactInfo['name'];
		#- Separate the name field details
		if(!empty($contactInfo['name']))
		{
			$name = explode(' ', $contactInfo['name']);
			$contactInfo['first_name'] = (count($name) > 0)? htmlentities($name[0], ENT_QUOTES): '';
			$theFirstName = array_shift($name);
			$contactInfo['last_name'] = (count($name) > 1)? htmlentities(implode(' ', $name), ENT_QUOTES): '';
		}
		else 
		{
			$contactInfo['first_name'] = '';
			$contactInfo['last_name'] = '';
		}
		
		#Fill in any possible ommissions
		$contactInfo['middle_name'] = !empty($contactInfo['middle_name'])? $contactInfo['middle_name']: '';
		$contactInfo['phone_number'] = !empty($contactInfo['phone_number'])? $contactInfo['phone_number']: '';
		$contactInfo['photo_url'] = !empty($contactInfo['photo_url'])? $contactInfo['photo_url']: '';
		
		# 2. Check if email unsubscribed
		$unsubscribed = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array', 'query'=>'check_if_user_unsubscribed_by_email', 'variables'=>array('email_address'=>$contactInfo['email_address']) ));
		# Proceed to send user
		if(empty($unsubscribed)){
			$contactId = $this->_query_reader->add_data('add_new_invitation_contact', $contactInfo);
			$response['success'] = !empty($contactId);
			if(empty($contactId)) $response['inDB'] = true;
		}
		else {
			$response['can_send'] = "NO"; 
			$response['unsubscribed'] = true;
		}
		log_message('debug', '_network/add_new_contact:: [2] response='.json_encode($response));
		log_message('debug', '_network/add_new_contact:: [3] contactInfo='.json_encode($contactInfo));
		
		return array_merge($response, $contactInfo);
	}
	
	
	
	
	
	
	
	
	
	# Import new contacts from a third party
	# RETURN OPTIONS: array, list
	function import_emails_from_third_party($userId, $userEmail, $userPassword, $host, $return='list', $existingEmails=array(), $emailLimit=MAX_EMAILS_TO_IMPORT)
	{
		log_message('debug', '_network/import_emails_from_third_party');
		log_message('debug', '_network/import_emails_from_third_party:: [1] userId='.$userId.' userEmail='.$userEmail.' userPassword='.$userPassword.' host='.$host.' return='.$return.
				' existingEmails='.$existingEmails.' emailLimit='.$emailLimit);
		
		$contacts = array();
		$source = strtoupper(substr(stristr($userEmail, '@'), 1));
		
		$mailbox = @imap_open($host,$userEmail,$userPassword);# OR die("<br />\nFAILLED! ".imap_last_error());
		imap_errors();
		imap_alerts();
				
		#Only search if the search passes
		if(!empty($mailbox))
		{
			$emailMessages = imap_search($mailbox,'ALL');
			rsort($emailMessages);
			$emails = $contacts = array();
			
			#Loop through the messages and pick the contacts
			foreach($emailMessages AS $message) 
			{
				#Fetch the headers of the emails and extract all the contact email addresses and the names
				#TODO: Add the option to get the messages by date range
				$overview = imap_rfc822_parse_headers(imap_fetchheader($mailbox,$message,0));
				
				#FROM
				/*foreach($overview->from AS $contactObj)
				{
					$fromEmail = $contactObj->mailbox.'@'.$contactObj->host;
					if(is_valid_email($fromEmail) && strtolower($userEmail) == strtolower($fromEmail))
					{
						$validFromEmail = strtolower($fromEmail);
						break;
					}
				}*/
				
				# ** 10/02/2015 Al Zziwa
				# TURNED OFF FOR BEING TOO RESTRICTIVE
				
				#Get emails to which the user actually responded to reduce spamming spammers
				#if(!empty($validFromEmail))
				#{
					#TO
					foreach($overview->from AS $contactObj)
					{
						$theEmail = strtolower((!empty($contactObj->mailbox) && !empty($contactObj->host))? $contactObj->mailbox.'@'.$contactObj->host:(!empty($contactObj->toaddress)? $contactObj->toaddress: ''));
						if(is_valid_email($theEmail) && !in_array($theEmail, $existingEmails) && strtolower($theEmail) != strtolower($userEmail))
						{
							array_push($emails, $theEmail);
							$contacts[$theEmail] = array('owner_user_id'=>$userId, 'email_address'=>$theEmail, 'name'=>(!empty($contactObj->personal)? remove_commas($contactObj->personal):''), 'source'=>$source);
						}
					}
				
				
					#CC
					if(!empty($overview->cc))
					{
						foreach($overview->cc AS $contactObj)
						{
							$theEmail = strtolower($contactObj->mailbox.'@'.$contactObj->host);
							if(is_valid_email($theEmail) && !in_array($theEmail, $existingEmails) && strtolower($theEmail) != strtolower($userEmail))
							{
								array_push($emails, $theEmail);
								$contacts[$theEmail] = array('owner_user_id'=>$userId, 'email_address'=>$theEmail, 'name'=>(!empty($contactObj->personal)? remove_commas($contactObj->personal):''), 'source'=>$source);
							}
						}
					}
				
				
					#BCC
					if(!empty($overview->bcc))
					{
						foreach($overview->bcc AS $contactObj)
						{
							$theEmail = strtolower($contactObj->mailbox.'@'.$contactObj->host);
							if(is_valid_email($theEmail) && !in_array($theEmail, $existingEmails) && strtolower($theEmail) != strtolower($userEmail))
							{
								array_push($emails, $theEmail);
								$contacts[$theEmail] = array('owner_user_id'=>$userId, 'email_address'=>$theEmail, 'name'=>(!empty($contactObj->personal)? remove_commas($contactObj->personal):''), 'source'=>$source);
							}
						}
					}
				#}
				
				
				#Remove any duplicates
				$emails = array_unique($emails);
				
				#Quit the loop if you have reached the fetch limit
				if((count($emails)+1) >= $emailLimit) break;
			}
		}
		else
		{
			$response['message'] = "WARNING: The login credentials were rejected.";
		}
		
		
		#Remove the owner's email address
		if(array_key_exists($userEmail, $contacts)) unset($contacts[$userEmail]);
		log_message('debug', '_network/import_emails_from_third_party:: [2] contacts='.json_encode($contacts));
		log_message('debug', '_network/import_emails_from_third_party:: [3] emails='.json_encode($emails));
		
		return $return == 'list'? $contacts: $emails;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	# Get a list of referrals for the given user
	function referrals($userId, $level, $offset, $limit, $phrase)
	{
		log_message('debug', '_network/referrals');
		log_message('debug', '_network/referrals:: [1] userId='.$userId.' level='.$level.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);
		
		$phraseCondition = (!empty($phrase)? " AND (U.first_name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%' OR U.last_name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%')": '');
		
		$result = $this->_query_reader->get_list('get_searchable_referral_list', array('user_id'=>$userId, 'phrase_condition'=>$phraseCondition, 'limit_text'=>" LIMIT ".$offset.",".$limit." "));
		$referrals = get_column_from_multi_array($result, 'referral_id');
		$referralInviteCount = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_total_invites', 'variables'=>array('user_ids'=>implode("','",array_diff($referrals, array('')))) ));
		$referralInviteCount = use_multi_column_as_key($referralInviteCount, 'referral_id');
		
		foreach($result AS $key=>$row) {
			$result[$key]['total_invites'] = !empty($row['referral_id']) && !empty($referralInviteCount[$row['referral_id']])? $referralInviteCount[$row['referral_id']]['invite_count']: '0';
		}
		log_message('debug', '_network/referrals:: [1] final='.json_encode($result));
		
		return $result;
	}
	
	
	
	# Get a list of invites by the given user
	function invites($userId, $level, $offset, $limit, $phrase)
	{
		log_message('debug', '_network/invites');
		log_message('debug', '_network/invites:: [1] userId='.$userId.' level='.$level.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);
		$phraseCondition = (!empty($phrase)? " AND (I.first_name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%' OR I.last_name LIKE '%".htmlentities($phrase, ENT_QUOTES)."%' OR I.email_address LIKE '%".htmlentities($phrase, ENT_QUOTES)."%')": '');
		
		log_message('debug', '_network/invites:: [2] phraseCondition='.$phraseCondition);
		$invites = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_searchable_invite_list',  'variables'=>array('user_id'=>$userId, 'phrase_condition'=>$phraseCondition, 'limit_text'=>" LIMIT ".$offset.",".$limit." ") ));
		log_message('debug', '_network/invites:: [3] get_searchable_invite_list='.json_encode($invites));
		
		$referrals = get_column_from_multi_array($invites, 'referral_id');
		log_message('debug', '_network/invites:: [4] get_column_from_multi_array='.implode(' ', $referrals));
		$referralInviteCount = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_list', 'query'=>'get_total_invites', 'variables'=>array('user_ids'=>implode("','",array_diff($referrals, array('')))) )); 
		log_message('debug', '_network/invites:: [5] referralInviteCount='.json_encode($referralInviteCount));
		$referralInviteCount = use_multi_column_as_key($referralInviteCount, 'referral_id');
		log_message('debug', '_network/invites:: [6] referralInviteCount='.json_encode($referralInviteCount));
		
		$final = array();
		foreach($invites AS $row) {

			log_message('debug', '_network/invites:: [7] looping over invites='.json_encode($row));

			# invitee signed up
			if(!empty($row['referral_id'])) {
				log_message('debug', '_network/invites:: [8] referal_id not empty');
				$photo = $this->_query_reader->get_row_as_array('get_user_photo', array('user_id'=>$row['referral_id'] ));
				log_message('debug', '_network/invites:: [9] get_user_photo');
				$row['photo_url'] = !empty($photo['photo_url'])? s3_url($photo['photo_url']): ''; 
				log_message('debug', '_network/invites:: [10] photo url='.$row['photo_url']);
				$row['invite_count'] = !empty($referralInviteCount[$row['referral_id']])? $referralInviteCount[$row['referral_id']]['invite_count']: '0';
				log_message('debug', '_network/invites:: [11] invite count='.$row['invite_count']);
			}
			# invitee not yet signed up
			else {
				log_message('debug', '_network/invites:: [12] inviter not yet signed up');
				$row['photo_url'] = '';
				$row['invite_count'] = '';
			}
			
			$final[] = $row;
		}
		log_message('debug', '_network/invites:: [13] final='.json_encode($final));
		
		return $final;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	#Function to get the user's referrals given the user ID
	public function get_user_referrals($userId, $action, $dateRange=array(), $summarizeBy=array())
	{
		log_message('debug', '_network/get_user_referrals');
		log_message('debug', '_network/get_user_referrals:: [1] userId='.$userId.' action='.$action.' dateRange='.json_encode($dateRange).' summarizeBy='.json_encode($summarizeBy));
		#getting direct referrals
		if($action == 'list' || $action == 'count' || $action == 'list_ids')
		{
			#Add more conditions based on the required data list
			$additionalQuery = (!empty($dateRange['start_date']))? " AND DATEDIFF(DATE(R.activation_date), DATE('".$dateRange['start_date']."')) >= 0 ": "";
			
			# Just count the referrals
			if($action == 'count')
			{
				$referralResult = $this->_query_reader->get_row_as_array('get_referral_count', array('user_id'=>$userId, 'query_part'=>$additionalQuery));
				$result = !empty($referralResult['referral_count'])? $referralResult['referral_count']: 0;
			}
			# IDs of the referrals
			else if($action == 'list_ids')
			{
				$result = $this->_query_reader->get_single_column_as_array('get_referral_ids', 'user_id', array('user_id'=>$userId, 'query_part'=>$additionalQuery));
				
			}
			# Details of the referrals
			else if($action == 'list')
			{
				$result = $this->_query_reader->get_list('get_user_referrals', array('user_id'=>$userId, 'query_part'=>$additionalQuery));
			}
			
		}
		
		#getting network referrals
		else if($action == 'network_list' || $action == 'network_count' || $action == 'network_list_ids' || $action == 'network_level_count' || $action == 'network_level_ids')
		{
			#Add more conditions based on the required data list
			$additionalQuery = (!empty($dateRange['start_date']))? " AND DATEDIFF(DATE(R.activation_date), DATE('".$dateRange['start_date']."')) >= 0 ": "";
			
			#For tracking the list of referrals
			$secondLevelReferrals = $thirdLevelReferrals = $fourthLevelReferrals = array();
			
			#First get the direct referrals
			$directReferrals = $this->_query_reader->get_list('get_referral_ids', array('user_id'=>$userId, 'query_part'=>''));
			
			foreach($directReferrals AS $level1Row)
			{
				#Get the second level referrals using the direct referrals
				$secondLevel = $this->_query_reader->get_list('get_referral_ids', array('user_id'=>$level1Row['user_id'], 'query_part'=>$additionalQuery));
				
				foreach($secondLevel AS $level2Row)
				{
					$thirdLevel = $this->_query_reader->get_list('get_referral_ids', array('user_id'=>$level2Row['user_id'], 'query_part'=>$additionalQuery));
					
					#Keep track of the referrals
					array_push($secondLevelReferrals, $level2Row['user_id']);
					
					foreach($thirdLevel AS $level3Row)
					{
						$fourthLevel = $this->_query_reader->get_list('get_referral_ids', array('user_id'=>$level3Row['user_id'], 'query_part'=>$additionalQuery));
					
						#Keep track of the referrals
						array_push($thirdLevelReferrals, $level3Row['user_id']);
						
						foreach($fourthLevel AS $level4Row)
						{
							#Keep track of the referrals
							array_push($fourthLevelReferrals, $level4Row['user_id']);
						}#End fourth level
					}#End third level
				}#End second level
			}#End direct level
			
			
			#Collect all the referral user IDs
			$networkReferralIds = array_merge($secondLevelReferrals, $thirdLevelReferrals, $fourthLevelReferrals);
			$levelTrack = array('2'=>$secondLevelReferrals, '3'=>$thirdLevelReferrals, '4'=>$fourthLevelReferrals);
			
			if($action == 'network_count')
			{
				$result = count($networkReferralIds);
			}
			else if($action == 'network_list_ids')
			{
				$result = $networkReferralIds;
			}
			else if($action == 'network_list')
			{
				#Now get all network referrals data
				if(!empty($summarizeBy))
				{
					$referralIds = (array_key_exists('referral_level', $summarizeBy))? ($summarizeBy['referral_level']=='1'? $this->get_user_referrals($userId, 'list_ids'): $levelTrack[$summarizeBy['referral_level']]): $networkReferralIds;
				}
				else
				{
					$referralIds = $networkReferralIds;
				}
				$minLimit = array_key_exists('lower_limit', $summarizeBy)? $summarizeBy['lower_limit']: '0';
				$maxLimit = array_key_exists('upper_limit', $summarizeBy)? $summarizeBy['upper_limit']: '50';
					
				#The limit is flexible to be set by the user
				$networkReferrals = $this->_query_reader->get_list('get_user_network_referrals', array('user_id_list'=>"'".implode("','", $referralIds)."'", 'limit_text'=>" LIMIT ".$minLimit.", ".$maxLimit));
				$result = array('list'=>$networkReferrals, 'levels'=>$levelTrack);
			}
			else if($action == 'network_level_count')
			{
				$levelCount = array('level1'=>0, 'level2'=>0, 'level3'=>0, 'level4'=>0);
				
				$referralResult = $this->_query_reader->get_row_as_array('get_referral_count', array('user_id'=>$userId, 'query_part'=>$additionalQuery));
				$levelCount['level1'] = !empty($referralResult['referral_count'])? $referralResult['referral_count']: 0;
				$levelCount['level2'] = count($levelTrack['2']);
				$levelCount['level3'] = count($levelTrack['3']);
				$levelCount['level4'] = count($levelTrack['4']);
				
				
				$result = $levelCount;
			}
			else if($action == 'network_level_ids')
			{
				$levelIds = array('level1'=>array(), 'level2'=>array(), 'level3'=>array(), 'level4'=>array());
				$levelIds['level1'] = $this->get_user_referrals($userId, 'list_ids');
				$levelIds['level2'] = $levelTrack['2'];
				$levelIds['level3'] = $levelTrack['3'];
				$levelIds['level4'] = $levelTrack['4'];
				
				
				$result = $levelIds;
			}
			
			
		}
		log_message('debug', '_network/get_user_referrals:: [2] result='.json_encode($result));
		return $result;
	}
	
	
	
	
	
	
	
	
	
	
	
	# Add a new contact for the user
	function send_invitations($userId, $emailList, $ipAddress, $baseUrl)
	{
		log_message('debug', '_networks/send_invitations');
		log_message('debug', '_networks/send_invitations:: [1] userId='.$userId.' emailList='.$emailList.' ipAddress='.$ipAddress.' baseUrl='.$baseUrl);
		
		$result = FALSE;
		$perEmail = array(); # Per email results
		
		# check if there are emails for users who already signed up or those who this user has already invited
		$existingUserEmails = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_single_column_as_array', 'query'=>'get_existing_user_emails', 'column'=>'email_address', 'variables'=>array('email_list'=>implode("','",$emailList), 'user_id'=>$userId) ));
		log_message('debug', '_networks/send_invitations:: [2] existingUserEmails='.$existingUserEmails);
		
		$scheduleCount = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_count', 'query'=>'get_user_invitations_by_status', 'variables'=>array('user_id'=>$userId, 'status'=>'pending') ));
		log_message('debug', '_networks/send_invitations:: [3] scheduleCount='.$scheduleCount);
		
		foreach($emailList AS $email){
			if(!in_array($email, $existingUserEmails)) {
				log_message('debug', '_networks/send_invitations:: [4] existingUserEmails not in email list');
				
				$scheduleCount++;
				$status = 'pending';
				# check which rules apply for this user
				if(rule_check($this,'invite_daily_limit_10', array('user_id'=>$userId)) && $scheduleCount > 10){
					log_message('debug', '_networks/send_invitations:: [5] invite_daily_limit_10');
					$status = 'paused';
				}
				if(rule_check($this,'invite_daily_limit_30', array('user_id'=>$userId)) && $scheduleCount > 30){
					log_message('debug', '_networks/send_invitations:: [6] invite_daily_limit_30');
					$status = 'paused';
				}
				if(rule_check($this,'stop_new_invite_sending', array('user_id'=>$userId)) 
					|| rule_check($this,'stop_all_invite_sending', array('user_id'=>$userId))
				){
					log_message('debug', '_networks/send_invitations:: [6] stop_new_invite_sending or stop_all_invite_sending');
					$status = 'paused';
				}
				
				$result = $this->schedule_invitation($email, $userId, $ipAddress, $status, $baseUrl.'r/'.format_id($userId));
			}
			
			
			# The user with this email already exists
			else $perEmail[$email] = 'user already invited';
		}
		log_message('debug', '_networks/send_invitations:: [7] result='.$result);
		log_message('debug', '_networks/send_invitations:: [8] perEmail='.json_encode($perEmail));
		return array(
			'result'=>($result? 'SUCCESS': 'FAIL'), 
			'msg'=>($result? 'Your invitations have been scheduled for sending.': 'ERROR: No invitations could be scheduled for sending.'),
			'per_email'=>$perEmail
		);
	}
	
	
	
	
	
	# schedule invitation message for sending
	function schedule_invitation($email, $userId, $ipAddress, $status, $joinLink)
	{
		log_message('debug', '_networks/schedule_invitation');		
		log_message('debug', '_networks/schedule_invitation:: [1] email='.$email . ' userId='.$userId . ' ipAddress='.$ipAddress.' status='.$status.' joinLink='.$joinLink);

		$result = server_curl(MESSAGE_SERVER_URL, array('__action'=>'run', 'query'=>'add_message_invite', 'return'=>'plain', 
			'variables'=>array(
					'email_address'=>$email, 
					'user_id'=>$userId, 
					'method_used'=>'email', 
					'message_status'=>$status ,
					'invite_message'=>'invitation_to_join_clout',
					'join_link'=>$joinLink,
					'sent_at_ip_address'=>$ipAddress,
					'first_name'=>'', 'last_name'=>'', 'phone_number'=>''
				)
		));
		log_message('debug', '_networks/schedule_invitation:: [2] result='.json_encode($result));
		
		return $result;
	}
	
	
	
	
	
	
	
	# Import contacts from file
	function import_contacts_from_file($userId, $fileFormat, $csvFile)
	{
		log_message('debug', '_networks/import_contacts_from_file');
		log_message('debug', '_networks/import_contacts_from_file:: [1] userId='.$userId . ' fileFormat='.$fileFormat . ' csvFile='.$csvFile);
		
		$fileName = basename($csvFile);
		$fileUrl = UPLOAD_DIRECTORY.$fileName;
		
		$ch = curl_init($csvFile);
		$fp = fopen($fileUrl, 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		
		if(file_exists($fileUrl) && filesize($fileUrl) > 0){
			# Collect the emails
			$existingEmails = $this->_query_reader->get_single_column_as_array('get_users_invited_emails', 'email_address', array('user_id'=>$userId));
			
			$this->load->model('_csv_processor');
			$fileContacts = $this->_csv_processor->get_contacts_through_file($userId, $fileUrl, $fileFormat, 'list', $existingEmails);
			if(!empty($fileContacts['result']) && $fileContacts['result']) $rawContacts = $fileContacts['data'];
		}
		
		$contacts = array();
		# Add the new contacts - taking note of those already in the system
		if(!empty($rawContacts)){
			foreach($rawContacts AS $email=>$contact) $contacts[$email] = $this->add_new_contact($contact);
		}
		log_message('debug', '_networks/import_contacts_from_file:: [2] contacts='.json_encode($contacts));
		
		return array('result'=>(!empty($contacts)? 'SUCCESS': 'FAIL'), 'contacts'=>$contacts);
	}
	
	
	
	
	
	
	
	
	# Add share link
	function add_share_link($userId)
	{
		log_message('debug', '_networks/add_share_link');
		log_message('debug', '_networks/add_share_link:: [1] userId='.$userId);
		
		# Get all current referral IDs
		$links = $this->_query_reader->get_single_column_as_array('get_share_links', 'link_id', array('user_id'=>$userId));
		$linkArray = !empty($links)? $links: array(format_id($userId));
		log_message('debug', '_networks/add_share_link:: [2] linkArray='.json_encode($linkArray));
		
		# for 26 times (letters of the alphabet) look for a free letter and append that to this user's ID
		# to generate a new link reference.
		for($i=0; $i<26; $i++){
			$newRef = format_id($userId).'-'.strtoupper(chr(97 + mt_rand(0, 25)));
			if(!in_array($newRef, $linkArray)) {
				array_push($linkArray, $newRef);
				break;
			}
		}
		
		if(empty($links)) $result = $this->_query_reader->run('add_share_link', array('url_id'=>format_id($userId), 'user_id'=>$userId, 'is_primary'=>'Y'));
		if(!empty($newRef)) $result = $this->_query_reader->run('add_share_link', array('url_id'=>$newRef, 'user_id'=>$userId, 'is_primary'=>'N'));
		log_message('debug', '_networks/add_share_link:: [3] result='.$result);
		
		$array = array();
		foreach($linkArray AS $linkId) array_push($array, array('link_id'=>$linkId));
		log_message('debug', '_networks/add_share_link:: [4] array='.json_encode($array));
		
		return (!empty($result) && $result? $array: array());
	}
	
	
	
	
	# Get the links a user can share 
	function links($userId)
	{
		log_message('debug', '_networks/links');
		log_message('debug', '_networks/links:: [1] userId='.$userId);
		
		$result = $this->_query_reader->get_list('get_share_links', array('user_id'=>$userId));
		log_message('debug', '_networks/links:: [2] result='.json_encode($result));
		
		return $result;
	}
	
	
	
	
	# add a network referral code
	function add_referral_code($userId, $newCode)
	{
		log_message('debug', '_networks/add_referral_code');
		log_message('debug', '_networks/add_referral_code:: [1] userId='.$userId.' newCode='.$newCode);
		
		# if the default user referral code is not yet saved save it first
		if($this->_query_reader->get_count('get_referral_code', array('referral_code'=>format_id($userId) )) == 0) {
			$result = $this->_query_reader->run('add_share_link', array('url_id'=>format_id($userId), 'user_id'=>$userId, 'is_primary'=>'Y'));
		}
		# then add the new referral code 
		$result = $this->_query_reader->run('add_referral_code', array('user_id'=>$userId, 'referral_code'=>$newCode));
		log_message('debug', '_networks/add_referral_code:: [2] result='.$result);
		
		return array('boolean'=>$result);
	}
	
	
	
	
	# check whether a referral code is valid
	function check_referral_code($userId, $checkCode)
	{
		log_message('debug', '_networks/check_referral_code');
		log_message('debug', '_networks/check_referral_code:: [1] userId='.$userId.' checkCode='.$checkCode);
		
		$check = $this->_query_reader->get_count('get_referral_code', array('referral_code'=>$checkCode));
		log_message('debug', '_networks/check_referral_code:: [2] check='.$check);
		
		return array('boolean'=>!($check > 0));
	}
	
	
	
	
	# get the referrer id given their code
	function get_referrer_by_code($referralCode)
	{
		log_message('debug', '_networks/get_referrer_by_code');
		log_message('debug', '_networks/get_referrer_by_code:: [1] referralCode='.$referralCode);
		
		$referralRecord = $this->_query_reader->get_row_as_array('get_referral_code', array('referral_code'=>$referralCode));
		log_message('debug', '_networks/get_referrer_by_code:: [2] referralRecord='.json_encode($referralRecord));
		
		# is this a recorded code or a default code
		if(!empty($referralRecord['_user_id'])){
			log_message('debug', '_networks/get_referrer_by_code:: [3] _user_id='.$referralRecord['_user_id']);
			return array('referrer_id'=>$referralRecord['_user_id']);
		}
		# default code
		else if($this->_query_reader->get_count('get_users_in_id_list', array('id_list'=>extract_id($referralCode) )) > 0) {
			log_message('debug', '_networks/get_referrer_by_code:: [4] get_users_in_id_list > 0');
			return array('referrer_id'=>extract_id($referralCode));
		}
		# invalid code
		else return array('referrer_id'=>'');
	}
}
