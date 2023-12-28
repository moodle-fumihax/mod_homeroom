<?php

//
// Warpper functions for Autoattend Block and Attendance Module
//
//

// function homeroom_get_all_member_ids()
// function homeroom_get_courses($slctyear)
// function homeroom_get_session_classes($courseid)
// function homeroom_get_attend_course($user, $slctyear)
// function homeroom_get_attend_students($courseid, $classid=0, $sort='', $order='')
// function homeroom_get_user_summary($userid, $courseid)
// function homeroom_get_all_attends($user, $slctyear, $sort, $order)
// function homeroom_get_classname($classid)
// function homeroom_get_ipresolv_url($ip)
// function homeroom_get_acronyms($courseid)
// function homeroom_download($format, $courseid, $students, $classes=null)

// function homeroom_attendance_to_autoattend(array $sessions)
// function homeroom_get_attendance_summary($userid, $courseid) 



if ($attendPlugin=='autoattend') {
	if (file_exists('../../blocks/autoattend/locallib.php')) {
		include_once('../../blocks/autoattend/locallib.php');
	}
	else if (file_exists('../../blocks/autoattend/lib.php')) {
		include_once('../../blocks/autoattend/lib.php');
	}
	else {
		$attendPlugin = '';
	}
}


//
// 出席システムにデータのある全てのユーザのID（配列）を返す．
//
function homeroom_get_all_member_ids()
{
	global $DB, $attendPlugin;

	$ids = array();

	// autoattend
	if ($attendPlugin=='autoattend') {
		$sql = 'SELECT DISTINCT studentid FROM {autoattend_students}';
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		$sql = 'SELECT DISTINCT studentid FROM {attendance_log}';
	}

	else return $ids;

	//
	$users = $DB->get_records_sql($sql);
	foreach ($users as $user) {
		if ($user->studentid>0) $ids[] = $user->studentid;
	}

	return $ids;
}


//
// $couseid の示すコースのクラスの情報（DBレコード）を返す．
//
function homeroom_get_session_classes($courseid)
{
	global $attendPlugin;

	$classes = array();
	if (!homeroom_permit_access($courseid)) return $classes;

	// autoattend
	if ($attendPlugin=='autoattend') {
		$classes = autoattend_get_session_classes($courseid);
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		// nop
	}

	return $classes;
}


//
// $slctyear の示す年度に開講されたコースの情報を返す．0 なら全期間．
// 年度の開始月は $CFG->semester_month (homeroom_get_semester) で指定される．
//
function homeroom_get_courses($slctyear)
{
	global $DB, $attendPlugin, $TIME_OFFSET;

	$courses = array();

	$slct = '';
	if ($slctyear>0) {
		$semester = homeroom_get_semester($slctyear);
	}
	$sort = 'ORDER BY se.sessdate DESC';

	// autoattend
	if ($attendPlugin=='autoattend') {
		if ($slctyear>0) {
			$slct  = ' WHERE se.sessdate>='.$semester[0].' AND se.sessdate<='.$semester[1];
		}
		$stqry = 'SELECT DISTINCT se.courseid FROM {autoattend_sessions} se '.$slct.' '.$sort;
		//
		$courseids = $DB->get_records_sql($stqry);
		$i = 0;
		foreach ($courseids as $course) {
			if (homeroom_permit_access($course->courseid)) {
				$courses[$i] = $DB->get_record('course', array('id'=>$course->courseid));
				$courses[$i]->sdate = $DB->get_field('autoattend_sessions', 'MIN(sessdate)', array('courseid'=>$course->courseid));
				$i++;
			}
		}
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		if ($slctyear>0) {
			$slct  = ' AND se.sessdate>='.$semester[0].' AND se.sessdate<='.$semester[1];
		}
		$stqry = 'SELECT DISTINCT ad.course,se.attendanceid FROM {attendance_sessions} se, {attendance} ad '.
						'WHERE se.attendanceid=ad.id '.$slct.' '.$sort;
		//
		$courseids = $DB->get_records_sql($stqry);
		$i = 0;
		foreach ($courseids as $course) {
			if (homeroom_permit_access($course->course)) {
				$courses[$i] = $DB->get_record('course', array('id'=>$course->course));
				$courses[$i]->sdate = $DB->get_field('attendance_sessions', 'MIN(sessdate)', array('attendanceid'=>$course->attendanceid));
				$i++;
			}
		}
	}

	return $courses;
}


