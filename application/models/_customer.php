<?php

class _customer extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        //if database connection error
        $this->load->database();
        $this->db->reconnect();
    }

    function get_customer_list($filter ,$searchData='' ,$sortData='', $sortType='', $merchantId )
    {
        log_message('debug', '_customer/list');
        log_message('debug', '_account/login:: [1] filter='.$filter.' searchdata='.$searchData. 'searchdata'.$sortData. 'sortType= '.$sortType .'merchantId= '.$merchantId);

        $params = array();
        $custome_query['distance'] = "";


        if($filter == "everyone"){ //display all customers

            //latitude first
            //longitude second
            //$position = '26.25544|-98.18303'; // customer position
            //$u_pos = explode("|", $position);
            //,_user.id
            $custome_query['distance'] = "store_distance(g_tracking.latitude,g_tracking.longitude) as distance_store,"; //send longitude and altitude to calculate distance
            $custome_query['where'] = "GROUP BY _user.id HAVING distance_store < 80 ORDER BY score DESC"; //80km => 50 mile //ORDER BY score_by_store.total_score DESC

            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [2] ' . json_encode($result));
            return $result;
        } elseif ($filter == "mycustomer"){ //display my customers
            $custome_query['where'] = "GROUP BY _user.id ORDER BY store_last_transaction DESC"; //order by last customer stansaction
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [3] ' . json_encode($result));
            return $result;
        } elseif ($filter == "herenow"){  //display customers online
            $custome_query['where'] = "where g_tracking.source = 'checkin' GROUP BY _user.id ORDER BY priority ASC";
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [4] ' . json_encode($result));
            return $result;
        } elseif ($filter == "reservations"){ //display reservation customers
            $custome_query['where'] = "where c_transaction.status = 'pending' OR c_transaction.status = 'approved' GROUP BY _user.id ORDER BY s_schedule.schedule_date DESC"; //order by reservation date
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [5] ' . json_encode($result));
            return $result;
        } elseif (!empty($searchData) && empty($filter)){ //display data by search input



            //section -> search by tab name
            //check if $searchData containes label name
            $params_tag['owner_id'] = $merchantId;
            $params_tag['label'] = $searchData;
            $query = $this->_query_reader->get_query_by_code('get_customer_tag_by_label',$params_tag);
            $result_tag = $this->_query_reader->read($query, 'get_list');
            //remaining only users id -> in variable $in
            $in = '';

            if(!empty($result_tag)){
                log_message('debug', '_customer/list by tag:: [7] tag name = '.$params_tag['label'].' tag_owner_id = '.$params_tag['owner_id'].' ' . json_encode($result_tag));
                //prepare MySql condition
                $in.="(";
                foreach ($result_tag as $key=>$val){
                   foreach ($val as $k=>$v){
                       $in.=$v.',';
                   }
                }
                $in = rtrim($in, ',');
                $in.=")";
                //my sql condition
                $custome_query['where'] = "WHERE cg.user_id IN ".$in." GROUP BY _user.id ";
                $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
                $result = $this->_query_reader->read($query, 'get_list');
                log_message('debug', '_customer/list:: [7] ' . json_encode($result));
                //return customer result by tag name
                return $result;


            }
            // END OF-> Search by tag name section


            //search by input search data
            $params['search_input'] = $searchData;
            /*
             * //old search query
            $custome_query['where'] = "GROUP BY _user.id HAVING  name LIKE '%".$params['search_input']."%'
                                             OR score = '".$params['search_input']."'
                                             OR in_store_spending = '".$params['search_input']."'
                                             OR competitor_spending = '".$params['search_input']."'
                                             OR category_spending = '".$params['search_input']."'
                                             OR related_spending = '".$params['search_input']."'
                                             OR overall_spending = '".$params['search_input']."'
                                             OR city = '".$params['search_input']."'
                                             OR state = '".$params['search_input']."'
                                             OR zip = '".$params['search_input']."'
                                             OR country = '".$params['search_input']."'
                                             OR age = '".$params['search_input']."'
                                             OR custom_label = '".$params['search_input']."'
                                             OR notes = '".$params['search_input']."'
                                             OR status = '".$params['search_input']."'
                                             OR action = '".$params['search_input']."'
                                            ";
            */
            $custome_query['where'] = "GROUP BY _user.id HAVING ";
            if(is_numeric($params['search_input'])){ // if numeric -> search in numeric fields
                $custome_query['where'].= "score = '".$params['search_input']."'
                                           OR in_store_spending = '".$params['search_input']."'
                                           OR competitor_spending = '".$params['search_input']."'
                                           OR category_spending = '".$params['search_input']."'
                                           OR related_spending = '".$params['search_input']."'
                                           OR overall_spending = '".$params['search_input']."'
                                           OR zip = '".$params['search_input']."'
                                           OR age = '".$params['search_input']."'
                                            ";
            } else {                               // if string -> search in string fields
                $custome_query['where'].= "name LIKE '%".$params['search_input']."%'
                                            OR city = '".$params['search_input']."'
                                            OR state = '".$params['search_input']."'
                                            OR country = '".$params['search_input']."'
                                            OR custom_label = '".$params['search_input']."'
                                            OR notes = '".$params['search_input']."'
                                            OR status = '".$params['search_input']."'
                                            OR action = '".$params['search_input']."'
                                            ";
            }

            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [6] ' . json_encode($result));
            return $result;

        }elseif(!empty($sortData) && !empty($sortType) && empty($filter) && empty($searchData)){ //sorting table row data

            switch ($sortType) {
                case 'DESC':
                    $order_by = 'DESC';
                break;
                case 'ASC':
                    $order_by = 'ASC';
                break;
                default:
                    $order_by = '';
            }

            $sort_array_data = [ //all sorting table rows
                'score' => 'score',
                'inStoreSpending' => 'in_store_spending',
                'competitorSpending' => 'competitor_spending',
                'categorySpending' => 'category_spending',
                'relatedSpending' => 'related_spending',
                'overallSpending' => 'overall_spending',
                'linkedAccounts' => 'linked_accounts',
                'activity' => 'activity',
                'city' => '_user.city',
                'state' => '_user.state',
                'zip' => 'zip',
                'country' => 'country',
                'gender' => '_user.gender',
                'age' => 'age',
                'customLabel' => 'custom_label',
                'notes' => 'notes',
                'priority' => 'priority',
                'network' => 'network',
                'invites' => 'invites',
                'upcoming' => 'upcoming',
                'time' => 'time',
                'type' => 'type',
                'size' => 'size',
                'status' => 'c_transaction.status',
                'action' => 'action',
                'otherReservations' => 'other_reservations',
                'lastCheckins' => 'last_checkins',
                'pastCheckins' => 'past_checkins',
                'inNetwork' => 'in_network',
                'transactions' => 'transactions',
                'reviews' => 'reviews',
                'favorited' => 'favorited',

            ];

            $params['order_by'] = 'ORDER BY '.$sort_array_data[$sortData].' '.$order_by.'';
            $custome_query['where'] = "GROUP BY _user.id ".$params['order_by']."";
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [7] ' . json_encode($result));
            return $result;
        } else { //if no status checkbox selected -> display all data
            $custome_query['where'] = "GROUP BY _user.id";
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/list:: [8] ' . json_encode($result));
            return $result;
        }
    }


    //return customers data by filter

    function get_filter_customer_list($countryCode='', $stateId='', $userAddress='')
    {

        log_message('debug', '_customer/filter');
        log_message('debug', '_account/login:: [2]    countryCode='.$countryCode. 'stateId'.$stateId. 'userAddres'.$userAddress);

        $params = array();
        $custome_query = array();      //mysql query
        $params['country_code'] = ''; //need to send country code
        $params['state_id'] = '';
        $params['store_address'] = '';
        $custome_query['distance'] = "";
        if(!empty($countryCode)){
            $params['country_code'] = $countryCode;
        }
        if(!empty($stateId)){
            $params['state_id'] = $stateId;
        }
        if(!empty($userAddress)){
            $params['store_address'] = $userAddress;
        }
        //query to return all stores
        if(empty($params['country_code']) && empty($params['state_id']) && empty($params['store_address'])){

            $custome_query['where'] = '';
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query); //get_customer_by_filter
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/filter:: [2] ' . json_encode($result));
            return $result;
        }

        //query to return stores by country_code
        if(!empty($params['country_code']) && empty($params['state_id']) && empty($params['store_address'])){ // && empty($params['state_id'])
            $all_country = explode(',',$params['country_code']);
            $country = '';
            foreach ($all_country as $row){
                $country .='\''.$row.'\''.',';
            }
            $country = rtrim($country, ',');
            $in = 'IN ('.$country.')';
            $custome_query['where'] = "where _user.country_code ".$in.""; //where _store._country_code='".$params['country_code']. //where _store._country_code ".$in."
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/filter:: [3] ' . json_encode($result));
            return $result;
        }

        //query to return stores by country_code and state_id
        if(!empty($params['country_code']) && !empty($params['state_id']) && empty($params['store_address'])){

            $all_state = explode(',',$params['state_id']);
            $country = '';
            foreach ($all_state as $row){
                $country .='\''.$row.'\''.',';
            }
            $country = rtrim($country, ',');
            $in = 'IN ('.$country.')';   //where _store._country_code='".$params['country_code']."' and _store._state_id ".$in."
            $custome_query['where'] = "where _user.country_code='".$params['country_code']."' and _user.state_id ".$in."";//where _store._country_code='".$params['country_code']."' and _store._state_id = '".$params['state_id']."'
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/filter:: [4] ' . json_encode($result));
            return $result;
        }

        //query to display filter data by country,state,store_address -> options
        if(!empty($params['country_code']) && !empty($params['state_id']) && !empty($params['store_address'])){
            $all_state = explode(',',$params['store_address']);
            $country = '';
            foreach ($all_state as $row){
                $country .='\''.$row.'\''.',';
            }
            $country = rtrim($country, ',');
            $in = 'IN ('.$country.')'; //where _store._country_code='".$params['country_code']."' and _store._state_id = '".$params['state_id']."' and _store.address_line_1 ".$in."
            $custome_query['where'] = "where _user.country_code='".$params['country_code']."' and _user.state_id = '".$params['state_id']."' and _user.address_line_1 ".$in.""; // _store.address_line_1 = '".$params['store_address']."'
            $query = $this->_query_reader->get_query_by_code('get_customer_list',$custome_query);
            $result = $this->_query_reader->read($query, 'get_list');
            log_message('debug', '_customer/filter:: [5] ' . json_encode($result));
            return $result;
        }
        return false;
    }

    public function get_all_country()
    {
        log_message('debug', '_customer/get_country');

        $query = $this->_query_reader->get_query_by_code('get_all_customer_country');
        $result = $this->_query_reader->read($query, 'get_list');
        log_message('debug', '_customer/get_country:: [2] ' . json_encode($result));
        return $result;
    }

    function get_all_state($countryCode)
    {
        log_message('debug', '_customer/get_state');
        log_message('debug', '_customer/get_state:: [1] countryCode='.$countryCode.'');

        $params = array();
        $params['country_code'] = $countryCode;
        $query = $this->_query_reader->get_query_by_code('all_store_state',$params);
        $result = $this->_query_reader->read($query, 'get_list');
        log_message('debug', '_customer/get_state:: [2] ' . json_encode($result));
        return $result;
    }

    function get_store_address($stateId)
    {

        log_message('debug', '_customer/get_address');
        log_message('debug', '_customer/get_address:: [1] stateId='.$stateId.'');

        $params = array();
        $params['state_id'] = $stateId;
        $query = $this->_query_reader->get_query_by_code('get_store_address_by_state_id',$params);
        $result = $this->_query_reader->read($query, 'get_list');
        log_message('debug', '_customer/get_address:: [2] ' . json_encode($result));
        return $result;
    }
    //filter of fields to display or hide
    function view_field_access($data,$view_access)
    {
        log_message('debug', '_customer/view_field_access');
        log_message('debug', '_customer/view_field_access:: [1] view remove column and section ' . json_encode($view_access));
        //section column map (name must be the same as column name of mysql result)
        $section_rules = array (
            'store' => array (
                'score',
                'in_store_spending',
                'competitor_spending',
                'category_spending',
                'related_spending',
                'overall_spending',
                'linked_accounts',
                'activity'
            ),
            'customer_details' => array (
                'city',
                'state',
                'zip',
                'country',
                'gender',
                'age',
                'custom_label',
                'notes',
                'priority',
                'network',
                'invites'
            ),
            'reservations' => array (
                'upcoming',
                'time',
                'type',
                'size',
                'status',
                'action',
                'other_reservations'
            ),
            'activity' => array (
                'last_checkins',
                'past_checkins',
                'in_network',
                'transactions',
                'reviews',
                'favorited'
            )
        );
        //names of sections which I want to remove from view
        $section = array();
        //individual column rules
        $column_rules = array();
        //all section column name
        $all_section_rules = array();
        foreach ($view_access as $key2=>$val2){
            $filter = explode('.',$val2); //explode array value
            if(isset($filter[1]) ){ //true if section name isset
                if($filter[0] == 'section') {
                    $section[] = $filter[1];
                }
            } else {              //if array have column name
                $column_rules[] = $filter[0];
            }
        }
        //if isset section => merge all section rules into one array
        if(!empty($section)){
            foreach ($section as $key=>$val){
                $all_section_rules[] = $section_rules[$val];
            }
            //all section rules put here (column name )
            $merge_section_rules = array();
            foreach ($all_section_rules as $key=>$val){
                foreach ($val as $k=>$v){
                    $merge_section_rules[] = $v;
                }
            }
        }
        //merge clumns and section columns into one array
        if(isset($merge_section_rules)) {
            $field_view_result = array_unique(array_merge($column_rules, $merge_section_rules));
        } else {
            $field_view_result = $column_rules;
        }
        $i = 0;
        $data['remove_section'] = $section;
        //remove column from respons data (remove don't need fields)
        foreach ($data as $key => $val) {
            foreach ($val as $key1 => $val1) {
                if (in_array($key1, $field_view_result)) {
                    unset($data[$i][$key1]);
                }
            }
            $i++;
        }
        return $data;




        /*

            $i = 0;
            foreach ($data as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    if (in_array($key1, $view_access)) {
                        unset($data[$i][$key1]);
                    }
                }
                $i++;
            }
            return $data;
        */
    }



    //update reservation status
    function reservation_status($users_id, $status, $user_id, $date)
    {
        log_message('debug', '_customer/reservation_status');
        log_message('debug', '_customer/reservation_status:: [1] users_id=' . json_decode($users_id) . 'status='.$status);
        $params = array();
        $p_owner_id = array();
        $update_result = array();
        $users_ids = explode(',',$users_id);
        //update all promotion reservation status

        foreach ($users_ids as $key => $val) {
            $params['id'] = $val;
            $params['reserv_status'] = $status;
            $params['user_id'] = $user_id; //the user who make reservation
            $result = $this->_query_reader->run('update_schedule_reservation_status', $params, TRUE);
            //if result true
            if($result === true) {
                $update_result[] = $result;
                //get promotion reservation owner id
            //    $p_owner_id []= $this->_query_reader->get_row_as_array('promotion_reservation_owner',array('promotion_id' => $val));
            } else { // if fals - reservation status didn't change and emeil dont send to user
                if(($key = array_search($val, $users_ids)) !== false) { //search in array value that need to remove (remove user id from array)
                    unset($users_ids[$key]);
                }
            }
            log_message('debug', '_promotion/reservation_status:: [2] result='.($result)?'success':'fail');
            $this->_logger->add_event(array(
                'user_id' => json_decode($users_id),
                'activity_code' => 'update_reservation_status',
                'result' => ($result) ? 'success' : 'fail',
                'log_details'=> 'result='. ($result)?'success':'fail',
                'uri'=> 'promotion/reservation_status',
                'ip_address'=>$_SERVER['REMOTE_ADDR']
            ));
        }
        /*
        foreach ($p_owner_id as $key1=>$val1) {
            foreach ($val1 as $k=>$v){
                //promotion reservation owner id
                $users_id[] = $v;
            }
        }*/


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
                        'select_user' => $users_ids,//$users_id, //send $users_id
                        'subject'=>'Reservation confirm',
                        'body'=>'Reservation confirm successfully',
                        'sms'=>'Reservation confirm successfully',
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

    }

    function message_send($users_id, $user_id, $date, $subject, $body, $sms)
    {
        log_message('debug', '_customer/message_send');
        log_message('debug', '_customer/message_send:: [1] users_id=' . json_decode($users_id) . 'message send by : '.$user_id .' message subject : '.$subject.' message body : '.$body.' sms message: '.$sms);
        $users_ids = explode(',',$users_id);

        //params to send message
        $data = array(
            'userId' => $user_id,
            'message'=> array (
                'useTemplate' => 'N',
                'templateId' => '',
                'senderType'=>'user',
                'sendToType'=>'filter',
                'sendTo'=>'select_user',
                'select_user' => $users_ids,
                'subject'=> htmlspecialchars($subject),
                'body'=> htmlspecialchars($body),
                'sms'=> htmlspecialchars($sms),
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
    //update perk status
    function update_perk_status($users_id, $user_id, $status, $time)
    {

        log_message('debug', '_customer/update_perk_status');
        log_message('debug', '_customer/update_perk_status:: [1] users_id=' . json_decode($users_id) . 'status='.$status);
        $params = array();
        $update_result = array();
        $users_ids = explode(',',$users_id);
        $perk_ids= array();
        //update all users perk status

        foreach ($users_ids as $key => $val) {
            $params['user_id'] = $val;
            $promotion = $this->_query_reader->get_list('select_all_perk', $params);
            if(!empty($promotion)) {
                foreach ($promotion as $k=>$v){
                    foreach ($v as $j =>$l){
                        //all users perks (promotion) id (need to update status)
                        $perk_ids[] = $l;
                    }
                }

                //update users perk
                foreach ($perk_ids as $k=>$v){
                    $perk_params['update_by_user_id'] = $user_id;
                    $perk_params['id'] = $v; //promotion id
                    $perk_params['status'] = $status;
                    $perk_params['time'] = $time;
                    $perk_status = $this->_query_reader->run('update_perk_status', $perk_params, TRUE);
                    if($perk_status === true){
                        log_message('debug', '_customer/update_perk_status:: [2] result= success  promotion (perk) id : '.$v. ' status update successfully' );
                        //send message to user (message -> perk status change)
                        $data = array(
                            'userId' => $params['user_id'],
                            'message'=> array (
                                'useTemplate' => 'N',
                                'templateId' => '',
                                'senderType'=>'user',
                                'sendToType'=>'filter',
                                'sendTo'=>'select_user',//'sendTo'=>'all_users'
                                'select_user' => explode(',',$val),//$users_id, //send $users_id
                                'subject'=>'Perk status update',
                                'body'=>'Perk '.$v.' status update successfully',
                                'sms'=>'Perk '.$v.' status update successfully',
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
                        $result = run_on_api(BASE_URL.'message/send',$data,'POST');

                        if($result['boolean'] === true){
                            log_message('debug', '_customer/update_perk_status email sending');
                            log_message('debug', '_customer/update_perk_status  :: [3] send email successfully to user'.' '.$val);
                        } else {
                            log_message('debug', '_promotion/reservation_status email sending');
                            log_message('debug', '_promotion/reservation_status  :: [3] result = fail to user '.' '.$val);
                        }
                    } else {
                        log_message('debug', '_customer/update_perk_status:: [4] result= fail  promotion (perk) id : '.$v. ' status update fail (maby perk already has the updated status)' );
                    }
                }
            }else { // if user have no perk
                log_message('debug', '_customer/update_perk_status:: [5] result= fail  user_id : '.$val. ' have no perk' );
                $this->_logger->add_event(array(
                    'user_id' => json_decode($users_id),
                    'activity_code' => 'update_reservation_status',
                    'result' =>  'user_id : '.$val. ' have no perk',
                    'log_details'=> 'fail',
                    'uri'=> 'promotion/reservation_status',
                    'ip_address'=>$_SERVER['REMOTE_ADDR']
                ));
            }
        }
        return true;
    }
    //create ,erchant tag (for easy search )
    function create_tagging ($owner_id, $label, $customers_id, $delete)
    {
        log_message('debug', '_customer/create_tagging');
        log_message('debug', '_customer/create_tagging:: [1] owner_id= '.$owner_id.', label = '.$label.' customers_in_tag = ' . json_encode($customers_id) . 'is+delete='.$delete);
        $params_d['label'] = htmlspecialchars($label);
        $params_d['owner_id'] = (int)$owner_id;
        $check_duplicate = $this->_query_reader->get_count('duplicate_customers_tag', $params_d);
        //check duplicate label name
        if($check_duplicate > 0){
            return 'The label name already exists!';
            die();
        }
        //add tag label
        $params['owner_id'] = (int)$owner_id;
        $params['label'] = htmlspecialchars($label);
        $params['delete'] = $delete;
        $add_tag = $this->_query_reader->add_data('add_customers_tag', $params); //add promotion rule
        //add tag group
        //customers_id - contains user id which I want to display in search bar
        $user_ids = explode(',',$customers_id);
        $result = array();
        foreach ($user_ids as $key => $val) {
            $params_r['tag_id'] = $add_tag;
            $params_r['user_id'] = (int)$val;
            $add_tag_group = $this->_query_reader->add_data('add_customers_tag_group', $params_r);
            //contains result response
            $result[] = $add_tag_group;
            //if error
            if(!$add_tag_group){
                log_message('debug', '_customer/create_tagging::(add_tag_group) [2] result= fail params ='.json_encode($params_r).'  ' );
            }

        }
        if(in_array(false,$result)){
            return array('error'=>json_encode($result));
        } else {
            return 'Tag create successfully!';
        }

    }
    //delete merchant tag
    function delete_tagg($tag_id, $owner_id)
    {
        log_message('debug', '_customer/delete_tagg');
        log_message('debug', '_customer/delete_tagg:: [1] owner_id= '.$owner_id.', tag_id = '.$tag_id);
        $params_d['tag_id'] = (int)($tag_id);
        $params_d['owner_id'] = (int)$owner_id;
        $delete = $this->_query_reader->run('delete_customers_tag', $params_d, true);
        //check duplicate label name
        if($delete){
            return true;
        } else {
            log_message('debug', '_customer/delete_tagg:: [2] result= fail params ='.json_encode($params_d).'  ' );
            return array('error'=>json_encode($params_d));
        }
    }
    //display all merchant tag names
    function display_tagg($owner_id){
        log_message('debug', '_customer/display_tagg');
        log_message('debug', '_customer/display_tagg: [1] owner_id= '.$owner_id);
        $params['owner_id'] = (int)$owner_id;
        $query = $this->_query_reader->get_query_by_code('get_all_customer_tag',$params);
        $result = $this->_query_reader->read($query, 'get_list');
        log_message('debug', '_customer/display_tagg:: [2] ' . json_encode($result));
        if(empty($result)){
            log_message('debug', '_customer/display_tagg: [3] result = fail, owner_id = '.$owner_id);
            return array('error' => 'No tag found by owner id'.' '.$params['owner_id']);
        }
        return $result;
        }
}