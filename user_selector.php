<?php


require_once('jbxl/jbxl_moodle_selector.php');




class  homeroom_user_selector_base extends jbxl_id_selector_base
{
	var $userid;
	var $roomid;
	var $author;
	var $name_pattern = 'fullname';


	public function __construct($action_url, $courseid, $roomid, $name_pattern)
	{
		global $USER;

		$active_title   = get_string('active','homeroom');
		$inactive_title = get_string('inactive','homeroom');

		$this->userid = $USER->id;
		$this->roomid = $roomid;
		$this->name_pattern = $name_pattern;
		$this->author = homeroom_get_authoriz($courseid, $this->roomid, $USER->id);

		parent::__construct('', $action_url, $inactive_title, $active_title);
	}


	public function get_name($id)
	{
		$name = jbxl_get_user_name($id, $this->name_pattern);
		return $name;
	}


	// 名前でソート
	public function sorting(array $ids)
	{
		$datas = array();
		foreach ($ids as $id=>$selected) {
			$name = $this->get_name($id);
			$datas[$name] = new StdClass();
			$datas[$name]->id = $id;
			$datas[$name]->selected = $selected;
		}

		ksort($datas);

		$ids = array();
		foreach ($datas as $data) {
			$ids[$data->id] = $data->selected;
		}

		return $ids;
	}


	protected function get_all_ids()
	{
		return array();
	}


	protected function get_record($id)
	{
	}


	protected function set_record($id, $rec)
	{
	}


	protected function set_item_left($rec)
	{
		if (!$rec) {
			$rec = new StdClass();
			$rec->roomid  = $this->roomid;
			$rec->teacher = $this->userid;
			$rec->student = 0;
		}
		$rec->selected = 0;

		return $rec;
	}


	protected function set_item_right($rec)
	{
		if (!$rec) {
			$rec = new StdClass();
			$rec->roomid  = $this->roomid;
			$rec->teacher = $this->userid;
			$rec->student = 0;
		}
		$rec->selected = 1;

		return $rec;
	}
}




class  homeroom_user_selector extends homeroom_user_selector_base
{
	protected function get_all_ids()
	{
		global $DB, $USER;

		$ids = array();

		if ($this->author==HOMEROOM_AUTHOR_ALL) {
			$datas = homeroom_get_all_member_ids();
			foreach($datas as $id) {
				$std = $DB->get_record('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$USER->id, 'student'=>$id));
				if ($std) $ids[$id] = $std->selected;
				else 	  $ids[$id] = 0;	// Left(Inactive)
			}
		}
		else if ($this->author==HOMEROOM_AUTHOR_SPECIFIC) {
			$members = $DB->get_records('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$USER->id));
			foreach($members as $member) {
				$ids[$member->student] = $member->selected;
			}
		}

		return $ids;
	}


	protected function get_record($id)
	{
		global $DB, $USER;

		$std = $DB->get_record('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$USER->id, 'student'=>$id)); 
		return $std;
	}


	protected function set_record($id, $rec)
	{
		global $DB, $USER;

		if ($this->author==HOMEROOM_AUTHOR_ALL and $rec->selected==0) {
			if ($rec->student) $DB->delete_records('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$USER->id, 'student'=>$rec->student));
		}
		//
		else {
			if ($rec->student==0) {
				$rec->student = $id;
				$DB->insert_record('homeroom_student', $rec);
			}
			else {
				$DB->update_record('homeroom_student', $rec);
			}
		}
	}
}




// for Admin
class  homeroom_specific_user_selector extends homeroom_user_selector_base
{
	public function set_userid($userid)
	{
		$this->userid = $userid;
	}


	protected function get_all_ids()
	{
		global $DB;

		$ids = array();
		$datas = homeroom_get_all_member_ids();
		foreach($datas as $id) {
			$std = $DB->get_record('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$this->userid, 'student'=>$id));
			if ($std) $ids[$id] = $std->selected;
			else 	  $ids[$id] = 0;	// Left(Inactive)
		}

		return $ids;
	}


	protected function get_record($id)
	{
		global $DB;

		$std = $DB->get_record('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$this->userid, 'student'=>$id)); 
		return $std;
	}


	protected function set_record($id, $rec)
	{
		global $DB;

		if ($rec->selected==0) {
			if ($rec->student) $DB->delete_records('homeroom_student', array('roomid'=>$this->roomid, 'teacher'=>$this->userid, 'student'=>$rec->student));
		}
		//
		else if ($rec->selected==1) {
			if ($rec->student==0) {
				$rec->student = $id;
				$DB->insert_record('homeroom_student', $rec);
			}
			else {
				$DB->update_record('homeroom_student', $rec);
			}
		}
	}
}



