<?php



//////////////////////////////////////////////////////////////////////////////////////////////
// User List

function homeroom_user_setting_header(&$table, $name_pattern, $sort, $order)
{
	global $CFG, $wwwBaseUrl;

	if ($order=='ASC') $order = 'DESC';
	else			   $order = 'ASC';

	$firstname_url = $wwwBaseUrl.'&amp;action=setting&amp;sort=firstname';
	$lastname_url  = $wwwBaseUrl.'&amp;action=setting&amp;sort=lastname';

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

	$table->head [] = $fullnamehead;
	$table->align[] = 'left';
	$table->size [] = '180px'; 
	$table->wrap [] = 'nowrap';

	if ($CFG->output_idnumber) {
		$table->head [] = '<a href="'.$wwwBaseUrl.'&amp;action=setting&amp;sort=idnumber&amp;order='.$order.'">ID</a>';;
		$table->align[] = 'center';
		$table->size [] = '60px'; 
		$table->wrap [] = '';
	}

	$table->head [] = get_string('author_perm', 'homeroom');
	$table->align[] = 'center';
	$table->size [] = '160px'; 
	$table->wrap [] = '';

	$table->head [] = '';
	$table->align[] = 'center';
	$table->size [] = '80px'; 
	$table->wrap [] = '';

	return;
}




//
function homeroom_user_setting($users, $courseid, $roomid, $name_pattern, $sort, $order)
{
	global $CFG, $DB, $OUTPUT, $wwwBaseUrl;
	
	$table = new html_table();
	$context = context_course::instance($courseid);

	//
	$i = 0;
	foreach($users as $user) {
		$pic_options = array('size'=>20, 'link'=>true, 'courseid'=>$courseid, 'alttext'=>true, 'popup'=>true);
		$table->data[$user->id][] = $i + 1;
		$table->data[$user->id][] = $OUTPUT->user_picture($user, $pic_options);
		$table->data[$user->id][] = $user->name;
	
		if ($CFG->output_idnumber) {
			if (empty($user->idnumber)) $table->data[$user->id][] = ' - ';
			else                        $table->data[$user->id][] = $user->idnumber;
		}

		$slct0 = '';
		$slct1 = '';
		$slct2 = '';
		$slct3 = '';
		$clink = ' - ';
		//
		$author = homeroom_get_authoriz($courseid, $roomid, $user->id);
		if ($author==HOMEROOM_AUTHOR_ONESELF)  {
			$slct1 = ' selected';
		} 
		else if ($author==HOMEROOM_AUTHOR_ALL) {
			$slct2 = ' selected';
			$specific_url = $wwwBaseUrl.'&amp;action=setting_select&amp;userid='.$user->id.'&amp;courseid='.$courseid;
			$clink = '<a href="'.$specific_url.'">'.get_string('useredit', 'homeroom').'</a>';
		}
		else if ($author==HOMEROOM_AUTHOR_SPECIFIC) {
			$slct3 = ' selected';
			$specific_url = $wwwBaseUrl.'&amp;action=setting_select&amp;userid='.$user->id.'&amp;courseid='.$courseid;
			$clink = '<a href="'.$specific_url.'">'.get_string('useredit', 'homeroom').'</a>';
		}
		else {
			$slct0 = ' selected';
		}

		$select = '<select name=userid'.$user->id.'>'.
					  '<option value="'.HOMEROOM_AUTHOR_FORBIDDEN.'"'.$slct0.'>'.get_string('author_forbidden','homeroom').'</option>'.
					  '<option value="'.HOMEROOM_AUTHOR_ONESELF.  '"'.$slct1.'>'.get_string('author_oneself','homeroom').'</option>';
		if (jbxl_is_teacher($user->id, $context) or jbxl_is_assistant($user->id, $context)) {
			$select.= '<option value="'.HOMEROOM_AUTHOR_ALL.	  '"'.$slct2.'>'.get_string('author_all','homeroom').'</option>'.
					  '<option value="'.HOMEROOM_AUTHOR_SPECIFIC. '"'.$slct3.'>'.get_string('author_specific','homeroom').'</option>';
		}
		$select.= '</select>';

		$table->data[$user->id][] = $select;
		$table->data[$user->id][] = $clink;

		$i++;
		//
		if ($i%PAGE_ROW_SIZE==0) {
			homeroom_user_setting_header($table, $name_pattern, $sort, $order);
			echo '<div style="margin: 0px 0px 0px 10px;"">';
			echo html_writer::table($table);
			echo '</div>';
			unset($table->data);
		}
	}

	if ($i%PAGE_ROW_SIZE!=0 or $i==0) {
		homeroom_user_setting_header($table, $name_pattern, $sort, $order);
		echo '<div style="margin: 0px 0px 0px 10px;"">';
		echo html_writer::table($table);
		echo '</div>';
	}
	//
	return;
}



