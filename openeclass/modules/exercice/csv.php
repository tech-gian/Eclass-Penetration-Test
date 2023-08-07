<?
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/

$require_current_course = TRUE;
include '../../include/init.php';

// IF PROF ONLY
if($is_adminOfCourse) {

	header("Content-disposition: filename=".$currentCourse."_".$_GET['exerciseId']."_".date("Y-m-d").".xls");
	header("Content-type: text/csv; charset=UTF-16");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	$bom = "\357\273\277";
	
	$crlf="\r\n";
	$output =  "$bom$langSurname\t$langName\t$langExerciseStart\t$langExerciseDuration\t$langYourTotalScore2$crlf";
	$output .=  "$crlf";
	
	mysql_select_db($currentCourseID);
	mysqli_select_db($conn, $currentCourseID);
	$sql = $conn->prepare("SELECT DISTINCT uid FROM `exercise_user_record` WHERE eid=?");
	$sql->bind_param("s", $_GET['exerciseId']);
	$sql->execute();
	$result = $sql->get_result();
	while($row=$result->fetch_assoc()) {
		$sid = $row['uid'];
		$tempSql = $conn->prepare("select nom, prenom from `$mysqlMainDb`.user where user_id=?");
		$tempSql->bind_param("s", $sid);
		$tempSql->execute();
		$StudentName = $tempSql->get_result();
		$theStudent = $StudentName->fetch_assoc()
		$nom = $theStudent["nom"];
		$prenom = $theStudent["prenom"];	
		mysql_select_db($currentCourseID);
		mysqli_select_db($conn, $currentCourseID);
		$sql2 = $conn->prepare("SELECT DATE_FORMAT(RecordStartDate, '%Y-%m-%d / %H:%i') AS RecordStartDate, 
			RecordEndDate, TIME_TO_SEC(TIMEDIFF(RecordEndDate,RecordStartDate)) AS TimeDuration, 
			TotalScore, TotalWeighting 
			FROM `exercise_user_record` WHERE uid=? AND eid=?");
		$sql2->bind_param("ss", $sid, $_GET['exerciseId']);
		$sql2->execute();
		$result2 = $sql2->get_result();
		while($row2=$result2->fetch_assoc()) {
			$output .= csv_escape($prenom) ."\t";
			$output .= csv_escape($nom) ."\t";
			$RecordStartDate = $row2['RecordStartDate'];
			$output .= csv_escape($RecordStartDate) ."\t";
			if ($row2['TimeDuration'] == '00:00:00' or empty($row2['TimeDuration'])) { // for compatibility 
				$output .= csv_escape($langNotRecorded) ."\t";
			} else {
				$output .= csv_escape(format_time_duration($row2['TimeDuration']))."\t";
			}		
			$TotalScore = $row2['TotalScore'];
			$TotalWeighting = $row2['TotalWeighting'];
			$output .= csv_escape("( $TotalScore/$TotalWeighting )"). "\t";
			$output .=  "$crlf";
		}
	}
	echo iconv('UTF-8', 'UTF-16LE', $output);
}  // end of initial if

