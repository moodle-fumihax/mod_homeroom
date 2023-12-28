<?php 

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$courseid = required_param('course', PARAM_INT);   // course

$urlparams['course'] = $courseid;
$PAGE->set_url('/mod/homeroom/index.php', $urlparams);


$course = $DB->get_record('course', array('id'=>$courseid));
if (!$course) {
	print_error(' Course ID is incorrect');
}

require_login($course->id);


/// Get all required strings
$strhomerooms = get_string('modulenameplural', 'homeroom');
$strhomeroom  = get_string('modulename', 'homeroom');


/// Print the header
if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?course=$course->id\">$course->shortname</a> ->";
}

print_header("$course->shortname: $strhomerooms", "$course->fullname", 
						"$navigation $strhomerooms", '', '', true, '', '');

/// Get all the appropriate data
if (! $homerooms = get_all_instances_in_course('homeroom', $course)) {
	notice('There are no homerooms', "../../course/view.php?id=$course->id");
	die();
}

/// Print the list of instances (your module will probably extend this)
$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

$table = new html_table();
if ($course->format=='weeks') {
	$table->head  = array($strweek, $strname);
	$table->align = array('center', 'left');
} 
else if ($course->format=='topics') {
	$table->head  = array($strtopic, $strname);
	$table->align = array('center', 'left', 'left', 'left');
} 
else {
	$table->head  = array($strname);
	$table->align = array('left', 'left', 'left');
}

foreach ($homerooms as $homeroom) {
	//
	if (!$homeroom->visible) {
		//Show dimmed if the mod is hidden
		$link = "<a class=\"dimmed\" href=\"view.php?course=$homeroom->coursemodule\">$homeroom->name</a>";
	} 
	else {
		//Show normal if the mod is visible
		$link = "<a href=\"view.php?course=$homeroom->coursemodule\">$homeroom->name</a>";
	}

	if ($course->format=='weeks' or $course->format=='topics') {
		$table->data[] = array($homeroom->section, $link);
	} else {
		$table->data[] = array($link);
	}
}

echo '<br />';
echo html_writer::table($table);

echo $OUTPUT->footer($course);
