<?php


///////////////////////////////////////////////////////////////////////////////////////////////
// Course List


function homeroom_course_list_header(&$table, $slctyear, $sort, $order)
{
	global $wwwBaseUrl;

	unset($table->head);
	unset($table->align);
	unset($table->size);
	unset($table->wrap);

	if ($order=='ASC') $order = 'DESC';
	else			   $order = 'ASC';	

	$linkurl = $wwwBaseUrl.'&amp;action=crslist&amp;slctyear='.$slctyear;
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
	$table->head [] = '<a href="'.$crsurl.'"><center>'.get_string('course', 'homeroom').'</center></a>';
	$table->align[] = 'left';
	$table->size [] = '100px';
	$table->wrap [] = 'nowrap';

	return;
}



function homeroom_print_course_list($slctyear=0, $sort='', $order='')
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
	$crsurl = $wwwBaseUrl.'&amp;action=crsreport';

	$courses = homeroom_get_courses($slctyear);

	$i = 0;
	foreach($courses as $course) {
		$datas[$i]['date']	   = $course->sdate + $TIME_OFFSET;
		$datas[$i]['courseid'] = $course->id;
		$datas[$i]['fullname'] = $course->fullname;
		$i++;
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
	include('html/course_list_header.html');

	$i = 0;
	foreach($datas as $data) {
		$table->data[$i][] = $i + 1;
		$table->data[$i][] = strftime(get_string('strftimedmy', 'homeroom'), $data['date']);
		$table->data[$i][] = '<a href="'.$crsurl.'&amp;crsid='.$data['courseid'].'">'.$data['fullname'].'</a>';
		$i++;
	}

	homeroom_course_list_header($table, $slctyear, $sort, $order);
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
