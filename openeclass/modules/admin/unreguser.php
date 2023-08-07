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


/**===========================================================================
	unreguser.php
	@last update: 27-06-2006 by Karatzidis Stratos
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================
        @Description: Delete user from platform and from courses (eclass version)

 	This script allows the admin to :
 	- permanently delete a user account
 	- delete a user from participating into a course

==============================================================================
*/

// BASETHEME, OTHER INCLUDES AND NAMETOOLS
$require_admin = TRUE;
include '../../include/baseTheme.php';
$nameTools = $langUnregUser;
$navigation[]= array ("url"=>"index.php", "name"=> $langAdmin);
$tool_content = "";

// get the incoming values and initialize them
$u = isset($_GET['u'])? intval($_GET['u']): false;
$c = isset($_GET['c'])? intval($_GET['c']): false;
$doit = isset($_GET['doit']);

$u_account = $u? uid_to_username($u): '';
$u_realname = $u? uid_to_name($u): '';
$u_statut = get_uid_statut($u);
$t = 0;

if (!$doit) {
        $tool_content .= "<h4>$langConfirmDelete</h4><p>$langConfirmDeleteQuestion1 <em>$u_realname ($u_account)</em>";
        if($c) {
                $tool_content .= " $langConfirmDeleteQuestion2 <em>".htmlspecialchars($c)."</em>";
        }
        $tool_content .= ";</p>
                <ul>
                <li>$langYes: <a href=\"unreguser.php?u=".htmlspecialchars($u)."&c=".htmlspecialchars($c)."&doit=yes\">$langDelete</a><br>&nbsp;</li>
                <li>$langNo: <a href=\"edituser.php?u=".htmlspecialchars($u)."\">$langBack</a></li>
                </ul>";
} else {
        if (!$c) {
                if ($u == 1) {
                        $tool_content .= $langTryDeleteAdmin;
                } else {
                        // now check if the user has registered courses...
                        $tempSql = $conn->prepare("SELECT * FROM cours_user WHERE user_id = ?");
                        $tempSql->bind_param("s", $u);
                        $tempSql->execute();
                        $q1 = $tempSql->get_result();
                        $total = mysqli_num_rows($q1);
                        if ($total>0) {
                                // user has courses, so not allowed to delete
                                $tool_content .= "$langUnregForbidden <em>$u_realname ($u_account)</em><br>";
                                $v = 0;	$s = 0;
                                for ($p = 0; $p < mysqli_num_rows($q1); $p++) {
                                        $l1 = $q1->fetch_assoc();
                                        $tutor = $l1['reg_date'];
                                        if ($tutor == '1') {
                                                $v++;
                                        } else {
                                                $s++;
                                        }
                                }

                                if ($v>0) {
                                        if ($s>0) {
                                                //display list
                                                $tool_content .= "$langUnregFirst <br><br>";
                                                $tempSql = $conn->prepare("SELECT a.code, a.intitule, b.statut, a.cours_id, b.tutor
                                                        FROM cours AS a LEFT JOIN cours_user AS b ON a.cours_id = b.cours_id
                                                        WHERE b.user_id = ? AND b.tutor=0 ORDER BY b.statut, a.faculte");
                                                $tempSql->bind_param("s", $u);
                                                $tempSql->execute();
                                                $sql = $tempSql->get_result();
                                                // αν ο χρήστης συμμετέχει σε μαθήματα τότε παρουσίασε τη λίστα
                                                if (mysqli_num_rows($sql) > 0) {
                                                        $tool_content .= "<h4>$langStudentParticipation</h4>\n".
                                                                "<table border=\"1\">\n<tr><th>$langLessonCode</th><th>$langLessonName</th>".
                                                                "<th>$langProperty</th><th>$langActions</th></tr>";
                                                        for ($j = 0; $j < mysqli_num_rows($sql); $j++) {
                                                                $logs = $sql->fetch_assoc();
                                                                $tool_content .= "<tr><td>".htmlspecialchars($logs['code'])."</td><td>".
                                                                        htmlspecialchars($logs['intitule'])."</td><td align=\"center\">";
                                                                switch ($logs['tutor'])
                                                                {
                                                                        case '1':
                                                                                $tool_content .= $langTeacher;
                                                                                $tool_content .= "</td><td align=\"center\">---</td></tr>\n";
                                                                                break;
                                                                        case '0':
                                                                                $tool_content .= $langStudent;
                                                                                $code = $logs['code'];
                                                                                $tool_content .= "</td><td align=\"center\"><a href=\"unreguser.php?u=".htmlspecialchars($u)."&c=$code\">$langDelete</a></td></tr>\n";
                                                                                break;
                                                                        default:
                                                                                $tool_content .= $langVisitor;
                                                                                $code = $logs['code'];
                                                                                $tool_content .= "</td><td align=\"center\"><a href=\"unreguser.php?u=".htmlspecialchars($u)."&c=$code\">$langDelete</a></td></tr>\n";
                                                                                break;
                                                                }
                                                        }
                                                        $tool_content .= "</table>\n";
                                                }
                                        } else {
                                                $tool_content .= "$langUnregTeacher<br>";
                                                $tempSql = $conn->prepare("SELECT a.code, a.intitule, b.statut, a.cours_id
                                                        FROM cours AS a LEFT JOIN cours_user AS b ON a.cours_id = b.cours_id
                                                        WHERE b.user_id = ? AND b.statut=1 ORDER BY b.statut, a.faculte");
                                                $tempSql->bind_param("s", $u);
                                                $tempSql->execute();
                                                $sql = $tempSql->get_result();
                                                // αν ο χρήστης συμμετέχει σε μαθήματα τότε παρουσίασε τη λίστα
                                                if (mysqli_num_rows($sql) > 0) {
                                                        $tool_content .= "<h4>$langStudentParticipation</h4>\n".
                                                                "<table border=\"1\">\n<tr><th>$langLessonCode</th><th>$langLessonName</th>".
                                                                "<th>$langProperty</th><th>$langActions</th></tr>";
                                                        for ($j = 0; $j < mysqli_num_rows($sql); $j++) {
                                                                $logs = $sql->fetch_assoc();
                                                                $tool_content .= "<tr><td>".htmlspecialchars($logs['code'])."</td><td>".
                                                                        htmlspecialchars($logs['intitule'])."</td><td align=\"center\">";
                                                                $tool_content .= $langTeacher;
                                                                $tool_content .= "</td><td align=\"center\">---</td></tr>\n";
                                                        }
                                                }
                                                $tool_content .= "</table>\n";

                                        }
                                } else {
                                        // display list
                                        $tool_content .= "$langUnregFirst <br><br>";
                                        $tempSql = $conn->prepare("SELECT a.code, a.intitule, b.statut, a.cours_id
                                                FROM cours AS a LEFT JOIN cours_user AS b ON a.cours_id = b.cours_id
                                                WHERE b.user_id = ? order by b.statut, a.faculte");
                                        $tempSql->bind_param("s", $u);
                                        $tempSql->execute();
                                        $sql = $tempSql->get_result();
                                        // αν ο χρήστης συμμετέχει σε μαθήματα τότε παρουσίασε τη λίστα
                                        if (mysqli_num_rows($sql) > 0) {
                                                $tool_content .= "<h4>$langStudentParticipation</h4>\n".
                                                        "<table border='1'>\n<tr><th>$langLessonCode</th><th>$langLessonName</th>".
                                                        "<th>$langProperty</th><th>$langActions</th></tr>";
                                                for ($j = 0; $j < mysqli_num_rows($sql); $j++) {
                                                        $logs = $sql->fetch_assoc();
                                                        $tool_content .= "<tr><td>".htmlspecialchars($logs['code'])."</td><td>".
                                                                htmlspecialchars($logs['intitule'])."</td><td align=\"center\">";
                                                        switch ($logs['statut']) {
                                                                case 1:
                                                                        $tool_content .= $langTeacher;
                                                                        $tool_content .= "</td><td align='center'>---</td></tr>\n";
                                                                        break;
                                                                case 5:
                                                                        $tool_content .= $langStudent;
                                                                        $code = $logs['code'];
                                                                        $tool_content .= "</td><td align='center'><a href='unreguser.php?u=$u&amp;c=$code'>$langDelete</a></td></tr>\n";
                                                                        break;
                                                                default:
                                                                        $tool_content .= $langVisitor;
                                                                        $tool_content .= "</td><td align='center'><a href='unreguser.php?u=$u&amp;c=$code'>$langDelete</a></td></tr>\n";
                                                                        break;
                                                        }
                                                }
                                                $tool_content .= "</table>\n";
                                        }
                                }
                                $t = 1;
                        } else {
                                $q = $conn->prepare("DELETE from user WHERE user_id = ?");
                                $q->bind_param("s", $u);
                                if ($q->execute() and $q->affected_rows > 0) {
                                        $t = 2;
                                } else {
                                        $t = 3;
                                }
                        }


                        switch($t)
                        {
                                case '1':	$tool_content .= "";	$m = 1; break;
                                case '2': $tool_content .= "<p>$langUserWithId $u $langWasDeleted.</p>\n";	$m = 0;	break;
                                case '3': $tool_content .= "$langErrorDelete";	$m = 1;	break;
                                default: $m = 0;	break;
                        }


                        $tempSql = $conn->prepare("DELETE from admin WHERE idUser = ?");
                        $tempSql->bind_param("s", $u);
                        if($u!=1) {
                                $tempSql->execute();
                        }
                        if ($tempSql->affected_rows > 0) {
                                $tool_content .= "<p>$langUserWithId ".htmlspecialchars($u)." $langWasAdmin.</p>\n";
                        }

                        // delete guest user from cours_user
                        if($u_statut == '10') {
                                $sql = db_query("DELETE from cours_user WHERE user_id = $u");
                        }
                }

        } elseif ($c and $u) {
                $q = $conn->prepare("DELETE from cours_user WHERE user_id = ? AND
                        cours_id = (SELECT cours_id FROM cours WHERE code = ?");
                $q->bind_param("ss", $u, $c);
                $q->execute();
                if ($q->affected_rows > 0) {
                        $tool_content .= "<p>$langUserWithId $u $langWasCourseDeleted $c.</p>\n";
                        $m = 1;
                }
        }
        else
        {
                $tool_content .= "$langErrorDelete";
        }
        $tool_content .= "<br>&nbsp;";
        if((isset($m)) && (!empty($m))) {
                $tool_content .= "<br><a href=\"edituser.php?u=".htmlspecialchars($u)."\">$langEditUser $u_account</a>&nbsp;&nbsp;&nbsp;";
        }
        $tool_content .= "<a href=\"./index.php\">$langBackAdmin</a>.<br />\n";
}

function get_uid_statut($u)
{
	global $mysqlMainDb, $conn;
        $tempSql = $conn->prepare("SELECT statut FROM user WHERE user_id = ?");
        $tempSql->bind_param("s", $u);
        $tempSql->execute();
        $tempResult = $tempSql->get_result();

	if ($r = $tempResult->fetch_assoc())
	{
		return $r['statut'];
	}
	else
	{
		return FALSE;
	}
}

draw($tool_content,3,'admin');
