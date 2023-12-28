<?php

//////////////////////////////////////////////////////////////////////////////////////////////
// User List

function homeroom_user_list_header(&$table, $name_pattern, $slctyear, $sort, $order)
{
	global $CFG, $wwwBaseUrl;

	if ($order=='ASC') $order = 'DESC';
	else			   $order = 'ASC';

	$firstname_url = $wwwBaseUrl.'&amp;sort=firstname&amp;slctyear='.$slctyear;
	$lastname_url  = $wwwBaseUrl.'&amp;sort=lastname&amp;slctyear='.$slctyear;
	$idnumber_url  = $wwwBaseUrl.'&amp;sort=idnumber&amp;slctyear='.$slctyear;

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

	$table->head [] = get_string('courselist', 'homeroom');
	$table->align[] = 'left';
	$table->size [] = '420px'; 
	$table->wrap [] = '';

	return;
}




//
function homeroom_print_user_list($students, $name_pattern, $slctyear=0, $sort='', $order='')
{
	global $CFG, $DB, $OUTPUT, $wwwBaseUrl;
	
	$table = new html_table();
	$linkurl = $wwwBaseUrl.'&amp;action=userreport&amp;slctyear='.$slctyear.'&amp;sort=date&amp;order=DESC';

	//
	echo '<br />';
	echo '<div align="center">';

	$pic_options = array('size'=>20, 'link'=>true, 'courseid'=>1, 'alttext'=>true, 'popup'=>true);
	$i = 0;
	foreach($students as $student) {
		$courselist = homeroom_get_user_course_list_string($student, $slctyear, 20);
		if (!empty($courselist)) {
			$i++;
			$table->data[$student->id][] = $i;
			$table->data[$student->id][] = $OUTPUT->user_picture($student, $pic_options);
			$table->data[$student->id][] = '<a href="'.$linkurl.'&amp;userid='.$student->id.'">'.$student->name.'</a>';
			if ($CFG->output_idnumber) {
				if (empty($student->idnumber)) $table->data[$student->id][] = '-';
				else                           $table->data[$student->id][] = $student->idnumber;
			}

			$courselist.= ', <a href="'.$wwwBaseUrl.'&amp;userid='.$student->id.
								'&amp;action=usercrslist&amp;slctyear='.$slctyear.'&amp;sort=date&amp;order=DESC">ALL</a>';
			$table->data[$student->id][] = $courselist;
			//
			if ($i%PAGE_ROW_SIZE==0) {
				homeroom_user_list_header($table, $name_pattern, $slctyear, $sort, $order);
				echo '<div style="margin: 0px 0px 0px 10px;"">';
				echo html_writer::table($table);
				echo '</div>';
				unset($table->data);
			}
		}
	}

	if ($i%PAGE_ROW_SIZE!=0 or $i==0) {
		homeroom_user_list_header($table, $name_pattern, $slctyear, $sort, $order);
		echo '<div style="margin: 0px 0px 0px 10px;"">';
		echo html_writer::table($table);
		echo '</div>';
	}
	//
	echo '</div>';
	echo '<br />';

	return;
}



function  homeroom_sort_members($members, $name_pattern, $sort, $order)
{
	$num = 10000000;
	$students = array();
	if ($name_pattern=='fullname') {
		if ($sort=='idnumber') {
			foreach($members as $member) {
				$member->name = jbxl_get_user_name($member, 'fullname');
				if (empty($member->idnumber)) {
					$students[$num] = $member;
					$num++;
				}
				else {
					$students[$member->idnumber] = $member;
				}
			}
		}
		else if ($sort=='lastname') {
			foreach($members as $member) {
				$member->name = jbxl_get_user_name($member, 'fullname');
				if (empty($member->lastname)) {
					$students[$num] = $member;
					$num++;
				}
				else {
					$students[$member->lastname] = $member;
				}
			}
		}
		else {
			foreach($members as $member) {
				$member->name = jbxl_get_user_name($member, 'fullname');
				if (empty($member->firstname)) {
					$students[$num] = $member;
					$num++;
				}
				else {
					$students[$member->firstname] = $member;
				}
			}
		}
	}
	else {
		foreach($members as $member) {
			$member->name = jbxl_get_user_name($member, $name_pattern);
			$students[$member->name] = $member;
		}
	}
	//

	if ($order=='ASC') ksort ($students);
	else               krsort($students);

	return $students;
}
