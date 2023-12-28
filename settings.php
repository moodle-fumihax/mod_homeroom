<?php

$courseid = optional_param('course', '0', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid));

//
$options = array('01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06',
				 '07'=>'07','08'=>'08','09'=>'09','10'=>'10','11'=>'11','12'=>'12');
$settings->add(new admin_setting_configselect('semester_month',
					get_string('semester_month', 'homeroom'),
					get_string('semester_month_desc', 'homeroom'), '01', $options));
//
$settings->add(new admin_setting_configcheckbox('output_idnumber', 
					get_string('output_idnumber', 'homeroom'),
				   	get_string('output_idnumber_desc', 'homeroom'), 1));

//
if (property_exists($CFG, 'page_row_size')) {
	if ($CFG->page_row_size<=0) $CFG->page_row_size = 15;
	if ($CFG->page_column_size<=0) $CFG->page_column_size = 15;
}

$settings->add(new admin_setting_configtext('page_row_size',
					get_string('page_row_size', 'homeroom'),
					get_string('page_row_size_desc', 'homeroom'), '15', PARAM_INT));

$settings->add(new admin_setting_configtext('page_column_size',
					get_string('page_column_size', 'homeroom'),
					get_string('page_column_size_desc', 'homeroom'), '15', PARAM_INT));

