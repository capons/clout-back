<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH."libraries/REST_Controller.php";

/**
 * This class controls obtaining actions related to reservations.
 *
 * @author Rebecca Lin <rebeccal@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 05/24/2016
 */
class Reservation extends REST_Controller
{
	# Constructor to set some default values at class load
	public function __construct()
	{
		parent::__construct();
		$this->load->model('_reservation');
		$this->load->model('_query_reader');
	}

	# Get a list of reservations
	public function list_get()
	{
		log_message('debug', 'Reservation/list_get');
		log_message('debug', 'Reservation/list_get:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

		$result = $this->_reservation->get_reservation_list(
			extract_id($this->get('userId')),
			(!empty($this->get('offset'))? $this->get('offset'): '0'),
			(!empty($this->get('limit'))? $this->get('limit'): '5'),
			$this->get('filters')
			);

		log_message('debug', 'Reservation/list_get:: [3] result='.json_encode($result));
		$this->response($result);
	}

 	# Get reservation by id
	public function list_by_id_get()
	{
		log_message('debug', 'Reservation/list_by_id_get');
		log_message('debug', 'Reservation/list_by_id_get:: [1] userId=' . (!empty($this->get('userId'))? extract_id($this->get('userId')): ''));

		$result = $this->_reservation->list_by_id($this->get('reservationId'));

		log_message('debug', 'Reservation/list_by_id_get:: [2] result='.json_encode($result));
		$this->response($result);
	}

	# Cancel reservation and send out message to user and store owner
	public function cancel_post()
	{
		log_message('debug', 'Reservation/cancel_post');
		log_message('debug', 'Reservation/cancel_post:: [1] userId='. (!empty($this->post('userId'))? extract_id($this->post('userId')): ''));
		$userId = (!empty($this->post('userId'))? extract_id($this->post('userId')): '');

		$result = $this->_reservation->update_status(
			$userId,
			$this->post('reservationStatus'),
			$this->post('status'),
			$this->post('promotionId'),
			$this->post('storeId')
		);

		log_message('debug', 'Reservation/cancel_post:: [6] result='.json_encode($result));
		$this->response($result);

	}

	# Update reservation detail and send out message to user and store owner
	public function update_post()
	{
		log_message('debug', 'Reservation/update_post');
		log_message('debug', 'Reservation/update_post:: [1] userId='. (!empty($this->post('userId'))? extract_id($this->post('userId')): ''));
		$userId = (!empty($this->post('userId'))? extract_id($this->post('userId')): '');

		$result = $this->_reservation->update_details(
			$userId,
			$this->post('reservationId'),
			$this->post('details'),
			$this->post('storeId')
		);

		log_message('debug', 'Reservation/update_post:: [5] result='.json_encode($result));
		$this->response($result);

	}

	# Make a new reservation and send out message to user and store owner
	public function add_post()
	{
		log_message('debug', 'Reservation/add_post');
		log_message('debug', 'Reservation/add_post:: [1] userId='. (!empty($this->post('userId'))? extract_id($this->post('userId')): ''));

		# Record reservation to database
		$result = $this->_reservation->add(
			(!empty($this->post('userId'))? extract_id($this->post('userId')): ''),
			$this->post('details'),
			$this->post('baseUrl')
		);

		log_message('debug', 'Reservation/add_post:: [3] result='.json_encode($result));
		$this->response($result);
	}
}