//
// $userの $slctyear年度の全コース（拒否コースは除く）の出欠情報を得る．
// 
//
function homeroom_get_attend_course($user, $slctyear)
{
	global $DB, $attendPlugin, $TIME_OFFSET;

	$courses = array();

	$slct = '';
	if ($slctyear>0) {
		$semester = homeroom_get_semester($slctyear);
		$slct  = ' AND se.sessdate>='.$semester[0].' AND se.sessdate<='.$semester[1].' ';
	}

	// autoattend
	if ($attendPlugin=='autoattend') {
		$sort  = 'ORDER BY st.calledtime DESC';
		$stqry = 'SELECT DISTINCT se.courseid FROM {autoattend_students} st, {autoattend_sessions} se '.
						'WHERE st.studentid=? AND st.attsid=se.id '.$slct.' '.$sort;
		$clqry = 'SELECT classid FROM {autoattend_classifies} WHERE studentid=? AND courseid=?';
		//
		$courseids = $DB->get_records_sql($stqry, array($user->id));
		foreach ($courseids as $course) {
			if (homeroom_permit_access($course->courseid)) {
				$ret = $DB->get_record_sql($clqry, array($user->id, $course->courseid));
				if (!$ret or $ret->classid>=0) {
					$courses[] = $DB->get_record('course', array('id'=>$course->courseid));
				}
			}
		}
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		$sort  = 'ORDER BY st.timetaken DESC';
		$stqry = 'SELECT DISTINCT ad.course FROM {attendance_log} st, {attendance_sessions} se, {attendance} ad '.
						'WHERE st.studentid=? AND st.sessionid=se.id AND se.attendanceid=ad.id '.$slct.' '.$sort;
		//
		$courseids = $DB->get_records_sql($stqry, array($user->id));
		foreach ($courseids as $course) {
			if (homeroom_permit_access($course->course)) {
				$courses[] = $DB->get_record('course', array('id'=>$course->course));
			}
		}
	}

	return $courses;
}


//
// $couseid に属するユーザのデータを得る．
//
// $classid: 特定のクラスを指定する．0 なら全クラス．
//
function homeroom_get_attend_students($courseid, $classid=0, $sort='', $order='')
{
	global $attendPlugin;

	$students = array();
	if (!homeroom_permit_access($courseid)) return $students;

	$context = jbxl_get_course_context($courseid);
	//
	$classinfo = new stdClass();
	$classinfo->classid = 0;
	$classinfo->name = get_string('nonclass', 'homeroom');

	if ($sort!='' and $order!='') $sort .= ' '.$order;
	$users = jbxl_get_course_students($context, $sort);
	if ($users) {
		foreach ($users as $user) {
			if ($attendPlugin=='autoattend') $classinfo = autoattend_get_user_class($user->id, $courseid);
			if ($classinfo->classid>=0 and (($classinfo->classid==$classid or $classid==0) or
											($classid==NON_CLASSID and $classinfo->classid==0))) {
				$students[$user->id]			= new stdClass();
				$students[$user->id]->id		= $user->id;
				$students[$user->id]->firstname = $user->firstname;
				$students[$user->id]->lastname  = $user->lastname;
				$students[$user->id]->idnumber  = $user->idnumber;
				$students[$user->id]->fullname  = fullname($user);
				$students[$user->id]->classid   = $classinfo->classid;
				$students[$user->id]->classname = $classinfo->name;
				$students[$user->id]->user	  = $user;
			}
		}
	}

	return $students;
}


//
// ユーザ $userid の $couseidコースにおける全データを返す．
//
function homeroom_get_user_summary($userid, $courseid)
{
	global $attendPlugin;

	$ret = '';
	if (!homeroom_permit_access($courseid)) return $ret;

	// autoattend
	if ($attendPlugin=='autoattend') {
		$ret = autoattend_get_user_summary($userid, $courseid);
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		$ret = homeroom_get_attendance_summary($userid, $courseid);
	}

	return $ret;
}


