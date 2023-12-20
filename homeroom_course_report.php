<?php

//////////////////////////////////////////////////////////////////////////////////////////////
//

function homeroom_course_report_header(&$table, $courseid, $sessions, $name_pattern, $classes, $settings, $sort, $order)
{
	global $CFG, $wwwBaseUrl, $TIME_OFFSET;

	if ($order=='ASC') $order = 'DESC';
	else			   $order = 'ASC';

	$firstname_url = $wwwBaseUrl.'&amp;action=crsreport&amp;crsid='.$courseid.'&amp;sort=firstname';
	$lastname_url  = $wwwBaseUrl.'&amp;action=crsreport&amp;crsid='.$courseid.'&amp;sort=lastname';
	$idnumber_url  = $wwwBaseUrl.'&amp;action=crsreport&amp;crsid='.$courseid.'&amp;sort=idnumber';

	if ($sort=='firstname') {
		$firstname = '<a href="'.$firstname_url.'&amp;order='.$order.'">'.get_string('firstname').'</a>';
		$lastname  = '<a href="'.$lastname_url.'">'.get_string('lastname').'</a>';
	}
	else {
		$firstname = '<a href="'.$firstname_url.'">'.get_string('firstname').'</a>';
		$lastname  = '<a href="'.$lastname_url.'&amp;order='.$order.'">'.get_string('lastname').'</a>';
	} 
	//
	$fullnamehead = jbxl_get_fullnamehead($name_pattern, $firstname, $lastname, '/');

	unset($table->head);
	unset($table->align);
	unset($table->size);
	unset($table->wrap);

	// Header
	$table->head [] = '#';
	$table->align[] = 'right';
	$table->size [] = '20px';
	$table->wrap [] = 'nowrap';

	$table->head [] = '';
	$table->align[] = '';
	$table->size [] = '20px';
	$table->wrap [] = 'nowrap';

	$table->head [] = '<center>'.$fullnamehead.'</center>';
	$table->align[] = 'left';
	$table->size [] = '180px';
	$table->wrap [] = 'nowrap';

	if ($CFG->output_idnumber) {
		if ($sort=='idnumber') $idnumber_url.= '&amp;order='.$order;
		$table->head [] = '<a href="'.$idnumber_url.'">ID</a>';
		$table->align[] = 'center';
		$table->size [] = '60px';
		$table->wrap [] = 'nowrap';
	}

	if ($classes) {
		$table->head [] = get_string('classname','homeroom');
		$table->align[] = 'center';
		$table->size [] = '80px'; 
		$table->wrap [] = 'nowrap';
	}

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

	array_push($table->head, $settings['P']->title, $settings['L']->title, $settings['E']->title, $settings['X']->title); 
	if ($settings['Y']->title!='') array_push($table->head, $settings['Y']->title); 

	if (!empty($sessions)) {
		$i = 0;
		foreach($sessions as $att) {
			if ($i>0 and $i%PAGE_COLUMN_SIZE==0) {
				$table->head [] = $fullnamehead;
				$table->align[] = 'left';
				$table->size [] = '180px'; 
				$table->wrap [] = 'nowrap';
			}
			$table->head [] = strftime(get_string('strftimedmshort','homeroom'), $att->sessdate+$TIME_OFFSET);
			$table->align[] = 'center';
			$table->size [] = '40px';
			$table->wrap [] = 'nowrap';
			$i++;
		}
	}

	return;
}



