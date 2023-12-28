<?php

defined('MOODLE_INTERNAL') || die;


//
require_once(dirname(__FILE__).'/lib.php');



function homeroom_init_session()
{
	global $SESSION;

	if (!empty($SESSION)) {
		if (!isset($SESSION->homeroom) or !is_object($SESSION->homeroom)) {
			$SESSION->homeroom = new stdClass();
		}
	}
}


function homeroom_get_event($cm, $action, $params='', $info='')
{
	global $CFG;
	if (file_exists ($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php')) {
		require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_tools.php');
		require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php');
	}
	else {
		require_once($CFG->dirroot.'/mod/homeroom/jbxl/jbxl_tools.php');
		require_once($CFG->dirroot.'/mod/homeroom/jbxl/jbxl_moodle_tools.php');
	}

	$ver = jbxl_get_moodle_version();

	$event = null;
	if (!is_array($params)) $params = array();

	if (floatval($ver)>=2.7) {
		$params = array(
			'context' => context_module::instance($cm->id),
			'other' => array('params' => $params, 'info'=> $info),
		);
		//
		if ($action=='view') {
			$event = \mod_homeroom\event\view_log::create($params);
		}
		else if ($action=='select') {
			$event = \mod_homeroom\event\select_log::create($params);
		}

	}

	// for Legacy add_to_log()	  
	else {
		if ($action=='select') {
			$file = 'select.php';
		}
		else {
			$file = 'view.php';
		}
		$param_str = jbxl_get_url_params_str($params);
		//
		$event = new stdClass();
		$event->courseid= $cm->course;
		$event->name	= 'homeroom';
		$event->action  = $action;
		$event->url	 = $file.$param_str;
		$event->info	= $info;
	}
   
	return $event;
}

