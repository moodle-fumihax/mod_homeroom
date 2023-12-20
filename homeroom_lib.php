<?php

// function homeroom_get_authoriz($courseid, $roomid, $userid)
// function homeroom_get_all_members()
// function homeroom_get_members($courseid, $roomid, $userid)
// function homeroom_has_show_permit($userid, $members)
// function homeroom_get_permit_users($students, $members)
// function homeroom_get_user_course_list_string($user, $slctyear=0, $limit=0)
// function homeroom_get_user_course_list_array($user, $slctyear=0, $limit=0)
// function homeroom_get_namepattern($courseid)

// function homeroom_get_user_info($user_id)
// function homeroom_print_error_messagebox($str, $id, $view_url='mod/homeroom')
// function homeroom_get_semester($year)




define('HOMEROOM_AUTHOR_FORBIDDEN', 0);
define('HOMEROOM_AUTHOR_ONESELF',	1);
define('HOMEROOM_AUTHOR_ALL',		2);
define('HOMEROOM_AUTHOR_SPECIFIC',  3);

defined('ONE_HOUR_TIME') || define('ONE_HOUR_TIME', 3600);
defined('ONE_DAY_TIME')  || define('ONE_DAY_TIME',  86400);

if (file_exists ('../../blocks/autoattend/jbxl/jbxl_moodle_tools.php')) {
	include_once('../../blocks/autoattend/jbxl/jbxl_tools.php');
	include_once('../../blocks/autoattend/jbxl/jbxl_moodle_tools.php');
}
else {
	include_once('jbxl/jbxl_tools.php');
	include_once('jbxl/jbxl_moodle_tools.php');
}
include_once('homeroom_exdb_lib.php');
include_once('timezonedef.php');


define('PAGE_ROW_SIZE', 	$CFG->page_row_size);
define('PAGE_COLUMN_SIZE',  $CFG->page_column_size);



/////////////////////////////////////////////////////////////////////////////


//
// $userid の $courseid, $roomid での権限を返す
//
function homeroom_get_authoriz($courseid, $roomid, $userid)
{
	global $DB;

	$ret = $DB->get_record('homeroom_user', array('roomid'=>$roomid, 'userid'=>$userid));
	//
	if (!$ret) {
		$insert = new StdClass();
		$insert->roomid = $roomid;
		$insert->userid = $userid;
		$insert->timemodified = time();

		if (jbxl_is_teacher($userid, $courseid) or jbxl_is_assistant($userid, $courseid)) {
			$insert->authorize = HOMEROOM_AUTHOR_ALL;
		}
		else {
			$insert->authorize = HOMEROOM_AUTHOR_ONESELF;
		}
		$DB->insert_record('homeroom_user', $insert);
		//
		$author = $insert->authorize;
	}
	else {
		$author = $ret->authorize;
	}

	return $author;
}


//
// システム内で，出欠システムを使用している全てのユーザを返す．
//
function homeroom_get_all_members()
{
	global $DB;
	
	//
	$members = array();	
	$ids = homeroom_get_all_member_ids();

	foreach ($ids as $id) {
		$members[] = $DB->get_record('user', array('id'=>$id, 'deleted'=>0));
	}
	return $members;
}   


//
// $userid が見ることのできる（権限のある）ユーザを返す．
// 
function homeroom_get_members($courseid, $roomid, $userid)
{
	global $DB;

	$members = array();

	$author = homeroom_get_authoriz($courseid, $roomid, $userid);

	if ($author==HOMEROOM_AUTHOR_ONESELF) {
		$members[] = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0));
	}
	//
	else if ($author==HOMEROOM_AUTHOR_ALL or $author==HOMEROOM_AUTHOR_SPECIFIC) {
		//
		$users = $DB->get_records('homeroom_student', array('roomid'=>$roomid, 'teacher'=>$userid));
		if ($users) {
			foreach ($users as $user) {
				if ($user->selected) {
					$members[] = $DB->get_record('user', array('id'=>$user->student, 'deleted'=>0));
				}
			}
		}
		else {
			if ($author==HOMEROOM_AUTHOR_ALL) {
				$members = homeroom_get_all_members();
			}
		}
	}

	return $members;
}


//
// $userid が $members（権限のあるユーザ）に含まれるか検査する．
//
// $student:  検査したいユーザ．
// $members:  権限（被権限）のあるユーザの配列． $members = homeroom_get_members();
//
function homeroom_has_show_permit($userid, $members)
{
	$perm = false;

	foreach($members as $member) {
		if ($member->id==$userid) {
			$perm = true;
			break;
		}
	}

	return $perm;
}
 


