<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining score information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/10/2015
 */
class Score extends REST_Controller
{

	#Constructor to set some default values at class load
	public function __construct()
    {
        parent::__construct();
        $this->load->model('_score');
	}


	# Get the store score
	public function store_get()
  	{
  		log_message('debug', 'Score/store_get');

		$result = array();

		if(!empty($this->get('userId')) && !empty($this->get('storeId'))){
			$storeId = extract_id($this->get('storeId'));
			$userId = extract_id($this->get('userId'));

			log_message('debug', 'Score/store_get:: [1] storeId='.$storeId.' userId='.$userId);

			# The store score for the user
			$score = $this->_score->get_store_score($storeId, $userId);
			log_message('debug', 'Score/store_get:: [1] score='.$score);

			# The level of the score
			$level = $this->_score->get_score_level($score);
			log_message('debug', 'Score/store_get:: [1] level='.json_encode($level));

			#Include the score breakdown?
			$breakdown = (!empty($this->get('includeScoreBreakdown')) && $this->get('includeScoreBreakdown') == 'TRUE')? $this->_score->get_store_score_breakdown($storeId, $userId): array();
			log_message('debug', 'Score/store_get:: [1] breakdown='.json_encode($breakdown));

			$result = array(
					'storeScore'=>$score,
					'scoreLevel'=>!empty($level['level'])? $level['level']: '0',
					'pointsToNextLevel'=>!empty($level['points_to_next_level'])? $level['points_to_next_level']: '0',
					'scoreBreakdown'=>$breakdown
					);
		}

		log_message('debug', 'Score/store_get:: [2] result='.json_encode($result));
		$this->response($result);
	}






	# Get the clout score
	public function clout_get()
  	{
  		log_message('debug', 'Score/clout_get');

		$result = array();

		if(!empty($this->get('userId'))){
			$userId = extract_id($this->get('userId'));
			log_message('debug', 'Score/clout_get:: [1] userId='.$userId);

			# The clout score for the user
			$score = $this->_score->get_clout_score($userId);
			log_message('debug', 'Score/clout_get:: [1] score='.$score);

			# The level of the score
			$level = !empty($score['clout_score'])? $this->_score->get_score_level($score['clout_score']): array();
			log_message('debug', 'Score/clout_get:: [1] level='.$level);

			$result = array(
					'cloutScore'=>!empty($score['clout_score'])?$score['clout_score']:'0',
					'scoreLevel'=>!empty($level['level'])? $level['level']: '0'
					);
		}

		log_message('debug', 'Score/clout_get:: [2] result='.json_encode($result));
		$this->response($result);
	}






	# Get the score settings
	public function settings_get()
  	{
  		log_message('debug', 'Score/setting_get');

		$result = $this->_score->get_settings(
			$this->get('type'),
			$this->get('use'),
			extract_id($this->get('storeId'))
		);

		log_message('debug', 'Score/setting_get:: [1] result='.json_encode($result));
		$this->response($result);
	}






	# Get the score details
	public function details_get()
  	{
  		log_message('debug', 'Score/details_get');

		$result = $this->_score->get_details(
			$this->get('type'),
			$this->get('codes'),
			extract_id($this->get('userId')),
			(!empty($this->get('storeId'))? extract_id($this->get('storeId')): '')
		);

		log_message('debug', 'Score/details_get:: [1] result='.json_encode($result));
		$this->response($result);
	}





}


/* End of controller file */
