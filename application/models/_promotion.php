<?php
/**
 * This class generates and formats promotion details.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/21/2015
 */
class _promotion extends CI_Model
{

    # Get promotions list by user, store and minimum score (optional)
    function get_promotion_list($userId, $storeId, $minScore = 0, $curDate) //$curDate remove after pull
    {
        log_message('debug', '_promotion/list');
        log_message('debug', '_promotion/details:: [1] userId=' . $userId . ' storeId=' . $storeId . ' minScore=' . $minScore);


        $params = array();
        $params['owner_type'] = 'store';
        $params['entered_by'] = $userId;
        $params['owner_id'] = $storeId;
        $params['min_score'] = $minScore;
        $promotion_activity = array(); //containes result with promotion blackouts
        $query = $this->_query_reader->get_query_by_code('get_promotion_list', $params);
        $result = $this->_query_reader->read($query, 'get_list');
        log_message('debug', '_promotion/list:: [2] ' . json_encode($result));
        $i = 0; //iteration counter

        foreach ($result as $key => $val) { //add blackout to promotion result
            $p_params['promotion_id'] = $val['id'];
            $p_query = $this->_query_reader->get_query_by_code('get_promotion_blackout', $p_params);
            $p_result = $this->_query_reader->read($p_query, 'get_list'); //promotion blackout list
            log_message('debug', '_promotion_blackouts/list:: [3] ' . json_encode($p_result));
            //1 step => check blackout of promotion to check active promotion or no
            if (!empty($p_result)) { //if promotion have blackout
                //if promotion "perk"
                if ($val['promotion_type'] == 'perk') { // if promotion type == 'perk'
                    foreach ($p_result as $k => $v) {
                        if ($v['display_date'] !== '0000-00-00') { //if date don't select
                            $start = strtotime($v['display_date'] . ' ' . $v['start_time']);
                            $end = strtotime($v['display_date'] . ' ' . $v['end_time']);
                            $current = strtotime($curDate);
                            if ($current > $start && $current < $end) {
                                $promotion_activity[$i][] = false; //false => if promotion not active in blackout time
                            } else {
                                $promotion_activity[$i][] = true;
                            }
                        } else {
                            //if blackout weekday == current day of the week => promotion disable
                            if (date('l', strtotime($curDate)) . 's' == $v['week_day']) {
                                $promotion_activity[$i][] = false; //false => if promotion not active in blackout time
                            } else {
                                $promotion_activity[$i][] = true;
                            }
                        }

                    }
                }
                //if promotion "cashback"
                if ($val['promotion_type'] == 'cashback') { // if promotion type == 'cashback'
                    foreach ($p_result as $k => $v) {
                        if ($v['display_date'] !== '0000-00-00') { //if date don't select
                            $date = strtotime(date("Y-m-d", strtotime($v['display_date'])));
                            $current = strtotime(date("Y-m-d", strtotime($curDate)));

                            if ($date == $current) {
                                $promotion_activity[$i][] = false; //false => if promotion not active in blackout time
                            } else {
                                $promotion_activity[$i][] = true;
                            }
                        } else {
                            //if blackout weekday == current day of the week => promotion disable
                            if (date('l', strtotime($curDate)) . 's' == $v['week_day']) {
                                $promotion_activity[$i][] = false; //false => if promotion not active in blackout time
                            } else {
                                $promotion_activity[$i][] = true;
                            }
                        }
                    }
                }
                //add blackout result to promotion data respons
                $result[$key]['blackouts'] = $p_result;
                //2 step -> check in promotion table -> active promotion or no!
                $p_start_d = strtotime($val['start_date']);
                $p_end_d = strtotime($val['end_date']);
                $p_current_d = strtotime($curDate);
                //promotion only active from start date to end date
                $promotion_activity[$i][] = true;
                if ($p_current_d < $p_start_d) {
                    $promotion_activity[$i][] = false; // promotion not active
                }
                if ($p_current_d > $p_end_d) {
                    $promotion_activity[$i][] = false; // promotion not active
                }
                if (in_array(false, $promotion_activity[$i])) { // if in array $promotion_activity[$i] isset false => promotion not active
                    $result[$key]['blackout_activity'] = false; //promotion activity type
                } else { //if promotion have no blackouts
                    $result[$key]['blackout_activity'] = true;  //promotion activity type
                }


            } else { //if promotion don't have blackouts
                //2 step -> check in promotion data -> active promotion or no!
                $p_start_d = strtotime($val['start_date']);
                $p_end_d = strtotime($val['end_date']);
                $p_current_d = strtotime($curDate);
                //promotion only active from start date to end date
                $promotion_activity[$i][] = true;
                if ($p_current_d < $p_start_d) {
                    $promotion_activity[$i][] = false; // promotion not active
                }
                if ($p_current_d > $p_end_d) {
                    $promotion_activity[$i][] = false; // promotion not active
                }
                $result[$key]['blackouts'] = false;
                if (in_array(false, $promotion_activity[$i])) { // if in array $promotion_activity[$i] isset false => promotion not active
                    $result[$key]['blackout_activity'] = false; //promotion activity type
                } else { //if promotion have no blackouts
                    $result[$key]['blackout_activity'] = true;  //promotion activity type
                }
            }
            $i++; //count
        }
        return $result;
    }


