<?php
/**
 * This class manages system data changes.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/05/2015
 */
class _change extends CI_Model
{
	
	# Add a flag to the change
	function add_change_flag($dataId, $userId, $details=array())
	{
		log_message('debug', '_change/add_change_flag');
		log_message('debug', '_change/add_change_flag:: [1] dataId='.$dataId.' userId='.$userId.' details='.json_encode($details));
		
		$result = FALSE;
		
		# Create a new flag if it was not simply selected
		if(!empty($details['displayed'])){
			if(empty($details['hidden'])) $flagId = $this->_query_reader->add_data('add_new_flag', array('name'=>htmlentities($details['displayed'], ENT_QUOTES), 'type'=>'user_defined'));
			else $flagId = $details['hidden'];
		
			log_message('debug', '_change/add_change_flag:: [2] flagId='.$flagId);
			if(!empty($flagId)){
				$changeFlagId = $this->_query_reader->add_data('add_change_flags', array('change_id'=>$dataId, 'flag_ids'=>"'".$flagId."'", 'user_id'=>$userId, 'notes'=>''));
				$result = !empty($changeFlagId);
				log_message('debug', '_change/add_change_flag:: [3] changeFlagId='.$changeFlagId);
				# Log the flag addition
				$flag = $this->_query_reader->get_row_as_array('get_flag_by_descriptor_change', array('change_flag_id'=>$changeFlagId));
				log_message('debug', '_change/add_change_flag:: [4] flag='.json_encode($flag));
				$this->_logger->add_event(array('user_id'=>$userId, 'activity_code'=>'change_flag_added', 'result'=>($result? 'SUCCESS': 'FAIL'), 'log_details'=>"flag=".$flag['flag_name']."|descriptor_id=".$flag['descriptor_id']."|change=".remove_tags($flag['change_name'])."|" ));
			}
		}
		log_message('debug', '_change/add_change_flag:: [4] result='.$result);
		return $result;
	}
	
	
	
	
	
	# Remove a flag from the change
	function remove_change_flag($dataId, $userId, $details=array())
	{
		log_message('debug', '_change/remove_change_flag');
		log_message('debug', '_change/remove_change_flag:: [1] dataId='.$dataId.' userId='.$userId.' details='.json_encode($details));
		
		$flag = $this->_query_reader->get_row_as_array('get_flag_by_descriptor_change', array('change_flag_id'=>$dataId));
		log_message('debug', '_change/remove_change_flag:: [2] flag='.json_encode($flag));
		
		$result = $this->_query_reader->run('delete_change_flag', array('change_flag_id'=>$dataId));
		log_message('debug', '_change/remove_change_flag:: [3] flag='.json_encode($result));
		
		# Log the flag removal
		$this->_logger->add_event(array('user_id'=>$userId, 'activity_code'=>'change_flag_removed', 'result'=>($result? 'SUCCESS': 'FAIL'), 'log_details'=>"flag=".$flag['flag_name']."|descriptor_id=".$flag['descriptor_id']."|change=".remove_tags($flag['change_name'])."|" ));
		
		return $result;
	}
	
	
	
	
	
	
	# Get change flags
	function get_change_flags($scope='all', $details=array())
	{
		log_message('debug', '_change/get_change_flags');
		log_message('debug', '_change/get_change_flags:: [1] scope='.$scope.' details='.json_encode($details));
		
		# Get only flags for a given change
		if($scope == 'by_change'){
			$result = $this->_query_reader->get_list('get_descriptor_change_flags', array(
					'change_id'=>$details['data_id'], 
					'phrase'=>(!empty($details['phrase'])? '%'.str_replace(' ','%',$details['phrase']).'%': '%'), 
					'limit_text'=>' LIMIT '.$details['offset'].','.$details['limit'].' '
			));
			log_message('debug', '_change/get_change_flags:: [2] result='.json_encode($result));
			return $result;
		}
		# The caller does not care about the change
		else if($scope == 'all'){
			$result = $this->_query_reader->get_list('get_all_change_flags', array(
					'phrase'=>(!empty($details['phrase'])? '%'.str_replace(' ','%',$details['phrase']).'%': '%'), 
					'limit_text'=>' LIMIT '.$details['offset'].','.$details['limit'].' '
			));
			log_message('debug', '_change/get_change_flags:: [3] result='.json_encode($result));
			return $result;
		}
	}
	
	
	
	
	
	# Get a list of changes for a descriptor
	function get_list($details)
	{
		log_message('debug', '_change/get_change_flags');
		log_message('debug', '_change/get_change_flags:: [1] details='.json_encode($details));
		
		$result = $this->_query_reader->get_list('get_descriptor_change_list', array(
					'descriptor_id'=>$details['data_id'],
					'phrase'=>(!empty($details['phrase'])? '%'.str_replace(' ','%',$details['phrase']).'%': '%'), 
					'limit_text'=>' LIMIT '.$details['offset'].','.$details['limit'].' ', 
					'user_id'=>$details['user_id']
			));
		
		log_message('debug', '_change/get_change_flags:: [2] result='.json_encode($result));
		return $result;
	}
	
	
	
	
	
	
	# Add a change record 
	function add($details)
	{
		log_message('debug', '_change/add');
		log_message('debug', '_change/add:: [1] details='.json_encode($details));
		
		$newChangeId = $this->_query_reader->add_data('add_change_record', $details); 
		log_message('debug', '_change/add:: [2] newChangeId='.$newChangeId);
		
		# Are we adding a flag record?
		$result = (!empty($newChangeId) && !empty($details['flag_details']))? $this->_query_reader->run('add_change_flags', array('change_id'=>$newChangeId, 'user_id'=>$details['user_id'], 'flag_ids'=>"'".implode("','", $details['flag_details']['flags'])."'", 'notes'=>(!empty($details['flag_details']['notes'])? htmlentities($details['flag_details']['notes'], ENT_QUOTES): '') )) : TRUE;
		log_message('debug', '_change/add:: [3] result='.json_encode($result));
		
		$return = !empty($newChangeId) && $result? $this->_query_reader->run('add_change_log', array_merge($details, array('change_id'=>$newChangeId))): FALSE;
		log_message('debug', '_change/add:: [4] return='.json_encode($return));
		
		return $return;
	}

	
}

