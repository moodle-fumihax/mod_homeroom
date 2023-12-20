<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * the first page to view the homeroom
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_homeroom
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/tablelib.php');

homeroom_init_session();
$SESSION->homeroom->is_started = false;

//
$id 	  = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$crsid	  = optional_param('crsid', 1, PARAM_INT);
$action   = optional_param('action', 'userlist', PARAM_ALPHAEXT);
$sort	  = optional_param('sort', 'lastname', PARAM_ALPHA);
$order	  = optional_param('order', 'ASC', PARAM_ALPHA);
$userid   = optional_param('userid', 0, PARAM_INT);
$printing = optional_param('printing', '', PARAM_ALPHA);
$slctyear = optional_param('slctyear', '', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

if (($formdata = data_submitted()) and !confirm_sesskey()) {
    print_error('invalidsesskey');
}

$now_year = date('Y');
$now_mnth = date('m');
if ($now_mnth < $CFG->semester_month) $now_year--;
if ($slctyear==='') {
	$slctyear = $now_year;
	if ($action=='userlist') $slctyear = 0;
}

$urlparams['id'] 	   = $id;
$urlparams['action']   = $action;
$urlparams['courseid'] = $courseid;
$urlparams['crsid']    = $crsid;
$urlparams['sort']	   = $sort;
$urlparams['order']	   = $order;
$urlparams['slctyear'] = $slctyear;
if ($userid)   $urlparams['userid']   = $userid;
if ($printing) $urlparams['printing'] = $printing;

//
$wwwBaseUrl = $CFG->wwwroot.'/mod/homeroom/view.php?id='.$id;


////////////////////////////////////////////////////////
//get the objects
if (! $cm = get_coursemodule_from_id('homeroom', $id)) {
	print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
	print_error('coursemisconf');
}
if (! $homeroom = $DB->get_record('homeroom', array('id'=>$cm->instance))) {
	print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$name_pattern = $homeroom->namepattern;
$attendPlugin = $homeroom->attendplugin;
$dispFeedback = $homeroom->feedback;
$mcontext = context_module::instance($cm->id);
$ccontext = context_course::instance($courseid);

//
include_once('homeroom_lib.php');


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
//
$homeroom_view_cap = false;
if (has_capability('mod/homeroom:view', $mcontext)) {
	$homeroom_view_cap = true;
}
else {
	homeroom_print_error_messagebox('homeroom_is_disable', $crsid, 'course');
	exit;
}

$members = homeroom_get_members($course->id, $homeroom->id, $USER->id);


///////////////////////////////////////////////////////////////////////////
// URL
$strhomerooms = get_string('modulenameplural', 'homeroom');
$strhomeroom  = get_string('modulename', 'homeroom');

$this_url = new moodle_url($wwwBaseUrl);
$this_url->params($urlparams);
$log_url  = explode('/', $this_url);

//
$event = homeroom_get_event($cm, 'view', $urlparams);
jbxl_add_to_log($event);

// Year Select
$slctoptions = array();
$slctoptions[0] = 'ALL';
for ($t=$now_year - 5; $t<=$now_year; $t++) {	// 5年前まで
	$slctoptions[$t] = $t;
}
$slcturl = $this_url->out();



///////////////////////////////////////////////////////////////////////////
// Download
if ($action=='crsreport' and $download!='') {
	require_once('homeroom_course_report.php');
	//
	$students = homeroom_get_attend_students($crsid, 0, $sort, $order);
	$students = homeroom_get_permit_users($students, $members);
	if ($students) {
   		$classes  = homeroom_get_session_classes($crsid);
    	if($download=='excel') {
       		homeroom_download('xls', $crsid, $students, $classes, $name_pattern);
			die();
		}
		else if($download=='text') {
       		homeroom_download('txt', $crsid, $students, $classes, $name_pattern);
			die();
		}
	}
}



///////////////////////////////////////////////////////////////////////////
// Print the page header
$PAGE->navbar->add(get_string('homeroom:list', 'homeroom'));
$PAGE->set_url($this_url);
$PAGE->set_title(format_string($homeroom->name));
$PAGE->set_heading(format_string($course->fullname));

if ($printing) $PAGE->set_pagelayout('print');
echo $OUTPUT->header();

$current_tab = $action;
if (!$printing) require('tabs.php');
if ($userid==0) $userid = $USER->id;
$fullname = jbxl_get_user_name($userid, $name_pattern);



///////////////////////////////////////////////////////////////////////////
// ユーザ表示
//   出欠を閲覧することが可能な学生の一覧を表示
//
if ($action=='userlist') {
	require_once('homeroom_user_list.php');
	$students = homeroom_sort_members($members, $name_pattern, $sort, $order);
	//
	include('html/select_header.html');
	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
	homeroom_print_user_list($students, $name_pattern, $slctyear, $sort, $order);
	echo $OUTPUT->box_end();
}



///////////////////////////////////////////////////////////////////////////
// ユーザコースリスト
//   １ユーザの全てのコースのリストを表示する．
//
else if ($action=='usercrslist') {
	$permit = homeroom_has_show_permit($userid, $members);
	if (!$permit) die();

	require_once('homeroom_user_course_list.php');
	if (!$printing) include('html/select_header.html');
	$user = $DB->get_record('user', array('id'=>$userid));
	homeroom_print_user_course_list($user, $name_pattern, $slctyear, $sort, $order, $printing);
	if ($printing) die();
}



///////////////////////////////////////////////////////////////////////////
// ユーザ レポート
//   １ユーザの全てのコースの出席を表示する．
//
else if ($action=='userreport') {
	$permit = homeroom_has_show_permit($userid, $members);
	if (!$permit) die();

	require_once('homeroom_user_report.php');
	if (!$printing) include('html/select_header.html');
	$user = $DB->get_record('user', array('id'=>$userid));
	homeroom_print_user_report($user, $name_pattern, $slctyear, $sort, $order, $printing);
	if ($printing) die();
}



///////////////////////////////////////////////////////////////////////////
// ユーザ コースレポート
//   １ユーザの特定のコースの出席を表示する．
//
else if ($action=='usercrsreport') {
	$permit = homeroom_has_show_permit($userid, $members);
	if (!$permit) die();

	require_once('homeroom_user_course_report.php');
	$ccrs = $DB->get_record('course', array('id'=>$crsid));
	$user = $DB->get_record('user',   array('id'=>$userid));
	homeroom_print_user_course_report($user, $ccrs, $name_pattern, $printing);
	if ($printing) die();
}


///////////////////////////////////////////////////////////////////////////
// コースリスト
//
else if ($action=='crslist') {
	require_once('homeroom_course_list.php');
	include('html/select_header.html');
	$user = $DB->get_record('user', array('id'=>$userid));
	homeroom_print_course_list($slctyear, $sort, $order);
}


///////////////////////////////////////////////////////////////////////////
// コースレポート
//
else if ($action=='crsreport') {
	require_once('homeroom_course_report.php');
	$students = homeroom_get_attend_students($crsid, 0, $sort, $order);
	homeroom_print_course_report($students, $members, $crsid, $name_pattern, $sort, $order);
}



/////////////////////////////////////////
if (empty($plugin)) $plugin = new stdClass();
include('version.php');
//
echo '<div align="center"><br />';
if ($homeroom->feedback) {
	echo '<a href="https://el.mml.tuis.ac.jp/moodle/mod/feedback/view.php?id=530" target="_blank"><strong>'.get_string('pleasefeedback','homeroom').'</strong></a>';
	echo '<br />'.get_string('removefeedback','homeroom');
    echo '<br /><br />';
}
echo '<a href="'.get_string('homeroom_url', 'homeroom').'" target="_blank"><i>mod_homeroom '.$plugin->release.'</i></a>';
echo '<br /><br />';
echo '</div>';


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

