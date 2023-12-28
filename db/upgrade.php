<?php

function xmldb_homeroom_upgrade($oldversion=0)
{

    global $CFG, $THEME, $DB;

    $result = true;
    $dbman = $DB->get_manager();


/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    // 2014061300
    if ($oldversion < 2014061300) {
        $table = new xmldb_table('homeroom');
        //
        $field = new xmldb_field('feedback',  XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'attendplugin');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return $result;
}