    # Add promotion
    function add_promotion($fields)
    {
        log_message('debug', '_promotion/add');
        log_message('debug', '_promotion/add:: [1] userId=' . $fields['userId'] . ' promotionType=' . $fields['promotionType'] . ' startDate=' . $fields['startDate'] . ' endDate=' . $fields['endDate'] . ' amount=' . $fields['amount'] . ' endDate=' . $fields['endDate'] . ' storeId=' . $fields['storeId'] . ' name=' . $fields['name'] . ' endScore=' . ((isset($fields['endScore'])) ? $fields['endScore'] : 0) . ' startScore=' . ((isset($fields['startScore'])) ? $fields['startScore'] : 0) . 'blackouts' . json_encode($fields['blackouts']));
        if ($fields['promotionType'] == 'cashback') {
            $cash_back_percentage = only_numbers($fields['cash_back_percentage']);
            $params['cash_back_percentage'] = $cash_back_percentage;
        } elseif ($fields['promotionType'] == 'perk') {
            $params['cash_back_percentage'] = NULL;
        }
        $params['promotion_type'] = $fields['promotionType'];
        $params['start_date'] = date("Y-m-d", strtotime($fields['startDate']));//format date
        $params['end_date'] = date("Y-m-d", strtotime($fields['endDate'])); //format date
        $params['amount'] = $fields['amount'];
        $params['owner_id'] = $fields['storeId'];
        $params['entered_by'] = $fields['userId'];
        $params['owner_type'] = 'store';
        $params['end_score'] = (isset($fields['endScore'])) ? $fields['endScore'] : 0;
        $params['start_score'] = (isset($fields['startScore'])) ? $fields['startScore'] : 0;
        $params['name'] = htmlentities($fields['name'], ENT_QUOTES);
        $params['description'] = htmlentities($fields['description'], ENT_QUOTES);
        //$params['category_id'] = (isset($fields['categoryId']))?$fields['categoryId']:0;
        //$params['cash_back_percentage'] = $fields['cash_back_percentage'];
        //$params['custom_category_id'] = (isset($fields['categoryId']))?$fields['categoryId']:0;
        $params['blackouts'] = $fields['blackouts'];
        $check_duplicates = $this->_query_reader->get_count('check_duplicat_promotion', $params);
        if ($check_duplicates == 0) {

            $result = $this->_query_reader->add_data('add_promotion', $params);
            if ($result) {
                //if promotion save -> send email
                $users_ids = explode(',',$fields['storeId']); //send to => Shopper who qualifies for that promotion
                //params to send message
                $data = array(
                    'userId' => $fields['userId'],
                    'message'=> array (
                        'useTemplate' => 'N',
                        'templateId' => '',
                        'senderType'=>'user',
                        'sendToType'=>'filter',
                        'sendTo'=>'select_user',
                        'select_user' => $users_ids,
                        'subject'=> 'Add new promotion',
                        'body'=> 'Promotion add successfully'.' '.$result,
                        'sms'=> 'Promotion add successfully'.' '.$result,
                        'attachment'=>'',
                        'templateAttachment'=>'',
                        'saveTemplate'=>'N',
                        'saveTemplateName'=>'N',
                        'sendDate'=>'10/26/2016 12:20 pm',
                        'methods'=> array (
                            'system',
                            'email',
                            'sms'
                        )
                    )
                );
                //send message
                $message = run_on_api(BASE_URL.'message/send',$data,'POST');
                if($message['boolean'] === true){
                    log_message('debug', '_promotion/add_promotion email sending');
                    log_message('debug', '_promotion/add_promotion  :: [4] send email to shopper '.json_encode($users_ids).' successfully');
                } else {
                    log_message('debug', '_promotion/add_promotion email sending');
                    log_message('debug', '_promotion/add_promotion  :: [4] send email to "select_user"=> error');
                }

                if (!empty($params['blackouts'])) { //save promotion blackouts
                    foreach ($params['blackouts'] as $key => $val) {
                        $b_params['promotion_id'] = "$result";
                        $b_params['week_day'] = ucfirst(str_replace(":", "", $val['week_day']) . 's'); //uppercase first latter | remove : from string | add "s" to the end!

                        //convert date to need format
                        if (!empty($val['date'])) {
                            $st_date = $val['date'];
                            $exp = explode(',', $st_date);
                            $day = date('d', strtotime($val['send_date'])); // get day of the month
                            $exp[0] = $day . '-' . $exp[0];
                            $blackout_date = implode(",", $exp);
                            //end convert date
                            $b_params['display_date'] = date("Y-m-d", strtotime($blackout_date));
                        } else {
                            //$b_params['display_date'] =strtotime($val['date']);
                            $b_params['display_date'] = ''; //save empty param -> promotion not available in  specific day of the week
                        }

                        if ($fields['promotionType'] == 'perk') {
                            $b_params['start_time'] = $val['start_time'];
                            $b_params['end_time'] = $val['end_time'];
                        } else {
                            $b_params['start_time'] = '';
                            $b_params['end_time'] = '';
                        }
                        $blackout_result = $this->_query_reader->add_data('add_promotion_blackout_schedule', $b_params);
                    }
                    //blackouts params
                    $r_params['rule_type'] = 'has_blackout_schedule';
                    $r_params['rule_details'] = 'Promotion Blackouts';
                    $r_params['promotion_id'] = "$result";
                    $r_params['type'] = 'Y';
                    $add_promotion_rule = $this->_query_reader->add_data('add_promotion_rule', $r_params); //add promotion rule
                }
                $result = $this->_query_reader->get_row_as_array('get_promotion_by_id', array('id' => $result));
                log_message('debug', '_promotion/add:: [2] result=' . json_encode($result));
                $this->_logger->add_event(array(
                    'user_id' => $fields['userId'],
                    'activity_code' => 'add_promotion',
                    'result' => ($result) ? 'success' : 'fail',
                    'log_details' => 'promotionId = ' . json_encode($result),
                    'uri' => 'promotion/add',
                    'ip_address' => $_SERVER['REMOTE_ADDR']

                ));
            }
            return $result;

        } else {
            return FALSE;
        }


        //return $result;

    }

