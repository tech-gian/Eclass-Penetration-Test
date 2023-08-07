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
// if we come from the home page

if (isset($from_home) and ($from_home == TRUE) and isset($_GET['cid'])) {
        session_start();
        $_SESSION['dbname'] = $cid;
}
$require_current_course = TRUE;
$require_prof = true;
$require_help = TRUE;
$helpTopic = 'Infocours';
include '../../include/baseTheme.php';
include '../../include/csrf_functions.php';

$nameTools = $langModifInfo;
$tool_content = "";

// submit
if (!$is_adminOfCourse) {
	$tool_content .= "<p>$langForbidden</p>";
        draw($tool_content, 2, 'course_info');
        exit;
}

$lang_editor = langname_to_code($language);

$head_content = <<<hContent
<script type="text/javascript">
        _editor_url  = "$urlAppend/include/xinha/";
        _editor_lang = "$lang_editor";
</script>
<script type="text/javascript" src="$urlAppend/include/xinha/XinhaCore.js"></script>
<script type="text/javascript" src="$urlAppend/include/xinha/my_config2.js"></script>
hContent;

function escape($name) {
        $input = filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING);
        if ($input !== null) {
            $GLOBALS[$name] = $input;
            $GLOBALS[$name . '_html'] = '<input type="hidden" name="' . $name .
                   '" value="' . htmlspecialchars($input) . '" />';
        } else {
            $GLOBALS[$name . '_html'] = $GLOBALS[$name] = '';
        }
    }
    

    $_POST['title'] = htmlspecialchars($_POST['title'], ENT_QUOTES);
    $_POST['description'] = htmlspecialchars($_POST['description'], ENT_QUOTES);
    $_POST['course_addon'] = htmlspecialchars($_POST['course_addon'], ENT_QUOTES);
    $_POST['course_keywords'] = htmlspecialchars($_POST['course_keywords'], ENT_QUOTES);
    $_POST['formvisible'] = htmlspecialchars($_POST['formvisible'], ENT_QUOTES);
    $_POST['titulary'] = htmlspecialchars($_POST['titulary'], ENT_QUOTES);
    $_POST['type'] = htmlspecialchars($_POST['type'], ENT_QUOTES);
    $_POST['password'] = htmlspecialchars($_POST['password'], ENT_QUOTES);


