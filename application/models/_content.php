<?php
/**
 * This class generates and formats content details. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 08/24/2015
 */
class _content extends CI_Model
{
	
	# Get tag details
	function tags($listType)
	{
		log_message('debug', '_content/tags');
		log_message('debug', '_content/tags:: [1] listType='.$listType);
		return $this->_query_reader->get_list('get_content_list', array('list_type'=>$listType, 'content_type'=>'tags' ));
	}
	
	
	
	
	# Get actions details
	function actions($listType)
	{
		log_message('debug', '_content/actions');
		log_message('debug', '_content/actions:: [1] listType='.$listType);
		return $this->_query_reader->get_list('get_content_list', array('list_type'=>$listType, 'content_type'=>'actions' ));
	}
	
}