    #Update promotion
    function update_promotion($fields)
    {

        log_message('debug', '_promotion/update');
        //log_message('debug', '_promotion/update:: [1] startDate=' . $fields['startDate']. ' endDate='.$fields['endDate']. ' amount='.$fields['amount'].' name='.$fields['name']. ' endScore='.$fields['endScore']. ' startScore='.$fields['startScore']);

        $params = array();

        $params['start_date'] = $fields['startDate'];
        $params['end_date'] = $fields['endDate'];
        $params['amount'] = $fields['amount'];
        $params['end_score'] = $fields['endScore'];
        $params['start_score'] = $fields['startScore'];

        $params['name'] = $fields['name'];

        $params['id'] = $fields['id'];
        if ($fields['name'] == 'Cash Back') {
            $cash_back_percentage = only_numbers($fields['cashBack']);
            $params['cash_back_percentage'] = $cash_back_percentage;
        } else {
            $params['cash_back_percentage'] = NULL;
        }

        //$params['description'] = $fields['description'];
        $result = $this->_query_reader->run('update_promotion', $params);

        if ($result) {
            $result = $this->_query_reader->get_row_as_array('get_promotion_by_id', array('id' => $fields['id']));
            log_message('debug', '_promotion/update:: [2] ' . json_encode($result));

            $this->_logger->add_event(array(
                'user_id' => $fields['userId'],
                'activity_code' => 'update_promotion',
                'result' => ($result) ? 'success' : 'fail',
                'log_details' => 'promotionId = ' . json_encode($result),
                'uri' => 'promotion/update',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ));
        }
        return $result;
    }

