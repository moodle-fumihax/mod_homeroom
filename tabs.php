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
 * prints the tabbed bar
 *
 * @author Fumi Iseki
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package homeroom
 */

defined('MOODLE_INTERNAL') OR die('not allowed');

$tabs = array();
$row  = array();
$inactive  = array();
$activated = array();

//some pages deliver the cmid instead the id
if (isset($cmid) and intval($cmid) and $cmid>0) {
	$roomid = $cmid;
}
else {
	$roomid = $id;
}
if (empty($crsid)) $crsid = optional_param('crsid', 1, PARAM_INT);

//
if (!$mcontext) $mcontext = context_module::instance($roomid);
if (!isset($current_tab)) {
	$current_tab = '';
}


$base_param = array('id'=>$roomid, 'userid'=>$userid, 'courseid'=>$courseid);

// View my homeroom
$options = array('id'=>$roomid, 'action'=>'userlist', 'courseid'=>$courseid);
$listurl = new moodle_url('/mod/homeroom/view.php', $options);
$row[] 	 = new tabobject('userlist', $listurl->out(), get_string('userlist', 'homeroom'));

if ($current_tab=='usercrslist' or $userid) {
	$options = array('action'=>'usercrslist', 'sort'=>'date', 'order'=>'DESC');
	$crslurl = new moodle_url('/mod/homeroom/view.php', array_merge($base_param, $options));
	$row[] 	 = new tabobject('usercrslist', $crslurl->out(), get_string('usercrslist', 'homeroom'));
}
if ($current_tab=='userreport' or $userid) {
	$options = array('action'=>'userreport', 'sort'=>'date', 'order'=>'DESC');
	$repturl = new moodle_url('/mod/homeroom/view.php', array_merge($base_param, $options));
	$row[] 	 = new tabobject('userreport', $repturl->out(), get_string('userreport', 'homeroom'));
}
if ($current_tab=='usercrsreport' or ($userid and $crsid>1)) {
	$options = array('action'=>'usercrsreport', 'sort'=>'date', 'order'=>'DESC');
	$crsrurl = new moodle_url('/mod/homeroom/view.php', array_merge($base_param, $options));
	$row[] 	 = new tabobject('usercrsreport', $crsrurl->out(), get_string('usercrsreport', 'homeroom'));
}

//
$options = array('id'=>$roomid, 'action'=>'crslist', 'courseid'=>$courseid);
$crslurl = new moodle_url('/mod/homeroom/view.php', $options);
$row[] 	 = new tabobject('crslist', $crslurl->out(), get_string('crslist', 'homeroom'));

if ($current_tab=='crsreport' or ($crsid>1 and empty($userid))) {
	$options = array('action'=>'crsreport', 'sort'=>'date', 'order'=>'DESC');
	$repturl = new moodle_url('/mod/homeroom/view.php', array_merge($base_param, $options));
	$row[] 	 = new tabobject('crsreport', $repturl->out(), get_string('crsreport', 'homeroom'));
}


// Edit members of homeroom
if (has_capability('mod/homeroom:select', $mcontext)) {
	$memburl = new moodle_url('/mod/homeroom/select.php', array_merge($base_param, array('action'=>'select')));
	$row[] 	 = new tabobject('select', $memburl->out(), get_string('userselect', 'homeroom'));
}
if (has_capability('mod/homeroom:setting', $mcontext)) {
	if ($current_tab=='setting_select') {
		$stslurl = new moodle_url('/mod/homeroom/setting_permit.php', array_merge($base_param, array('action'=>'setting_select')));
		$row[] 	 = new tabobject('setting_select', $stslurl->out(), get_string('usersettingselect', 'homeroom'));
	}
}

if (has_capability('mod/homeroom:setting', $mcontext)) {
	$setgurl = new moodle_url('/mod/homeroom/setting_permit.php', array_merge($base_param, array('action'=>'setting')));
	$row[] 	 = new tabobject('setting', $setgurl->out(), get_string('setting', 'homeroom'));
}


//
$row[] = new tabobject('', $CFG->wwwroot.'/course/view.php?id='.$courseid, get_string('returnbutton', 'homeroom'));
//
if (count($row) > 1) {
	$tabs[] = $row;
	print_tabs($tabs, $current_tab, $inactive, $activated);
}