//
// 指定された年度における，$user の全ての授業データを返す．$slctyearが0なら，全期間．
//
function homeroom_get_all_attends($user, $slctyear, $sort, $order)
{
	global $DB, $attendPlugin;

	$attends  = array();
	//
	$slct = '';
	if ($slctyear>0) {
		$semester = homeroom_get_semester($slctyear);
		$slct  = ' AND se.sessdate>='.$semester[0].' AND se.sessdate<='.$semester[1].' ';
	}
	//
	if ($sort=='date') {
		$sort = 'ORDER BY se.sessdate '.$order;
	}
	else { // default
		$sort = 'ORDER BY se.sessdate DESC';
	}

	// autoattend
	if ($attendPlugin=='autoattend') {
		$items = 'st.*, se.sessdate, se.starttime, se.endtime, se.description, se.courseid';
		$stqry = 'SELECT '.$items.' FROM {autoattend_students} st, {autoattend_sessions} se '.
						'WHERE st.studentid=? AND st.attsid=se.id '.$slct.' '.$sort;
		$clqry = 'SELECT classid FROM {autoattend_classifies} WHERE studentid=? AND courseid=?';

		$sessions = $DB->get_records_sql($stqry, array($user->id));

		$i = 0;
		foreach ($sessions as $session) {
			if (homeroom_permit_access($session->courseid)) {
				$ret = $DB->get_record_sql($clqry, array($user->id, $session->courseid));
				if (!$ret or $ret->classid>=0) {
					$attends[$i] = $session;
					$attends[$i]->course = $DB->get_record('course', array('id'=>$session->courseid));
					if (!$ret) $attends[$i]->classid = 0;
					else	   $attends[$i]->classid = $ret->classid;
					$i++;
				}
			}
		}
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		$items = 'st.*, se.sessdate, se.duration, se.description, ad.course, ss.acronym';
		$stqry = 'SELECT '.$items.' FROM {attendance_log} st, {attendance_sessions} se, {attendance} ad, {attendance_statuses} ss '.
						'WHERE st.studentid=? AND st.sessionid=se.id AND se.attendanceid=ad.id AND st.statusid=ss.id '.$slct.' '.$sort;

		$sessions = $DB->get_records_sql($stqry, array($user->id));
		$sessions = homeroom_attendance_to_autoattend($sessions);

		$i = 0;
		foreach ($sessions as $session) {
			if (homeroom_permit_access($session->courseid)) {
				$attends[$i] = $session;
				$attends[$i]->course = $DB->get_record('course', array('id'=>$session->courseid));
				$i++;
			}
		}
	}

	return $attends;
}


//
// $classid のクラス名を返す．
//
function homeroom_get_classname($classid)
{
	global $attendPlugin;

	$ret = '';

	// autoattend
	if ($attendPlugin=='autoattend') {
		$ret = autoattend_get_user_classname($classid);
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		$ret = ' - ';
	}

	return $ret;
}


//
//
function homeroom_get_ipresolv_url($ip)
{
	global $attendPlugin;

	// autoattend
	if ($attendPlugin=='autoattend') {
		$ret = autoattend_get_ipresolv_url($ip);
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		require_once('jbxl/jbxl_tools.php');
		$ret = jbxl_get_ipresolv_url($ip);
	}

	return $ret;
}




////////////////////////////////////////////////////////////////////////////////////////////////////
//

