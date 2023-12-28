<?php

namespace mod_homeroom\event;


defined('MOODLE_INTERNAL') || die();


class select_log extends \core\event\base
{
	public static function get_name()		// イベント名
	{
		return 'select';
	}


	public function get_url()
	{
		$params = array();
		if (isset($this->other['params'])) $params = $this->other['params'];
		if (!is_array($params)) $params = array();

		$params = array_merge(array('id' => $this->contextinstanceid), $params);
		return new \moodle_url('/mod/homeroom/select.php', $params);
	}


	public function get_description()
	{
		$info = '';
		if (isset($this->other['info'])) $info = $this->other['info'];

		return $info;
	}


	protected function init()
	{
		$this->data['crud'] = 'u'; 						// イベントの種類　c(reate), r(ead), u(pdate), d(elete)
		$this->data['edulevel'] = self::LEVEL_OTHER; 	// 教育レベル LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER 
	}
}
