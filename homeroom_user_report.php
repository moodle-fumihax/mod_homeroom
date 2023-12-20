<?php

//
// １ユーザの全コースの出欠をごちゃ混ぜに出力する
//
//

///////////////////////////////////////////////////////////////////////////////////////////////
// User Report

function homeroom_user_report_header(&$table, $userid, $slctyear, $sort, $order, $printing)
{
	global $wwwBaseUrl;
	
	unset($table->head);
	unset($table->align);
	unset($table->size);
	unset($table->wrap);

	if ($order=='ASC') $order = 'DESC';
	else 			   $order = 'ASC';

	$linkurl = $wwwBaseUrl.'&amp;userid='.$userid.'&amp;action=userreport&amp;slctyear='.$slctyear.'&amp;printing='.$printing;
	$dateurl = $linkurl.'&amp;sort=date';
	$nameurl = $linkurl.'&amp;sort=fullname';

	// Header
	$table->head [] = '#';
	$table->align[] = 'right';
	$table->size [] = '20px';
	$table->wrap [] = 'nowrap';

	if ($sort=='date') $dateurl.= '&amp;order='.$order;
	$table->head [] = '<a href="'.$dateurl.'">'.get_string('date').'</a>';
	$table->align[] = 'center';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('starttime', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '60px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('endtime', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '60px';
	$table->wrap [] = 'nowrap';

	if ($sort=='fullname') $nameurl.= '&amp;order='.$order;
	$table->head [] = '<a href="'.$nameurl.'">'.get_string('coursename', 'homeroom').'</a>';
	$table->align[] = 'lef';
	$table->size [] = '100px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('classname', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('description','homeroom');
	$table->align[] = 'left';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('status', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '40px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('callmethod', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '60px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('calledtime', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '60px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('ip', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('remarks', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '120px';
	$table->wrap [] = 'nowrap';

	return;
}




//
//
//
function homeroom_print_user_report($user, $name_pattern='fullname', $slctyear=0, $sort='', $order='', $printing=null)
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

 	$attends = homeroom_get_all_attends($user, $slctyear, $sort, $order);

	$table = new html_table();
	$linkurl  = $wwwBaseUrl.'&amp;userid='.$user->id.'&amp;slctyear='.$slctyear.'&amp;action=usercrsreport';
	$printUrl = $wwwBaseUrl.'&amp;userid='.$user->id.'&amp;slctyear='.$slctyear.'&amp;action=userreport&amp;printing=yes&amp;sort='.$sort.'&amp;order='.$order;

	if ($CFG->output_idnumber) {
		if (empty($user->idnumber)) $user_idnum = ' - ';
		else 						$user_idnum = $user->idnumber;
		$disp_idnum = '['.$user_idnum.']';
	}
	else {
		$user_idnum = '';
		$disp_idnum = '';
	}

	//
	$datas = array();
	$i = 0;
	foreach($attends as $att) {
		if ($att->classid>=0) {
			$course_fullname = $att->course->fullname;
			if (!$printing) $course_link = '<a href="'.$linkurl.'&amp;crsid='.$att->course->id.'">'.$course_fullname.'</a>';
			else 			$course_link = $course_fullname;
			//
			$datas[$i]['date']		= $att->sessdate  + $TIME_OFFSET;
			$datas[$i]['stime']		= $att->starttime + $TIME_OFFSET;
			$datas[$i]['etime']		= $att->endtime   + $TIME_OFFSET;
			$datas[$i]['fullname']  = $course_fullname;
			$datas[$i]['fnameurl']  = $course_link;
			$datas[$i]['classname'] = homeroom_get_classname($att->classid);
			$datas[$i]['desc'] 		= $att->description ? $att->description: get_string('nodescription', 'homeroom');

			$acronyms = homeroom_get_acronyms($att->course->id);
	
			if ($att->studentid) {
				if ($att->status=='Y') {
					if (time()>$att->endtime) {
						$datas[$i]['acronym'] = $acronyms['X']->acronym;
					}
					else {
						$datas[$i]['acronym'] = get_string('novalue', 'homeroom');
					}
				}
				else {
					$datas[$i]['acronym'] = $acronyms[$att->status]->acronym;
				}
				$datas[$i]['method'] = get_string($att->called.'methodfull', 'homeroom');
			}
			else {
				$datas[$i]['acronym'] = get_string('novalue', 'homeroom');
				$datas[$i]['method']  = get_string('novalue', 'homeroom');
			}
			//
			if (!$att->studentid OR $att->status==='X' OR $att->status==='Y') {
				$datas[$i]['calledtime'] = get_string('novalue', 'homeroom');;
			}
			else {
				$sessndate  = strftime(get_string('strftimedmshort', 'homeroom'), $att->sessdate   + $TIME_OFFSET);
				$calleddate = strftime(get_string('strftimedmshort', 'homeroom'), $att->calledtime + $TIME_OFFSET);
				$calledtime = strftime(get_string('strftimehmshort', 'homeroom'), $att->calledtime + $TIME_OFFSET);
				if ($sessndate===$calleddate) {
					$datas[$i]['calledtime'] = $calledtime;
				}
				else {
					$datas[$i]['calledtime'] = $calledtime.'&nbsp;('.$calleddate.')';
				}
			}
			//
			$ipaddr = $att->ipaddress ? $att->ipaddress : get_string('novalue', 'homeroom');
			if ($ipaddr) {
				$ipurl = homeroom_get_ipresolv_url($ipaddr);
				if ($ipurl) $datas[$i]['ip'] = "<a href=$ipurl target=_blank>$ipaddr</a>";
				else		$datas[$i]['ip'] = $ipaddr;
			}
			else {
				$datas[$i]['ip'] = get_string('novalue', 'homeroom');
			}

			$datas[$i]['remarks'] = $att->remarks;
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
	else			   array_multisort($keys, SORT_DESC, $datas);

	$username = jbxl_get_user_name($user, $name_pattern);
	include('html/user_report_header.html');
	//
	$i = 0;
	foreach($datas as $data) {
		$table->data[$i][] = $i + 1;
		$table->data[$i][] = strftime(get_string('strftimedmy',		'homeroom'), $data['date']);
		$table->data[$i][] = strftime(get_string('strftimehourmin',	'homeroom'), $data['stime']);
		$table->data[$i][] = strftime(get_string('strftimehourmin',	'homeroom'), $data['etime']);
		$table->data[$i][] = $data['fnameurl'];
		$table->data[$i][] = $data['classname'];
		$table->data[$i][] = $data['desc'];
		$table->data[$i][] = $data['acronym'];
		$table->data[$i][] = $data['method'];
		$table->data[$i][] = $data['calledtime'];
		$table->data[$i][] = $data['ip'];
		$table->data[$i][] = $data['remarks'];
		$i++;
	}

	homeroom_user_report_header($table, $user->id, $slctyear, $sort, $order, $printing);

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