//
// 出席，欠席，遅刻などの Acronym を得る．
//
function homeroom_get_acronyms($courseid)
{
	global $DB, $attendPlugin;

	$acronyms = array();
	if (!homeroom_permit_access($courseid)) return $acronyms;

	// autoattend
	if ($attendPlugin=='autoattend') {
		$acronyms = autoattend_get_grade_settings($courseid);
	}

	// attendance
	else if ($attendPlugin=='attendance') {

		$att = $DB->get_record('attendance', array('course'=>$courseid));
		if ($att) $attid = $att->id;
		else	  $attid = 0;

		$result = $DB->get_records('attendance_statuses', array('attendanceid'=>$attid), 'id'); 
		if (!$result) {
			$result = $DB->get_records('attendance_statuses', array('attendanceid'=>0), 'id');  // use default
		}

		$num = 0;
		foreach ($result as $res) {
			if (empty($res->acronym))	  $res->acronym = get_string($res->acronym.'acronym', 'attendance');
			if (empty($res->description)) $res->description = get_string($res->acronym.'full', 'attendance');
			$res->title = $res->acronym;
			//
			if ($num==0) 	  $acronyms['P'] = $res;
			else if ($num==1) $acronyms['X'] = $res;
			else if ($num==2) $acronyms['L'] = $res;
			else if ($num==3) $acronyms['E'] = $res;
			else break;
			$num++;
		}

		$acronyms['Y'] = new stdClass();
		//$acronyms['Y']->acronym = get_string('Yacronym', 'block_autoattend');
		//$acronyms['Y']->title = get_string('Ytitle', 'block_autoattend');
		//$acronyms['Y']->description = get_string('Ydesc', 'block_autoattend');
		$acronyms['Y']->acronym = '';
		$acronyms['Y']->title = '';
		$acronyms['Y']->description = '';
	}

	return $acronyms;
}  


//
// コース $courseid がホームルームモジュールからのアクセスを拒否しているかどうかを確認する．
//
function homeroom_permit_access($courseid) 
{
	global $DB, $attendPlugin;

	if ($courseid==0) return true;
	$access = true;

	// autoattend
	if ($attendPlugin=='autoattend') {
		$ret = $DB->get_field('autoattendmod', 'homeroom', array('course'=>$courseid));
		if (!$ret) $access = false;
	}

	// attendance
	//else if ($attendPlugin=='attendance') {
	//}

	return $access;
}


//
// $students の $courseid における出欠データをダウンロードする．
// attendance モジュールでは未実装．
//
function homeroom_download($format, $courseid, $students, $classes=null, $name_pattern='fullname')
{
	global $attendPlugin;

	if ($attendPlugin=='autoattend') {
		$datas = autoattend_make_download_data($courseid, $classes, 0, 'all', 0, 'all', $students);
		jbxl_download_data($format, $datas);
	}

	// attendance
	else if ($attendPlugin=='attendance') {
		$datas = homeroom_make_download_attendance_data($courseid, $students, $name_pattern);
		jbxl_download_data($format, $datas);
	}
	
	return;
}




////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 内部関数
//

//
// attendance モジュールの出欠データのサマリを得る．
// X のあるデータは，元々 attendanceモジュールに無いデータを示す．
// 内部関数
//