    function delete_promotion($id)
    {
        $params = array();

        $params['id'] = $id;
        $query = $this->_query_reader->run('delete_promotion', $params);
        if ($query) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    # Get promotion list by level id
    function get_promotions_by_level($userId, $storeId, $levelId)
    {
        log_message('debug', '_promotion/get_promotions_by_level');
        log_message('debug', '_promotion/get_promotions_by_level:: userId=' . $userId . ' storeId=' . $storeId . ' levelId=' . $levelId);

        $params = array();
        $params['owner_type'] = 'store';
        $params['entered_by'] = $userId;
        $params['owner_id'] = $storeId;

        $params['level_id'] = $levelId;

        $query = $this->_query_reader->get_query_by_code('get_promotions_by_level', $params);

        $result = $this->_query_reader->read($query, 'get_list');

        log_message('debug', '_promotion/get_promotions_by_level:: [2] result=' . json_encode($result));

        return $result;

    }

    # Update promotion status
    //$storeOwnerId,
    function update_promotion_status($promotionId, $userId, $newActionStatus, $storeId)
    {

        log_message('debug', '_promotion/update_status');
        //storeOwnerId='.$storeOwnerId.
        log_message('debug', '_promotion/update_status:: [1] promotionId=' . $promotionId . ' userId=' . $userId . '   new_action_status=' . $newActionStatus);

        $params = array();
        /*
        if ($userId) {
            $params['owner_type'] = 'person';
            $params['owner_id'] = $userId;
        } else if ($storeOwnerId) {
            $params['owner_type'] = 'store';
            $params['owner_id'] = $userId;
        } else return array();
        */
        $params['owner_type'] = 'store';
        $params['owner_id'] = $userId;
        $params['promotion_id'] = $promotionId;

        switch ($newActionStatus) {
            case 'cancel':
                $status = 'deleted';
                break;
            case 'publish':
                $status = 'active';
                break;
            case 'pause':
                $status = 'inactive';
                break;
            default:
                return false;
        }
        $params['status'] = $status;




        $result = $this->_query_reader->run('update_promotion_status', $params, true);
        //if update promotion status is "ACTIVE" and Promotion status update == true => send mail to all qualifies user
        if($result == true && $params['status'] == 'active') {
                $params_m['user_id'] = $userId;
                $params_m['store_id'] = $storeId;
                $params_m['prom_id'] = $promotionId;
                $query = $this->_query_reader->get_query_by_code('user_promotion_qualifies', $params_m);
                $qualifi_user_ids = $this->_query_reader->read($query, 'get_list');
                //qualifi user id container
                $ids = '';
                foreach ($qualifi_user_ids as $key=>$val){
                    if($val['qualifies'] == 'qualify') {
                        $ids .= $val['user_id'] . ',';
                    }
                }
                $ids = rtrim($ids,',');

                $users_ids = explode(',',$ids); //send to => Shopper who qualifies for that promotion
                //params to send message
                $data = array(
                    'userId' => $params_m['user_id'],
                    'message'=> array (
                        'useTemplate' => 'N',
                        'templateId' => '',
                        'senderType'=>'user',
                        'sendToType'=>'filter',
                        'sendTo'=>'select_user',
                        'select_user' => $users_ids,
                        'subject'=> 'You qualifies to promotion',
                        'body'=> 'This proposal will interest you '.' '.$promotionId,
                        'sms'=> 'this proposal will interest you '.' '.$promotionId,
                        'attachment'=>'',
                        'templateAttachment'=>'',
                        'saveTemplate'=>'N',
                        'saveTemplateName'=>'N',
                        'sendDate'=>'10/26/2016 12:20 pm',
                        'methods'=> array (
                            'system',
                            'email',
                            'sms'
                        )
                    )
                );
                //send message
                $message = run_on_api(BASE_URL.'message/send',$data,'POST');
                if($message['boolean'] === true){
                    log_message('debug', '_promotion/update_promotion_status email sending');
                    log_message('debug', '_promotion/update_promotion_status  :: [7] send email to shoppers '.json_encode($users_ids).' successfully');
                } else {
                    log_message('debug', '_promotion/update_promotion_status email sending');
                    log_message('debug', '_promotion/add_promotion  :: [4] send email to shoppers '.json_encode($users_ids).' => error');
                }
                return $result;
            } else {
            $this->_logger->add_event(array(
                'user_id' => $userId,
                'activity_code' => 'promotion_status_change',
                'result' => ($result) ? 'success' : 'fail',
                'log_details' => 'promotionId = ' . $promotionId . ' new_action_status = ' . $newActionStatus,
                'uri' => 'promotion/status',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ));

            return $result;
        }
    }

    # Add custom category
    function add_category($userId, $storeOwnerId, $categoryType, $categoryId, $subCategoryId)
    {
        log_message('debug', '_promotion/create_category');
        log_message('debug', '_promotion/create_category:: [1] userId=' . $userId . ' storeOwnerId=' . $storeOwnerId . ' categoryType=' . $categoryType . ' categoryId=' . $categoryId . ' sub_category_id=' . $subCategoryId);


//		if (empty($categoryId)) {
//			return array(
//				'error' => 'Category id was not specified'
//			);
//		}

        //DEFINE CATEGORY AND SUBCATEGORY

        if ($categoryType == 'competitor') {
            $competitors = $this->_query_reader->get_list('get_store_competitors', array(
                'store_id' => $storeOwnerId
            ));

            $competitorId = NULL;
            foreach ($competitors as $competitor) {
                if ($competitor['id'] == $categoryId) {
                    $competitorId = $competitor['id'];
                    $competitorStore = $competitor['competitor_id'];
                    break;
                }
            }

            if (!$competitorId) {
                return array(
                    'error' => 'Competitor not found'
                );
            }
            $storeSubCaterory = $this->_query_reader->get_row_as_array('get_store_sub_categories', array('store_id' => $competitorStore));

            if ($storeSubCaterory) {
                $categoryId = $storeSubCaterory['_category_id'];
                $subCategoryId = $storeSubCaterory['_sub_category_id'];
            }
        } else if ($categoryType == 'sub_category') {

        } else if ($categoryType == 'category') {

        } else {
            return array(
                'error' => 'wrong category type'
            );
        }


        //DEFINE CATEGORY LABEL
        if ($subCategoryId) {
            $subCategory = $this->get_category($categoryId, $subCategoryId);
            $subCategoryName = $subCategory['name'];
        }

        $category = $this->get_category($categoryId);
        $categoryName = $category['name'];

        $params = array();
        $params['user_id'] = $userId;
        $params['category_id'] = "$categoryId";
        $params['status'] = 'active';
        if ($storeOwnerId) {
            $params['store_owner_id'] = $storeOwnerId;
        } else {
            $params['store_owner_id'] = NULL;
        }

        //CREATE PARENT CUSTOM CATEGORY IF NOT EXISTS
        if (($categoryType != 'category') && ($subCategoryId) && (!($this->check_parent_exists($userId, $storeOwnerId, $categoryId)))) {
            $params['category_type'] = 'category';

            $params['category_label'] = $categoryName;

            $params['sub_category_id'] = $subCategoryId;


            $result = $this->_query_reader->add_data('add_custom_category', $params);

            if ($result) {
                $id = $result;
                if ($storeOwnerId) {
                    $query = $this->_query_reader->get_query_by_code('get_custom_levels_with_store', array('user_id' => $userId, 'store_owner_id' => $storeOwnerId));
                } else {
                    $query = $this->_query_reader->get_query_by_code('get_custom_levels', array('user_id' => $userId));
                }

                $levels = $this->_query_reader->read($query, 'get_list');
                foreach ($levels as $level) {
                    $params = array(
                        'category_id' => $id,
                        'level_id' => $level['id'],
                        'amount' => 0
                    );
                    $result = $this->_query_reader->add_data('add_category_level_connection', $params);
                }
            }
        }


        if ($subCategoryId) {
            $params['category_label'] = $subCategoryName;
        } else {
            if ($categoryId == 0) {
                $params['category_label'] = 'Total Spendings';
            } else {
                $params['category_label'] = $categoryName;
            }
        }
        if (empty($subCategoryId)) {
            $params['sub_category_id'] = 'NULL';
        } else {
            $params['sub_category_id'] = "$subCategoryId";
        }

        //$params['sub_category_id'] = $subCategoryId;


        $params['category_type'] = $categoryType;


        if ($this->check_category_exists($userId, $categoryId, $subCategoryId)) {
            if ($categoryId == 0) { //add to custom category / categoty_id == NULL
                $params['category_id'] = 'NULL';
            }
            $result = $this->_query_reader->add_data('add_custom_category', $params);

        } else {

            $result = FALSE;

        }


        //CREATE CUSTOM LEVELS
        if ($result) {
            $id = $result;
            if ($storeOwnerId) {
                $query = $this->_query_reader->get_query_by_code('get_custom_levels_with_store', array('user_id' => $userId, 'store_owner_id' => $storeOwnerId));
            } else {
                $query = $this->_query_reader->get_query_by_code('get_custom_levels', array('user_id' => $userId));
            }

            $levels = $this->_query_reader->read($query, 'get_list');
            foreach ($levels as $level) {
                $params = array(
                    'category_id' => $id,
                    'level_id' => $level['id'],
                    'amount' => 0
                );
                $result = $this->_query_reader->add_data('add_category_level_connection', $params);
            }
        } else {
            return array(
                'error' => $result
            );
        }

        $this->_query_reader->get_row_as_array('get_custom_category_by_id', array('id' => $id));

        $result = $this->_query_reader->get_row_as_array('get_custom_category_by_id', array('id' => $id));

        log_message('debug', '_promotion/create_category:: [2] category=' . json_encode($result));

        $this->_logger->add_event(array(
            'user_id' => $userId,
            'activity_code' => 'add_category',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'category_id=' . $id,
            'uri' => 'promotion/category_create',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return $result;
    }

    # Get custom categories list
    function get_categories_list($userId, $storeOwnerId)
    {

        log_message('debug', '_promotion/get_categories_list');
        log_message('debug', '_promotion/get_categories_list:: [1] userId=' . $userId . ' storeOwnerId=' . $storeOwnerId);

        if ($storeOwnerId) {
            $query = $this->_query_reader->get_query_by_code('get_custom_categories_with_store', array(
                'user_id' => $userId,
                'store_owner_id' => $storeOwnerId
            ));
        } else {
            $query = $this->_query_reader->get_query_by_code('get_custom_categories', array(
                'user_id' => $userId
            ));
        }


        $result = array();
        $categories = $this->_query_reader->read($query, 'get_list');

        if (empty($categories)) { //add default category
            if ($storeOwnerId) {

                $add_defaul_cat = $this->add_category($userId, $storeOwnerId, 'category', 0, '');
                $query = $this->_query_reader->get_query_by_code('get_custom_categories_with_store', array(
                    'user_id' => $userId,
                    'store_owner_id' => $storeOwnerId
                ));
                $categories = $this->_query_reader->read($query, 'get_list');
            } else {

                $add_defaul_cat = $this->add_category($userId, NULL, 'category', 0, '');
                $query = $this->_query_reader->get_query_by_code('get_custom_categories', array(
                    'user_id' => $userId
                ));
                $categories = $this->_query_reader->read($query, 'get_list');
            }
        }

        $subcategories = array();
        foreach ($categories as $key => $category) {
            if (($category['category_type'] == 'sub_category') || ($category['category_type'] == 'competitor')) {
                $subcategories[] = $category;
                unset($categories[$key]);
            }
        }


        foreach ($categories as $key => $category) {
            $categories[$key]['sub_categories'] = array();
            foreach ($subcategories as $subcategory) {
                if ($subcategory['category_id'] == $category['category_id']) {
                    $categories[$key]['sub_categories'][] = $subcategory;
                }
            }
        }


        $result['categories'] = $categories;
        /*
        if(empty($result['category'])){ //add default category
            if ($storeOwnerId) {
                $add_defaul_cat = $this->add_category($userId, $storeOwnerId, 'category', 11, NULL);

            } else {
                $add_defaul_cat = $this->add_category($userId, NULL, 'category', 11, NULL);
            }
        }
        */


        if ($storeOwnerId) {
            $query = $this->_query_reader->get_query_by_code('get_custom_levels_with_store', array(
                'user_id' => $userId,
                'store_owner_id' => $storeOwnerId
            ));


        } else {
            $query = $this->_query_reader->get_query_by_code('get_custom_levels', array(
                'user_id' => $userId
            ));

        }


        $levels = $this->_query_reader->read($query, 'get_list');
        if (empty($levels)) { //add default level
            if (!isset($add_defaul_cat['error'])) { //check add category or no
                if ($storeOwnerId) {
                    $default_level_name = $this->_query_reader->get_list('get_default_level');
                    foreach ($default_level_name as $val) {
                        $this->add_level($userId, $storeOwnerId, 5, $val['name']);
                    }
                    $query = $this->_query_reader->get_query_by_code('get_custom_levels_with_store', array(
                        'user_id' => $userId,
                        'store_owner_id' => $storeOwnerId
                    ));
                    $levels = $this->_query_reader->read($query, 'get_list');
                } else {
                    $default_level_name = $this->_query_reader->get_list('get_default_level');
                    foreach ($default_level_name as $val) {
                        $this->add_level($userId, NULL, 5, $val['name']);
                    }
                    $query = $this->_query_reader->get_query_by_code('get_custom_levels', array(
                        'user_id' => $userId
                    ));
                    $levels = $this->_query_reader->read($query, 'get_list');
                }
            }

        }


        foreach ($levels as $key => $level) {
            $ammounts = $this->_query_reader->get_list('get_category_level_connection_by_level', array(
                'level_id' => $level['id']
            ));
            $levels[$key]['values'] = $ammounts;
        }


        $result['levels'] = $levels;

        log_message('debug', '_promotion/get_categories_list:: [2] categories=' . json_encode($result));

        return $result;
    }

    # Change value for category-level connection
    function change_value($userId, $connectionId, $value)
    {
        log_message('debug', '_promotion/change_value');
        log_message('debug', '_promotion/change_value:: [1] userId=' . $userId . ' connectionId=' . $connectionId . ' value=' . $value);

        $connection = $this->_query_reader->get_row_as_array('get_category_level_connection_with_users', array(
            'id' => $connectionId
        ));

        if (!$connection) {
            return array('error' => 'Connection does not exists');
        }

        if (($connection['category_user_id'] != $userId) || ($connection['levels_user_id'] != $userId)) {
            return array('error' => 'Access denied');
        }

        $result = $this->_query_reader->run('update_value_category_level_connection', array(
            'id' => $connectionId, 'value' => $value
        ));

        log_message('debug', '_promotion/change_value:: [2] result=' . $result);

        $this->_logger->add_event(array(
            'user_id' => $userId,
            'activity_code' => 'update_level_value',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'connectionId=' . json_encode($connection),
            'uri' => 'promotion/change_value',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return array(
            'success' => $result
        );
    }

    # Add custom level
    function add_level($userId, $storeOwnerId, $levelId, $name)
    {
        log_message('debug', '_promotion/create_level');
        log_message('debug', '_promotion/create_level:: [1] userId=' . $userId . ' storeOwnerId=' . $storeOwnerId . ' levelId=' . $levelId . ' name=' . $name);

        $params = array();
        $params['user_id'] = $userId;
        $params['store_owner_id'] = $storeOwnerId;
        $params['level_id'] = $levelId;
        $params['name'] = $name;
        $params['status'] = 'active';

        $result = $this->_query_reader->add_data('add_custom_level', $params, TRUE);

        if ($result) {
            $id = $result;
            if ($storeOwnerId) {
                $query = $this->_query_reader->get_query_by_code('get_custom_categories_with_store', array('user_id' => $userId, 'store_owner_id' => $storeOwnerId));
            } else {
                $query = $this->_query_reader->get_query_by_code('get_custom_categories', array('user_id' => $userId));
            }
            $categories = $this->_query_reader->read($query, 'get_list');

            foreach ($categories as $category) {
                $params = array(
                    'category_id' => $category['id'],
                    'level_id' => $id,
                    'amount' => 0
                );
                $result = $this->_query_reader->add_data('add_category_level_connection', $params);
            }


            $levels = $this->_query_reader->get_list('get_level_by_id', array('id' => $id));
            foreach ($levels as $key => $level) {
                $ammounts = $this->_query_reader->get_list('get_category_level_connection_by_level', array(
                    'level_id' => $level['id']
                ));
                $levels[$key]['values'] = $ammounts;
            }

            log_message('debug', '_promotion/create_level:: [2] result=' . json_encode($levels));

            $this->_logger->add_event(array(
                'user_id' => $userId,
                'activity_code' => 'add_level',
                'result' => ($result) ? 'success' : 'fail',
                'log_details' => 'level_id=' . $id,
                'uri' => 'promotion/add_level',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ));

            return $levels;
        }

        return $result;

    }

    # Change custom level name
    function update_level_name($id, $name, $userId)
    {
        log_message('debug', '_promotion/update_level_name');
        log_message('debug', '_promotion/update_level_name:: [1] id=' . $id . 'name=' . $name);

        $params = array();
        $params['user_id'] = $userId;
        $params['name'] = $name;
        $params['id'] = $id;

        $result = $this->_query_reader->run('change_custom_level_name', $params, TRUE);

        log_message('debug', '_promotion/update_level_name:: [2] result=' . ($result) ? 'success' : 'fail');

        $this->_logger->add_event(array(
            'user_id' => $userId,
            'activity_code' => 'update_level_name',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'result=' . ($result) ? 'success' : 'fail',
            'uri' => 'promotion/change_level_name',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return $result;
    }

    # Change custom category name
    function update_category_name($id, $categoryLabel, $userId)
    {
        log_message('debug', '_promotion/update_category_name');
        log_message('debug', '_promotion/update_category_name:: [1] id=' . $id . 'categoryLabel=' . $categoryLabel);

        $params = array();
        $params['user_id'] = $userId;
        $params['category_label'] = $categoryLabel;
        $params['id'] = $id;

        $result = $this->_query_reader->run('change_custom_category_name', $params, TRUE);

        log_message('debug', '_promotion/update_category_name:: [2] result=' . ($result) ? 'success' : 'fail');

        $this->_logger->add_event(array(
            'user_id' => $userId,
            'activity_code' => 'update_category_name',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'result=' . ($result) ? 'success' : 'fail',
            'uri' => 'promotion/change_category_name',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return $result;
    }

    # Set custom level status to deleted
    function delete_level($id, $userId, $storeOwnerId)
    {
        log_message('debug', '_promotion/delete_level');
        log_message('debug', '_promotion/delete_level:: [1] id=' . $id . ' userId=' . $userId . ' storeOwnerId=' . $storeOwnerId);

        $level = $this->_query_reader->get_row_as_array('get_custom_level_by_id', array('id' => $id));

        if (!$level) {
            return array('error' => 'Level does not exists');
        }

        if (($level['user_id'] != $userId) || (($storeOwnerId) && ($storeOwnerId != $level['store_owner_id']))) {
            return array('error' => 'Access denied');
        }

        $result = $this->_query_reader->run('delete_connections_by_level', array('level_id' => $id));

        $result = $this->_query_reader->run('delete_custom_level', array('id' => $id));

        log_message('debug', '_promotion/delete_level:: [2] result=' . ($result) ? 'success' : 'fail');

        $this->_logger->add_event(array(
            'user_id' => $userId,
            'activity_code' => 'delete_level',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'result=' . ($result) ? 'success' : 'fail',
            'uri' => 'promotion/delete_level',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return $result;
    }

    # Set custom category status to deleted
    function delete_category($id, $userId, $storeOwnerId)
    {
        log_message('debug', '_promotion/delete_category');
        log_message('debug', '_promotion/delete_category:: [1] id=' . $id . ' userId=' . $userId . ' storeOwnerId=' . $storeOwnerId);

        $level = $this->_query_reader->get_row_as_array('get_custom_category_by_id', array('id' => $id));

        if (!$level) {
            return array('error' => 'Category does not exists');
        }

        if (($level['user_id'] != $userId) || (($storeOwnerId) && ($storeOwnerId != $level['store_owner_id']))) {
            return array('error' => 'Access denied');
        }

        $result = $this->_query_reader->run('delete_connections_by_category', array('category_id' => $id));
        $result = $this->_query_reader->run('delete_custom_category', array('id' => $id));

        log_message('debug', '_promotion/delete_category:: [2] result=' . ($result) ? 'success' : 'fail');

        $this->_logger->add_event(array(
            'user_id' => $userId,
            'activity_code' => 'delete_category',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'result=' . ($result) ? 'success' : 'fail',
            'uri' => 'promotion/delete_category',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return $result;
    }

    #Display extra offer conditions in a mode a user can view
    function details($promotionId, $fields, $userId = '')
    {
        log_message('debug', '_promotion/details');
        log_message('debug', '_promotion/details:: [1] promotionId=' . $promotionId . ' fields=' . json_encode($fields) . ' userId=' . $userId);

        $offer = $this->_query_reader->get_row_as_array('get_promotion_by_id', array('promotion_id' => $promotionId));
        log_message('debug', '_promotion/details:: [2] offer=' . json_encode($offer));

        #Pick the required fields from the returned data
        $result = array();
        foreach ($fields AS $field) {
            $result[$field] = !empty($offer[$field]) ? $offer[$field] : '';
        }
        if (in_array('extra_conditions', $fields)) $result['extra_conditions'] = $this->extra_offer_conditions($promotionId, $userId);
        if (in_array('offer_bar_code', $fields) && !empty($offer['date_entered'])) $result['offer_bar_code'] = $this->bar_code($promotionId, $offer['date_entered']);

        log_message('debug', '_promotion/details:: [2] result=' . json_encode($result));
        return $result;
    }


    # Check if a promotion requires scheduling
    function requires_scheduling($promotionId)
    {
        log_message('debug', '_promotion/requires_scheduling');
        log_message('debug', '_promotion/requires_scheduling:: [1] promotionId=' . $promotionId);
        return $this->does_promotion_have_rule($promotionId, 'requires_scheduling') ? 'Y' : 'N';
    }


    # Generate the offer bar-code
    function bar_code($promotionId, $promotionDate)
    {
        log_message('debug', '_promotion/bar_code');
        log_message('debug', '_promotion/bar_code:: [1] promotionId=' . $promotionId . ' promotionDate=' . $promotionDate);

        $date = date('Ymd-Hi', strtotime($promotionDate)) . '-' . str_pad(strtoupper(dechex($promotionId)), 10, '0', STR_PAD_LEFT);
        log_message('debug', '_promotion/bar_code:: [1] date=' . json_encode($date));

        return $date;
    }


    #Does promotion has a given rule attached to it
    function does_promotion_have_rule($promotionId, $ruleCode)
    {
        log_message('debug', '_promotion/does_promotion_have_rule');
        log_message('debug', '_promotion/does_promotion_have_rule:: [1] promotionId=' . $promotionId . ' ruleCode=' . $ruleCode);

        $rule = $this->_query_reader->get_row_as_array('get_rule_for_promotion', array('promotion_id' => $promotionId, 'rule_type' => $ruleCode));
        log_message('debug', '_promotion/does_promotion_have_rule:: [2] rule=' . json_encode($rule));

        return !empty($rule);
    }


    #Display extra offer conditions in a mode a user can view
    function extra_offer_conditions($promotionId, $userId = '')
    {
        log_message('debug', '_promotion/apply_rules');
        log_message('debug', '_promotion/apply_rules:: [1] promotionId=' . $promotionId . ' userId=' . $userId);

        $display = array();
        #1. Get all active rules of the promotion
        $promotionRules = $this->_query_reader->get_list('get_promotion_rules', array('promotion_id' => $promotionId));
        log_message('debug', '_promotion/apply_rules:: [2] promotionRules=' . json_encode($promotionRules));

        #2. Now format the rule into values readable by a user
        foreach ($promotionRules AS $rule) {
            $amountBreakdown = explode('|', $rule['rule_amount']);
            $valueBreakdown = !empty($amountBreakdown[1]) ? explode('-', $amountBreakdown[1]) : array();

            switch ($rule['rule_type']) {
                case 'schedule_available':
                    array_push($display, "On " . date('l', strtotime($amountBreakdown[0])) . "s at " . date('g:ia', strtotime($valueBreakdown[0])) . " to " . (!empty($valueBreakdown[1]) ? date('g:ia', strtotime($valueBreakdown[1])) : 'Late'));
                    break;

                case 'schedule_blackout':
                    array_push($display, "Except On " . date('l', strtotime($amountBreakdown[0])) . "s at " . date('g:ia', strtotime($valueBreakdown[0])) . " to " . (!empty($valueBreakdown[1]) ? date('g:ia', strtotime($valueBreakdown[1])) : 'Late'));
                    break;

                case 'how_many_uses':
                    array_push($display, "Max " . $amountBreakdown[0] . " uses");
                    break;

                case 'distance_from_location':
                    array_push($display, "Atleast " . $amountBreakdown[0] . " miles from " . $valueBreakdown[0]);
                    break;

                case 'at_the_following_stores':
                    $storeIdList = explode(',', $amountBreakdown[0]);
                    $storeAddress = "";
                    foreach ($storeIdList AS $id) {
                        $store = $this->_query_reader->get_row_as_array('get_store_locations_by_id', array('store_id' => $id, 'user_id' => $userId));
                        $storeAddress .= "<br>" . $store['full_address'];
                    }
                    array_push($display, "At the following stores: " . $storeAddress);
                    break;

                case 'for_new_customers':
                    array_push($display, "New customers");
                    break;

                case 'per_transaction_spending_greater_than':
                    array_push($display, "For spending greater than " . $amountBreakdown[0]);
                    break;

                case 'life_time_spending_greater_than':
                    array_push($display, "For lifetime spending greater than " . $amountBreakdown[0]);
                    break;

                case 'life_time_visits_greater_than':
                    array_push($display, "For lifetime visits greater than " . $amountBreakdown[0]);
                    break;

                case 'last_visit_occurred':
                    array_push($display, "If last visit occured after " . date('m/d/Y', strtotime($amountBreakdown[0])));
                    break;

                case 'only_those_who_visited_competitors':
                    array_push($display, "If you visited our competitor");
                    break;

                case 'accepted_gender':
                    array_push($display, ucwords($amountBreakdown[0]) . "s only");
                    break;

                case 'age_range':
                    array_push($display, "Age " . implode('-', $amountBreakdown) . 'yrs');
                    break;

                case 'network_size_greater_than':
                    array_push($display, "If your network size is greater than " . $amountBreakdown[0]);
                    break;

                default:
                    break;
            }
        }

        log_message('debug', '_promotion/apply_rules:: [3] display=' . json_encode($display));
        return $display;
    }


    function get_all_store()
    {
        log_message('debug', '_promotion/get_all_store');

        $query = $this->_query_reader->get_query_by_code('all_store_data');
        $result = $this->_query_reader->read($query, 'get_list');
        log_message('debug', '_promotion/get_all_store:: [2] ' . json_encode($result));
        return $result;
    }

    //delete blackout
    function delete_blackout($id, $promotion_id)
    {
        log_message('debug', '_promotion/delete_blackout');
        log_message('debug', '_promotion/delete_blackout:: [1] id=' . $id);
        //if last query remaining -> delete row from table "clout_v1_3cron.promotion_tules"
        $blackout_count = $this->_query_reader->get_count('get_promotion_blackout', array('promotion_id' => $promotion_id));
        if ($blackout_count == 1) { //if black
            $this->_query_reader->run('delete_promotion_rules', array('id' => $promotion_id));
        }
        if ($blackout_count !== 0) {
            $result = $this->_query_reader->run('delete_blackout', array('id' => $id));
        } else {
            $result['error'] = 'Have no Blackout with id' . ' ' . $id;
        }


        log_message('debug', '_promotion/delete_category:: [2] result=' . ($result['error']) ? 'success' : 'fail');

        $this->_logger->add_event(array(
            'id' => $id,
            'activity_code' => 'delete_blackout',
            'result' => ($result) ? 'success' : 'fail',
            'log_details' => 'result=' . ($result['error']) ? 'success' : 'fail',
            'uri' => 'promotion/delete_blackout',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        return $result;
    }


    //update reservation status
    /*
    function reservation_status($promotion_id, $status, $user_id, $date)
    {
        log_message('debug', '_promotion/reservation_status');
        log_message('debug', '_promotion/reservation_status:: [1] promotion_id=' . $promotion_id . 'status='.$status);

        $params = array();
        $p_owner_id = array();
        $update_result = array();
        $promotion_ids = explode(',',$promotion_id);
        //update all promotion reservation status
        foreach ($promotion_ids as $key => $val) {
            $params['id'] = $val;
            $params['reserv_status'] = $status;
            $params['user_id'] = $user_id;
            $result = $this->_query_reader->run('update_schedule_reservation_status', $params, TRUE);
            //if result true
            if($result === true) {
                $update_result[$val] = $result;
                //get promotion reservation owner id
                $p_owner_id []= $this->_query_reader->get_row_as_array('promotion_reservation_owner',array('promotion_id' => $val));
            }
            log_message('debug', '_promotion/reservation_status:: [2] result='.($result)?'success':'fail');
            $this->_logger->add_event(array(
                'promotion_id' => $promotion_id,
                'activity_code' => 'update_reservation_status',
                'result' => ($result) ? 'success' : 'fail',
                'log_details'=> 'result='. ($result)?'success':'fail',
                'uri'=> 'promotion/reservation_status',
                'ip_address'=>$_SERVER['REMOTE_ADDR']
            ));
        }
        foreach ($p_owner_id as $key1=>$val1) {
            foreach ($val1 as $k=>$v){
                //promotion reservation owner id
                $users_id[] = $v;
            }
        }

        if(!empty($update_result)) {
            if (!in_array(false, $update_result)) {

                $data = array(
                    'userId' => $params['user_id'],
                    'message'=> array (
                        'useTemplate' => 'N',
                        'templateId' => '',
                        'senderType'=>'user',
                        'sendToType'=>'filter',
                        'sendTo'=>'select_user',//'sendTo'=>'all_users'
                        'select_user' => $users_id, //send $users_id
                        'subject'=>'Promotion reservation confirm',
                        'body'=>'Promotion reservation confirm successfully',
                        'sms'=>'Promotion reservation confirm successfully',
                        'attachment'=>'',
                        'templateAttachment'=>'',
                        'saveTemplate'=>'N',
                        'saveTemplateName'=>'N',
                        'sendDate'=>$date,
                        'methods'=> array (
                            'system',
                            'email',
                            'sms'
                        )
                    )
                );
                //send message
                $result = run_on_api(BASE_URL.'message/send',$data,'POST');

                if($result['boolean'] === true){
                    log_message('debug', '_promotion/reservation_status email sending');
                    log_message('debug', '_promotion/reservation_status  :: [1] send email to "select_user"=> successfully');
                } else {
                    log_message('debug', '_promotion/reservation_status email sending');
                    log_message('debug', '_promotion/reservation_status  :: [2] send email to "select_user"=> error');
                }


                return $result;
            }
        }
        return false;

        /*

        if($result === true){
            //send system email to notify that reservation confirm
            //config message send
            $data = array(
                'userId' => $params['user_id'],
                'message'=> array (
                    'useTemplate' => 'N',
                    'templateId' => '',
                    'senderType'=>'user',
                    'sendToType'=>'filter',
                    'sendTo'=>'select_user',//'sendTo'=>'all_users'
                    'select_user' => $users_id,
                    'subject'=>'Promotion reservation confirm',
                    'body'=>'Promotion reservation confirm successfully',
                    'sms'=>'Promotion reservation confirm successfully',
                    'attachment'=>'',
                    'templateAttachment'=>'',
                    'saveTemplate'=>'N',
                    'saveTemplateName'=>'N',
                    'sendDate'=>$date,
                    'methods'=> array (
                        'system',
                        'email',
                        'sms'
                    )
                )
            );
            //send message
            $result = run_on_api(BASE_URL.'message/send',$data,'POST');
            /*
            if($result['boolean'] === true){
                log_message('debug', '_promotion/reservation_status email sending');
                log_message('debug', '_promotion/reservation_status  :: [1] send email to "all_users"=> successfully');
            } else {
                log_message('debug', '_promotion/reservation_status email sending');
                log_message('debug', '_promotion/reservation_status  :: [2] send email to "all_users"=> error');
            }
            */

    //    return $result;

    //   }

    //   return $result;

   // }




	# STUB: Apply the promo rules to check if a user qualifies for the chosen offerlist
	function apply_rules($storeId, $userId, $offers)
	{
		log_message('debug', '_promotion/apply_rules');
		log_message('debug', '_promotion/apply_rules:: [1] storeId='.$storeId.' userId='.$userId.' offsers='.json_encode($offers));
		$offersList = $offers;

		#TODO: Apply rules to check if user qualifies


		return $offersList;
	}

	# Get category or subcategory by id
	private function get_category($categoryId, $subCategoryId = NULL)
	{
		if ($subCategoryId) {
			return $this->_query_reader->get_row_as_array('get_categories_level_2_by_id', array('id' => $subCategoryId));
		} else {
			return $this->_query_reader->get_row_as_array('get_categories_level_1_by_id', array('id' => $categoryId));
		}
	}

	# Check if category parent exists
	private function check_parent_exists($userId, $storeId, $category)
	{
		return !empty($this->_query_reader->get_row_as_array('get_custom_category_by_category_id', array(
			'user_id' => $userId,
			'store_owner_id' => $storeId,
			'category_id' => $category
		)));
	}

	private function check_category_exists($userId, $category, $subCategory)
	{
		$whereArray = array();
		$whereArray[] = '(user_id=' . $userId.')';
		$whereArray[] = '(category_id='.$category.')';
		if ($subCategory) {
			$whereArray[] = '(sub_category_id='.$subCategory.')';
		}

		$where = implode(' AND ', $whereArray);
		return empty($this->_query_reader->get_row_as_array('get_category_with_subcategory', array('where' => $where)));

	}
}

