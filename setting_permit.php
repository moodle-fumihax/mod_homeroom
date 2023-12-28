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

if (file_exists ($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php')) {
	require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php');
//	require_once(dirname(__FILE__).'/jbxl/jbxl_moodle_tools.php');
}
else {
	require_once($CFG->dirroot.'/mod/homeroom/jbxl/jbxl_moodle_tools.php');
//	require_once(dirname(__FILE__).'/jbxl/jbxl_moodle_tools.php');
}

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

$urlparams['id'] 	   = $id;
$urlparams['action']   = $action;
if ($userid) $urlparams['userid']   = $userid;
$urlparams['courseid'] = $courseid;
$urlparams['crsid']	   = $crsid;
$urlparams['sort']	   = $sort;
$urlparams['order']	   = $order;
if ($printing) $urlparams['printing'] = $printing;

if (($formdata = data_submitted()) and !confirm_sesskey()) {
    print_error('invalidsesskey');
}
//
$wwwBaseUrl = $CFG->wwwroot.'/mod/homeroom/setting_permit.php?id='.$id;


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
require_once('homeroom_lib.php');


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
//
$homeroom_setting_cap = false;
if (has_capability('mod/homeroom:setting', $mcontext)) {
	$homeroom_setting_cap = true;
}


///////////////////////////////////////////////////////////////////////////
// URL
$strhomerooms = get_string('modulenameplural', 'homeroom');
$strhomeroom  = get_string('modulename', 'homeroom');

$this_url = new moodle_url($wwwBaseUrl);
$this_url->params($urlparams);
$log_url  = explode('/', $this_url);


///////////////////////////////////////////////////////////////////////////
// Print the page header
$PAGE->navbar->add(get_string('homeroom:list', 'homeroom'));
$PAGE->set_url($this_url);
$PAGE->set_title(format_string($homeroom->name));
$PAGE->set_heading(format_string($course->fullname));

if ( $printing) $PAGE->set_pagelayout('print');
echo $OUTPUT->header();

$current_tab = $action;
if (!$printing) require('tabs.php');
if ($userid==0) $userid = $USER->id;
$fullname = jbxl_get_user_name($userid, $name_pattern);


///////////////////////////////////////////////////////////////////////////
// Print the main part of the page

//echo $OUTPUT->heading(format_text($homeroom->name));

// Check
if (!$homeroom_setting_cap) {
	homeroom_print_error_messagebox('homeroom_is_disable', $crsid, 'course');
	exit;
}


///////////////////////////////////////////////////////////////////////////
//

// POST
if ($formdata and $action=='setting') {
	//
	foreach ($formdata as $key=>$auth) {
		if (substr($key,0,6)=='userid') {
			$uid = substr($key, 6, strlen($key)-6);
			if (is_numeric($uid)) {
				$update = $DB->get_record('homeroom_user', array('roomid'=>$homeroom->id, 'userid'=>$uid));
				if ($update) {
					$update->authorize = $auth;
					$update->timemodified = time();
					$DB->update_record('homeroom_user', $update);
					if ($auth==HOMEROOM_AUTHOR_ALL) {
						$DB->delete_records('homeroom_student', array('roomid'=>$homeroom->id, 'teacher'=>$uid, 'selected'=>0));
					}
				}
			}
		}
	}
}


if ($action=='setting') {
	//
	$students = array();
	$users = jbxl_get_course_users($ccontext);
	//
	if ($name_pattern=='fullname') {
		if ($sort=='idnumber') {
			$num = 10000000;
			foreach($users as $user) {
				$user->name = jbxl_get_user_name($user, 'fullname');
				if (empty($user->idnumber)) {
					$students[$num] = $user;
					$num++;
				}
				else {
					$students[$user->idnumber] = $user;
				}
			}
        }
		else if ($sort=='lastname') {
			foreach($users as $user) {
				$user->name = jbxl_get_user_name($user, 'fullname');
				$students[$user->lastname] = $user;
			}
		}
		else {
			foreach($users as $user) {
				$user->name = jbxl_get_user_name($user, 'fullname');
				$students[$user->firstname] = $user;
			}
		}
	}
	else {
		foreach($users as $user) {
			$user->name = jbxl_get_user_name($user, $name_pattern);
			$students[$user->name] = $user;
		}
	}
	//
	if ($order=='ASC') ksort ($students);
	else			   krsort($students);
	//

	require_once('homeroom_setting.php');
	$posturl = $wwwBaseUrl.'&amp;action=setting&amp;sort='.$sort.'&amp;order='.$order;
	//
	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
	include('html/setting_permit.html');
	echo $OUTPUT->box_end();
}


else if ($action=='setting_select') {
    //
    require_once('user_selector.php');

    $selector = new homeroom_specific_user_selector($this_url, $courseid, $homeroom->id, $name_pattern);
    $selector->set_userid($userid);
    $selector->set_title(get_string('usersettingselector', 'homeroom', $fullname));
    $selector->execute();

    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    $selector->print_page();
    echo $OUTPUT->box_end();
}



/////////////////////////////////////////
if (empty($plugin)) $plugin = new stdClass();
include('version.php');
//
echo '<div align="center"><br />';
echo '<a href="'.get_string('homeroom_url', 'homeroom').'" target="_blank"><i>mod_homeroom '.$plugin->release.'</i></a>';
//if ($homeroom->feedback) {
//	echo '&nbsp;&nbsp;&nbsp;';
//	echo '<a href="https://el.mml.tuis.ac.jp/moodle/mod/feedback/view.php?id=530" target="_blank"><strong>'.get_string('pleasefeedback','homeroom').'</strong></a>';
//}
echo '<br /><br />';
echo '</div>';


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