if (isset($_POST['submit']) && validate_csrf_token()) {
        if (empty($_POST['title'])) {
                $tool_content .= "<p class='caution_small'>$langNoCourseTitle<br />
                                  <a href='$_SERVER[PHP_SELF]'>$langAgain</a></p><br />";
        } else {
                if (isset($_POST['localize'])) {
                        $newlang = $language = langcode_to_name($_POST['localize']);
                        // include_messages
                        include("${webDir}modules/lang/$language/common.inc.php");
                        $extra_messages = "${webDir}/config/$language.inc.php";
                        if (file_exists($extra_messages)) {
                                include $extra_messages;
                        } else {
                                $extra_messages = false;
                        }
                        include("${webDir}modules/lang/$language/messages.inc.php");
                        if ($extra_messages) {
                                include $extra_messages;
                        }
                }

                // update course settings
                if (isset($_POST['checkpassword']) and
                    isset($_POST['formvisible']) and
                    $_POST['formvisible'] == '1') {
                        $password = $password;
                } else {
                        $password = "";
                }

                list($facid, $facname) = explode('--', $_POST['facu']);
                $tempSql = $conn->prepare("UPDATE `$mysqlMainDb`.cours
                        SET intitule = ?,
                        faculte = ?,
                        description = ?,
                        course_addon = ?,
                        course_keywords = ?,
                        visible = ?,
                        titulaires = ?,
                        languageCourse = ?,
                        type = ?,
                        password = ?,
                        faculteid = ?
                        WHERE cours_id = ?");
                $tempSql->bind_param("ssssssssssss", $_POST['title'], $facname, $_POST['description'], $_POST['course_addon'], $_POST['course_keywords'],
                        $_POST['formvisible'], $_POST['titulary'], $newlang, $_POST['type'], $_POST['password'], $facid, $cours_id);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$mysqlMainDb`.cours_faculte
                        SET faculte = ?,
                        facid = ?
                        WHERE code=?");
                $tempSql->bind_param("sss", $facname, $facid, $currentCourseID);
                $tempSql->execute();

                // update Home Page Menu Titles for new language
                mysql_select_db($currentCourseID, $db);
                mysqli_select_db($conn, $currentCourseID);
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_AGENDA'");
                $tempSql->bind_param("s", $langAgenda);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_LINKS'");
                $tempSql->bind_param("s", $langLinks);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_DOCS'");
                $tempSql->bind_param("s", $langDoc);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_VIDEO'");
                $tempSql->bind_param("s", $langVideo);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_ASSIGN'");
                $tempSql->bind_param("s", $langWorks);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_ANNOUNCE'");
                $tempSql->bind_param("s", $langAnnouncements);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_USERS'");
                $tempSql->bind_param("s", $langAdminUsers);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_FORUM'");
                $tempSql->bind_param("s", $langForums);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_EXERCISE'");
                $tempSql->bind_param("s", $langExercices);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_COURSEINFO'");
                $tempSql->bind_param("s", $langModifyInfo);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_GROUPS'");
                $tempSql->bind_param("s", $langGroups);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_DROPBOX'");
                $tempSql->bind_param("s", $langDropBox);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_CHAT'");
                $tempSql->bind_param("s", $langConference);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_DESCRIPTION'");
                $tempSql->bind_param("s", $langCourseDescription);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_QUESTIONNAIRE'");
                $tempSql->bind_param("s", $langQuestionnaire);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_LP'");
                $tempSql->bind_param("s", $langLearnPath);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_USAGE'");
                $tempSql->bind_param("s", $langUsage);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_TOOLADMIN'");
                $tempSql->bind_param("s", $langToolManagement);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_WIKI'");
                $tempSql->bind_param("s", $langWiki);
                $tempSql->execute();
                $tempSql = $conn->prepare("UPDATE `$currentCourseID`.accueil SET rubrique=? WHERE define_var='MODULE_ID_UNITS'");
                $tempSql->bind_param("s", $langCourseUnits);
                $tempSql->execute();

                $tool_content .= "<p class='success_small'>$langModifDone<br />
                        <a href='".$_SERVER['PHP_SELF']."'>$langBack</a></p><br />
                        <p><a href='{$urlServer}courses/$currentCourseID/index.php'>$langBackCourse</a></p><br />";
        }
} else {

		$tool_content .= "<div id='operations_container'><ul id='opslist'>";
		$tool_content .= "<li><a href='archive_course.php'>$langBackupCourse</a></li>
  		<li><a href='delete_course.php'>$langDelCourse</a></li>
    		<li><a href='refresh_course.php'>$langRefreshCourse</a></li></ul></div>";

                $sql = $conn->prepare("SELECT cours_faculte.faculte,
                        cours.intitule, cours.description, cours.course_keywords, cours.course_addon,
                        cours.visible, cours.fake_code, cours.titulaires, cours.languageCourse,
                        cours.departmentUrlName, cours.departmentUrl, cours.type, cours.password, cours.faculteid
                        FROM `$mysqlMainDb`.cours, `$mysqlMainDb`.cours_faculte
                        WHERE cours.code=?
                        AND cours_faculte.code=?");
                $sql->bind_param("ss", $currentCourseID, $currentCourseID);
                $sql->execute();
                $result = $sql->get_result();
		$c = $result->fetch_assoc();
		$title = q($c['intitule']);
		$facu = $c['faculteid'];
		$type = $c['type'];
		$visible = $c['visible'];
		$visibleChecked[$visible] = " checked='1'";
		$fake_code = q($c['fake_code']);
		$titulary = q($c['titulaires']);
		$languageCourse	= $c['languageCourse'];
		$description = q($c['description']);
		$course_keywords = q($c['course_keywords']);
		$course_addon = q($c['course_addon']);
		$password = q($c['password']);
		$checkpasssel = empty($password)? '': " checked='1'";

		@$tool_content .="
		<form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>
		<table width='99%' align='left'>
		<thead><tr>
		<td>
		<table width='100%' class='FormData' align='left'>
		<tbody>
		<tr>
			<th class='left' width='150'>&nbsp;</th>
			<td><b>$langCourseIden</b></td>
			<td>&nbsp;</td>
			</tr>
		<tr>
			<th class='left'>$langCode&nbsp;:</th>
			<td>$fake_code</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<th class='left'>$langCourseTitle&nbsp;:</th>
			<td><input type='text' name='title' value='$title' size='60' class='FormData_InputText' /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<th class='left'>$langTeachers&nbsp;:</th>
			<td><input type='text' name='titulary' value='$titulary' size='60' class='FormData_InputText' /></td>
		<td>&nbsp;</td>
		</tr>
			<tr><th class='left'>$langFaculty&nbsp;:</th>
			<td>
		<select name='facu' class='auth_input'>";
                $tempSql = $conn->prepare("SELECT id, name FROM `$mysqlMainDb`.faculte ORDER BY number");
                $tempSql->execute();
                $resultFac = $tempSql->get_result();
		while ($myfac = $resultFac->fetch_assoc()) {
                        if ($myfac['id'] == $facu) {
                                $selected = ' selected="1"';
                        } else {
                                $selected = '';
                        }
                        $tool_content .= "<option value='$myfac[id]--" .
                                         q($myfac['name']) . "'$selected>" .
                                         q($myfac['name']) . "</option>";
		}
                $tool_content .= "</select></td><td>&nbsp;</td></tr>
		<tr>
		<th class='left'>$langType&nbsp;:</th>
		<td>";

                $tool_content .= selection(array('pre' => $langpre, 'post' => $langpost, 'other' => $langother), 'type', $type);
                $tool_content .= "</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <th class='left'>$langDescription&nbsp;:</th>
        <td width='100'>
	      <table class='xinha_editor'>
          <tr>
             <td><textarea id='xinha' name='description' cols='20' rows='4' class='FormData_InputText'>$description</textarea></td>
          </tr>
          </table>
        </td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <th class='left'>$langCourseKeywords&nbsp;</th>
        <td><input type='text' name='course_keywords' value='$course_keywords' size='60' class='FormData_InputText' /></td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <th class='left'>$langCourseAddon&nbsp;</th>
        <td width='100'>
	      <table class='xinha_editor'>
          <tr>
        <td><textarea id='xinha2' name='course_addon' cols='20' rows='4' class='FormData_InputText'>$course_addon</textarea></td>
        </tr>
          </table>
          </td>
          <td>&nbsp;</td>
      </tr>
      </tbody>
      </table>
      <p>&nbsp;</p>
      <table width='100%' class='FormData' align='left'>
      <tbody>
      <tr>
        <th class='left' width='150'>&nbsp;</th>
        <td colspan='2'><b>$langConfidentiality</b></td>
      </tr>
      <tr>
        <th class='left'><img src='../../template/classic/img/OpenCourse.gif' alt='$m[legopen]' title='$m[legopen]' width='16' height='16' />&nbsp;$m[legopen]&nbsp;:</th>
        <td width='1'><input type='radio' name='formvisible' value='2'".@$visibleChecked[2]." /></td>
        <td>$langPublic&nbsp;</td>
      <tr>
        <th rowspan='2' class='left'><img src='../../template/classic/img/Registration.gif' alt='$m[legrestricted]' title='$m[legrestricted]' width='16' height='16' />&nbsp;$m[legrestricted]&nbsp;:</th>
        <td><input type='radio' name='formvisible' value='1'".@$visibleChecked[1]." /></td>
        <td>$langPrivOpen</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td bgcolor='#F8F8F8'><input type='checkbox' name='checkpassword'$checkpasssel />&nbsp;$langOptPassword&nbsp;<input type='text' name='password' value='$password' class='FormData_InputText' />
        </td>
      </tr>
      <tr>
        <th class='left'><img src='../../template/classic/img/ClosedCourse.gif' alt='$m[legclosed]' title='$m[legclosed]' width='16' height='16' />&nbsp;$m[legclosed]&nbsp;:</th>
        <td><input type='radio' name='formvisible' value='0'".@$visibleChecked[0]." /></td>
        <td>$langPrivate&nbsp;</td>
      </tr>
      </tbody>
      </table>
      <p>&nbsp;</p>
      <table width='100%' class='FormData' align='left'>
      <tbody>
      <tr>
        <th class='left' width='150'>&nbsp;</th>
        <td colspan='2'><b>$langLanguage</b></td>
      </tr>
      <tr>
        <th class='left'>$langOptions&nbsp;:</th>
        <td width='1'>";
		$language = $c['languageCourse'];
		$tool_content .= lang_select_options('localize');
		$tool_content .= "
        </td>
        <td><small>$langTipLang</small></td>
      </tr>
      <tr>
        <th class='left' width='150'>&nbsp;</th>
        <td>
        <input type = 'hidden' name = 'csrf_token' value ='".$_SESSION['csrf_token']."'/>
        <input type='submit' name='submit' value='$langSubmit' /></td>
        <td>&nbsp;</td>
      </tr>
      </tbody>
      </table>
    </td>
  </tr>
  </thead>
  </table>
</form>";
}

add_units_navigation(TRUE);
draw($tool_content, 2, 'course_info', $head_content);