//
function homeroom_print_course_report($students, $members, $courseid, $name_pattern, $sort, $order)
{
	global $DB, $CFG, $OUTPUT, $wwwBaseUrl;
	
	$table = new html_table();
	$linkurl  = $wwwBaseUrl.'&amp;action=userreport';
	$downurl  = $wwwBaseUrl.'&amp;action=crsreport&amp;crsid='.$courseid.'&amp;sort='.$sort.'&amp;order='.$order;
	$settings = homeroom_get_acronyms($courseid);
	$classes  = homeroom_get_session_classes($courseid);
	$datas    = array();
	$sessions = array();
	$columns  = 0;

	// Course Title
  	$course_fullname = $DB->get_field('course', 'fullname', array('id'=>$courseid));
    include('html/course_report_header.html');

	$pic_options = array('size'=>20, 'link'=>true, 'courseid'=>1, 'alttext'=>true, 'popup'=>true);
	$i = 0;
	foreach($students as $student) {
		$perm = homeroom_has_show_permit($student->id, $members);
		if ($perm) {
			$student->name 		= jbxl_get_user_name($student->id, $name_pattern);
			$student_info 		= homeroom_get_user_info($student->id);
			$datas[$i]['id'] 	= $student->id;
			$datas[$i]['pic'] 	= $OUTPUT->user_picture($student_info, $pic_options);
			$datas[$i]['name'] 	= '<a href="'.$linkurl.'&amp;userid='.$student->id.'">'.$student->name.'</a>';

			if ($CFG->output_idnumber) {
				if (empty($student->idnumber)) $datas[$i]['idnum'] = '-'; 
				else						   $datas[$i]['idnum'] = $student->idnumber; 
			}

			$user_summary = homeroom_get_user_summary($student->id, $courseid);
			$atts = $user_summary['attitems'];
			$datas[$i]['classname'] = $user_summary['classname'];
			$datas[$i]['grade'] 	= $user_summary['grade']; 
			$datas[$i]['percent'] 	= $user_summary['percent'].'%';
			$datas[$i]['P'] 		= $user_summary['P'];
			$datas[$i]['L'] 		= $user_summary['L'];
			$datas[$i]['E'] 		= $user_summary['E'];
			$datas[$i]['X'] 		= $user_summary['X'];
			$datas[$i]['Y'] 		= $user_summary['Y'];

			$j = 0;
			$datas[$i]['session'] = array();
			foreach ($atts as $att) {
				if ($att and ($att->classid==$user_summary['classid'] or $att->classid==0)) {
					if (empty($att->status)) {
						$datas[$i]['session'][$j] = get_string('novalue','homeroom');
					}
					else {
						$datas[$i]['session'][$j] = $settings[$att->status]->acronym;
					}
				} 
				else {
					$datas[$i]['session'][$j] = get_string('novalue','homeroom');
				}
				$j++;
			}
			$columns = max($columns, $j);							// for attendance module
			if (count($sessions)<count($atts)) $sessions = $atts;	// for attendance module

			$i++;
		}
	}

	//
	$i = 0;
	foreach ($datas as $data) {
		$i++;
		$table->data[$i][] = $i;
		$table->data[$i][] = $data['pic'] ;
		$table->data[$i][] = $data['name'] ;
		if ($CFG->output_idnumber) $table->data[$i][] = $data['idnum'];
		if ($classes) 			   $table->data[$i][] = $data['classname'];
		$table->data[$i][] = $data['grade'] ;
		$table->data[$i][] = $data['percent'] ;
		$table->data[$i][] = $data['P'] ;
		$table->data[$i][] = $data['L'] ;
		$table->data[$i][] = $data['E'] ;
		$table->data[$i][] = $data['X'] ;
		if ($settings['Y']->title!='') $table->data[$i][] = $data['Y'] ;

		$len = count($data['session']);
		for ($j=0; $j<$columns; $j++) {
			if ($j>0 and $j%PAGE_COLUMN_SIZE==0) {
				$table->data[$i][] = '<a href="'.$wwwBaseUrl.'&amp;action=userreport&amp;userid='.$student->id.'">'.$data['name'].'</a>';
			}
			if ($j<$len) $table->data[$i][] = $data['session'][$j];
			else 		 $table->data[$i][] = get_string('novalue', 'homeroom');
		}

		//
		if ($i%PAGE_ROW_SIZE==0) {
			homeroom_course_report_header($table, $courseid, $sessions, $name_pattern, $classes, $settings, $sort, $order);
			echo '<div align="center" style="overflow-x: auto;">';
			echo html_writer::table($table);
			echo '</div><br /><br />';
			unset($table->data);
		}
	}

	if ($i%PAGE_ROW_SIZE!=0 or $i==0) {
		homeroom_course_report_header($table, $courseid, $sessions, $name_pattern, $classes, $settings, $sort, $order);
		echo '<div align="center" style="overflow-x: auto;">';
		echo html_writer::table($table);
		echo '</div><br /><br />';
	}

	return;
}