//
// $students の中から権限のあるユーザを選び出して返す．
// 	
// $students: 検査したいユーザ配列．
// $members:  権限（被権限）のあるユーザの配列． $members = homeroom_get_members();
//
function homeroom_get_permit_users($students, $members)
{
	$users = array();

	foreach ($students as $student) {
		foreach($members as $member) {
			if ($member->id==$student->id) {
				$users[] = $student;
				break;
			}
		}
	}
	return $users;
}



//
// $user が出欠を取っているコースの文字列を返す．
//
// $limit: 返すコースの数の上限．0 なら全てのコース名の文字列（リンク付き）．
//  
function homeroom_get_user_course_list_string($user, $slctyear=0, $limit=0)
{
	global $wwwBaseUrl;

	$courselisting = '';

	//$mycourses = enrol_get_all_users_courses($user->id, true, NULL, 'visible DESC,sortorder ASC');
	$mycourses = homeroom_get_attend_course($user, $slctyear);
	if ($mycourses) {
		$shown = 0;
		foreach ($mycourses as $mycourse) {
			if ($mycourse->category) {
				$ccontext = context_course::instance($mycourse->id);
				if ($mycourse->visible==0) {
					if (!has_capability('moodle/course:viewhiddencourses', $ccontext)) {
						continue;
					}
				}
				if ($shown>0) $courselisting .= ', ';
				$linkurl = $wwwBaseUrl.'&amp;userid='.$user->id.'&amp;crsid='.$mycourse->id.'&amp;action=usercrsreport';			
				$courselisting .= "<a href=\"$linkurl\">".$ccontext->get_context_name(false).'</a>';			
			}
			//
			$shown++;
			if($limit!=0 and $shown==$limit) {
				$courselisting .= '...';
				break;
			}
		}
	}

	return $courselisting;
}


//
// $user が出欠を取っているコースの配列を返す．
//
// $limit: 返すコースの数の上限．0 なら全てのコース名の配列．
//  
function homeroom_get_user_course_list_array($user, $slctyear=0, $limit=0)
{
	global $DB, $wwwBaseUrl;

	$courses = array();

	$mycourses = homeroom_get_attend_course($user, $slctyear);
	if ($mycourses) {
		$shown = 0;
		foreach ($mycourses as $mycourse) {
			if ($mycourse->category) {
				$ccontext = context_course::instance($mycourse->id);
				if ($mycourse->visible==0) {
					if (!has_capability('moodle/course:viewhiddencourses', $ccontext)) {
						continue;
					}
				}
				$course = $DB->get_record('course', array('id'=>$mycourse->id));
				if ($course) $courses[$mycourse->id] = $course;			
			}
			//
			$shown++;
			if($limit!=0 and $shown==$limit) {
				break;
			}
		}
	}

	return $courses;
}


function homeroom_get_namepattern($courseid)
{
	global $DB;

	if ($courseid==0) return 'fullname';

	$pattern = $DB->get_field('homeroom', 'namepattern', array('course'=>$courseid));
	if (!$pattern) $pattern = 'fullname';

	return $pattern;
}



/////////////////////////////////////////////////////////////////////////////////////////////
// $user is user object or user id
//

function homeroom_get_user_info($user_id)
{
	global $DB;

	$ufields = user_picture::fields('u');   // u.id, u.picture, u.firstname, u.lastname, u.imagealt, u.email
	$sql = 'SELECT '.$ufields.' FROM {user} u WHERE u.id='.$user_id;
	$ret = $DB->get_record_sql($sql);

	return $ret;
}


function homeroom_print_error_messagebox($str, $id, $view_url='mod/homeroom')
{
	global $OUTPUT, $CFG;

	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

	if ($str!='' and $str!=null) {
		echo '<h2><font color="red"><div align="center">';
		echo get_string($str, 'homeroom');
		echo '</div></font></h2>';
	}

	echo $OUTPUT->continue_button($CFG->wwwroot.'/'.$view_url.'/view.php?id='.$id);
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
}


//
function homeroom_get_semester($year)
{
	global $CFG, $TIME_OFFSET;

	$ret = array();
	$ret[0] = strtotime($year.'/'.$CFG->semester_month.'/01') - $TIME_OFFSET;
	$ret[1] = $ret[0] + ONE_DAY_TIME*365;

	return $ret;
}