// $summary['userid']   : ユーザID
// $summary['courseid'] : コースID
// $summary['attitems'] : 学生の各授業のRawデータ（配列）
// $summary['complete'] : 出席コマ数（早退，遅刻を含む）
// $summary['grade']	: 出席点
// $summary['percent']  : 出席率（出席点ベース）
// $summary['P']		: 正常出席数 
// $summary['X']		: 欠席数．クローズしたセッションで Y の物を含む． 
// $summary['L']		: 遅刻数 
// $summary['E']		: 早退数 
// $summary['maxgrade'] : 最高出席点（皆勤の場合の出席点）
//
// X $summary['Y']		  : 未了数．ただしクローズしたセッションは X とする． 
// X $summary['settings'] : 出席点の配分Rawデータ（配列）
// X $summary['classid']  : クラスID
// X $summary['classname']: クラス名
// X $summary['mingrade'] : 最低出席点（全欠の場合の出席点）
// X $summary['leccount'] : 実施した授業のコマ数
////
function homeroom_get_attendance_summary($userid, $courseid) 
{
	global $CFG, $DB;

	$ntime = time();

	$sort  = 'ORDER BY se.sessdate ASC';
	$items = 'st.*, se.sessdate, se.duration, se.description, ad.course, ss.acronym, ss.grade, ad.grade as maxgrade';
	$stqry = 'SELECT '.$items.' FROM {attendance_log} st, {attendance_sessions} se, {attendance} ad, {attendance_statuses} ss '.
						'WHERE st.studentid=? AND ad.course=? AND st.sessionid=se.id AND se.attendanceid=ad.id AND st.statusid=ss.id '.$sort;

	$attitems = $DB->get_records_sql($stqry, array($userid, $courseid));
	if (!$attitems) return false;
	$attitems = homeroom_attendance_to_autoattend($attitems);

	$summary = array();
	$summary['userid']   = $userid;
	$summary['courseid'] = $courseid;
	$summary['attitems'] = $attitems;

	$status = array('P', 'X', 'L', 'E', 'Y');
	$summary['settings'] = array();
	foreach ($status as $st) {
		$summary[$st] = 0;
		$summary['settings'][$st] = new StdClass();
		$summary['settings'][$st]->status = $st;
	}

	$complete = 0;
	$leccount = 0;
	$grade = 0;
	if ($attitems) {
		foreach($attitems as $att) {
			if ($att->status) {
				$complete++;
				$grade += $att->grade;
				$summary[$att->status]++;
			}
			//else {
			//	$summary['Y']++;
			//}
			$leccount++;
		}
	}
	$summary['Y'] = $leccount - $summary['P'] - $summary['L'] - $summary['E'] - $summary['X'];
	$summary['complete'] = $complete;		   // 出席コマ数（早退，遅刻を含む）
	$summary['leccount'] = $leccount;
	$sessnum = $complete;

	//
	$summary['grade'] 	 = $grade;
	$summary['maxgrade'] = $attitems[0]->maxgrade;
	$summary['mingrade'] = 0;
	//
	$gradelevel = $summary['maxgrade'] - $summary['mingrade'];
	if ($gradelevel!=0) {
		$percent = 100*($summary['grade']-$summary['mingrade'])/$gradelevel;
		$summary['percent'] = sprintf('%0.1f', $percent);
	}
	else {
		$summary['percent'] = ' - ';
	}

	$summary['classid']  = 0;
	$summary['classname']= ' - ';

	return $summary;
}


//
// attendanceモジュールでの授業データを autoattendの形式に変換する．
//
function homeroom_attendance_to_autoattend(array $sessions)
{
	$attends = array();

	$i = 0;
	foreach ($sessions as $session) {
		$attends[$i] 			 = $session;
		$attends[$i]->attsid 	 = $session->sessionid;
		$attends[$i]->called 	 = 'M';
		$attends[$i]->calledby 	 = $session->takenby;
		$attends[$i]->calledtime = $session->timetaken;
		$attends[$i]->ipaddress  = ' - ';
		$attends[$i]->starttime  = $session->sessdate;
		$attends[$i]->endtime 	 = $session->sessdate + $session->duration;
		$attends[$i]->courseid	 = $session->course;
		$attends[$i]->classid 	 = 0;

		$acronyms = homeroom_get_acronyms($session->course);
		foreach ($acronyms as $key=>$data) {
			if ($session->acronym==$data->acronym) {
				$attends[$i]->status = $key;
			}
		}

		$i++;
	}

	return $attends;
}


//
// ダウンロード用のデータを作る
//
// $courseid: ダウンロードするコースのID
// $students: 表示する学生．
// $viewmode: ダウンロードする期間．'all', 'weeks', 'months'
// $starttm:  viewmode で，'weeks', 'months' を指定した場合の始まりの時刻指定．0なら現在．
// $attsid:   ダウンロードするセッションのID．'all', 0なら全てのセッション．

