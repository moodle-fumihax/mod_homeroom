<?php

//
// １学生の１コースの出欠を出力する
//
//

///////////////////////////////////////////////////////////////////////////////////////////////
// User Report

function homeroom_user_course_report_header(&$table)
{
	unset($table->head);
	unset($table->align);
	unset($table->size);
	unset($table->wrap);

	// Header
	$table->head [] = '#';
	$table->align[] = 'right';
	$table->size [] = '20px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('date');
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

	$table->head [] = get_string('session_classname', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '80px';
	$table->wrap [] = 'nowrap';

	$table->head [] = get_string('description','homeroom');
	$table->align[] = 'left';
	$table->size [] = '40px';
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




function homeroom_print_user_course_row($left, $right)
{
	echo "\n<tr><td nowrap=\"nowrap\" align=\"right\" valign=\"top\" class=\"label c0\">$left</td>
				<td align=\"left\" valign=\"top\" class=\"info c1\">$right</td></tr>\n";
}




function homeroom_print_user_course_report($user, $course, $name_pattern, $printing=null)
{
	global $DB, $CFG, $USER, $OUTPUT, $wwwBaseUrl, $TIME_OFFSET;

	$userid   = $user->id;
	$crsid	  = $course->id;
	$context  = jbxl_get_course_context($crsid);
	$summary  = homeroom_get_user_summary($userid, $crsid);
	$acronyms = homeroom_get_acronyms($crsid);
	$printUrl = $wwwBaseUrl.'&amp;userid='.$userid.'&amp;action=usercrsreport&amp;crsid='.$crsid.'&amp;printing=yes';

	if(!$summary) {
		notice(get_string('attendnotstarted','homeroom'), $CFG->wwwroot.'/course/view.php?id='.$crsid);
	}
	else {
		$complete  = $summary['complete'];
		$percent   = $summary['percent'].' %';
		$grade	   = $summary['grade'];
		$maxgrade  = $summary['maxgrade'];
		$settings  = $summary['settings'];
		$classid   = $summary['classid'];
		$classname = $summary['classname'];

		//
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
		$username = jbxl_get_user_name($userid, $name_pattern);
		include('html/user_course_report_header.html');

		//
		if ($classid>=0) {	  // !出欠から除外
			//
			$table = new html_table();
			homeroom_user_course_report_header($table);

			$i = 0;
			foreach($summary['attitems'] as $att) {
				if ($att->classid==$classid or $att->classid==0) {
					$table->data[$i][] = $i + 1;
					$table->data[$i][] = strftime(get_string('strftimedmy',    'homeroom'), $att->sessdate  + $TIME_OFFSET);
					$table->data[$i][] = strftime(get_string('strftimehourmin','homeroom'), $att->starttime + $TIME_OFFSET);
					$table->data[$i][] = strftime(get_string('strftimehourmin','homeroom'), $att->endtime   + $TIME_OFFSET);
					$table->data[$i][] = homeroom_get_classname($att->classid);
					$table->data[$i][] = $att->description ? $att->description: get_string('nodescription', 'homeroom');

					if ($att->studentid) {
						if ($att->status=='Y') {
							if (time()>$att->endtime) {
								$table->data[$i][] = $acronyms['X']->acronym;
							}
							else {
								$table->data[$i][] = get_string('novalue', 'homeroom');
							}
						}
						else {
							$table->data[$i][] = $acronyms[$att->status]->acronym;
						}
						$table->data[$i][] = get_string($att->called.'methodfull', 'homeroom');
					}
					else {
						$table->data[$i][] = get_string('novalue', 'homeroom');
						$table->data[$i][] = get_string('novalue', 'homeroom');
					}

					//
					if (!$att->studentid OR $att->status==='X' OR $att->status==='Y') {
						$table->data[$i][] = get_string('novalue', 'homeroom');;
					}
					else {
						$sessndate  = strftime(get_string('strftimedmshort', 'homeroom'), $att->sessdate   + $TIME_OFFSET);
						$calleddate = strftime(get_string('strftimedmshort', 'homeroom'), $att->calledtime + $TIME_OFFSET);
						$calledtime = strftime(get_string('strftimehmshort', 'homeroom'), $att->calledtime + $TIME_OFFSET);
						if ($sessndate===$calleddate) {
							$table->data[$i][] = $calledtime;
						}
						else {
							$table->data[$i][] = $calledtime.'&nbsp;('.$calleddate.')';
						}
					}

					//
					$ipaddr = $att->ipaddress ? $att->ipaddress : get_string('novalue', 'homeroom');
					if ($ipaddr) {
						$ipurl = homeroom_get_ipresolv_url($ipaddr);
						if ($ipurl) $table->data[$i][] = "<a href=$ipurl target=_blank>$ipaddr</a>";
						else		$table->data[$i][] = $ipaddr;
					}
					else {
						$table->data[$i][] = get_string('novalue', 'homeroom');
					}

					$table->data[$i][] = $att->remarks;
					$i++;
				}
			}
			echo '<div align="left">';
			echo html_writer::table($table);
			echo '</div>';
		}

		//
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		//
		echo '</div>';
	}

	return;
}



