<?php

function xmldb_homeroom_install()
{
    global $DB;

    /// Disable this module by default (because it's not technically part of Moodle 2.0)
    $DB->set_field('modules', 'visible', 1, array('name'=>'homeroom'));
}
