<?php

define('DEFAULT_MAX_DURATION', 900);

class action {

    function record($module_name, $action_name = "access") {
        global $uid, $currentCourseID, $conn;
        $action_type = new action_type();
        $action_type_id = $action_type->get_action_type_id($action_name);
        $exit = $action_type->get_action_type_id('exit');
        $module_id = $this->get_module_id($module_name);

        ###ophelia -28-08-2006 : add duration to previous
        if ($currentCourseID != null) {
            $sql = $conn->prepare("SELECT id, TIME_TO_SEC(TIMEDIFF(NOW(), date_time)) AS diff, action_type_id
                FROM `$currentCourseID`.actions
                WHERE user_id = ?
                ORDER BY id DESC LIMIT 1");
            $sql->bind_param("s", $uid);
            $sql->execute();
            $result = $sql->get_result();
            $last_id = $diff = $last_action = 0;
            if ($result and mysqli_num_rows($result) > 0) {
                    $row = $result->fetch_assoc();
                    $last_id = $row['id'];
                    $diff = $row['diff'];
                    $last_action = $row['action_type_id'];
                    $result->free_result();
                    # Update previous action with corect duration
                    if ($last_id and $last_action != $exit and $diff < DEFAULT_MAX_DURATION) {
                            $sql = $conn->prepare("UPDATE `$currentCourseID`.actions
                                SET duration = ?
                                WHERE id = ?");
                            $sql->bind_param("ss", $diff, $last_id);
                            $sql->execute();
                    }
            }
        }
        if ($action_type_id == $exit) {
                $duration = 0;
        } else {
                $duration = DEFAULT_MAX_DURATION;
        }
        if ($currentCourseID != null) {
            $sql = $conn->prepare("INSERT INTO `$currentCourseID`.actions SET
                module_id = ?,
                user_id = ?,
                action_type_id = ?,
                date_time = NOW(),
                duration = ?");
            $sql->bind_param("ssss", $module_id, $uid, $action_type_id, $duration);
            $sql->execute();
        }
    }


#ophelia 2006-08-02: per month and per course
    function summarize() {
        global $currentCourseID, $conn;
        
        ## edw ftia3e tis hmeromhnies
        $now = date('Y-m-d H:i:s');
        $current_month = date('Y-m-01 00:00:00');
        
        $sql_0 = $conn->prepare("SELECT min(date_time) as min_date FROM `$currentCourseID`.actions"); //gia na doume
        $sql_1 = $conn->prepare("SELECT DISTINCT module_id FROM `$currentCourseID`.actions ");  //arkei gia twra.

 
        $sql_0->execute();
        $result = $sql_0->get_result();
        while ($row = $result->fetch_assoc()) {
            $start_date = $row['min_date'];
        }
	if (empty($start_date)) {
		$start_date = '2003-01-01 00:00:00';
	}
        $result->free_result();

	$stmp = strtotime($start_date);
        $end_stmp = $stmp + 31*24*60*60;  //min time + 1 month
        $end_date = date('Y-m-01 00:00:00', $end_stmp);
        while ($end_date < $current_month){
            $sql_1->execute();
            $result = $sql_1->get_result();
            while ($row = $result->fetch_assoc()) {
                #edw kanoume douleia gia ka8e module
                $module_id = $row['module_id'];

                $sql_2 = $conn->prepare("SELECT count(id) as visits, sum(duration) as total_dur FROM `$currentCourseID`.actions ".
                    " WHERE module_id = ? AND ".
                    " date_time >= ? AND ".
                    " date_time < ? ");
                $sql_2->bind_param("sss", $module_id, $start_date, $end_date);
                    
                $sql_2->execute();
                $result_2 = $sql_2->get_result();
                while ($row2 = $result_2->fetch_assoc()) {
                    $visits = $row2['visits'];
                    $total_dur = $row2['total_dur'];
                }
                $result_2->free_result();
                $sql_3 = $conn->prepare("INSERT INTO `$currentCourseID`.actions_summary SET ".
                    " module_id = ?, ".
                    " visits = ?, ".
                    " start_date = ?, ".
                    " end_date = ?, ".
                    " duration = ?");
                $sql_3->bind_param("sssss", $module_id, $visits, $start_date, $end_date, $total_dur);
                $sql_3->execute();
            
                $sql_4 = $conn->prepare("DELETE FROM `$currentCourseID`.actions ".
                    " WHERE module_id = ? ".
                    " AND date_time >= ? AND ".
                    " date_time < ?");
                $sql_4->bind_param("sss", $module_id, $start_date, $end_date);
                $sql_4->execute();
            
            }
            $result->free_result();
            
            #next month
            $start_date = $end_date;
	    $stmp = $end_stmp;	
            $end_stmp += 31*24*60*60;  //end time + 1 month
            $end_date = date('Y-m-01 00:00:00', $end_stmp);
	    $start_date = date('Y-m-01 00:00:00', $stmp);
        }
    }


    function get_module_id($module_name) {
        global $currentCourseID, $conn;
        if ($currentCourseID == null) {
            return 0;
        }
        $sql = $conn->prepare("SELECT id FROM `$currentCourseID`.accueil WHERE define_var = ?");
        $sql->bind_param("s", $module_name);
        $sql->execute();
        $result = $sql->get_result();
        if ($result and mysqli_num_rows($result) > 0) {
                $row = $result->fetch_assoc();
                $id = $row['id'];
                $result->free_result();
                return $id;
        } else {
                return 0;
        }
    }

}

class action_type {
    function get_action_type_id($action_name) {
        global $currentCourseID, $conn;
        if ($currentCourseID == null) {
            return false;
        }
        $sql = $conn->prepare("SELECT id FROM `$currentCourseID`.action_types WHERE name = ?");
        $sql->bind_param("s", $action_name);
        $sql->execute();
        $result = $sql->get_result();
        if ($result and mysqli_num_rows($result) > 0) {
                $row = $result->fetch_assoc();
                $id = $row['id'];
                $result->free_result();
                return $id;
        } else {
                return false;
        }
    }
}