function homeroom_make_download_attendance_data($courseid, $students, $name_pattern)
{
	global $CFG, $DB, $TIME_OFFSET;

	$settings = homeroom_get_acronyms($courseid);

	$sessions = array();
	$temps = array();

	$i = 0;
	foreach($students as $student) {
		$temps[$i] = array();
		//
		$temps[$i]['name'] = jbxl_get_user_name($student->id, $name_pattern);
		if ($CFG->output_idnumber) {
			if (empty($student->idnumber)) $idnumber = '-';
			else 						   $idnumber = $student->idnumber;
			$temps[$i]['idnum'] = $idnumber;
		}
		$user_summary = homeroom_get_user_summary($student->id, $courseid);

		$temps[$i]['grade']   = $user_summary['grade']; 
		$temps[$i]['percent'] = $user_summary['percent'].'%';
		$temps[$i]['P'] 	  = $user_summary['P'];
		$temps[$i]['L'] 	  = $user_summary['L'];
		$temps[$i]['E'] 	  = $user_summary['E'];
		$temps[$i]['X'] 	  = $user_summary['X'];

		$atts = $user_summary['attitems'];
		$j = 0;
		$temps[$i]['session'] = array();

		foreach ($atts as $att) {
			if ($att and ($att->classid==$user_summary['classid'] or $att->classid==0)) {
				if (empty($att->status)) {
					$temps[$i]['session'][$j] = get_string('novalue','homeroom');
				}
				else {
					$temps[$i]['session'][$j] = $settings[$att->status]->acronym;
				}
			}
			else {
				$temps[$i]['session'][$j] = get_string('novalue','homeroom');
			}
			$j++;
		}
		$columns = max($columns, $j);							// for attendance module
		if (count($sessions)<count($atts)) $sessions = $atts;	// for attendance module
		//
		$i++;
	}


	//
	$datas = new stdClass();
	$datas->attr = array();	// 属性 'string', 'number'. デフォルトは 'string' 
	$datas->data = array();

	$j = 0;
	$k = 0;
	$datas->attr[0] = array();
	$datas->data[0] = array();

	$datas->attr[0][$k++] = '';
	$datas->data[0][$j++] = jbxl_get_fullnamehead($name_pattern, get_string('firstname'), get_string('lastname'), '/');
	if ($CFG->output_idnumber) {
		$datas->attr[0][$k++] = '';
		$datas->data[0][$j++] = 'ID';
	}

	$datas->attr[0][$k++] = '';
	$datas->attr[0][$k++] = '';
	$datas->attr[0][$k++] = '';
	$datas->attr[0][$k++] = '';
	$datas->attr[0][$k++] = '';
	$datas->attr[0][$k++] = '';
	//
	$datas->data[0][$j++] = get_string('attendgrade',  'block_autoattend');
	$datas->data[0][$j++] = get_string('attendpercent','block_autoattend');
	$datas->data[0][$j++] = $settings['P']->title;
	$datas->data[0][$j++] = $settings['L']->title;
	$datas->data[0][$j++] = $settings['E']->title;
	$datas->data[0][$j++] = $settings['X']->title;

	if (!empty($sessions)) {
		foreach($sessions as $att) {
			$datas->attr[0][$k++] = '';
			$datas->data[0][$j++] = strftime(get_string('strftimedmshort','homeroom'), $att->sessdate+$TIME_OFFSET);
		}
	}

	//
	$i = 1;
	foreach ($temps as $temp) {
		$j = 0;
		$k = 0;
		$datas->attr[$i] = array();
		$datas->data[$i] = array();

		$datas->attr[$i][$k++] = '';
		$datas->data[$i][$j++] = $temp['name'];
		if ($CFG->output_idnumber) {
			$datas->attr[$i][$k++] = '';
			$datas->data[$i][$j++] = $temp['idnum'];
		}

		$datas->attr[$i][$k++] = 'number';
		$datas->attr[$i][$k++] = 'number';
		$datas->attr[$i][$k++] = 'number';
		$datas->attr[$i][$k++] = 'number';
		$datas->attr[$i][$k++] = 'number';
		$datas->attr[$i][$k++] = 'number';
		//
		$datas->data[$i][$j++] = $temp['grade'] ;
		$datas->data[$i][$j++] = $temp['percent'] ;
		$datas->data[$i][$j++] = $temp['P'] ;
		$datas->data[$i][$j++] = $temp['L'] ;
		$datas->data[$i][$j++] = $temp['E'] ;
		$datas->data[$i][$j++] = $temp['X'] ;

		$len = count($temp['session']);
		for ($l=0; $l<$columns; $l++) {
			$datas->attr[$i][$k++] = '';
			if ($l<$len) $datas->data[$i][$j++] = $temp['session'][$l];
			else 		 $datas->data[$i][$j++] = get_string('novalue', 'homeroom');
		}
		$i++;
	}

	return $datas;
}

