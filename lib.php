<?php

/// Library of functions and constants for module homeroom


defined('MOODLE_INTERNAL') || die;



//
function homeroom_supports($feature)
{
	switch($feature) {
		case FEATURE_GROUPS:					return false;
		case FEATURE_GROUPINGS:					return false;
		case FEATURE_GROUPMEMBERSONLY:			return false;
		case FEATURE_MOD_INTRO:					return true;
		case FEATURE_COMPLETION_TRACKS_VIEWS:	return false;
		case FEATURE_COMPLETION_HAS_RULES:		return false;
		case FEATURE_GRADE_HAS_GRADE:			return false;
		case FEATURE_GRADE_OUTCOMES:			return false;
		case FEATURE_BACKUP_MOODLE2:			return false;
		case FEATURE_SHOW_DESCRIPTION:			return true;

		default: return null;
	}
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $label
 * @return bool|int
 */
function homeroom_add_instance($homeroom)
{
	global $DB;

	$homeroom->timemodified = time();

	$ret = $DB->get_record('homeroom', array('course'=>$homeroom->course));
	//if ($ret) {
	//	print_error('mod_homeroom/already one instance is exist in this course');
	//	return false;
	//}

	$ret = $DB->insert_record('homeroom', $homeroom);
	if ($ret) $homeroom->id = $ret;

	return $ret;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $label
 * @return bool
 */
function homeroom_update_instance($homeroom)
{
	global $DB;

	if (!property_exists($homeroom, 'feedback')) $homeroom->feedback = 0;

	$homeroom->timemodified = time();
	$homeroom->id = $homeroom->instance;

	$ret = $DB->update_record('homeroom', $homeroom);

	return $ret;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function homeroom_delete_instance($id) 
{
	global $DB;

	$homeroom = $DB->get_record('homeroom', array('id'=>$id));
	if (!$homeroom) return false;

	$result = true;
	$ret = $DB->delete_records('homeroom', array('id'=>$homeroom->id));
	if (!$ret) $result = false;

	return $result;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @TODO: implement this moodle function (if needed)
 **/
function homeroom_user_outline($course, $user, $mod, $homeroom) 
{
	return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @TODO: implement this moodle function (if needed)
 **/
function homeroom_user_complete($course, $user, $mod, $homeroom) 
{
	return true;
}


/**
 * Given a course and a date, prints a summary of all the new
 * messages posted in the course since that date
 *
 * @param object $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return bool success
 */
function homeroom_print_recent_activity($course, $isteacher, $timestart)
{
	global $CFG;

  	// True if anything was printed, otherwise false 
	return false;
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 **/
function homeroom_cron()
{
	return false;
}


