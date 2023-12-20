﻿<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    mod_homeroom
 * @copyright  Fumi.Iseki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


require_once ($CFG->dirroot.'/course/moodleform_mod.php');


class mod_homeroom_mod_form extends moodleform_mod
{
    function definition()
    {
        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        //
        $mform->addElement('text', 'name', get_string('name', 'homeroom'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        if (method_exists($this, 'standard_intro_elements')) {
            $this->standard_intro_elements(get_string('description', 'homeroom'));
        }
        else {
            $this->add_intro_editor(true, get_string('description', 'homeroom'));
        }

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'homeroomhdr', get_string('homeroom_options', 'homeroom'));
        $plugins['autoattend'] = 'AutoAttend';
        $plugins['attendance'] = 'Attendance';
        $mform->addElement('select', 'attendplugin', get_string('readingplugin', 'homeroom'), $plugins);
        $mform->addHelpButton('attendplugin', 'readingplugin', 'homeroom');
        $mform->setDefault('attendplugin', 'autoattend');

        $choices['fullname']  = get_string('use_item', 'homeroom', get_string('fullnameuser'));
        $choices['firstname'] = get_string('use_item', 'homeroom', get_string('firstname'));
        $choices['lastname']  = get_string('use_item', 'homeroom', get_string('lastname'));
        $mform->addElement('select', 'namepattern', get_string('username_manage', 'homeroom'), $choices);
        $mform->addHelpButton('namepattern', 'username_manage', 'homeroom');
        $mform->setDefault('namepattern', 'fullname');

        $mform->addElement('checkbox', 'feedback', get_string('feedback_title', 'homeroom'), get_string('feedback_disp', 'homeroom'));
        $mform->setDefault('feedback', true);
        $mform->addHelpButton('feedback', 'feedback_disp', 'homeroom');

        //
        //-------------------------------------------------------------------------------
        // for Group
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, false, null);
    }
}

