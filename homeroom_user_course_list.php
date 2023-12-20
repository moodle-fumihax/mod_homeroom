<?php


///////////////////////////////////////////////////////////////////////////////////////////////
// User Report

function homeroom_user_course_list_header(&$table, $userid, $settings, $slctyear, $sort, $order, $printing)
{
	global $wwwBaseUrl;

	unset($table->head);
	unset($table->align);
	unset($table->size);
	unset($table->wrap);

	if ($order=='ASC') $order = 'DESC';
	else			   $order = 'ASC';	

	$linkurl = $wwwBaseUrl.'&amp;userid='.$userid.'&amp;action=usercrslist&amp;slctyear='.$slctyear.'&amp;printing='.$printing;
	$dateurl = $linkurl.'&amp;sort=date';
	$crsurl  = $linkurl.'&amp;sort=fullname';

	// Header
	$table->head [] = '#';
	$table->align[] = 'right';
	$table->size [] = '20px';
	$table->wrap [] = 'nowrap';

	if ($sort=='date') $dateurl.= '&amp;order='.$order;
	$table->head [] = '<a href="'.$dateurl.'">'.get_string('startdate','homeroom').'</a>';
	$table->align[] = 'center';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	if ($sort=='fullname') $crsurl.= '&amp;order='.$order;
	$table->head [] = '<a href="'.$crsurl.'">'.get_string('coursename', 'homeroom').'</a>';
	$table->align[] = 'lfet';
	$table->size [] = '100px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('classname','homeroom');
	$table->align[] = 'center';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('attendgradeshort','homeroom');
	$table->align[] = 'center';
	$table->size [] = '20px';
	$table->wrap [] = 'nowrap';

	$table->head [] = '%';
	$table->align[] = 'center';
	$table->size [] = '40px';
	$table->wrap [] = 'nowrap';

	for ($i=0; $i<5; $i++) {
		$table->align[] = 'center';
		$table->size [] = '20px';
		$table->wrap [] = 'nowrap';
	}

	array_push($table->head, $settings['P']->title, $settings['L']->title, $settings['E']->title, $settings['X']->title, $settings['Y']->title);

	return;
}




function homeroom_print_user_course_list($user, $name_pattern='fullname', $slctyear=0, $sort='', $order='', $printing=null)
{
	global $CFG, $DB, $OUTPUT, $wwwBaseUrl, $TIME_OFFSET;

	if ($sort=='') {
		$sort  = 'date';
		$order = 'DESC';
	}
	else {
		if ($sort!='courseid' and $sort!='fullname') $sort = 'date';
	}
	if ($order=='') $order = 'DESC';


	$table = new html_table();
	$datas = array();
	$linkurl  = $wwwBaseUrl.'&amp;userid='.$user->id.'&amp;action=usercrsreport&amp;slctyear='.$slctyear;
	$printUrl = $wwwBaseUrl.'&amp;userid='.$user->id.'&amp;action=usercrslist&amp;slctyear='.$slctyear.'&amp;printing=yes&amp;sort='.$sort.'&order='.$order;

	$settings = homeroom_get_acronyms(0);
	$courses  = homeroom_get_user_course_list_array($user, $slctyear);

	$i = 0;
	foreach($courses as $course) {
		$summary = homeroom_get_user_summary($user->id, $course->id);
		if($summary and $summary['classid']>=0) {
			$course_fullname = $course->fullname;
			if (!$printing) $course_link = '<a href="'.$linkurl.'&amp;crsid='.$course->id.'">'.$course_fullname.'</a>';
			else 			$course_link = $course_fullname;
			//
			$firstdate = reset($summary['attitems']);
			$datas[$i]['date'] 		= $firstdate->sessdate + $TIME_OFFSET;
			$datas[$i]['courseid'] 	= $course->id;
			$datas[$i]['fullname']  = $course_fullname;
			$datas[$i]['fnameurl']  = $course_link;
			$datas[$i]['classname'] = $summary['classname'];
			$datas[$i]['grade'] 	= $summary['grade'].'/'.$summary['maxgrade'];
			$datas[$i]['percent'] 	= $summary['percent'];
			$datas[$i]['P'] 		= $summary['P'];
			$datas[$i]['L'] 		= $summary['L'];
			$datas[$i]['E'] 		= $summary['E'];
			$datas[$i]['X'] 		= $summary['X'];
			$datas[$i]['Y'] 		= $summary['Y'];
			$i++;
		}
	}

	//
	// sorted by 'date', 'courseid', 'fullname'
	$keys = array();
	foreach ($datas as $key=>$data) {
		$keys[$key] = $data[$sort];
	}
	if ($order=='ASC') array_multisort($keys, SORT_ASC,  $datas);
	else 			   array_multisort($keys, SORT_DESC, $datas);

	//
	if ($CFG->output_idnumber) {
		if (empty($user->idnumber)) $disp_idnum = '[ - ]';
		else $disp_idnum = '['.$user->idnumber.']';
	}
	else $disp_idnum = '';

	//
	$username = jbxl_get_user_name($user, $name_pattern);
	include('html/user_report_header.html');

	$i = 0;
	foreach($datas as $data) {
		$table->data[$i][] = $i + 1;
		$table->data[$i][] = strftime(get_string('strftimedmy', 'homeroom'), $data['date']);
		$table->data[$i][] = $data['fnameurl'];
		$table->data[$i][] = $data['classname'];
		$table->data[$i][] = $data['grade'];
		$table->data[$i][] = $data['percent'];
		$table->data[$i][] = $data['P'];
		$table->data[$i][] = $data['L'];
		$table->data[$i][] = $data['E'];
		$table->data[$i][] = $data['X'];
		$table->data[$i][] = $data['Y'];
		$i++;
	}

	homeroom_user_course_list_header($table, $user->id, $settings, $slctyear, $sort, $order, $printing);
	echo '<div align="center">';
	echo html_writer::table($table);
	echo '</div>';

	echo '</td>';
	echo '</tr>';
	echo '</table>';
	//
	echo '</div>';

	return;
}




